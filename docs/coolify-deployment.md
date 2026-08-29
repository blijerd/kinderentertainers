# Coolify Deployment

Doelinfrastructuur: Hetzner VPS, Ubuntu 24.04, Docker, Coolify, PostgreSQL (en optioneel Redis) op `server.blijevent.nl`.

Kinderentertainers.nl volgt dezelfde Coolify-aanpak als Blijwin OS: één Docker-image, rolling web-updates, restricted SSH vanuit GitHub Actions, en geen destructieve database-acties tijdens build.

## Overzicht

De productie-image (`Dockerfile` stage `production`) is een Laravel-runtime met nginx en PHP-FPM. PostgreSQL (en Redis) horen als aparte Coolify-resources, niet in deze image.

Zelfde image, drie commando's:

| Resource | Coolify-naam | Command |
|---|---|---|
| Web | `ke-productie` | `web` |
| Queue worker | `ke-worker` | `worker` |
| Scheduler | `ke-scheduler` | `scheduler` |

De Docker-build:

- installeert Composer-dependencies met `--no-dev`
- bouwt Vite-assets met Node
- draait geen migraties, seeders of destructieve database-acties
- stampt `bootstrap/build-ref` als `YYWWddhh.NNN` in `Europe/Amsterdam` (jaar, ISO-week, dag, uur, volgnummer `001`-`999` binnen dat uur)

De entrypoint draait bij web-start `php artisan migrate --force` (standaard `RUN_MIGRATIONS=auto`), bouwt config/route/view-cache als `www-data`, publiceert Filament/Livewire-assets en synchroniseert de gedeelde Vite-assetcache.

## Build-instellingen

- Build pack: Dockerfile
- Dockerfile path: `Dockerfile`
- Target: `production` (laatste stage; Coolify gebruikt die standaard)
- Exposed port: `80`
- Health endpoint: `/up`
- PHP: 8.3 FPM
- Webserver: nginx
- Supervisor in de webcontainer: alleen nginx + PHP-FPM

Zet in Coolify geen vaste containernaam en geen host-poortmapping. Traefik/proxy alleen, containerpoort `80`. Stop grace period minstens 60 seconden.

## Coolify-resources

Maak deze resources apart in Coolify:

- PostgreSQL: bijvoorbeeld `ke-postgres`
- Redis (aanbevolen voor cache, sessies en queues): bijvoorbeeld `ke-redis`
- Applicaties: `ke-productie`, `ke-worker`, `ke-scheduler` (zelfde Git-repo en image)

Voeg `https://static.kinderentertainers.nl` toe als extra FQDN op de web-app zodat Traefik het cookievrije static-subdomein naar dezelfde container routeert.

## Environment

Minimum:

```dotenv
APP_NAME="Kinderentertainers.nl"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kinderentertainers.nl
APP_KEY=base64:...
APP_TIMEZONE=Europe/Amsterdam
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=null
ASSET_URL=https://static.kinderentertainers.nl
SETUP_TOKEN=
```

Coolify terminiert HTTPS bij Traefik en stuurt plain HTTP naar de container. `TRUSTED_PROXIES=*` is nodig zodat `X-Forwarded-Proto` klopt (URLs, redirects, Filament, Livewire, secure cookies). Zet Traefik HTTP→HTTPS op permanent (308/301). Nginx gebruikt `absolute_redirect off` zodat interne redirects geen `http://` Location lekken.

Database:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=ke-postgres
DB_PORT=5432
DB_DATABASE=kinderentertainers
DB_USERNAME=kinderentertainers
DB_PASSWORD=...
DB_SSLMODE=prefer
```

Redis (productie-aanbevolen):

```dotenv
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=ke-redis
REDIS_PORT=6379
```

Runtime:

```dotenv
LOG_CHANNEL=stack
LOG_LEVEL=error
FILESYSTEM_DISK=local
MAIL_MAILER=postmark
RUN_MIGRATIONS=auto
RUN_OPTIMIZE=true
RUN_STORAGE_LINK=true
PUBLISH_FRAMEWORK_ASSETS_ON_BOOT=true
SYNC_VITE_BUILD_ASSET_CACHE_ON_BOOT=true
WAIT_FOR_DB=true
```

Draai nooit `migrate:fresh --seed` op productie. De eerste beheerder maak je met `php artisan app:bootstrap` of `/setup?token=SETUP_TOKEN`.

## Persistente opslag

Koppel een Coolify-volume op:

```text
/var/www/html/storage
```

Web, worker en scheduler moeten **dezelfde** storage zien (uploads, PDF's, Vite-assetcache). `bootstrap/cache` mag ephemerisch blijven.

Compiled Blade staat onder `storage/framework/views/{build-ref}/` (uit `bootstrap/build-ref`) zodat een rolling update de templates van de vorige image niet overschrijft. Alleen de web-entrypoint mag `view:cache` draaien, als `www-data`, onder een boot-lock. Worker en scheduler mogen de gedeelde views-directory niet wissen en niet recursief `chown`-en.

## Healthchecks en rolling updates

De productie-image definieert:

```dockerfile
HEALTHCHECK --interval=10s --timeout=5s --start-period=600s --retries=3 \
    CMD ["/usr/local/bin/kinderentertainers-healthcheck"]
