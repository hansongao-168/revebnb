<?php

namespace App\Console\Commands;

use App\Support\SqliteDatabaseImporter;
use Illuminate\Console\Command;

class ImportSqliteDatabaseCommand extends Command
{
    protected $signature = 'db:import-sqlite
                            {path? : Path to the SQLite file (default: database/database.sqlite)}
                            {--fresh : Truncate target tables before import}
                            {--force : Skip confirmation prompts (use with --fresh)}';

    protected $description = 'Import application data from a SQLite database file into the current MySQL connection';

    public function handle(SqliteDatabaseImporter $importer): int
    {
        $path = $this->argument('path') ?? database_path('database.sqlite');

        if (config('database.default') === 'sqlite') {
            $this->components->warn('Current default connection is sqlite. This command is intended for importing into MySQL.');

            if (! $this->confirm('Continue anyway?', false)) {
                return self::FAILURE;
            }
        }

        if (! is_file($path)) {
            $this->components->error("SQLite file not found: {$path}");

            return self::FAILURE;
        }

        $fresh = (bool) $this->option('fresh');

        if ($fresh && ! $this->option('force') && $this->input->isInteractive()) {
            if (! $this->confirm('This will DELETE existing rows in users, tenants, landlords, listings, and related tables. Continue?', false)) {
                return self::FAILURE;
            }
        }

        $this->components->info('Importing from: '.$path);

        try {
            $counts = $importer->import($path, $fresh);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($counts === []) {
            $this->components->warn('No rows imported (SQLite tables may be empty).');

            return self::SUCCESS;
        }

        $this->table(['Table', 'Rows'], collect($counts)->map(fn (int $count, string $table) => [$table, $count])->values()->all());

        $this->components->success('Import completed.');

        return self::SUCCESS;
    }
}
