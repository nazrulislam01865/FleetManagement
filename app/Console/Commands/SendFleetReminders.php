<?php

namespace App\Console\Commands;

use App\Models\Fleet\FleetContract;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVendorParty;
use App\Models\Fleet\FleetYard;
use App\Services\FleetNotificationService;
use App\Services\FleetRecordOwnershipService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendFleetReminders extends Command
{
    protected $signature = 'fleet:send-reminders {--date= : Testing date in YYYY-MM-DD format}';
    protected $description = 'Send targeted document, licence and contract expiry reminders.';

    public function handle(FleetNotificationService $notifications, FleetRecordOwnershipService $ownership): int
    {
        if (! Schema::hasTable('notifications')) {
            $this->warn('Run the notification migrations first.');
            return self::FAILURE;
        }

        $today = $this->option('date')
            ? CarbonImmutable::parse((string) $this->option('date'), 'Asia/Dhaka')->startOfDay()
            : CarbonImmutable::now('Asia/Dhaka')->startOfDay();
        $sent = 0;

        foreach ($this->documentSources() as $source) {
            if (! Schema::hasTable((new $source['model'])->getTable())) {
                continue;
            }

            $source['model']::query()->chunkById(100, function ($records) use ($source, $today, $notifications, $ownership, &$sent): void {
                foreach ($records as $record) {
                    $sent += $this->sendDocumentReminders($record, $source, $today, $notifications, $ownership);
                }
            });
        }

        $sent += $this->sendRecordDateReminders(FleetDriver::class, 'drivers', 'licenseValidity', 'Driver licence', 'fleet.drivers', $today, $notifications, $ownership);
        $sent += $this->sendRecordDateReminders(FleetContract::class, 'contracts', 'contractEnd', 'Contract', 'fleet.contracts', $today, $notifications, $ownership);

        $this->info("{$sent} notification(s) delivered.");
        return self::SUCCESS;
    }

    private function documentSources(): array
    {
        return [
            ['model' => FleetYard::class, 'resource' => 'yards', 'documents' => 'documents', 'label' => 'Yard', 'route' => 'fleet.yards'],
            ['model' => FleetVehicle::class, 'resource' => 'vehicles', 'documents' => 'docs', 'label' => 'Vehicle', 'route' => 'fleet.vehicles'],
            ['model' => FleetVendorParty::class, 'resource' => 'parties', 'documents' => 'documents', 'label' => 'Vendor', 'route' => 'fleet.vendors'],
            ['model' => FleetDriver::class, 'resource' => 'drivers', 'documents' => 'documents', 'label' => 'Driver', 'route' => 'fleet.drivers'],
            ['model' => FleetEmployee::class, 'resource' => 'employees', 'documents' => 'documents', 'label' => 'Employee', 'route' => 'fleet.employees'],
            ['model' => FleetContract::class, 'resource' => 'contracts', 'documents' => 'documents', 'label' => 'Contract', 'route' => 'fleet.contracts'],
        ];
    }

    private function sendDocumentReminders(Model $record, array $source, CarbonImmutable $today, FleetNotificationService $notifications, FleetRecordOwnershipService $ownership): int
    {
        $payload = is_array($record->payload) ? $record->payload : [];
        $documents = data_get($payload, $source['documents'], []);
        if (! is_array($documents)) {
            return 0;
        }

        $sent = 0;
        $recipientIds = $ownership->recipients($source['resource'], (string) $record->code, $payload);

        foreach ($documents as $index => $document) {
            if (! is_array($document) || blank($document['expiry'] ?? null)) {
                continue;
            }

            try {
                $expiry = CarbonImmutable::parse((string) $document['expiry'], 'Asia/Dhaka')->startOfDay();
            } catch (Throwable) {
                continue;
            }

            $configuredDays = $this->reminderDays((string) ($document['reminder'] ?? ''));
            $daysRemaining = (int) round($today->diffInDays($expiry, false));
            $shouldSend = $daysRemaining === 0 || ($configuredDays !== null && $daysRemaining === $configuredDays);
            if (! $shouldSend) {
                continue;
            }

            $documentName = trim((string) ($document['name'] ?? 'Document')) ?: 'Document';
            $timing = $daysRemaining === 0 ? 'expires today' : "expires in {$daysRemaining} days";
            $dedupe = implode(':', ['document', $source['resource'], $record->code, $index, $expiry->toDateString(), $daysRemaining]);

            $sent += $notifications->notifyUserIdsAndAdmins($recipientIds, [
                'title' => 'Document expiry reminder',
                'message' => "{$documentName} for {$source['label']} {$record->code} {$timing} ({$expiry->format('d M Y')}).",
                'category' => 'reminder',
                'icon' => '⏰',
                'url' => Route::has($source['route']) ? route($source['route']) : '',
                'resource' => $source['resource'],
                'resource_code' => (string) $record->code,
            ], $dedupe);
        }

        return $sent;
    }

    private function sendRecordDateReminders(string $modelClass, string $resource, string $field, string $label, string $routeName, CarbonImmutable $today, FleetNotificationService $notifications, FleetRecordOwnershipService $ownership): int
    {
        $model = new $modelClass;
        if (! Schema::hasTable($model->getTable())) {
            return 0;
        }

        $sent = 0;
        $modelClass::query()->chunkById(100, function ($records) use ($resource, $field, $label, $routeName, $today, $notifications, $ownership, &$sent): void {
            foreach ($records as $record) {
                $payload = is_array($record->payload) ? $record->payload : [];
                if (blank($payload[$field] ?? null)) {
                    continue;
                }

                try {
                    $expiry = CarbonImmutable::parse((string) $payload[$field], 'Asia/Dhaka')->startOfDay();
                } catch (Throwable) {
                    continue;
                }

                $daysRemaining = (int) round($today->diffInDays($expiry, false));
                if (! in_array($daysRemaining, [30, 15, 7, 0], true)) {
                    continue;
                }

                $recipientIds = $ownership->recipients($resource, (string) $record->code, $payload);
                $timing = $daysRemaining === 0 ? 'expires today' : "expires in {$daysRemaining} days";
                $dedupe = implode(':', ['record-date', $resource, $record->code, $field, $expiry->toDateString(), $daysRemaining]);

                $sent += $notifications->notifyUserIdsAndAdmins($recipientIds, [
                    'title' => $label.' expiry reminder',
                    'message' => "{$label} for {$record->code} {$timing} ({$expiry->format('d M Y')}).",
                    'category' => 'reminder',
                    'icon' => '⏰',
                    'url' => Route::has($routeName) ? route($routeName) : '',
                    'resource' => $resource,
                    'resource_code' => (string) $record->code,
                ], $dedupe);
            }
        });

        return $sent;
    }

    private function reminderDays(string $reminder): ?int
    {
        return preg_match('/(\d+)/', $reminder, $matches) === 1 ? (int) $matches[1] : null;
    }
}
