# Kinderentertainers.nl

Versie: `0.1.2`

Boekingsplatform voor kinderentertainers. Bezoekers zoeken op skill, regio, beschikbaarheid en werkgebied, doen een specifieke of algemene aanvraag, vergelijken beschikbare matches en entertainers beheren hun eigen profiel, skills, beschikbaarheid, tarieven, offertes, facturatie- en betaalinstellingen.

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
