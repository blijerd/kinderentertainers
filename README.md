# Kinderentertainers.nl

Versie: `0.1.2`

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

Vereiste PHP-extensies: `dom`, `mbstring`, `xml`, `xmlwriter`, `json`, `libxml`, `tokenizer`.

Gebruik voor lokale ontwikkeling daarna:

```bash
composer run dev
```

## Demo-logins

- Admin: `admin@kinderentertainers.nl` / `password`
- Entertainer: `entertainer1@kinderentertainers.nl` / `password`
- Klant: `klant@kinderentertainers.nl` / `password`

## Routes

- Publiek: `/`
- Entertainers: `/kinderentertainers`
- Algemene aanvraag: `/aanvragen`
- Specifieke aanvraag: `/kinderentertainers/{slug}/aanvragen`
- Login: `/login`
- Klantportaal: `/klantportaal`
- Entertainer-dashboard: `/dashboard`
- Adminpanel: `/admin`
- Eerste setup als er nog geen gebruikers zijn: `/setup`

## Functionaliteit

- Livewire componenten: `availability-check`, `booking-request-form` en `entertainer-index`.
- Entertainer-dashboard voor eigen profiel, skills, beschikbaarheid, tarieven, specifieke aanvragen en algemene aanvraagmatches.
- Klantportaal voor eigen aanvragen bekijken, gegevens wijzigen, offerte accepteren, documenten downloaden en klantberichten lezen.
- Filament 5 adminresources voor gebruikers, entertainers, skills, beschikbaarheid, tarieven en aanvragen.
- Policies: admins mogen alles; entertainers beheren alleen eigen records.
- Beschikbaarheidscheck controleert actieve entertainers en skills, beschikbare blokken, overlappende optie/bezet/niet-beschikbaar blokken en conflicterende specifieke of algemene aanvragen.
- Aanvraagvalidatie controleert aanvraagtype, actieve entertainer of actieve skill, toekomstige datum, tijden, B2B-bedrijfsnaam en toegestane skills.

## Testen

```bash
php artisan test
composer test
npm run build
```

## Bekende beperkingen

- Nog geen betalingen of facturen.
- Offerteacceptatie in het klantportaal is een basisworkflow zonder betaal- of handtekeningkoppeling.
- Werkgebied/regio is voorbereid in het datamodel, maar blokkeert algemene matches nog niet omdat afstandsvalidatie nog niet betrouwbaar genoeg is.
