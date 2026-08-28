<?php

use App\Enums\LegalDocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (LegalDocumentType::cases() as $type) {
            $document = DB::table('legal_documents')->where('type', $type->value)->first();

            if ($document === null) {
                continue;
            }

            $current = DB::table('legal_document_versions')
                ->where('legal_document_id', $document->id)
                ->whereNull('replaced_at')
                ->orderByDesc('published_at')
                ->first();

            if ($current && $current->version_label === 'v2') {
                continue;
            }

            $now = now();

            if ($current) {
                DB::table('legal_document_versions')
                    ->where('id', $current->id)
                    ->update(['replaced_at' => $now, 'updated_at' => $now]);
            }

            DB::table('legal_document_versions')->insert([
                'legal_document_id' => $document->id,
                'version_label' => 'v2',
                'body' => $this->body($type),
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        foreach (LegalDocumentType::cases() as $type) {
            $document = DB::table('legal_documents')->where('type', $type->value)->first();

            if ($document === null) {
                continue;
            }

            DB::table('legal_document_versions')
                ->where('legal_document_id', $document->id)
                ->where('version_label', 'v2')
                ->delete();

            DB::table('legal_document_versions')
                ->where('legal_document_id', $document->id)
                ->where('version_label', 'v1')
                ->update(['replaced_at' => null, 'updated_at' => now()]);
        }
    }

    private function body(LegalDocumentType $type): string
    {
        return match ($type) {
            LegalDocumentType::Terms => <<<'MD'
# Algemene voorwaarden

Deze voorwaarden gelden voor het gebruik van Kinderentertainers.nl, het boekingsplatform dat opdrachtgevers en kinderentertainers bij elkaar brengt.

## Rol van het platform

Kinderentertainers.nl is een bemiddelingsplatform. De overeenkomst voor de uitvoering van de opdracht komt tot stand tussen de opdrachtgever en de entertainer. De entertainer blijft verantwoordelijk voor de uitvoering, prijsafspraken, reiskosten, annulering, verzekering, facturatie en fiscale verplichtingen.

Het platform faciliteert profielen, aanvragen, matching, offertes, berichten, documenten en optionele betaallinks. Het platform is geen partij bij de opdracht en int geen vergoeding namens de entertainer, tenzij dat later schriftelijk anders wordt overeengekomen.

## Accounts

Opdrachtgevers en entertainers zijn verantwoordelijk voor juiste gegevens en het geheimhouden van inloggegevens. Entertainers verschijnen pas publiek nadat het platform het profiel heeft goedgekeurd.

## Aanvragen, offertes en betalingen

Een aanvraag is een verzoek, geen boeking. Een boeking ontstaat pas nadat de opdrachtgever een offerte van de entertainer heeft geaccepteerd. Aanbetalingen lopen via de betaalinstelling van de entertainer of via een handmatige instructie. Het platform verwerkt geen volledige uitbetaling aan entertainers.

## Annulering

Annulering volgt de afspraak in de offerte of het entertainerprofiel. Het platform kan een aanvraag, account of profiel weigeren of beëindigen bij misbruik, onjuiste gegevens of strijd met deze voorwaarden.

## Aansprakelijkheid

Het platform is niet aansprakelijk voor schade door uitvoering van een opdracht, uitval van een entertainer, prijsverschillen of externe betaal- of boekhoudkoppelingen, behalve bij opzet of grove nalatigheid en voor zover wettelijk toegestaan.

## Wijzigingen

We kunnen deze voorwaarden wijzigen. De gepubliceerde versie op deze pagina geldt voor nieuwe aanvragen vanaf de publicatiedatum.
MD,
            LegalDocumentType::Privacy => <<<'MD'
# Privacyverklaring

Kinderentertainers.nl verwerkt persoonsgegevens om het boekingsplatform te laten werken. We doen dat op basis van de AVG.

## Welke gegevens

We verwerken onder meer naam, e-mailadres, telefoonnummer, accountgegevens, adres- en evenementgegevens, leeftijd en aantal kinderen voor zover dat nodig is voor de aanvraag, berichten, offertes, reviews en administratie. Entertainers leveren daarnaast profiel-, tarief-, beschikbaarheid- en integratiegegevens.

We vragen geen medische gegevens en geen extra gegevens over individuele kinderen, behalve wat de opdrachtgever zelf invult om de act te laten aansluiten.

## Doelen en grondslagen

- Uitvoering van de overeenkomst: accounts, aanvragen, matching, offertes, berichten en documenten.
- Wettelijke verplichting: administratie en bewaarplicht waar die van toepassing is.
- Gerechtvaardigd belang: beveiliging, fraudepreventie, verbetering van het platform en afhandeling van geschillen.
- Toestemming: optionele cookies voor analytics of marketing, en optionele koppelingen die een entertainer zelf activeert.

## Delen van gegevens

Aanvraaggegevens gaan naar de entertainer(s) die bij de aanvraag horen, zodat zij kunnen reageren en de opdracht kunnen uitvoeren. Entertainers mogen die gegevens alleen gebruiken voor die aanvraag, de uitvoering en hun eigen administratie.

Gegevens kunnen naar verwerkers gaan die het platform technisch draaien, zoals hosting, e-mail, opslag en betalingen. Entertainers kunnen zelf koppelingen aanzetten (betaling, boekhouding, agenda, mail). Sleutels daarvoor worden versleuteld opgeslagen. Die partijen verwerken dan gegevens onder verantwoordelijkheid van de entertainer of volgens hun eigen voorwaarden.

## Bewaartermijn

Accountgegevens bewaren we zolang het account bestaat. Aanvragen, offertes en berichten bewaren we zolang dat nodig is voor de opdracht, administratie en geschillen, en daarna niet langer dan wettelijk nodig. Reviews die je publicatie goedkeurt blijven zichtbaar totdat ze worden verwijderd of het profiel offline gaat.

## Jouw rechten

Je kunt je gegevens inzien, corrigeren, beperken, laten verwijderen of meenemen, en bezwaar maken, via het account of via het contactadres in de footer. Je kunt een klacht indienen bij de Autoriteit Persoonsgegevens.

## Contact

Gebruik het contactadres op de website. Bedrijfsnaam, KvK-nummer en vestigingsadres staan in de footer zodra die bekend zijn.
MD,
            LegalDocumentType::Cookie => <<<'MD'
# Cookieverklaring

Kinderentertainers.nl gebruikt cookies en vergelijkbare technieken.

## Noodzakelijk

Noodzakelijke cookies zijn altijd aan. Ze horen bij de sessie, beveiliging (CSRF), cookievoorkeuren en toegankelijkheidsvoorkeuren zoals licht of donker thema. Zonder deze cookies werkt inloggen en het plaatsen van een aanvraag niet betrouwbaar.

## Analytics

Analytics-cookies meten of de site gebruikt wordt, bijvoorbeeld via Plausible. Ze worden alleen geplaatst als je daarvoor toestemming geeft in de cookiebanner.

## Marketing

Marketing-cookies of externe media worden alleen geladen na toestemming. Zolang er geen campagnetags actief zijn, gebeurt er niets in deze categorie.

## Toestemming wijzigen

Je kunt je keuze later aanpassen via Cookievoorkeuren in de footer. Toegankelijkheidsvoorkeuren bewaren we lokaal in je browser.
MD,
        };
    }
};
