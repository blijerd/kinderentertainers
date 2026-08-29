<?php

namespace App\Console\Commands;

use App\Actions\DeleteContentRedirect;
use App\Actions\UpsertContentRedirect;
use App\Models\ContentRedirect;
use App\Support\Content\ContentRedirectPath;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ContentUpsertRedirectCommand extends Command
{
    protected $signature = 'content:redirect
                            {from : Bronpad, bijvoorbeeld /oude-pagina}
                            {to? : Doelpad of http(s)-URL}
                            {--status=301 : HTTP-status (301, 302, 307 of 308)}
                            {--disable : Redirect uitzetten zonder te verwijderen}
                            {--delete : Redirect soft-deleten}';

    protected $description = 'Maak of werk een permanente redirect (301) bij via de command line.';

    public function handle(UpsertContentRedirect $upsert, DeleteContentRedirect $delete): int
    {
        try {
            $from = ContentRedirectPath::normalizeFrom($this->argument('from'));
            $redirect = ContentRedirect::withTrashed()->where('from_path', $from)->first();

            if ($this->option('delete')) {
                if ($redirect === null || $redirect->trashed()) {
                    $this->error("Geen redirect gevonden voor {$from}.");

                    return self::FAILURE;
                }

                $delete->handle($redirect);
                $this->info("Redirect {$from} is verwijderd.");

                return self::SUCCESS;
            }

            $to = $this->argument('to');

            if ((! is_string($to) || $to === '') && ($redirect === null || ! $redirect->exists)) {
                $this->error('Geef een doelpad of URL mee.');

                return self::FAILURE;
            }

            $payload = [
                'from_path' => $from,
                'status_code' => (int) $this->option('status'),
                'is_active' => ! $this->option('disable'),
            ];

            if (is_string($to) && $to !== '') {
                $payload['to_url'] = $to;
            }

            $redirect = $upsert->handle($redirect, $payload);
            $this->info("Redirect {$redirect->from_path} → {$redirect->to_url} ({$redirect->status_code})");

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error($exception->validator->errors()->first() ?: $exception->getMessage());

            return self::FAILURE;
        }
    }
}
