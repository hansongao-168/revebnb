<?php

namespace App\Console\Commands;

use App\Site\Support\SitePageManifest;
use Illuminate\Console\Command;

class SyncSitePagesCommand extends Command
{
    protected $signature = 'site:sync-pages';

    protected $description = 'Sync site_pages from config/site-pages.php';

    public function handle(SitePageManifest $manifest): int
    {
        $manifest->sync();

        $this->info('Site pages synced.');

        return self::SUCCESS;
    }
}
