# Kinderentertainers.nl

Versie: `0.1.1`

Boekingsplatform voor kinderentertainers. Bezoekers zoeken op skill, regio en beschikbaarheid, doen een specifieke of algemene aanvraag, en entertainers beheren hun eigen profiel, skills, beschikbaarheid, tarieven en aanvragen.

## Installatie

`vendor/`, `node_modules`, `.env`, `.DS_Store`, `__MACOSX/` en testcaches horen niet in de repository. Installeer dependencies lokaal:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan test
```

Gebruik voor lokale ontwikkeling daarna:

```bash
composer run dev
```

## Demo-logins

- Admin: `admin@kinderentertainers.nl` / `password`
- Entertainer: `entertainer1@kinderentertainers.nl` / `password`

## Routes

- Publiek: `/`
- Entertainers: `/kinderentertainers`
- Algemene aanvraag: `/aanvragen`
- Specifieke aanvraag: `/kinderentertainers/{slug}/aanvragen`
- Login: `/login`
- Entertainer-dashboard: `/dashboard`
- Adminpanel: `/admin`
- Eerste setup als er nog geen gebruikers zijn: `/setup`

## Functionaliteit

- Livewire componenten: `availability-check`, `booking-request-form` en `entertainer-index`.
- Entertainer-dashboard voor eigen profiel, skills, beschikbaarheid, tarieven en aanvraagstatussen.
- Filament 5 adminresources voor gebruikers, entertainers, skills, beschikbaarheid, tarieven en aanvragen.
- Policies: admins mogen alles; entertainers beheren alleen eigen records.
- Beschikbaarheidscheck controleert beschikbare blokken, overlappende optie/bezet/niet-beschikbaar blokken en conflicterende aanvragen.
- Aanvraagvalidatie controleert aanvraagtype, actieve entertainer of actieve skill, toekomstige datum, tijden, B2B-bedrijfsnaam en toegestane skills.

## Testen

```bash
php artisan test
composer test
npm run build
```

## Bekende beperkingen

- Nog geen betalingen, offertes of facturen.
- Klantaccounts zijn voorbereid via rollen, maar nog niet als frontendportaal uitgewerkt.
- Matchreacties van entertainers op algemene aanvragen zijn datamodelmatig voorbereid, maar nog niet als volledige dashboardworkflow uitgewerkt.