```

`docker/healthcheck.sh` doet voor de **web**-rol een `curl` naar `http://127.0.0.1/up` met `Host` uit `APP_URL`. Zonder die header weigert TrustHosts met HTTP 400, waardoor Coolify de nieuwe container nooit healthy markeert. Worker en scheduler luisteren niet op poort 80; dezelfde script markeert die rollen healthy als PHP nog kan booten.

De start-period van 600 seconden dekt migrate + optimize voordat nginx luistert. Zet in Coolify `health_check_start_period=600` als de UI die waarde overschrijft.

Coolify moet een **rolling update** doen voor `ke-productie`: nieuwe container starten naast de oude, wachten tot healthy, dan de oude stoppen. Deploy-log:

```text
Rolling update started
New container is healthy
Rolling update completed
```

Als Coolify terugvalt op stop-then-start, is de site 502 zolang de nieuwe container migrate/optimize draait.

### Fingerprinted Vite-assets tijdens overlap

Tijdens de korte overlap kunnen HTML en hashed CSS/JS bij verschillende containers terechtkomen.

Mitigaties in deze repo:

1. Web-boot draait `scripts/deploy/sync_vite_build_asset_cache.sh` (expand-only merge naar `storage/app/vite-build-cache/assets`).
2. Nginx serveert `/build/assets/` eerst uit die cache, met fallback naar de image.
3. `scripts/deploy/coolify_github_deploy.sh` start `scripts/deploy/prune_stale_ke_productie_containers.sh` op de achtergrond na een web-deploy. Die waiter accepteert alleen een healthy container die na het queue-moment is gestart, en stopt oudere siblings. Log: `/var/log/kinderentertainers-coolify-prune.log`.

Herinstalleer de host-helpers na wijzigingen:

```bash
install -m 755 scripts/deploy/coolify_github_deploy.sh \
  /usr/local/sbin/kinderentertainers-coolify-github-deploy.sh
install -m 755 scripts/deploy/prune_stale_ke_productie_containers.sh \
  /usr/local/sbin/prune_stale_ke_productie_containers.sh
```

Vul daarna de Coolify-UUIDs in `scripts/deploy/coolify_github_deploy.sh` (`WEB_UUID`, `WORKER_UUID`, `SCHEDULER_UUID`) en installeer het script opnieuw.

### Zero-downtime probe

Tijdens een redeploy:

```bash
./scripts/deploy/verify_zero_downtime_window.sh https://kinderentertainers.nl
```

Het script faalt bij elke non-2xx/3xx (inclusief 502) op `/` of `/up`.

## GitHub Actions productie-trigger

Bij voorkeur GitHub Actions (`Deploy Production`) in plaats van Deploy in de Coolify-UI.

Triggers:

- `workflow_dispatch` met `confirm=DEPLOY` op `refs/heads/main`
- een push naar `main` waarvan het commitbericht `[deploy]` bevat

Coolify blijft localhost-only (`127.0.0.1:8000`). De workflow SSH't naar `root@server.blijevent.nl` met een **forced-command** key die alleen mag:

```text
/usr/local/sbin/kinderentertainers-coolify-github-deploy.sh
```

Toegestane remote commands:

```text
deploy
deploy --force
deploy --apps=web
deploy --apps=web,worker,scheduler --force
```

De helper leest het Coolify-token uit `/root/.config/kinderentertainers/coolify-deploy-token` en queued:

```text
GET http://127.0.0.1:8000/api/v1/deploy?uuid=<web>[,<worker>,<scheduler>]&force=false
Authorization: Bearer <deploy-token>
```

Server-setup:

```bash
install -d -m 700 /root/.config/kinderentertainers
# Coolify deploy-only API token in /root/.config/kinderentertainers/coolify-deploy-token
# Forced-command deploy key in /root/.ssh/authorized_keys:
# command="/usr/local/sbin/kinderentertainers-coolify-github-deploy.sh",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ...
```

GitHub secret:

- `PRODUCTION_DEPLOY_SSH_PRIVATE_KEY`

GitHub environment `production`: alleen branch `main`. De workflow vereist daarnaast het typen van `DEPLOY`.

Expose Coolify-poort `8000` niet publiek. Geef GitHub geen volledige root-shell key.

## Rollback

Een image-rollback herstelt code, maar draait migraties niet terug. Voor risicovolle releases:

- PostgreSQL-back-up of snapshot
- noteer de gedeployde image/commit
- geen destructieve migraties

```bash
DB_HOST=... DB_USERNAME=... DB_PASSWORD=... ./docker/scripts/backup-postgres.sh
```
