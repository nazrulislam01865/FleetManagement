<?php

namespace App\Console\Commands;

use App\Support\FleetRbac;
use Illuminate\Console\Command;

class SyncFleetRbac extends Command
{
    protected $signature = 'fleet:rbac-sync {--force : Reapply the default matrix to every role}';

    protected $description = 'Synchronize Fleet roles and permissions outside the web request lifecycle.';

    public function handle(): int
    {
        FleetRbac::syncDefaults((bool) $this->option('force'));
        FleetRbac::assignDefaultRoles();

        $this->info('Fleet roles and permissions synchronized.');

        return self::SUCCESS;
    }
}
