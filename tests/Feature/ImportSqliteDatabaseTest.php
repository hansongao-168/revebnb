<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Support\SqliteDatabaseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\TestCase;

class ImportSqliteDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_importer_throws_when_sqlite_file_missing(): void
    {
        $this->expectException(RuntimeException::class);

        app(SqliteDatabaseImporter::class)->import('/tmp/revebnb-missing-sqlite.db');
    }

    public function test_import_sqlite_command_imports_listings_from_backup_file(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('SQLite import command test requires MySQL as default connection.');
        }

        $path = database_path('database.sqlite');

        if (! is_file($path)) {
            $this->markTestSkipped('database/database.sqlite backup file not found.');
        }

        $this->artisan('db:import-sqlite', [
            'path' => $path,
            '--fresh' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertGreaterThanOrEqual(24, Listing::query()->count());
        $this->assertGreaterThanOrEqual(1, User::query()->count());
    }
}
