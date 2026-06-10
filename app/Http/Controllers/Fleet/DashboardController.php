<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetFuelRecharge;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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

        // Dashboard summaries must not leak module data to roles that cannot
        // open the corresponding module.
        $vehicles = $can('vehicles.view') ? $this->rows(FleetVehicle::class) : [];
        $drivers = $can('drivers.view') ? $this->rows(FleetDriver::class) : [];
        $trips = $can('trips.view') ? $this->rows(FleetTrip::class) : [];
        $fuelRecharges = $can('fuel_recharge.view') ? $this->rows(FleetFuelRecharge::class) : [];
        $clients = $can('clients.view') ? $this->rows(FleetClient::class) : [];
        $attendance = $can('driver_attendance.view') ? $this->rows(FleetDriverAttendance::class) : [];
        $employees = $can('employees.view') ? $this->rows(FleetEmployee::class) : [];
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
                        'url' => trim((string) ($data['url'] ?? '')),
                        'is_unread' => $notification->read_at === null,
                        'created_at' => optional($notification->created_at)
                            ->timezone('Asia/Dhaka')
                            ->format('d M Y, h:i A'),
                    ];
                })
                ->values()
                ->all()
            : [];

        $activeVehicleCount = collect($vehicles)->filter(function (array $row) {
            return in_array($row['status'] ?? 'Active', ['Active', 'Needs document review'], true);
        })->count();

        $totalTripCost = collect($trips)->sum(fn (array $row) => (float) ($row['totalCost'] ?? 0));
        $totalTripPaid = collect($trips)->sum(fn (array $row) => (float) ($row['paidAmount'] ?? 0));
        $totalTripBalance = collect($trips)->sum(fn (array $row) => max(0, (float) ($row['balanceDue'] ?? ((float) ($row['totalCost'] ?? 0) - (float) ($row['paidAmount'] ?? 0)))));
        $paidTrips = collect($trips)->filter(function (array $row): bool {
            $total = (float) ($row['totalCost'] ?? 0);
            $balance = max(0, (float) ($row['balanceDue'] ?? ($total - (float) ($row['paidAmount'] ?? 0))));

            return $total > 0 && $balance <= 0.009;
        })->count();
        $totalPayroll = collect($drivers)->sum(fn (array $row) => (float) ($row['salary'] ?? 0))
            + collect($employees)->sum(fn (array $row) => (float) ($row['salary'] ?? 0));
        $totalFuelExpense = collect($fuelRecharges)
            ->filter(fn (array $row): bool => strcasecmp(trim((string) ($row['status'] ?? '')), 'Submitted') === 0)
            ->sum(fn (array $row): float => (float) ($row['totalAmount'] ?? 0));
        $expiringDrivers = collect($drivers)->filter(function (array $row) {
            $date = $row['licenseValidity'] ?? null;
            if (! $date) {
                return false;
            }

            try {
                return now()->diffInDays(Carbon::parse($date), false) <= 180;
            } catch (\Throwable) {
                return false;
            }
        })->count();

        $totalAttendanceKm = collect($attendance)->sum(fn (array $row) => (float) ($row['distance'] ?? 0));
        $activeClientCount = collect($clients)->filter(
            fn (array $row): bool => strcasecmp(trim((string) ($row['status'] ?? '')), 'Active') === 0
        )->count();

        return [
            'stats' => [
                [
                    'label' => 'Total Vehicles',
                    'value' => count($vehicles),
                    'helper' => $activeVehicleCount.' active / usable',
                    'icon' => '🚗',
                    'route' => 'fleet.vehicles',
                    'permission' => 'vehicles.view',
                ],
                [
                    'label' => 'Drivers',
                    'value' => count($drivers),
                    'helper' => $expiringDrivers.' license warning',
                    'icon' => '🧑‍✈️',
                    'route' => 'fleet.drivers',
                    'permission' => 'drivers.view',
                ],
                [
                    'label' => 'Trips',
                    'value' => count($trips),
                    'helper' => $paidTrips.' fully paid · ৳'.number_format($totalTripBalance, 2).' balance',
                    'icon' => '🧭',
                    'route' => 'fleet.trips',
                    'permission' => 'trips.view',
                ],
                [
                    'label' => 'Clients',
                    'value' => count($clients),
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
                'fuel_recharges' => array_slice($fuelRecharges, 0, 5),
                'vehicles' => $this->recentRowsWithMedia($vehicles, 'image'),
                'trips' => array_slice($trips, 0, 5),
                'drivers' => $this->recentRowsWithMedia($drivers, 'photo'),
                'clients' => array_slice($clients, 0, 5),
                'employees' => $this->recentRowsWithMedia($employees, 'photo'),
            ],
            'access' => [
                'vehicles' => $can('vehicles.view'),
                'drivers' => $can('drivers.view'),
                'trips' => $can('trips.view'),
                'clients' => $can('clients.view'),
                'vendors' => $can('vendors.view'),
                'attendance' => $can('driver_attendance.view'),
                'fuelPrices' => $can('fuel_prices.view'),
                'fuelRecharge' => $can('fuel_recharge.view'),
                'employees' => $can('employees.view'),
            ],
            'warnings' => [
                ['title' => 'Driver license review', 'value' => $expiringDrivers, 'description' => 'Drivers with license validity within 180 days.'],
                ['title' => 'Trip payment balance', 'value' => '৳'.number_format($totalTripBalance, 2), 'description' => 'Remaining client payments across saved trips.'],
                ['title' => 'Total attendance distance', 'value' => number_format($totalAttendanceKm, 2).' km', 'description' => 'Distance from driver attendance logs.'],
            ],
        ];
    }

    private function recentRowsWithMedia(array $rows, string $mediaKey): array
    {
        return collect($rows)
            ->take(5)
            ->map(function (array $row) use ($mediaKey): array {
                $row['_dashboardMediaUrl'] = $this->dashboardMediaUrl($row[$mediaKey] ?? null);

                return $row;
            })
            ->values()
            ->all();
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

            return route('fleet.files.show', ['path' => $path], false);
        }

        foreach (['fileUrl', 'file_url', 'previewUrl', 'preview_url', 'url'] as $urlKey) {
            $url = trim((string) ($file[$urlKey] ?? ''));
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function rows(string $modelClass): array
    {
        return $modelClass::query()
            ->latest('id')
            ->get()
            ->map(fn (Model $row) => $row->payload ?? [])
            ->values()
            ->all();
    }
}
