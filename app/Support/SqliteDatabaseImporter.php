<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

class SqliteDatabaseImporter
{
    /**
     * @var list<string>
     */
    private const IMPORT_ORDER = [
        'users',
        'tenants',
        'saas_users',
        'landlords',
        'listings',
        'listing_images',
        'listing_unavailability_blocks',
        'bookings',
        'stored_urls',
        'audit_logs',
    ];

    /**
     * @var list<string>
     */
    private const TRUNCATE_ORDER = [
        'audit_logs',
        'stored_urls',
        'bookings',
        'listing_unavailability_blocks',
        'listing_images',
        'listings',
        'landlord_access_tokens',
        'landlords',
        'saas_panel_login_tokens',
        'saas_users',
        'tenants',
        'users',
    ];

    /**
     * @return array<string, int>
     */
    public function import(string $sqlitePath, bool $fresh = false): array
    {
        if (! is_file($sqlitePath)) {
            throw new RuntimeException("SQLite file not found: {$sqlitePath}");
        }

        $sqlite = new PDO('sqlite:'.$sqlitePath);
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $counts = [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            if ($fresh) {
                $this->truncateTargetTables();
            }

            foreach (self::IMPORT_ORDER as $table) {
                if (! $this->sqliteTableExists($sqlite, $table)) {
                    continue;
                }

                $rows = $this->fetchSqliteRows($sqlite, $table);

                if ($rows === []) {
                    continue;
                }

                $this->insertRows($table, $rows);
                $counts[$table] = count($rows);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return $counts;
    }

    private function sqliteTableExists(PDO $sqlite, string $table): bool
    {
        $statement = $sqlite->prepare(
            'SELECT name FROM sqlite_master WHERE type = ? AND name = ? LIMIT 1',
        );
        $statement->execute(['table', $table]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchSqliteRows(PDO $sqlite, string $table): array
    {
        $statement = $sqlite->query('SELECT * FROM "'.$table.'"');

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        $mysqlColumns = $this->mysqlColumnNames($table);

        foreach (array_chunk($rows, 100) as $chunk) {
            $payload = [];

            foreach ($chunk as $row) {
                $filtered = [];

                foreach ($row as $column => $value) {
                    if (! in_array($column, $mysqlColumns, true)) {
                        continue;
                    }

                    $filtered[$column] = $this->normalizeValue($value);
                }

                if ($filtered !== []) {
                    $payload[] = $filtered;
                }
            }

            if ($payload !== []) {
                DB::table($table)->insert($payload);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function mysqlColumnNames(string $table): array
    {
        return Collection::make(
            DB::select('SHOW COLUMNS FROM `'.$table.'`'),
        )->pluck('Field')->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && $value === '') {
            return $value;
        }

        return $value;
    }

    private function truncateTargetTables(): void
    {
        foreach (self::TRUNCATE_ORDER as $table) {
            if (! $this->mysqlTableExists($table)) {
                continue;
            }

            try {
                DB::table($table)->truncate();
            } catch (Throwable) {
                DB::table($table)->delete();
            }
        }
    }

    private function mysqlTableExists(string $table): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
}
