<?php

namespace App\Http\Middleware;

use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetContract;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetDue;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetFuelPrice;
use App\Models\Fleet\FleetFuelRecharge;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVendorParty;
use App\Services\FleetNotificationService;
use App\Services\FleetRecordOwnershipService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CaptureFleetActivityNotifications
{
    private const EXCLUDED_PREFIXES = [
        'fleet.notifications.',
        'fleet.uploads.',
    ];

    /**
     * These endpoints use a full-table payload for create, update and delete.
     * The pre-request snapshot lets notifications describe the real change
     * instead of producing a generic notification for every POST request.
     */
    private const SYNC_RESOURCES = [
        'fleet.vehicles.sync' => [FleetVehicle::class, 'id', true],
        'fleet.fuel-prices.sync' => [FleetFuelPrice::class, 'fuelPriceId', true],
        'fleet.fuel-recharge.sync' => [FleetFuelRecharge::class, 'rechargeId', true],
        'fleet.vendors.sync' => [FleetVendorParty::class, 'partyId', true],
        'fleet.trips.sync' => [FleetTrip::class, 'tripId', true],
        'fleet.drivers.sync' => [FleetDriver::class, 'driverId', true],
        'fleet.driver-attendance.sync' => [FleetDriverAttendance::class, 'logId', true],
        'fleet.employees.sync' => [FleetEmployee::class, 'employeeId', true],
        'fleet.contracts.sync' => [FleetContract::class, 'contractId', true, ['fuel_recharge', 'attendance']],
        'fleet.clients.sync' => [FleetClient::class, 'clientId', true],
        // Dues sync updates only the supplied rows and does not delete omitted rows.
        'fleet.dues.sync' => [FleetDue::class, 'code', false],
    ];

    private const MODULE_LABELS = [
        'yards' => 'Yard',
        'vehicles' => 'Vehicle',
        'fuel-prices' => 'Fuel Price',
        'fuel-recharge' => 'Fuel',
        'dues' => 'Dues and Payroll',
        'vendors' => 'Vendor',
        'trips' => 'Trip',
        'drivers' => 'Driver',
        'driver-attendance' => 'Drive Log',
        'employees' => 'Employee',
        'contracts' => 'Contract',
        'clients' => 'Client',
        'master-data' => 'Master Data',
        'users' => 'User Management',
        'role-matrix' => 'Role Matrix',
        'settings' => 'Settings',
    ];

    public function __construct(
        private readonly FleetNotificationService $notifications,
        private readonly FleetRecordOwnershipService $ownership,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $mutationSnapshot = $this->captureMutationSnapshot($request);
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $request->attributes->set('fleet_response_payload', $response->getData(true));
        }

        if (! $this->shouldProcessActivity($request, $response)) {
            return $response;
        }

        try {
            // Ownership tracking remains unchanged for every successful data request.
            $this->ownership->capture($request);

            foreach ($this->crudEvents($request, $mutationSnapshot) as $event) {
                $this->sendActivityNotification(
                    $request,
                    $event['action'],
                    $event['count'],
                    $event['code']
                );
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function shouldProcessActivity(Request $request, Response $response): bool
    {
        if (! $request->user() || ! $request->is('fleet/*') || $request->isMethod('GET')) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $routeName = (string) $request->route()?->getName();
        if ($routeName === '') {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (Str::startsWith($routeName, $prefix)) {
                return false;
            }
        }

        return $routeName !== 'fleet.notifications.pusher-auth';
    }

    /**
     * Return only genuine create, update or delete events.
     * Operational actions such as opening filters, uploading temporary files,
     * reading notifications and generating payroll do not create activity alerts.
     *
     * @return array<int, array{action:string,count:int,code:string}>
     */
    private function crudEvents(Request $request, ?array $snapshot): array
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === '' || Str::contains($routeName, '.documents.upload')) {
            return [];
        }

        if ($routeName === 'fleet.dues.generate-payroll') {
            $created = (int) data_get(
                (array) $request->attributes->get('fleet_response_payload', []),
                'created',
                0
            );

            return $created > 0 ? [[
                'action' => 'created',
                'count' => $created,
                'code' => trim((string) $request->input('month', '')),
            ]] : [];
        }

        if (isset(self::SYNC_RESOURCES[$routeName])) {
            return $this->syncCrudEvents($snapshot);
        }

        if ($routeName === 'fleet.driver-attendance.store') {
            $row = (array) $request->input('row', []);
            $code = trim((string) ($row['logId'] ?? ''));

            return [[
                'action' => (bool) ($snapshot['record_exists'] ?? false) ? 'updated' : 'created',
                'count' => 1,
                'code' => $code,
            ]];
        }

        if ($routeName === 'fleet.master-data.document-names.save') {
            $document = (array) $request->input('document', []);
            $code = trim((string) ($document['code'] ?? $document['name'] ?? ''));
            $editingCode = trim((string) $request->input('editingCode', ''));

            return [[
                'action' => $editingCode !== '' ? 'updated' : 'created',
                'count' => 1,
                'code' => $code,
            ]];
        }

        if ($request->isMethod('DELETE') || Str::endsWith($routeName, '.destroy')) {
            return [[
                'action' => 'deleted',
                'count' => 1,
                'code' => $this->recordCode($request),
            ]];
        }

        if ($request->isMethod('PUT')
            || $request->isMethod('PATCH')
            || Str::endsWith($routeName, ['.update', '.update-logo'])) {
            return [[
                'action' => 'updated',
                'count' => 1,
                'code' => $this->recordCode($request),
            ]];
        }

        if (Str::endsWith($routeName, '.store')) {
            return [[
                'action' => 'created',
                'count' => 1,
                'code' => $this->recordCode($request),
            ]];
        }

        // Remaining save endpoints represent an explicit update action, not a click/read event.
        if (Str::endsWith($routeName, ['.sync', '.save'])) {
            return [[
                'action' => 'updated',
                'count' => 1,
                'code' => $this->recordCode($request),
            ]];
        }

        return [];
    }

    /**
     * @return array<int, array{action:string,count:int,code:string}>
     */
    private function syncCrudEvents(?array $snapshot): array
    {
        if (! is_array($snapshot)) {
            return [];
        }

        $events = [];
        foreach (['created', 'updated', 'deleted'] as $action) {
            $codes = array_values(array_filter((array) ($snapshot[$action] ?? [])));
            if ($codes === []) {
                continue;
            }

            $events[] = [
                'action' => $action,
                'count' => count($codes),
                'code' => count($codes) === 1 ? (string) $codes[0] : '',
            ];
        }

        return $events;
    }

    private function captureMutationSnapshot(Request $request): ?array
    {
        if (! $request->user() || ! $request->is('fleet/*') || $request->isMethod('GET')) {
            return null;
        }

        $routeName = (string) $request->route()?->getName();

        if ($routeName === 'fleet.driver-attendance.store') {
            $row = (array) $request->input('row', []);
            $code = trim((string) ($row['logId'] ?? ''));

            return [
                'record_exists' => $code !== ''
                    && FleetDriverAttendance::query()->where('code', $code)->exists(),
            ];
        }

        $configuration = self::SYNC_RESOURCES[$routeName] ?? null;
        if (! is_array($configuration)) {
            return null;
        }

        [$modelClass, $idKey, $deleteMissing] = array_pad($configuration, 3, null);
        $excludedStatuses = $configuration[3] ?? [];
        $rows = $request->input('rows', []);
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }
        if (! is_array($rows)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $existingQuery = $modelClass::query();
        if (is_array($excludedStatuses) && $excludedStatuses !== []) {
            $existingQuery->whereNotIn('status', $excludedStatuses);
        }

        $existing = $existingQuery
            ->get()
            ->mapWithKeys(function (Model $record): array {
                $payload = is_array($record->getAttribute('payload'))
                    ? $record->getAttribute('payload')
                    : [];

                return [(string) $record->getAttribute('code') => $this->comparablePayload($payload)];
            })
            ->all();

        $incoming = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = trim((string) ($row[$idKey] ?? ''));
            if ($code === '') {
                continue;
            }

            $incoming[$code] = $this->comparablePayload($row);
        }

        $created = [];
        $updated = [];
        foreach ($incoming as $code => $payload) {
            if (! array_key_exists($code, $existing)) {
                $created[] = $code;
                continue;
            }

            if ($existing[$code] != $payload) {
                $updated[] = $code;
            }
        }

        $deleted = $deleteMissing
            ? array_values(array_diff(array_keys($existing), array_keys($incoming)))
            : [];

        return compact('created', 'updated', 'deleted');
    }

    private function comparablePayload(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach (['createdAt', 'updatedAt', 'created_at', 'updated_at'] as $metadataKey) {
            unset($value[$metadataKey]);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->comparablePayload($item);
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    private function sendActivityNotification(Request $request, string $action, int $count, string $recordCode): void
    {
        $routeName = (string) $request->route()?->getName();
        $segments = explode('.', $routeName);
        $moduleKey = $segments[1] ?? 'system';
        $module = self::MODULE_LABELS[$moduleKey] ?? Str::headline(str_replace('-', ' ', $moduleKey));
        $actor = $request->user();

        $message = trim($actor->name.' '.$action.' '.$module);
        if ($count > 1) {
            $message .= ' ('.$count.' records)';
        } elseif ($recordCode !== '') {
            $message .= ' '.$recordCode;
        }
        $message .= '.';

        $this->notifications->notifyAdmins([
            'title' => $module.' '.Str::headline($action),
            'message' => $message,
            'category' => 'activity',
            'icon' => $action === 'deleted' ? '🗑️' : ($action === 'created' ? '➕' : '✏️'),
            'url' => $this->safeCurrentModuleUrl($request),
            'actor_name' => $actor->name,
            'resource' => $moduleKey,
            'resource_code' => $recordCode,
            'action' => $action,
        ]);
    }

    private function recordCode(Request $request): string
    {
        foreach (['code', 'user', 'paymentType', 'vendorContractorType'] as $parameter) {
            $value = $request->route($parameter);
            if (is_scalar($value)) {
                return trim((string) $value);
            }
            if (is_object($value) && isset($value->id)) {
                return (string) $value->id;
            }
        }

        foreach (['yardId', 'tripId', 'vehicleId', 'driverId', 'employeeId', 'clientId', 'contractId', 'partyId', 'rechargeId', 'logId', 'code'] as $key) {
            if (filled($request->input($key))) {
                return trim((string) $request->input($key));
            }
        }

        return '';
    }

    private function safeCurrentModuleUrl(Request $request): string
    {
        $routeName = (string) $request->route()?->getName();
        $indexRoutes = [
            'yards' => 'fleet.yards',
            'vehicles' => 'fleet.vehicles',
            'fuel-prices' => 'fleet.fuel-prices',
            'fuel-recharge' => 'fleet.fuel-recharge',
            'dues' => 'fleet.dues',
            'vendors' => 'fleet.vendors',
            'trips' => 'fleet.trips',
            'drivers' => 'fleet.drivers',
            'driver-attendance' => 'fleet.driver-attendance',
            'employees' => 'fleet.employees',
            'contracts' => 'fleet.contracts',
            'clients' => 'fleet.clients',
            'master-data' => 'fleet.master-data',
            'users' => 'fleet.users',
            'role-matrix' => 'fleet.role-matrix',
            'settings' => 'fleet.settings',
        ];
        $moduleKey = explode('.', $routeName)[1] ?? '';
        $indexRoute = $indexRoutes[$moduleKey] ?? null;

        return $indexRoute && \Illuminate\Support\Facades\Route::has($indexRoute)
            ? route($indexRoute)
            : route('fleet.dashboard');
    }
}
