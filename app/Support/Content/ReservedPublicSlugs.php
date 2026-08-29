<?php

namespace App\Support\Content;

final class ReservedPublicSlugs
{
    /**
     * @var list<string>
     */
    public const SLUGS = [
        'admin',
        'dashboard',
        'klantportaal',
        'kinderentertainers',
        'aanvragen',
        'aanvraag-bedankt',
        'reviews',
        'review-bedankt',
        'algemene-voorwaarden',
        'privacyverklaring',
        'cookieverklaring',
        'offertes',
        'login',
        'registreren',
        'logout',
        'setup',
        'sitemap.xml',
        'webhooks',
        'email',
        'wachtwoord-vergeten',
        'wachtwoord-herstellen',
        'blog',
        'nieuws',
        'foto',
        'fotos',
        'media',
        'storage',
        'up',
        'build',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::SLUGS;
    }

    public static function landingPageConstraint(): string
    {
        $alternation = implode('|', array_map(
            static fn (string $slug): string => str_replace('.', '\\.', $slug),
            self::SLUGS,
        ));

        return '^(?!'.$alternation.'$)[a-z0-9-]+$';
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::SLUGS, true);
    }
}
