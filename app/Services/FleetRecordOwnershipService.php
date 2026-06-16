<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FleetRecordOwnershipService
{
    private const SYNC_RESOURCES = [
        'fleet.vehicles.sync' => ['vehicles', 'id'],
        'fleet.fuel-prices.sync' => ['fuel_prices', 'fuelPriceId'],
        'fleet.fuel-recharge.sync' => ['fuel_recharges', 'rechargeId'],
        'fleet.vendors.sync' => ['parties', 'partyId'],
        'fleet.trips.sync' => ['trips', 'tripId'],
        'fleet.drivers.sync' => ['drivers', 'driverId'],
        'fleet.driver-attendance.sync' => ['driver_attendance', 'logId'],
        'fleet.employees.sync' => ['employees', 'employeeId'],
        'fleet.contracts.sync' => ['contracts', 'contractId'],
        'fleet.clients.sync' => ['clients', 'clientId'],
        'fleet.dues.sync' => ['dues', 'code', false],
        'fleet.vehicles.records.store' => ['vehicles', 'id', false],
        'fleet.vehicles.records.update' => ['vehicles', 'id', false],
        'fleet.fuel-prices.records.store' => ['fuel_prices', 'fuelPriceId', false],
        'fleet.fuel-prices.records.update' => ['fuel_prices', 'fuelPriceId', false],
        'fleet.fuel-recharge.records.store' => ['fuel_recharges', 'rechargeId', false],
        'fleet.fuel-recharge.records.update' => ['fuel_recharges', 'rechargeId', false],
        'fleet.vendors.records.store' => ['parties', 'partyId', false],
        'fleet.vendors.records.update' => ['parties', 'partyId', false],
        'fleet.trips.records.store' => ['trips', 'tripId', false],
        'fleet.trips.records.update' => ['trips', 'tripId', false],
        'fleet.drivers.records.store' => ['drivers', 'driverId', false],
        'fleet.drivers.records.update' => ['drivers', 'driverId', false],
        'fleet.employees.records.store' => ['employees', 'employeeId', false],
        'fleet.employees.records.update' => ['employees', 'employeeId', false],
        'fleet.contracts.records.store' => ['contracts', 'contractId', false],
        'fleet.contracts.records.update' => ['contracts', 'contractId', false],
        'fleet.clients.records.store' => ['clients', 'clientId', false],
        'fleet.clients.records.update' => ['clients', 'clientId', false],
    ];

    /**
     * Return creator names keyed by record code without adding N+1 queries.
     *
     * @return array<string, string>
     */
    public function creatorNames(string $resource, iterable $codes): array
    {
        if (! Schema::hasTable('fleet_record_owners') || ! Schema::hasTable('users')) {
            return [];
        }

        $normalizedCodes = collect($codes)
            ->map(fn ($code): string => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedCodes->isEmpty()) {
            return [];
        }

        return DB::table('fleet_record_owners as owners')
            ->join('users', 'users.id', '=', 'owners.user_id')
            ->where('owners.resource_type', $resource)
            ->whereIn('owners.resource_code', $normalizedCodes->all())
            ->pluck('users.name', 'owners.resource_code')
            ->map(fn ($name): string => trim((string) $name))
            ->all();
    }

    public function creatorName(string $resource, string $code): ?string
    {
        $name = $this->creatorNames($resource, [$code])[$code] ?? null;

        return filled($name) ? (string) $name : null;
    }

    public function claimRecord(string $resource, string $code, int $userId): void
    {
        if (! Schema::hasTable('fleet_record_owners')) {
            return;
        }

        $this->claim($resource, trim($code), $userId);
    }

    public function forgetRecord(string $resource, string $code): void
    {
        if (! Schema::hasTable('fleet_record_owners')) {
            return;
        }

        $this->forget($resource, trim($code));
    }

    public function syncResource(string $resource, iterable $codes, int $userId): void
    {
        if (! Schema::hasTable('fleet_record_owners')) {
            return;
        }

        $this->syncOwners(
            $resource,
            collect($codes)->map(fn ($code): string => trim((string) $code))->filter()->unique()->values()->all(),
            $userId
        );
    }

    /**
     * Preserve the original creator when an editable master-data code changes.
     */
    public function moveRecord(string $resource, string $oldCode, string $newCode, int $fallbackUserId): void
    {
        if (! Schema::hasTable('fleet_record_owners')) {
            return;
        }

        $oldCode = trim($oldCode);
        $newCode = trim($newCode);

        if ($newCode === '') {
            return;
        }

        if ($oldCode === '') {
            $this->claim($resource, $newCode, $fallbackUserId);
            return;
        }

        if ($oldCode === $newCode) {
            return;
        }

        DB::transaction(function () use ($resource, $oldCode, $newCode): void {
            $originalUserId = DB::table('fleet_record_owners')
                ->where('resource_type', $resource)
                ->where('resource_code', $oldCode)
                ->value('user_id');

            DB::table('fleet_record_owners')
                ->where('resource_type', $resource)
                ->where('resource_code', $oldCode)
                ->delete();

            if ($originalUserId) {
                $this->claim($resource, $newCode, (int) $originalUserId);
            }
        });
    }

    public function capture(Request $request, ?array $mutationSnapshot = null): void
    {
        if (! Schema::hasTable('fleet_record_owners') || ! $request->user() instanceof User) {
            return;
        }

        $routeName = (string) $request->route()?->getName();
        $userId = (int) $request->user()->id;

        if (isset(self::SYNC_RESOURCES[$routeName])) {
            [$resource, $idKey, $replaceAll] = array_pad(self::SYNC_RESOURCES[$routeName], 3, true);
            // Full ownership replacement is safe only when the caller explicitly
            // confirms that the request contains every row in the resource.
            $replaceAll = (bool) $replaceAll && $request->boolean('_legacy_replace_all');

            // The activity middleware already compared the database state with
            // the incoming rows. Use that result so an edit never assigns an
            // untracked legacy record to the editor as its original creator.
            if (is_array($mutationSnapshot)) {
                foreach ((array) ($mutationSnapshot['created'] ?? []) as $code) {
                    $this->claim($resource, trim((string) $code), $userId);
                }
                foreach ((array) ($mutationSnapshot['deleted'] ?? []) as $code) {
                    $this->forget($resource, trim((string) $code));
                }
                return;
            }

            $codes = collect((array) $request->input('rows', []))
                ->filter(fn ($row): bool => is_array($row))
                ->map(fn (array $row): string => trim((string) ($row[$idKey] ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($replaceAll) {
                $this->syncOwners($resource, $codes, $userId);
            } else {
                foreach ($codes as $code) {
                    $this->claim($resource, $code, $userId);
                }
            }
            return;
        }

        if ($routeName === 'fleet.yards.store') {
            $responsePayload = (array) $request->attributes->get('fleet_response_payload', []);
            $code = trim((string) data_get($responsePayload, 'record.yardId', ''));
            if ($code !== '') {
                $this->claim('yards', $code, $userId);
            }
            return;
        }

        if ($routeName === 'fleet.yards.update') {
            // Updates preserve the original creator. Legacy rows remain
            // explicitly untracked instead of being assigned to the editor.
            return;
        }

        if ($routeName === 'fleet.yards.destroy') {
            $this->forget('yards', trim((string) $request->route('code')));
        }
    }

    public function recipients(string $resource, string $code, array $payload = []): array
    {
        $ids = collect();

        if (Schema::hasTable('fleet_record_owners')) {
            $ownerId = DB::table('fleet_record_owners')
                ->where('resource_type', $resource)
                ->where('resource_code', $code)
                ->value('user_id');
            if ($ownerId) {
                $ids->push((int) $ownerId);
            }
        }

        $emails = $this->extractEmails($payload);
        if ($emails !== [] && Schema::hasTable('users')) {
            $ids = $ids->merge(User::query()->whereIn('email', $emails)->pluck('id'));
        }

        return $ids->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    private function syncOwners(string $resource, array $codes, int $userId): void
    {
        DB::transaction(function () use ($resource, $codes, $userId): void {
            $query = DB::table('fleet_record_owners')->where('resource_type', $resource);
            if ($codes === []) {
                $query->delete();
            } else {
                $query->whereNotIn('resource_code', $codes)->delete();
            }

            foreach ($codes as $code) {
                $this->claim($resource, $code, $userId);
            }
        });
    }

    private function claim(string $resource, string $code, int $userId): void
    {
        if ($code === '') {
            return;
        }

        DB::table('fleet_record_owners')->insertOrIgnore([
            'resource_type' => $resource,
            'resource_code' => $code,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function forget(string $resource, string $code): void
    {
        if ($code === '') {
            return;
        }

        DB::table('fleet_record_owners')
            ->where('resource_type', $resource)
            ->where('resource_code', $code)
            ->delete();
    }

    private function extractEmails(array $payload): array
    {
        $emails = [];
        array_walk_recursive($payload, function ($value) use (&$emails): void {
            if (is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($value));
            }
        });

        return array_values(array_unique($emails));
    }
}
