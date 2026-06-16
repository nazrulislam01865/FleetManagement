<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetFuelRecharge;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVendorParty;
use App\Support\FleetPhoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends FleetBaseController
{
    protected string $activeMenu = 'dashboard';
    protected string $view = 'fleetman.dashboard';
    protected string $page = 'dashboard';

    public function index(): View
    {
        return view($this->view, $this->shared($this->activeMenu, [
            'page' => $this->page,
            'dashboard' => $this->dashboardData(),
        ]));
    }

    private function dashboardData(): array
    {
        $user = auth()->user();
        $can = static fn (string $permission): bool => ! $user
            || ! method_exists($user, 'canFleet')
            || $user->canFleet($permission);

        $access = [
            'vehicles' => $can('vehicles.view'),
            'drivers' => $can('drivers.view'),
            'trips' => $can('trips.view'),
            'clients' => $can('clients.view'),
            'vendors' => $can('vendors.view'),
            'attendance' => $can('driver_attendance.view'),
            'fuelPrices' => $can('fuel_prices.view'),
            'fuelRecharge' => $can('fuel_recharge.view'),
            'employees' => $can('employees.view'),
        ];

        $vehicleCount = $access['vehicles'] ? FleetVehicle::query()->count() : 0;
        $activeVehicleCount = $access['vehicles']
            ? FleetVehicle::query()
                ->where(function ($query): void {
                    $query->whereNull('status')
                        ->orWhereIn('status', ['Active', 'Needs document review']);
                })
                ->count()
            : 0;

        $driverCount = $access['drivers'] ? FleetDriver::query()->count() : 0;
        $expiringDrivers = $access['drivers']
            ? FleetDriver::query()
                ->whereNotNull('license_validity')
                ->whereBetween('license_validity', [now()->toDateString(), now()->addDays(180)->toDateString()])
                ->count()
            : 0;

        $tripCount = $access['trips'] ? FleetTrip::query()->count() : 0;
        $tripSummary = $access['trips']
            ? FleetTrip::query()->selectRaw(
                'COALESCE(SUM(total_cost), 0) as total_cost, '
                .'COALESCE(SUM(paid_amount), 0) as paid_amount, '
                .'COALESCE(SUM(balance_due), 0) as balance_due, '
                .'COALESCE(SUM(CASE WHEN total_cost > 0 AND balance_due <= 0.009 THEN 1 ELSE 0 END), 0) as paid_trips'
            )->first()
            : null;

        $clientCount = $access['clients'] ? FleetClient::query()->count() : 0;
        $activeClientCount = $access['clients']
            ? FleetClient::query()->whereRaw('LOWER(status) = ?', ['active'])->count()
            : 0;

        $totalTripCost = (float) ($tripSummary?->total_cost ?? 0);
        $totalTripPaid = (float) ($tripSummary?->paid_amount ?? 0);
        $totalTripBalance = (float) ($tripSummary?->balance_due ?? 0);
        $paidTrips = (int) ($tripSummary?->paid_trips ?? 0);

        $totalPayroll = 0.0;
        if ($access['drivers']) {
            $totalPayroll += (float) FleetDriver::query()->sum('salary_amount');
        }
        if ($access['employees']) {
            $totalPayroll += (float) FleetEmployee::query()->sum('salary_amount');
        }

        $totalFuelExpense = $access['fuelRecharge']
            ? (float) FleetFuelRecharge::query()
                ->whereRaw('LOWER(status) = ?', ['submitted'])
                ->sum('total_amount')
            : 0.0;

        $totalAttendanceKm = $access['attendance']
            ? (float) FleetDriverAttendance::query()->sum('distance_km')
            : 0.0;

        $notifications = $user && Schema::hasTable('notifications')
            ? $user->notifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($notification): array {
                    $data = is_array($notification->data) ? $notification->data : [];

                    return [
                        'id' => (string) $notification->id,
                        'title' => trim((string) ($data['title'] ?? 'FleetMan Notification')),
                        'message' => trim((string) ($data['message'] ?? '')),
                        'icon' => trim((string) ($data['icon'] ?? '🔔')) ?: '🔔',
                        'url' => $this->notificationTargetUrl($data),
                        'is_unread' => $notification->read_at === null,
                        'created_at' => optional($notification->created_at)
                            ->timezone('Asia/Dhaka')
                            ->format('d M Y, h:i A'),
                    ];
                })
                ->values()
                ->all()
            : [];

        return [
            'stats' => [
                [
                    'label' => 'Total Vehicles',
                    'value' => $vehicleCount,
                    'helper' => $activeVehicleCount.' active / usable',
                    'icon' => '🚗',
                    'route' => 'fleet.vehicles',
                    'permission' => 'vehicles.view',
                ],
                [
                    'label' => 'Drivers',
                    'value' => $driverCount,
                    'helper' => $expiringDrivers.' license warning',
                    'icon' => '🧑‍✈️',
                    'route' => 'fleet.drivers',
                    'permission' => 'drivers.view',
                ],
                [
                    'label' => 'Trips',
                    'value' => $tripCount,
                    'helper' => $paidTrips.' fully paid · ৳'.number_format($totalTripBalance, 2).' balance',
                    'icon' => '🧭',
                    'route' => 'fleet.trips',
                    'permission' => 'trips.view',
                ],
                [
                    'label' => 'Clients',
                    'value' => $clientCount,
                    'helper' => $activeClientCount.' active client'.($activeClientCount === 1 ? '' : 's'),
                    'icon' => '🏢',
                    'route' => 'fleet.clients',
                    'permission' => 'clients.view',
                ],
            ],
            'finance' => [
                'trip_cost' => $totalTripCost,
                'trip_paid' => $totalTripPaid,
                'trip_balance' => $totalTripBalance,
                'payroll' => $totalPayroll,
                'fuel_expense' => $totalFuelExpense,
                'attendance_km' => $totalAttendanceKm,
            ],
            'recent' => [
                'notifications' => $notifications,
                'fuel_recharges' => $access['fuelRecharge'] ? $this->recentRowsFromModel(FleetFuelRecharge::class, 'rechargeId', 'fleet.fuel-recharge.show') : [],
                'vehicles' => $access['vehicles'] ? $this->recentRowsFromModel(FleetVehicle::class, 'id', 'fleet.vehicles.show', 'image') : [],
                'trips' => $access['trips'] ? $this->recentRowsFromModel(FleetTrip::class, 'tripId', 'fleet.trips.show') : [],
                'drivers' => $access['drivers'] ? $this->recentRowsFromModel(FleetDriver::class, 'driverId', 'fleet.drivers.show', 'photo') : [],
                'clients' => $access['clients'] ? $this->recentRowsFromModel(FleetClient::class, 'clientId', 'fleet.clients.show', 'photo') : [],
                'vendors' => $access['vendors'] ? $this->recentRowsFromModel(FleetVendorParty::class, 'partyId', 'fleet.vendors.show', 'photo') : [],
                'employees' => $access['employees'] ? $this->recentRowsFromModel(FleetEmployee::class, 'employeeId', 'fleet.employees.show', 'photo') : [],
            ],
            'access' => $access,
            'warnings' => [
                [
                    'title' => 'Driver license review',
                    'value' => $expiringDrivers,
                    'description' => 'Drivers with license validity within 180 days.',
                    'url' => $access['drivers']
                        ? route('fleet.drivers', ['license_filter' => 'within-180-days'])
                        : null,
                ],
                ['title' => 'Trip payment balance', 'value' => '৳'.number_format($totalTripBalance, 2), 'description' => 'Remaining client payments across saved trips.'],
                ['title' => 'Total attendance distance', 'value' => number_format($totalAttendanceKm, 2).' km', 'description' => 'Distance from driver attendance logs.'],
            ],
        ];
    }

    /**
     * Query only the five rows shown by a recent-activity card.
     *
     * @param class-string<Model> $modelClass
     */
    private function recentRowsFromModel(string $modelClass, string $idKey, string $showRoute, ?string $mediaKey = null): array
    {
        return $modelClass::query()
            ->latest('id')
            ->limit(5)
            ->get(['code', 'payload'])
            ->map(function (Model $record) use ($idKey, $showRoute, $mediaKey): array {
                $row = is_array($record->payload) ? $record->payload : [];
                $code = trim((string) ($row[$idKey] ?? $record->code));
                $row['_recordCode'] = (string) $record->code;
                $row['_dashboardViewUrl'] = $this->canViewRecordDetails() && $code !== '' && Route::has($showRoute)
                    ? route($showRoute, ['code' => $code])
                    : '';

                if ($mediaKey !== null) {
                    $row['_dashboardMediaUrl'] = $this->dashboardMediaUrl($row[$mediaKey] ?? null);
                }

                return $row;
            })
            ->values()
            ->all();
    }

    private function notificationTargetUrl(array $data): string
    {
        $resource = trim((string) ($data['resource'] ?? ''));
        $code = trim((string) ($data['resource_code'] ?? ''));
        $showRoutes = [
            'yards' => 'fleet.yards.show',
            'vehicles' => 'fleet.vehicles.show',
            'fuel-prices' => 'fleet.fuel-prices.show',
            'fuel-recharge' => 'fleet.fuel-recharge.show',
            'vendors' => 'fleet.vendors.show',
            'trips' => 'fleet.trips.show',
            'contracts' => 'fleet.contracts.show',
            'drivers' => 'fleet.drivers.show',
            'clients' => 'fleet.clients.show',
            'driver-attendance' => 'fleet.driver-attendance.show',
            'employees' => 'fleet.employees.show',
        ];
        $showRoute = $showRoutes[$resource] ?? null;

        if ($this->canViewRecordDetails()
            && ($data['action'] ?? '') !== 'deleted'
            && $code !== ''
            && $showRoute !== null
            && Route::has($showRoute)) {
            return route($showRoute, ['code' => $code]);
        }

        return trim((string) ($data['url'] ?? ''));
    }

    private function dashboardMediaUrl(mixed $file): string
    {
        if (is_string($file)) {
            $value = trim($file);
            if ($value === '') {
                return '';
            }

            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $file = $decoded;
            } elseif (preg_match('#^https?://#i', $value) || str_starts_with($value, '/')) {
                return $value;
            } else {
                $file = ['filePath' => $value];
            }
        }

        if (! is_array($file)) {
            return '';
        }

        foreach (['file', 'media', 'upload'] as $nestedKey) {
            if (! isset($file['filePath']) && ! isset($file['path']) && is_array($file[$nestedKey] ?? null)) {
                $file = array_merge($file[$nestedKey], $file);
            }
        }

        $path = trim((string) (
            $file['filePath']
            ?? $file['file_path']
            ?? $file['storagePath']
            ?? $file['storage_path']
            ?? $file['path']
            ?? ''
        ));

        if ($path !== '') {
            $path = preg_replace('#^(public/|storage/)#', '', ltrim($path, '/')) ?? $path;

            return FleetPhoto::url($path, false);
        }

        foreach (['fileUrl', 'file_url', 'previewUrl', 'preview_url', 'url'] as $urlKey) {
            $url = trim((string) ($file[$urlKey] ?? ''));
            if ($url !== '') {
                return FleetPhoto::rewriteStoredUrl($url, false);
            }
        }

        return '';
    }

}
