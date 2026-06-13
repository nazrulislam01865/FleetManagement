<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetDue;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class TripController extends FleetBaseController
{
    protected string $activeMenu = 'trips';
    protected string $view = 'fleetman.trips';
    protected string $page = 'trips';
    protected string $resource = 'trips';
    protected string $idKey = 'tripId';
    protected string $nameKey = 'purpose';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetTrip::class;

    public function sync(Request $request): JsonResponse
    {
        $rows = $request->input('rows', []);
        if (! is_array($rows)) {
            $rows = json_decode((string) $rows, true) ?: [];
        }

        $vehicleMap = $this->vehicleAliasMap();
        $driverMap = $this->driverAliasMap();
        $clientMap = $this->clientAliasMap();
        $normalizedRows = collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => $this->normalizeTrip($row, $vehicleMap, $driverMap, $clientMap))
            ->values()
            ->all();

        $validator = Validator::make(['rows' => $normalizedRows], [
            'rows' => ['present', 'array'],
            'rows.*' => ['array'],
            'rows.*.tripValidationVersion' => ['nullable', 'integer', 'min:1'],
            'rows.*.tripId' => ['required', 'string', 'max:100', 'distinct'],
            'rows.*.startDate' => ['required', 'date'],
            'rows.*.vehicle' => ['required', 'string', 'max:255'],
            'rows.*.vehicleId' => ['nullable', 'string', 'max:100'],
            'rows.*.driver' => ['required', 'string', 'max:255'],
            'rows.*.driverId' => ['nullable', 'string', 'max:100'],
            'rows.*.purpose' => ['nullable', 'string', 'max:255'],
            'rows.*.client' => ['nullable', 'string', 'max:255'],
            'rows.*.clientId' => ['nullable', 'string', 'max:100'],
            'rows.*.fromLocation' => ['nullable', 'string', 'max:255'],
            'rows.*.toLocation' => ['nullable', 'string', 'max:255'],
            'rows.*.odoStart' => ['nullable', 'numeric', 'min:0'],
            'rows.*.odoEnd' => ['nullable', 'numeric', 'min:0'],
            'rows.*.totalCost' => ['required', 'numeric', 'gt:0'],
            'rows.*.payments' => ['present', 'array'],
            'rows.*.payments.*.method' => ['required', 'string', 'max:100'],
            'rows.*.payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'rows.*.payments.*.reference' => ['nullable', 'string', 'max:255'],
            'rows.*.details' => ['required', 'string'],
        ], [
            'rows.*.tripId.required' => 'Trip ID is required.',
            'rows.*.startDate.required' => 'Start date is required.',
            'rows.*.vehicle.required' => 'Vehicle is required.',
            'rows.*.driver.required' => 'Driver is required.',
            'rows.*.totalCost.required' => 'Total cost is required.',
            'rows.*.totalCost.gt' => 'Total cost must be greater than zero.',
            'rows.*.payments.*.method.required' => 'A payment method is required for every entered payment.',
            'rows.*.payments.*.amount.gt' => 'Every payment amount must be greater than zero.',
            'rows.*.details.required' => 'Trip details are required.',
        ]);

        $clientIds = collect($clientMap)->pluck('id')->filter()->unique()->all();
        $existingTrips = Schema::hasTable('fleet_trips')
            ? FleetTrip::query()->get()->mapWithKeys(fn (FleetTrip $trip) => [$trip->code => $trip->payload ?? []])->all()
            : [];

        $validator->after(function ($validator) use ($normalizedRows, $clientIds, $existingTrips): void {
            foreach ($normalizedRows as $index => $row) {
                $existing = $existingTrips[(string) ($row['tripId'] ?? '')] ?? null;
                $sameHistoricalClient = is_array($existing)
                    && (string) ($existing['clientId'] ?? '') === (string) ($row['clientId'] ?? '')
                    && (string) ($existing['client'] ?? '') === (string) ($row['client'] ?? '');

                $usesCurrentTripValidation = (int) ($row['tripValidationVersion'] ?? 0) >= 2;
                $hasClientValue = filled($row['clientId'] ?? null) || filled($row['client'] ?? null);
                if ($usesCurrentTripValidation
                    && $this->isClientVisit((string) ($row['purpose'] ?? ''))
                    && $hasClientValue
                    && (blank($row['clientId'] ?? null)
                        || blank($row['client'] ?? null)
                        || (! in_array((string) $row['clientId'], $clientIds, true) && ! $sameHistoricalClient))) {
                    $validator->errors()->add("rows.$index.client", 'Select a valid client from the saved client suggestions or leave the field blank.');
                }

                $odoStart = $row['odoStart'] ?? null;
                $odoEnd = $row['odoEnd'] ?? null;
                if ($odoStart !== null && $odoStart !== '' && $odoEnd !== null && $odoEnd !== '' && (float) $odoEnd < (float) $odoStart) {
                    $validator->errors()->add("rows.$index.odoEnd", 'Odo end cannot be lower than Odo start.');
                }

                $totalCost = (float) ($row['totalCost'] ?? 0);
                $paidAmount = collect($row['payments'] ?? [])->sum(fn (array $payment) => (float) ($payment['amount'] ?? 0));
                if ($paidAmount > $totalCost + 0.009) {
                    $validator->errors()->add("rows.$index.payments", 'Total paid cannot exceed the total bill (trip cost).');
                }
            }
        });

        $validated = $validator->validate();
        $request->merge(['rows' => $validated['rows']]);
        $response = parent::sync($request);

        $this->syncTripBalances($validated['rows']);

        return $response;
    }

    private function normalizeTrip(array $row, array $vehicleMap, array $driverMap, array $clientMap): array
    {
        $vehicle = $this->findAlias($vehicleMap, [$row['vehicleId'] ?? null, $row['vehicle'] ?? null]);
        $driver = $this->findAlias($driverMap, [$row['driverId'] ?? null, $row['driver'] ?? null]);
        $client = $this->findAlias($clientMap, [$row['clientId'] ?? null, $row['client'] ?? null]);

        $totalCost = (float) ($row['totalCost'] ?? $row['tripTotalCost'] ?? 0);
        if ($totalCost <= 0) {
            $totalCost = (float) ($row['fuelCost'] ?? 0)
                + (float) ($row['foodCost'] ?? 0)
                + (float) ($row['tolls'] ?? 0)
                + (float) ($row['otherCost'] ?? 0)
                + (float) ($row['accommodationCost'] ?? 0);
        }
        $totalCost = round($totalCost, 2);

        $payments = collect(is_array($row['payments'] ?? null) ? $row['payments'] : [])
            ->filter(fn ($payment) => is_array($payment))
            ->map(fn (array $payment) => [
                'method' => trim((string) ($payment['method'] ?? '')),
                'amount' => round((float) ($payment['amount'] ?? 0), 2),
                'reference' => trim((string) ($payment['reference'] ?? '')),
            ])
            ->filter(fn (array $payment) => $payment['method'] !== '' || $payment['amount'] > 0 || $payment['reference'] !== '')
            ->values()
            ->all();

        $paidAmount = round(collect($payments)->sum(fn (array $payment) => (float) $payment['amount']), 2);
        $balanceDue = round(max(0, $totalCost - $paidAmount), 2);
        $paymentState = $balanceDue <= 0.009 ? 'Paid' : ($paidAmount > 0 ? 'Partially Paid' : 'Unpaid');

        $normalized = [
            'tripValidationVersion' => isset($row['tripValidationVersion']) ? (int) $row['tripValidationVersion'] : null,
            'tripId' => trim((string) ($row['tripId'] ?? '')),
            'startDate' => (string) ($row['startDate'] ?? ''),
            'vehicle' => trim((string) ($row['vehicle'] ?? '')),
            'vehicleId' => $vehicle['id'] ?? trim((string) ($row['vehicleId'] ?? '')),
            'driver' => trim((string) ($row['driver'] ?? '')),
            'driverId' => $driver['id'] ?? trim((string) ($row['driverId'] ?? '')),
            'purpose' => trim((string) ($row['purpose'] ?? '')),
            'client' => $client['label'] ?? trim((string) ($row['client'] ?? '')),
            'clientId' => $client['id'] ?? trim((string) ($row['clientId'] ?? '')),
            'fromLocation' => trim((string) ($row['fromLocation'] ?? '')),
            'toLocation' => trim((string) ($row['toLocation'] ?? '')),
            'odoStart' => $this->nullableNumber($row['odoStart'] ?? null),
            'odoEnd' => $this->nullableNumber($row['odoEnd'] ?? null),
            'totalCost' => number_format($totalCost, 2, '.', ''),
            'payments' => $payments,
            'paidAmount' => number_format($paidAmount, 2, '.', ''),
            'balanceDue' => number_format($balanceDue, 2, '.', ''),
            'paymentState' => $paymentState,
            'details' => trim((string) ($row['details'] ?? '')),
        ];

        return $normalized;
    }

    private function nullableNumber(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }

    private function vehicleAliasMap(): array
    {
        if (! Schema::hasTable('fleet_vehicles')) {
            return [];
        }

        $map = [];
        foreach (FleetVehicle::query()->get() as $vehicle) {
            $payload = $vehicle->payload ?? [];
            $id = (string) ($payload['id'] ?? $vehicle->code);
            $name = (string) ($payload['name'] ?? $vehicle->name ?? $vehicle->code);
            $label = trim($id.' - '.$name, ' -');
            $item = ['id' => $id, 'label' => $label];
            foreach ([$id, $vehicle->code, $name, $label, $payload['regNo'] ?? null] as $alias) {
                if (filled($alias)) {
                    $map[strtolower(trim((string) $alias))] = $item;
                }
            }
        }

        return $map;
    }

    private function driverAliasMap(): array
    {
        if (! Schema::hasTable('fleet_drivers')) {
            return [];
        }

        $map = [];
        foreach (FleetDriver::query()->get() as $driver) {
            $payload = $driver->payload ?? [];
            $id = (string) ($payload['driverId'] ?? $driver->code);
            $name = (string) ($payload['fullName'] ?? $driver->name ?? $driver->code);
            $label = trim($id.' - '.$name, ' -');
            $item = ['id' => $id, 'label' => $label];
            foreach ([$id, $driver->code, $name, $label, $payload['contact'] ?? null, $payload['phone'] ?? null] as $alias) {
                if (filled($alias)) {
                    $map[strtolower(trim((string) $alias))] = $item;
                }
            }
        }

        return $map;
    }

    private function clientAliasMap(): array
    {
        if (! Schema::hasTable('fleet_clients')) {
            return [];
        }

        $map = [];
        foreach (FleetClient::query()->get() as $client) {
            $payload = $client->payload ?? [];
            $id = (string) ($payload['clientId'] ?? $client->code);
            $name = (string) ($payload['clientName'] ?? $client->name ?? $client->code);
            $label = trim($id.' - '.$name, ' -');
            $item = ['id' => $id, 'label' => $label];
            foreach ([$id, $client->code, $name, $label, $payload['phone'] ?? null, $payload['email'] ?? null] as $alias) {
                if (filled($alias)) {
                    $map[strtolower(trim((string) $alias))] = $item;
                }
            }
        }

        return $map;
    }

    private function isClientVisit(string $purpose): bool
    {
        return strcasecmp(trim($purpose), 'Client Visit') === 0;
    }

    private function findAlias(array $map, array $values): ?array
    {
        foreach ($values as $value) {
            $key = strtolower(trim((string) $value));
            if ($key !== '' && isset($map[$key])) {
                return $map[$key];
            }
        }

        return null;
    }

    private function syncTripBalances(array $rows): void
    {
        if (! Schema::hasTable('fleet_dues')) {
            return;
        }

        DB::transaction(function () use ($rows): void {
            $tripIds = collect($rows)->pluck('tripId')->filter()->values();
            FleetDue::query()
                ->where('source_type', 'Trip')
                ->when($tripIds->isNotEmpty(), fn ($query) => $query->whereNotIn('source_id', $tripIds))
                ->when($tripIds->isEmpty(), fn ($query) => $query)
                ->delete();

            foreach ($rows as $row) {
                $tripId = (string) ($row['tripId'] ?? '');
                $balance = (float) ($row['balanceDue'] ?? 0);
                if ($tripId === '') {
                    continue;
                }

                if ($balance <= 0.009) {
                    FleetDue::query()->where('code', 'DUE-TRP-'.$tripId)->delete();
                    continue;
                }

                FleetDue::updateOrCreate(
                    ['code' => 'DUE-TRP-'.$tripId],
                    [
                        'type' => 'Trip Payment Balance',
                        'party_type' => 'Client',
                        'party_id' => $row['clientId'] ?? null,
                        'source_type' => 'Trip',
                        'source_id' => $tripId,
                        'amount' => $balance,
                        'status' => 'Pending',
                        'due_date' => $row['startDate'] ?? null,
                        'payload' => [
                            'totalCost' => $row['totalCost'] ?? 0,
                            'paidAmount' => $row['paidAmount'] ?? 0,
                            'balanceDue' => $balance,
                            'payments' => $row['payments'] ?? [],
                            'vehicleId' => $row['vehicleId'] ?? null,
                            'driverId' => $row['driverId'] ?? null,
                            'purpose' => $row['purpose'] ?? null,
                            'clientId' => $row['clientId'] ?? null,
                            'client' => $row['client'] ?? null,
                        ],
                    ]
                );
            }
        });
    }
}
