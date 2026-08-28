# Kinderentertainers.nl

Versie: `0.1.2`

Boekingsplatform voor kinderentertainers. Bezoekers zoeken op skill, regio, beschikbaarheid en werkgebied, doen een specifieke of algemene aanvraag, vergelijken beschikbare matches en entertainers beheren hun eigen profiel, skills, beschikbaarheid, tarieven, offertes, facturatie- en betaalinstellingen.

## Installatie

`vendor/`, `node_modules`, `.env`, `.DS_Store`, `__MACOSX/` en testcaches horen niet in de repository. Installeer dependencies lokaal:

Vereiste PHP-extensies: `dom`, `mbstring`, `xml`, `xmlwriter`, `json`, `libxml`, `tokenizer` en `pdo_pgsql`.
Maak voor PostgreSQL eenmalig de lokale databases aan:

```bash
createuser kinderentertainers
createdb -O kinderentertainers kinderentertainers
createdb -O kinderentertainers kinderentertainers_test
```

Installeer daarna de applicatie:

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

## Docker

Start de volledige lokale stack met PHP-FPM, Nginx, PostgreSQL, queue-worker en scheduler:

```bash
docker compose up --build
```

De applicatie is daarna bereikbaar op `http://localhost:8080`. PostgreSQL is vanaf de host bereikbaar op poort `5433` met database `kinderentertainers`, gebruiker `kinderentertainers` en wachtwoord `secret`.

De `app`-container draait migraties automatisch bij het opstarten. Voor demo-data en de demo-logins:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Handige Docker-commando's:

```bash
docker compose exec app php artisan test
docker compose exec app php artisan tinker
docker compose exec app composer test
docker compose down
docker compose down -v
```

Gebruik `docker compose down -v` alleen wanneer ook de lokale PostgreSQL-data en Laravel storage-volumes weg mogen.

## Productie met Docker

Voor hosts die alleen de `Dockerfile` bouwen, gebruikt de laatste stage `production`: die start Nginx, PHP-FPM, de queue-worker en de scheduler via Supervisor in één container en serveert HTTP op poort `80`. Zet in de hostingomgeving minimaal `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://kinderentertainers.nl`, `APP_KEY`, de databasevariabelen, `MAIL_MAILER` (Postmark of SMTP) en bij voorkeur `RUN_OPTIMIZE=true`. Gebruik `RUN_MIGRATIONS=true` alleen wanneer migraties bij deploy automatisch mogen draaien. Zet `SETUP_TOKEN` op een lang geheim; zonder token is `/setup` in productie onbereikbaar.

Bij een Docker Compose-deploy moet de reverse proxy naar de service `web` op poort `80` wijzen. De service `app` is alleen PHP-FPM op poort `9000` en is geen HTTP-backend; als Traefik/Coolify/Dokploy daarheen routeert, krijgt de site geen bruikbare server.

Maak de eerste beheerder zonder demo-data:

```bash
php artisan app:bootstrap --name="Jouw naam" --email="admin@kinderentertainers.nl" --password="..."
```

Of open `/setup?token=SETUP_TOKEN` als `SETUP_TOKEN` is gezet. Draai nooit `migrate:fresh --seed` op productie: de seeder maakt lokaal demo-logins met wachtwoord `password`, en slaat die in productie over.

Vul daarna in het adminpanel (`/admin`) eventueel extra skills, keur entertainerprofielen goed en zet KvK, BTW en vestigingsadres in de env (`COMPANY_KVK`, `COMPANY_BTW`, `COMPANY_ADDRESS`, `COMPANY_EMAIL`). E-mail vereist SPF/DKIM op `kinderentertainers.nl`. PostgreSQL-back-up:

```bash
DB_HOST=... DB_USERNAME=... DB_PASSWORD=... ./docker/scripts/backup-postgres.sh
```

## Static asset host

Voor productie kan CSS/JS via het cookie-vrije static-subdomein worden geserveerd:

```env
ASSET_URL=https://static.kinderentertainers.nl
```

Laat `static.kinderentertainers.nl` naar dezelfde `public_html/` release wijzen als de hoofdsite. De root van dat subdomein wordt bewust afgehandeld door `public_html/static.kinderentertainers.nl/index.html`, zodat Laravel daar niet hoeft te booten. Zorg dat `/build/assets/*` cross-origin bereikbaar blijft met een lange immutable cache; de meegeleverde Apache/LiteSpeed `.htaccess` en Docker nginx-config zijn hierop voorbereid.

## Demo-logins

- Admin: `admin@kinderentertainers.nl` / `password`
- Entertainer: `entertainer1@kinderentertainers.nl` / `password`
- Klant: `klant@kinderentertainers.nl` / `password`

## Routes

