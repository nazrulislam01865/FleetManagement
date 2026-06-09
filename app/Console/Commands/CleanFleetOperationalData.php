<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CleanFleetOperationalData extends Command
{
    /**
     * Run examples:
     *
     * php artisan fleet:clean-operational-data --dry-run
     * php artisan fleet:clean-operational-data
     * php artisan fleet:clean-operational-data --force
     */
    protected $signature = 'fleet:clean-operational-data
                            {--dry-run : Show which tables will be cleaned without deleting anything}
                            {--force : Run without confirmation}';

    protected $description = 'Delete operational Fleet Management data while preserving Master Data, fuel rates, users and permissions.';

    /**
     * Only these tables will be cleaned.
     *
     * Master Data, users, roles, permissions, fuel prices and migrations
     * are intentionally not included.
     */
    private const TABLES_TO_CLEAN = [
        /*
         |--------------------------------------------------------------------------
         | Notification and ownership data
         |--------------------------------------------------------------------------
         */
        'fleet_notification_deliveries',
        'fleet_record_owners',
        'notifications',

        /*
         |--------------------------------------------------------------------------
         | Finance and payroll
         |--------------------------------------------------------------------------
         */
        'fleet_dues',

        /*
         |--------------------------------------------------------------------------
         | Operations
         |--------------------------------------------------------------------------
         */
        'fleet_driver_attendances',
        'fleet_fuel_recharges',
        'fleet_trips',

        /*
         |--------------------------------------------------------------------------
         | Fleet records
         |--------------------------------------------------------------------------
         */
        'fleet_yards',
        'fleet_vehicles',
        'fleet_contracts',

        /*
         |--------------------------------------------------------------------------
         | People and business records
         |--------------------------------------------------------------------------
         */
        'fleet_vendor_parties',
        'fleet_drivers',
        'fleet_employees',
        'fleet_clients',

        /*
         |--------------------------------------------------------------------------
         | Temporary Laravel data
         |--------------------------------------------------------------------------
         */
        'sessions',
        'password_reset_tokens',
        'jobs',
        'job_batches',
        'failed_jobs',
        'cache',
        'cache_locks',
    ];

    public function handle(): int
    {
        $tables = collect(self::TABLES_TO_CLEAN)
            ->filter(fn (string $table): bool => Schema::hasTable($table))
            ->values();

        if ($tables->isEmpty()) {
            $this->warn('No matching operational tables were found.');

            return self::SUCCESS;
        }

        $tableInformation = $tables
            ->map(function (string $table): array {
                return [
                    'table' => $table,
                    'rows' => DB::table($table)->count(),
                ];
            });

        $this->newLine();
        $this->warn('The following operational data will be permanently deleted:');

        $this->table(
            ['Table', 'Current Rows'],
            $tableInformation
                ->map(fn (array $item): array => [
                    $item['table'],
                    number_format($item['rows']),
                ])
                ->all()
        );

        $totalRows = $tableInformation->sum('rows');

        $this->info('Total rows selected for deletion: '.number_format($totalRows));

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run completed. Nothing was deleted.');

            return self::SUCCESS;
        }

        if (
            ! $this->option('force')
            && ! $this->confirm(
                'This action cannot be undone. Do you want to permanently clean these tables?',
                false
            )
        ) {
            $this->warn('Database cleaning cancelled.');

            return self::SUCCESS;
        }

        $deletedTables = [];
        $failedTable = null;

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                $failedTable = $table;

                DB::table($table)->truncate();

                $deletedTables[] = $table;

                $this->line("Cleaned: {$table}");
            }
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error(
                'Database cleaning failed'
                .($failedTable ? " while processing {$failedTable}" : '')
                .'.'
            );

            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->newLine();
        $this->info('Operational database data cleaned successfully.');
        $this->info(count($deletedTables).' table(s) were cleaned.');

        $this->newLine();
        $this->comment('Preserved data:');
        $this->line('- Users');
        $this->line('- Roles and permissions');
        $this->line('- Master Data');
        $this->line('- Fuel rates / fuel prices');
        $this->line('- Migration history');

        $this->newLine();
        $this->warn('All logged-in sessions were cleared. Users must log in again.');

        return self::SUCCESS;
    }
}
