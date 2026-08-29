# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public_html ./public_html
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-fpm-bookworm AS app

ARG INSTALL_DEV=false

WORKDIR /var/www/html

ENV APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        default-mysql-client \
        git \
        gosu \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        postgresql-client \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer install --prefer-dist --no-interaction --no-progress --no-scripts; \
    else \
        composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader; \
    fi

COPY . .
COPY --from=assets /app/public_html/build ./public_html/build

RUN mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/app/vite-build-cache/assets \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
    && ln -snf ../storage/app/public public_html/storage \
    && if [ "$INSTALL_DEV" = "true" ]; then \
        composer dump-autoload --optimize; \
    else \
        composer dump-autoload --optimize --no-dev; \
    fi \
    && chown -R www-data:www-data bootstrap/cache storage public_html/storage

COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY docker/healthcheck.sh /usr/local/bin/kinderentertainers-healthcheck
RUN chmod +x /usr/local/bin/docker-entrypoint /usr/local/bin/kinderentertainers-healthcheck \
        scripts/deploy/*.sh \
    && mkdir -p storage/app/vite-build-cache/assets

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public_html /var/www/html/public_html
COPY --from=app /var/www/html/storage/app/public /var/www/html/storage/app/public

EXPOSE 80

FROM app AS production

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx-light supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisor/laravel.conf /etc/supervisor/conf.d/laravel.conf
COPY docker/php/opcache-production.ini /usr/local/etc/php/conf.d/opcache.ini
RUN sed -i 's/fastcgi_pass app:9000;/fastcgi_pass 127.0.0.1:9000;/' /etc/nginx/conf.d/default.conf \
    && nginx -t

# Compact public footer stamp: YYWWddhh.NNN in Europe/Amsterdam
# (year, ISO week, day, hour, sequence 001-999 within that hour, then wraps to 001).
ARG KE_BUILD_REF
ARG KE_BUILD_SEQ
RUN build_ref="${KE_BUILD_REF:-}" \
    && if [ -z "$build_ref" ]; then \
         stamp="$(TZ=Europe/Amsterdam date +%y%V%d%H)"; \
         seq="${KE_BUILD_SEQ:-}"; \
         if [ -z "$seq" ]; then \
           min="$(TZ=Europe/Amsterdam date +%M)"; \
           sec="$(TZ=Europe/Amsterdam date +%S)"; \
           min="${min#${min%%[!0]*}}"; \
           sec="${sec#${sec%%[!0]*}}"; \
           [ -z "$min" ] && min=0; \
           [ -z "$sec" ] && sec=0; \
           into=$((min * 60 + sec)); \
           seq=$((into / 36 + 1)); \
         else \
           seq="$(printf '%s' "$seq" | tr -cd '0-9')"; \
           seq="${seq#${seq%%[!0]*}}"; \
         fi; \
         if [ -z "$seq" ] || [ "$seq" -le 0 ]; then seq=1; fi; \
         seq=$(( (seq - 1) % 999 + 1 )); \
         seq="$(printf '%03d' "$seq")"; \
         build_ref="${stamp}.${seq}"; \
       fi \
    && printf '%s' "$build_ref" > /var/www/html/bootstrap/build-ref \
    && chown www-data:www-data /var/www/html/bootstrap/build-ref

EXPOSE 80

# Coolify rolling updates wait for this before removing the previous web container.
# start-period covers migrate + config/view cache before nginx listens.
# Healthcheck sends Host from APP_URL: TrustHosts rejects bare 127.0.0.1 with HTTP 400.
HEALTHCHECK --interval=10s --timeout=5s --start-period=600s --retries=3 \
    CMD ["/usr/local/bin/kinderentertainers-healthcheck"]

CMD ["web"]
