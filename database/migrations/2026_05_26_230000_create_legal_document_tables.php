<?php

use App\Enums\LegalDocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->unique();
            $table->string('title');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('legal_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_id')->constrained()->cascadeOnDelete();
            $table->string('version_label');
            $table->longText('body');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('replaced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['legal_document_id', 'version_label']);
        });

        foreach (LegalDocumentType::cases() as $type) {
            $documentId = DB::table('legal_documents')->insertGetId([
                'type' => $type->value,
                'title' => $type->label(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('legal_document_versions')->insert([
                'legal_document_id' => $documentId,
                'version_label' => 'v1',
                'body' => $this->defaultBody($type),
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_versions');
        Schema::dropIfExists('legal_documents');
    }

    private function defaultBody(LegalDocumentType $type): string
    {
        return match ($type) {
            LegalDocumentType::Terms => "# Algemene voorwaarden\n\nDeze voorwaarden beschrijven de afspraken tussen Kinderentertainers.nl, de entertainer en de opdrachtgever. De entertainer blijft verantwoordelijk voor de uitvoering, prijsafspraken, annulering en facturatie van de eigen opdracht.\n\n## Facturatie\n\nElke entertainer factureert zelf. Dat kan via een gekoppeld boekhoudpakket in het dashboard of handmatig buiten het platform.",
            LegalDocumentType::Privacy => "# Privacyverklaring\n\nKinderentertainers.nl verwerkt gegevens om aanvragen te kunnen ontvangen, matchen en doorsturen naar entertainers. Entertainers gebruiken klantgegevens alleen voor de behandeling van de aanvraag, uitvoering van de opdracht en eigen administratie.\n\n## Integraties\n\nEntertainers kunnen zelf koppelingen activeren voor betaling, boekhouding, mail en meldingen. Sleutels worden versleuteld opgeslagen.",
            LegalDocumentType::Cookie => "# Cookieverklaring\n\nWe gebruiken noodzakelijke cookies voor sessies, beveiliging, cookievoorkeuren en toegankelijkheidsvoorkeuren. Optionele voorkeuren voor analytics en marketing worden pas gebruikt na toestemming.\n\n## Toegankelijkheid\n\nJe kunt licht, donker of automatisch thema kiezen. Die voorkeur bewaren we lokaal in je browser.",
        };
    }
};
