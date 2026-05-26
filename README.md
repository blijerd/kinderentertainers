# Kinderentertainers.nl

Versie: `0.1.2`

Professioneel boekingsplatform voor kinderentertainers. Bezoekers zoeken entertainers op skill, regio en beschikbaarheid en leggen een aanvraag vast. Entertainers beheren hun profiel, skills, beschikbaarheid, tarieven en aanvragen.

## Features

- Publieke homepage, entertainer-overzicht, detailpagina, beschikbaarheidscheck, aanvraagformulier en bedankpagina.
- Aanvraagflow voor specifieke entertainers en algemene skill-aanvragen met automatische matches.
- Rollen: `admin`, `entertainer` en voorbereid `klant`.
- Adminpanel met Filament 5 resources voor gebruikers, entertainers, skills, beschikbaarheid, tarieven en aanvragen.
- Entertainer-dashboard voor eigen profielcontext en aanvraagstatussen.
- Eloquent models, migrations, factories, seeders, enums, policies en form requests.
- Demo-data met admin, 3 entertainers, standaard skills, tarieven, beschikbaarheid en aanvragen.

## Installatie

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

## Database Setup

Gebruik MySQL in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kinderentertainers
DB_USERNAME=root
DB_PASSWORD=
```

Daarna:

```bash
php artisan migrate:fresh --seed
```

Voor bestaande installaties:

```bash
php artisan migrate
php artisan db:seed
```

Demo-logins:

- Admin: `admin@kinderentertainers.nl` / `password`
- Entertainer: `entertainer1@kinderentertainers.nl` / `password`

## Gebruik

- Publiek: `/`, `/kinderentertainers`
- Entertainer login: `/login`
- Entertainer dashboard: `/dashboard`
- Adminpanel: `/admin`

## Rollen En Rechten

- `admin`: volledige toegang via Filament adminpanel en policies.
- `entertainer`: beheert eigen profielcontext, tarieven, beschikbaarheid en aanvragen.
- `klant`: voorbereid voor latere klantaccounts.

## Development Commando's

```bash
composer run dev
npm run dev
npm run build
php artisan optimize:clear
```

## Test Commando's

```bash
php artisan test
composer test
```

## Bestandsstructuur

- `app/Models`: Eloquent models en relaties.
- `app/Enums`: status- en klanttype-enums.
- `app/Actions`: businesslogica voor beschikbaarheid en aanvraagcreatie.
- `app/Http/Controllers`: dunne webcontrollers.
- `app/Http/Requests`: validatie voor aanvragen en beschikbaarheid.
- `app/Filament/Resources`: Nederlandse adminresources.
- `resources/views`: Blade layouts, publieke pagina's, dashboard en Livewire componentviews.
- `database/migrations`: schema voor platformtabellen en permissies.
- `database/seeders`: rollen, skills en demo-data.
- `tests/Feature`: basistests voor platformflows en autorisatie.

## Developer Notes

- Geen koppeling met BlijwinOS.
- Nog geen betalingen, offertes of facturen; de booking-request structuur laat uitbreiding toe.
- Class-, method- en databasenaamgeving is Engels; UI-labels zijn Nederlands.

## Auteur

Edwin Rasser / Blijwin®

## Licentie

MIT

## Changelog

Zie [CHANGELOG.md](CHANGELOG.md).