- Publiek: `/`
- Entertainers: `/kinderentertainers`
- Algemene aanvraag: `/aanvragen`
- Specifieke aanvraag: `/kinderentertainers/{slug}/aanvragen`
- Algemene aanvraagmatches kiezen: `/aanvragen/{bookingRequest}/matches/{token}`
- Offerte bekijken en accepteren: `/offertes/{token}`
- Reviews: `/reviews/{token}`
- Juridische pagina's: `/algemene-voorwaarden`, `/privacyverklaring`, `/cookieverklaring`
- Login: `/login`
- Registratie: `/registreren`
- Wachtwoord herstellen: `/wachtwoord-vergeten`
- E-mailverificatie: `/email/verificatie`
- Klantportaal: `/klantportaal`
- Entertainer-dashboard: `/dashboard`
- Adminpanel: `/admin`
- Eerste setup als er nog geen gebruikers zijn: `/setup`
- Sitemap: `/sitemap.xml`
- Robots: `/robots.txt`
- Betaalwebhooks: `/webhooks/betalingen/{provider}`
- Publieke landingspagina's: `/{landingPage:slug}` met gereserveerde systeemroutes uitgesloten.

## Functionaliteit

- Livewire componenten: `availability-check`, `booking-request-form` en `entertainer-index`.
- Publieke entertainerindex met filters voor skill, leeftijd, type feest, prijs, regio, beoordeling, beschikbaarheid, taal en werkgebied.
- Algemene aanvragen matchen actieve entertainers op skill, beschikbaarheid, regio-ordening, afstandsscore en werkgebied. `CreateBookingRequest` maakt alleen matches aan die binnen `MatchScoreService::isInsideWorkingArea(...)` vallen.
- Afstand wordt geschat via bekende steden en postcodeprefixes. Als de afstand onbekend is, blijft een match toegestaan en krijgt deze een neutrale afstandsscore.
- Matchscore weegt skill, beschikbaarheid, afstand, reviews, reactiesnelheid en featured-status mee.
- Entertainer-dashboard voor eigen profiel, publicatieaanvraag, skills, beschikbaarheid, herhalende beschikbaarheid, tarieven, facturatie, betaalinstellingen, integraties, specifieke aanvragen, offertes en algemene aanvraagmatches.
- Klantportaal voor eigen aanvragen bekijken, gegevens wijzigen, berichten plaatsen, annuleren, favorieten beheren, offertes accepteren en documenten downloaden.
- Offerteworkflow met prestatieprijs, reiskosten, aanbetaling, geldigheid, voorwaardenversie, acceptatie-audit en status/tijdlijnregistratie.
- Self-billing workflow: de entertainer factureert zelf; het platform maakt factuurinstructies, betaalreferenties en optioneel externe betaalcheckouts aan.
- Betaalproviders voor aanbetalingen: handmatig/overig plus Mollie, Stripe, PayPal, Pay.nl en Rabobank Smart Pay via entertainerintegraties.
- Facturatie-integratie ondersteunt handmatige instructies en Moneybird; overige providers krijgen een externe referentie/instructie.
- Betaalwebhooks werken payment status bij voor bekende externe betaal-ID's.
- Documentdownloads voor offerte, boekingsbevestiging, factuurinstructie en annulering.
- Reviewflow met reviewlinks na bevestigde afgelopen boekingen en moderatie/publicatie.
- Filament 5 adminresources voor beheer van gebruikers, entertainers, skills, beschikbaarheid, tarieven, aanvragen en landingspagina's.
- Policies: admins mogen alles; entertainers beheren alleen eigen records.
- Beschikbaarheidscheck controleert actieve entertainers en skills, beschikbare blokken, overlappende optie/bezet/niet-beschikbaar blokken en conflicterende specifieke of algemene aanvragen.
- Aanvraagvalidatie controleert aanvraagtype, actieve entertainer of actieve skill, toekomstige datum, tijden, B2B-bedrijfsnaam en toegestane skills.
- Geplande consolejobs:
  - `reviews:send-links` dagelijks om 09:00.
  - `bookings:send-reminders` elk uur voor bijna verlopen offertes en boekingen van morgen.
  - `integrations:check` dagelijks om 08:00.
  - `calendar:sync-bookings` elke 15 minuten.

## Testen

```bash
php artisan test
composer test
npm run build
```

## Bekende beperkingen

- Afstandsbepaling is een interne schatting op basis van bekende steden, postcodeprefixes en regiofallbacks; er is nog geen routeplanner/geocodingprovider gekoppeld.
- Werkgebied blokkeert algemene matches alleen wanneer een afstand kan worden bepaald. Bij onbekende afstand blijft de match mogelijk.
- Betalingen lopen alleen voor aanbetalingen via checkoutlinks of handmatige instructies; volledige betalingsafhandeling en uitbetaling blijven buiten het platform.
- Self-billing betekent dat entertainers zelf verantwoordelijk blijven voor hun factuurproces. Het platform legt instructies, referenties en integratiestatus vast.
- PDF-documenten zijn minimale platformdocumenten en geen volledige boekhoudkundige facturen.
