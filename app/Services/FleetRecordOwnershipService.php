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
        'fleet.dues.sync' => ['dues', 'dueId'],
    ];

    public function capture(Request $request): void
    {
        if (! Schema::hasTable('fleet_record_owners') || ! $request->user() instanceof User) {
            return;
        }

        $routeName = (string) $request->route()?->getName();
        $userId = (int) $request->user()->id;

        if (isset(self::SYNC_RESOURCES[$routeName])) {
            [$resource, $idKey] = self::SYNC_RESOURCES[$routeName];
            $codes = collect((array) $request->input('rows', []))
                ->filter(fn ($row): bool => is_array($row))
                ->map(fn (array $row): string => trim((string) ($row[$idKey] ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $this->syncOwners($resource, $codes, $userId);
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
            $code = trim((string) $request->route('code'));
            if ($code !== '') {
                $this->claim('yards', $code, $userId);
            }
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
