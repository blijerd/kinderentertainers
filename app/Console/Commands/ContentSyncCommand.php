<?php

namespace App\Console\Commands;

use App\Actions\SyncRepositoryContent;
use Illuminate\Console\Command;

class ContentSyncCommand extends Command
{
    protected $signature = 'content:sync
                            {--path= : Pad naar de contentmap (standaard: content/)}
                            {--dry-run : Toon wat er zou worden bijgewerkt zonder te schrijven}';

    protected $description = 'Importeer landingspagina\'s, blogposts en foto\'s uit content/ naar de database zonder te deployen.';

    public function handle(SyncRepositoryContent $sync): int
    {
        $path = $this->option('path');
        $path = is_string($path) && $path !== '' ? $path : null;
        $dryRun = (bool) $this->option('dry-run');

        $report = $sync->handle($path, $dryRun);

        $prefix = $dryRun ? 'Dry-run: ' : '';
        $this->info("{$prefix}{$report->media} foto('s), {$report->pages} pagina('s) en {$report->posts} blogpost(s) verwerkt.");

        foreach ($report->errors as $error) {
            $this->error($error);
        }

        return $report->hasErrors() ? self::FAILURE : self::SUCCESS;
    }
}
