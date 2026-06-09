<?php

namespace App\Http\Middleware;

use App\Services\FleetNotificationService;
use App\Services\FleetRecordOwnershipService;
use Closure;
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
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $request->attributes->set('fleet_response_payload', $response->getData(true));
        }

        if (! $this->shouldCapture($request, $response)) {
            return $response;
        }

        try {
            $this->ownership->capture($request);
            $this->sendActivityNotification($request);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function shouldCapture(Request $request, Response $response): bool
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

    private function sendActivityNotification(Request $request): void
    {
        $routeName = (string) $request->route()?->getName();
        $segments = explode('.', $routeName);
        $moduleKey = $segments[1] ?? 'system';
        $module = self::MODULE_LABELS[$moduleKey] ?? Str::headline(str_replace('-', ' ', $moduleKey));
        $action = $this->actionLabel($request, $routeName);
        $count = is_array($request->input('rows')) ? count($request->input('rows')) : null;
        $actor = $request->user();
        $recordCode = $this->recordCode($request);

        $message = trim($actor->name.' '.$action.' '.$module);
        if ($count !== null) {
            $message .= ' ('.$count.' record'.($count === 1 ? '' : 's').')';
        } elseif ($recordCode !== '') {
            $message .= ' '.$recordCode;
        }
        $message .= '.';

        $this->notifications->notifyAdmins([
            'title' => $module.' activity',
            'message' => $message,
            'category' => 'activity',
            'icon' => '📝',
            'url' => $this->safeCurrentModuleUrl($request),
            'actor_name' => $actor->name,
            'resource' => $moduleKey,
            'resource_code' => $recordCode,
        ]);
    }

    private function actionLabel(Request $request, string $routeName): string
    {
        if ($request->isMethod('DELETE')) {
            return 'deleted';
        }
        if ($request->isMethod('PUT') || $request->isMethod('PATCH') || Str::endsWith($routeName, ['.update', '.update-logo'])) {
            return 'updated';
        }
        if (Str::contains($routeName, 'generate-payroll')) {
            return 'generated';
        }
        if (Str::endsWith($routeName, '.sync')) {
            return 'saved';
        }

        return 'created';
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

        foreach (['yardId', 'tripId', 'vehicleId', 'driverId', 'employeeId', 'clientId', 'contractId', 'partyId', 'rechargeId'] as $key) {
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
