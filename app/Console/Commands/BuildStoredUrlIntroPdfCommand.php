<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

class BuildStoredUrlIntroPdfCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'docs:build-stored-url-intro-pdf';

    /**
     * @var string
     */
    protected $description = 'Render docs/stored-urls-intro to public/docs/stored-urls-intro.pdf (requires weasyprint on PATH)';

    public function handle(): int
    {
        $htmlPath = storage_path('app/tmp-stored-urls-intro.html');
        File::ensureDirectoryExists(dirname($htmlPath));

        $html = View::make('docs.stored-urls-intro')->render();
        File::put($htmlPath, $html);

        $outPath = public_path('docs/stored-urls-intro.pdf');
        File::ensureDirectoryExists(dirname($outPath));

        $process = new Process(['weasyprint', $htmlPath, $outPath]);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput() !== '' ? $process->getErrorOutput() : $process->getOutput());

            return self::FAILURE;
        }

        $this->info(sprintf('Wrote %s', $outPath));

        File::delete($htmlPath);

        return self::SUCCESS;
    }
}
