<?php

namespace App\Http\Controllers;

use App\Enums\LegalDocumentType;
use App\Services\LegalDocumentRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class PublicLegalDocumentController extends Controller
{
    public function terms(LegalDocumentRepository $documents): View
    {
        return $this->show($documents, LegalDocumentType::Terms);
    }

    public function privacy(LegalDocumentRepository $documents): View
    {
        return $this->show($documents, LegalDocumentType::Privacy);
    }

    public function cookies(LegalDocumentRepository $documents): View
    {
        return $this->show($documents, LegalDocumentType::Cookie);
    }

    private function show(LegalDocumentRepository $documents, LegalDocumentType $type): View
    {
        $version = $documents->currentVersion($type);

        abort_unless($version, 404);

        return view('legal.show', [
            'bodyHtml' => Str::markdown($version->body),
            'type' => $type,
            'version' => $version,
        ]);
    }
}
