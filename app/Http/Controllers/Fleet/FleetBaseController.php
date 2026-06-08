<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Support\FleetBrand;
use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetContract;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDriverContactType;
use App\Models\Fleet\FleetDocumentName;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetFuelPrice;
use App\Models\Fleet\FleetFuelRecharge;
use App\Models\Fleet\FleetLookup;
use App\Models\Fleet\FleetPartyType;
use App\Models\Fleet\FleetPaymentType;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVehicleCategory;
use App\Models\Fleet\FleetVehicleSubCategory;
use App\Models\Fleet\FleetVendorParty;
use App\Models\Fleet\FleetVendorContractorType;
use App\Support\FleetRbac;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

abstract class FleetBaseController extends Controller
{
    protected string $activeMenu = 'vehicles';
    protected string $view = 'fleetman.vehicles';
    protected string $page = 'vehicles';
    protected string $resource = 'vehicles';
    protected string $idKey = 'id';
    protected string $nameKey = 'name';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetVehicle::class;

    public function index(): View
    {
        return view($this->view, $this->shared($this->activeMenu, [
            'page' => $this->page,
        ]));
    }

    public function show(string $code): View
    {
        $this->ensureRecordDetailsAccess();

        $modelClass = $this->modelClass;
        $query = $modelClass::query()->where('code', $code);

        if ($this->resource === 'contracts') {
            $query->whereNotIn('status', ['fuel_recharge', 'attendance']);
        }

        /** @var Model $record */
        $record = $query->firstOrFail();
        $payload = is_array($record->payload) ? $record->payload : [];
        if ($this->resource === 'fuel_recharges') {
            unset($payload['submittedAt'], $payload['submittedLocation']);
        }
        $detail = $this->recordDetailDefinition();
        $recordTitle = trim((string) ($payload[$detail['title_key']] ?? $payload[$this->nameKey] ?? $record->name ?? $record->code));

        $recordViewData = [
            'record' => $record,
            'recordPayload' => $payload,
            'recordTitle' => $recordTitle !== '' ? $recordTitle : $record->code,
            'detail' => $detail,
        ];

        return view('fleetman.record-view', array_merge(
            $this->shared($this->activeMenu, array_merge([
                'page' => 'record-detail',
            ], $recordViewData)),
            $recordViewData,
        ));
    }

    protected function ensureRecordDetailsAccess(): void
    {
        $user = auth()->user();
        $roleSlug = strtolower((string) ($user?->fleetRole?->slug ?? ''));
        $roleName = strtolower((string) ($user?->fleetRole?->name ?? ''));
        $allowed = (bool) ($user?->isFleetSuperAdmin())
            || in_array($roleSlug, ['super_admin', 'admin_user'], true)
            || $roleName === 'admin user';

        abort_unless($allowed, 403, 'Only Super Admin and Admin User can view full details.');
    }

    protected function recordDetailDefinition(): array
    {
        $definitions = [
            'vehicles' => [
                'title' => 'Vehicle Details',
                'list_label' => 'Vehicle List',
                'list_route' => 'fleet.vehicles',
                'title_key' => 'name',
            ],
            'fuel_prices' => [
                'title' => 'Fuel Price Details',
                'list_label' => 'Fuel Price List',
                'list_route' => 'fleet.fuel-prices',
                'title_key' => 'name',
            ],
            'fuel_recharges' => [
                'title' => 'Fuel Recharge Details',
                'list_label' => 'Recharge List',
                'list_route' => 'fleet.fuel-recharge',
                'title_key' => 'rechargeId',
            ],
            'parties' => [
                'title' => 'Vendor / Party Details',
                'list_label' => 'Vendor List',
                'list_route' => 'fleet.vendors',
                'title_key' => 'partyName',
            ],
            'trips' => [
                'title' => 'Trip Details',
                'list_label' => 'Trip List',
                'list_route' => 'fleet.trips',
                'title_key' => 'tripId',
            ],
            'drivers' => [
                'title' => 'Driver Details',
                'list_label' => 'Driver List',
                'list_route' => 'fleet.drivers',
                'title_key' => 'fullName',
            ],
            'clients' => [
                'title' => 'Client Details',
                'list_label' => 'Client List',
                'list_route' => 'fleet.clients',
                'title_key' => 'clientName',
            ],
            'employees' => [
                'title' => 'Employee Details',
                'list_label' => 'Employee List',
                'list_route' => 'fleet.employees',
                'title_key' => 'fullName',
            ],
            'driver_attendance' => [
                'title' => 'Driver Attendance Details',
                'list_label' => 'Log List',
                'list_route' => 'fleet.driver-attendance',
                'title_key' => 'logId',
            ],
            'contracts' => [
                'title' => 'Contract Details',
                'list_label' => 'Contract List',
                'list_route' => 'fleet.contracts',
                'title_key' => 'contractId',
            ],
        ];

        return $definitions[$this->resource] ?? [
            'title' => 'Record Details',
            'list_label' => 'Back to List',
            'list_route' => 'fleet.dashboard',
            'title_key' => $this->nameKey,
        ];
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['present', 'array'],
            'rows.*' => ['array'],
        ]);

        $rows = $validated['rows'];
        $this->validateUniqueDocumentNames($rows, $this->resource === 'vehicles' ? 'docs' : 'documents');
        $modelClass = $this->modelClass;
        $idKey = $this->idKey;
        $nameKey = $this->nameKey;
        $statusKey = $this->statusKey;

        DB::transaction(function () use ($modelClass, $rows, $idKey, $nameKey, $statusKey) {
            $incomingCodes = collect($rows)
                ->map(fn (array $row) => (string) ($row[$idKey] ?? ''))
                ->filter()
                ->values();

            $modelClass::query()->whereNotIn('code', $incomingCodes)->delete();

            foreach ($rows as $row) {
                $code = (string) ($row[$idKey] ?? '');
                if ($code === '') {
                    continue;
                }

                $modelClass::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $row[$nameKey] ?? $code,
                        'status' => $row[$statusKey] ?? null,
                        'payload' => $row,
                    ]
                );
            }
        });

        return response()->json([
            'ok' => true,
            'rows' => $this->recordsFor($modelClass),
        ]);
    }

    protected function validateUniqueDocumentNames(array $rows, string $documentKey = 'documents'): void
    {
        foreach ($rows as $rowIndex => $row) {
            $documents = $row[$documentKey] ?? [];
            if (! is_array($documents)) {
                continue;
            }

            $seen = [];
            foreach ($documents as $documentIndex => $document) {
                if (! is_array($document)) {
                    continue;
                }

                $name = trim((string) ($document['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $key = strtolower($name);
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages([
                        "rows.$rowIndex.$documentKey.$documentIndex.name" => "The document type '$name' has already been selected for this record.",
                    ]);
                }

                $seen[$key] = true;
            }
        }
    }

    protected function shared(string $activeMenu, array $pageData = []): array
    {
        $user = auth()->user();
        $roleName = $user?->fleetRole?->name ?? 'User';

        $logoUrl = FleetBrand::logoUrl();

        return [
            'brand' => array_merge(config('fleetman.brand'), [
                'logo_url' => $logoUrl,
            ]),
            'account' => array_merge(config('fleetman.account'), [
                'title' => $roleName,
                'name' => $user?->name ?? (config('fleetman.account.name') ?? 'User'),
            ]),
            'menuGroups' => $this->authorizedMenuGroups(config('fleetman.menu', [])),
            'activeMenu' => $activeMenu,
            'fleetman' => array_merge([
                'options' => $this->optionsFromDatabase(),
                'contracts' => $this->fuelRechargeContracts(),
                'photoRequirements' => $this->photoRequirements(),
                'fuelStations' => $this->fuelStationOptions(),
                'samples' => $this->recordsFromDatabase(),
                'records' => $this->recordsFromDatabase(),
                'tripMasters' => $this->tripMastersFromDatabase(),
                'contractMasters' => $this->contractMastersFromDatabase(),
                'attendanceMasters' => $this->attendanceMastersFromDatabase(),
                'latestFuelRates' => $this->latestActiveFuelRates(),
                'resources' => $this->resourceUrls(),
                'auth' => $this->fleetAuthPayload(),
            ], $pageData),
        ];
    }

    protected function authorizedMenuGroups(array $groups): array
    {
        return collect($groups)
            ->map(function (array $group): array {
                $items = collect($group['items'] ?? [])
                    ->map(function (array $item): ?array {
                        $children = collect($item['children'] ?? [])
                            ->filter(fn (array $child): bool => $this->menuItemAllowed($child))
                            ->values()
                            ->all();

                        if ($children !== []) {
                            $item['children'] = $children;
                            return $item;
                        }

                        unset($item['children']);

                        return $this->menuItemAllowed($item) ? $item : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                $group['items'] = $items;

                return $group;
            })
            ->filter(fn (array $group): bool => count($group['items'] ?? []) > 0)
            ->values()
            ->all();
    }

    protected function menuItemAllowed(array $item): bool
    {
        $permission = $item['permission'] ?? FleetRbac::permissionForRoute($item['route'] ?? null);

        if (! $permission) {
            return ! empty($item['route']);
        }

        $user = auth()->user();

        return ! $user || ! method_exists($user, 'canFleet') || $user->canFleet($permission);
    }

    protected function fleetAuthPayload(): array
    {
        $user = auth()->user();
        $permissionKeys = collect(FleetRbac::permissions())->pluck('key')->values()->all();
        $allowedPermissions = collect($permissionKeys)
            ->filter(fn (string $permission): bool => ! $user || ! method_exists($user, 'canFleet') || $user->canFleet($permission))
            ->values()
            ->all();

        return [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'role' => $user?->fleetRole ? [
                'id' => $user->fleetRole->id,
                'name' => $user->fleetRole->name,
                'slug' => $user->fleetRole->slug,
            ] : null,
            'permissions' => $allowedPermissions,
            'isSuperAdmin' => $user?->isFleetSuperAdmin() ?? false,
        ];
    }

    protected function resourceUrls(): array
    {
        return [
            'vehicles' => [
                'sync' => route('fleet.vehicles.sync'),
                'show_template' => route('fleet.vehicles.show', ['code' => '__CODE__']),
            ],
            'fuel_prices' => [
                'sync' => route('fleet.fuel-prices.sync'),
                'show_template' => route('fleet.fuel-prices.show', ['code' => '__CODE__']),
            ],
            'fuel_recharges' => [
                'sync' => route('fleet.fuel-recharge.sync'),
                'show_template' => route('fleet.fuel-recharge.show', ['code' => '__CODE__']),
            ],
            'parties' => array_filter([
                'sync' => route('fleet.vendors.sync'),
                'show_template' => route('fleet.vendors.show', ['code' => '__CODE__']),
                'document_upload' => Route::has('fleet.vendors.documents.upload') ? route('fleet.vendors.documents.upload') : null,
            ]),
            'trips' => [
                'sync' => route('fleet.trips.sync'),
                'show_template' => route('fleet.trips.show', ['code' => '__CODE__']),
            ],
            'contracts' => Route::has('fleet.contracts.sync') ? [
                'sync' => route('fleet.contracts.sync'),
                'show_template' => route('fleet.contracts.show', ['code' => '__CODE__']),
            ] : [],
            'drivers' => [
                'sync' => route('fleet.drivers.sync'),
                'show_template' => route('fleet.drivers.show', ['code' => '__CODE__']),
            ],
            'clients' => [
                'sync' => route('fleet.clients.sync'),
                'show_template' => route('fleet.clients.show', ['code' => '__CODE__']),
            ],
            'driver_attendance' => [
                'sync' => route('fleet.driver-attendance.sync'),
                'show_template' => route('fleet.driver-attendance.show', ['code' => '__CODE__']),
            ],
            'employees' => [
                'sync' => route('fleet.employees.sync'),
                'show_template' => route('fleet.employees.show', ['code' => '__CODE__']),
            ],
            'master_data' => Route::has('fleet.master-data.sync') ? ['sync' => route('fleet.master-data.sync')] : [],
            'uploads' => [
                'store' => route('fleet.uploads.store'),
                'preview_template' => route('fleet.uploads.preview', ['token' => '__TOKEN__']),
                'destroy_template' => route('fleet.uploads.destroy', ['token' => '__TOKEN__']),
                'file_template' => route('fleet.files.show', ['path' => '__PATH__']),
            ],
        ];
    }

    protected function optionsFromDatabase(): array
    {
        return [
            'vendors' => $this->uniqueValues($this->payloadColumn(FleetVendorParty::class, 'partyName')),
            'vehicle_vendors' => $this->driverVendorValues(),
            'driver_vendors' => $this->driverVendorValues(),
            'drivers' => $this->uniqueValues($this->payloadColumn(FleetDriver::class, 'fullName')),
            'vehicle_categories' => $this->vehicleCategoryOptions(),
            'usage_types' => $this->choiceValues('usage_type'),
            'fuel_types' => $this->fuelTypeValues(),
            'fuel_price_types' => $this->fuelTypeValues(),
            'fuel_units' => $this->fuelUnitValues(),
            'fuel_statuses' => $this->values('fuel_status'),
            'document_templates' => $this->documentNameValues('Vehicles', 'document_template'),
            'document_reminders' => $this->values('document_reminder'),
            'party_types' => $this->partyTypeValues(),
            'party_statuses' => $this->values('party_status'),
            'vendor_contractor_types' => $this->vendorContractorTypeValues(),
            'payment_terms' => $this->values('payment_term'),
            'payment_types' => $this->paymentTypeValues(),
            'party_document_templates' => $this->documentNameValues(['Vendors', 'Vendors & Parties'], 'party_document_template'),
            'trip_statuses' => $this->values('trip_status'),
            'trip_around' => $this->values('trip_around'),
            'trip_periods' => $this->values('trip_period'),
            'trip_purposes' => $this->values('trip_purpose'),
            'driver_license_types' => $this->driverLicenseTypeValues(),
            'driver_contact_types' => $this->driverContactTypeValues(),
            'driver_salary_tenures' => $this->values('driver_salary_tenure'),
            'rental_payment_cycles' => config('fleetman.options.rental_payment_cycles', ['Daily', 'Weekly', 'Monthly', 'Contract']),
            'driver_statuses' => $this->values('driver_status'),
            'driver_duty_types' => $this->choiceValues('driver_duty_type'),
            'driver_document_templates' => $this->documentNameValues('Drivers', 'driver_document_template'),
            'client_types' => $this->clientTypeValues(),
            'client_statuses' => $this->values('client_status'),
            'client_contact_methods' => $this->contactMethodValues(),
            'attendance_statuses' => $this->values('attendance_status'),
            'employee_statuses' => $this->values('employee_status'),
            'employee_salary_tenures' => $this->values('employee_salary_tenure'),
            'employee_designations' => $this->values('employee_designation'),
            'employee_document_templates' => $this->documentNameValues('Employees', 'employee_document_template'),
            'contract_document_templates' => $this->documentNameValues('Contracts', 'contract_document_template'),
        ];
    }

    protected function values(string $group): array
    {
        return FleetLookup::query()
            ->active()
            ->where('group', $group)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('value')
            ->values()
            ->all();
    }


    protected function paymentTypeValues(): array
    {
        if (! Schema::hasTable('fleet_payment_types')) {
            return config('fleetman.options.payment_types', []);
        }

        return FleetPaymentType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    protected function partyTypeValues(): array
    {
        if (! Schema::hasTable('fleet_party_types')) {
            return $this->values('party_type');
        }

        return FleetPartyType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    protected function driverContactTypeValues(): array
    {
        if (! Schema::hasTable('fleet_driver_contact_types')) {
            return config('fleetman.options.driver_contact_types', ['Personal', 'Home', 'Relative']);
        }

        $values = FleetDriverContactType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return count($values) > 0
            ? $values
            : config('fleetman.options.driver_contact_types', ['Personal', 'Home', 'Relative']);
    }

    protected function vendorContractorTypeValues(): array
    {
        if (! Schema::hasTable('fleet_vendor_contractor_types')) {
            return config('fleetman.options.vendor_contractor_types', ['Car Related', 'Non-Car Related']);
        }

        $values = FleetVendorContractorType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return count($values) > 0
            ? $values
            : config('fleetman.options.vendor_contractor_types', ['Car Related', 'Non-Car Related']);
    }

    protected function driverVendorValues(): array
    {
        if (! Schema::hasTable('fleet_vendor_parties')) {
            return [];
        }

        $carRelatedTypes = Schema::hasTable('fleet_vendor_contractor_types')
            ? FleetVendorContractorType::query()
                ->active()
                ->where('is_car_related', true)
                ->pluck('name')
                ->map(fn (string $name): string => strtolower(trim($name)))
                ->filter()
                ->values()
                ->all()
            : ['car related'];

        return FleetVendorParty::query()
            ->where('status', 'Active')
            ->get()
            ->map(fn (FleetVendorParty $party) => $party->payload ?? [])
            ->filter(function (array $party) use ($carRelatedTypes): bool {
                $partyType = strtolower(trim((string) ($party['partyType'] ?? '')));
                $vendorType = strtolower(trim((string) ($party['vendorContractorType'] ?? '')));

                return in_array($vendorType, $carRelatedTypes, true)
                    || in_array($partyType, ['transport vendor', 'driver supply vendor'], true);
            })
            ->map(fn (array $party): string => trim((string) ($party['partyName'] ?? '')))
            ->filter()
            ->unique(fn (string $name): string => strtolower($name))
            ->values()
            ->all();
    }

    protected function driverLicenseTypeValues(): array
    {
        if (! Schema::hasTable('fleet_licence_types')) {
            return $this->values('driver_license_type');
        }

        return \App\Models\Fleet\FleetLicenceType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    protected function clientTypeValues(): array
    {
        if (! Schema::hasTable('fleet_client_types')) {
            return $this->values('client_type');
        }

        return \App\Models\Fleet\FleetClientType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    protected function contactMethodValues(): array
    {
        if (! Schema::hasTable('fleet_contact_methods')) {
            return $this->values('client_contact_method');
        }

        return \App\Models\Fleet\FleetContactMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    protected function fuelTypeValues(): array
    {
        if (! Schema::hasTable('fleet_fuel_types') || ! Schema::hasColumn('fleet_fuel_types', 'name') || ! Schema::hasColumn('fleet_fuel_types', 'is_active')) {
            return $this->values('fuel_type');
        }

        $values = \App\Models\Fleet\FleetFuelType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return count($values) > 0 ? $values : $this->values('fuel_type');
    }

    protected function fuelUnitValues(): array
    {
        if (! Schema::hasTable('fleet_fuel_units') || ! Schema::hasColumn('fleet_fuel_units', 'name') || ! Schema::hasColumn('fleet_fuel_units', 'is_active')) {
            return $this->values('fuel_unit');
        }

        $values = \App\Models\Fleet\FleetFuelUnit::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return count($values) > 0 ? $values : $this->values('fuel_unit');
    }


    protected function latestActiveFuelRates(): array
    {
        if (! Schema::hasTable('fleet_fuel_prices')) {
            return [];
        }

        return FleetFuelPrice::query()
            ->where('status', 'Active')
            ->latest('id')
            ->get()
            ->map(fn (FleetFuelPrice $row) => $row->payload ?? [])
            ->filter(fn (array $row) => filled($row['fuelType'] ?? null) && filled($row['price'] ?? null))
            ->sortByDesc(fn (array $row) => (($row['effectiveDate'] ?? '') ?: '').'|'.($row['fuelPriceId'] ?? ''))
            ->unique(fn (array $row) => $row['fuelType'])
            ->mapWithKeys(fn (array $row) => [
                $row['fuelType'] => [
                    'price' => $row['price'],
                    'unit' => $row['unit'] ?? '',
                    'effectiveDate' => $row['effectiveDate'] ?? '',
                    'fuelPriceId' => $row['fuelPriceId'] ?? '',
                    'name' => $row['name'] ?? '',
                ],
            ])
            ->all();
    }

    protected function documentNameValues(string|array $module, string $fallbackGroup): array
    {
        if (Schema::hasTable('fleet_document_names')) {
            $query = FleetDocumentName::query()->active();

            if (Schema::hasColumn('fleet_document_names', 'document_type')) {
                $modules = array_values(array_filter(array_map('strval', (array) $module)));
                $query->where(function ($documentQuery) use ($modules) {
                    $documentQuery
                        ->whereIn('document_type', $modules)
                        ->orWhere('document_type', 'All Modules');
                });
            }

            $masterDocuments = $query
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();

            if (count($masterDocuments) > 0) {
                return $this->uniqueValues($masterDocuments);
            }
        }

        return $this->values($fallbackGroup);
    }

    protected function choiceValues(string $group): array
    {
        return FleetLookup::query()
            ->active()
            ->where('group', $group)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (FleetLookup $lookup) => array_merge([
                'value' => $lookup->value,
                'title' => $lookup->label,
                'description' => '',
            ], $lookup->meta ?? []))
            ->values()
            ->all();
    }

    protected function vehicleCategoryOptions(): array
    {
        if (Schema::hasTable('fleet_vehicle_categories') && Schema::hasTable('fleet_vehicle_sub_categories')) {
            $categories = FleetVehicleCategory::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            if ($categories->isNotEmpty()) {
                $subCategories = FleetVehicleSubCategory::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                    ->groupBy('vehicle_category_code');

                return $categories
                    ->mapWithKeys(fn (FleetVehicleCategory $category) => [
                        $category->name => ($subCategories[$category->code] ?? collect())
                            ->pluck('name')
                            ->values()
                            ->all(),
                    ])
                    ->all();
            }
        }

        return FleetLookup::query()
            ->active()
            ->where('group', 'vehicle_category')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->mapWithKeys(fn (FleetLookup $lookup) => [
                $lookup->value => $lookup->meta['sub_categories'] ?? [],
            ])
            ->all();
    }


    protected function payloadColumn(string $modelClass, string $key): array
    {
        return $modelClass::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Model $row) => $row->payload[$key] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    protected function uniqueValues(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->values()
            ->all();
    }

    protected function recordsFromDatabase(): array
    {
        return [
            'contracts' => $this->contractRecordsFromDatabase(),
            'vehicles' => $this->recordsFor(FleetVehicle::class),
            'fuel_prices' => $this->recordsFor(FleetFuelPrice::class),
            'fuel_recharges' => $this->recordsFor(FleetFuelRecharge::class),
            'parties' => $this->recordsFor(FleetVendorParty::class),
            'trips' => $this->recordsFor(FleetTrip::class),
            'drivers' => $this->recordsFor(FleetDriver::class),
            'clients' => $this->recordsFor(FleetClient::class),
            'driver_attendance' => $this->recordsFor(FleetDriverAttendance::class),
            'employees' => $this->recordsFor(FleetEmployee::class),
        ];
    }

    protected function recordsFor(string $modelClass): array
    {
        return $modelClass::query()
            ->latest('id')
            ->get()
            ->map(fn (Model $row) => $row->payload ?? [])
            ->values()
            ->all();
    }


    protected function contractRecordsFromDatabase(): array
    {
        if (! Schema::hasTable('fleet_contracts')) {
            return [];
        }

        return FleetContract::query()
            ->whereNotIn('status', ['fuel_recharge', 'attendance'])
            ->latest('id')
            ->get()
            ->map(fn (FleetContract $row) => $row->payload ?? [])
            ->values()
            ->all();
    }

    protected function contractMastersFromDatabase(): array
    {
        return [
            'parties' => [
                'Client' => $this->contractClientOptions(),
                'Vendor' => $this->contractVendorOptions(),
            ],
            'vehicles' => $this->contractVehicleOptions(),
            'drivers' => $this->contractDriverOptions(),
        ];
    }

    protected function contractClientOptions(): array
    {
        if (! Schema::hasTable('fleet_clients')) {
            return [];
        }

        return FleetClient::query()
            ->orderBy('name')
            ->orderBy('code')
            ->get()
            ->map(function (FleetClient $client): array {
                $payload = $client->payload ?? [];
                $id = (string) ($payload['clientId'] ?? $client->code);
                $name = (string) ($payload['clientName'] ?? $client->name ?? $client->code);
                $phone = (string) ($payload['phone'] ?? '');
                $status = (string) ($payload['status'] ?? $client->status ?? '');
                $type = (string) ($payload['clientType'] ?? 'Client');

                return [
                    'id' => $id,
                    'name' => $name,
                    'label' => trim($id.' - '.$name, ' -'),
                    'phone' => $phone,
                    'status' => $status,
                    'type' => $type,
                ];
            })
            ->values()
            ->all();
    }

    protected function contractVendorOptions(): array
    {
        if (! Schema::hasTable('fleet_vendor_parties')) {
            return [];
        }

        return FleetVendorParty::query()
            ->orderBy('name')
            ->orderBy('code')
            ->get()
            ->map(function (FleetVendorParty $party): array {
                $payload = $party->payload ?? [];
                $id = (string) ($payload['partyId'] ?? $party->code);
                $name = (string) ($payload['partyName'] ?? $party->name ?? $party->code);
                $phone = (string) ($payload['phone'] ?? $payload['mobile'] ?? '');
                $status = (string) ($payload['status'] ?? $party->status ?? '');
                $type = (string) ($payload['partyType'] ?? 'Vendor');

                return [
                    'id' => $id,
                    'name' => $name,
                    'label' => trim($id.' - '.$name, ' -'),
                    'phone' => $phone,
                    'status' => $status,
                    'type' => $type,
                ];
            })
            ->values()
            ->all();
    }

    protected function contractVehicleOptions(): array
    {
        if (! Schema::hasTable('fleet_vehicles')) {
            return [];
        }

        $latestOdoByVehicle = $this->latestFuelRechargeOdoByVehicle();

        return FleetVehicle::query()
            ->orderBy('name')
            ->orderBy('code')
            ->get()
            ->map(function (FleetVehicle $vehicle): array {
                $payload = $vehicle->payload ?? [];
                $id = (string) ($payload['id'] ?? $vehicle->code);
                $name = (string) ($payload['name'] ?? $vehicle->name ?? $vehicle->code);
                $type = (string) ($payload['subCategory'] ?? $payload['category'] ?? $payload['model'] ?? 'Vehicle');
                $regNo = (string) ($payload['regNo'] ?? '');
                $status = (string) ($payload['status'] ?? $vehicle->status ?? '');

                return [
                    'id' => $id,
                    'name' => $name,
                    'label' => trim($id.' - '.$name, ' -'),
                    'type' => $type,
                    'regNo' => $regNo,
                    'status' => $status,
                    'note' => collect([$regNo, $status ? 'Status: '.$status : null])->filter()->join(' • '),
                ];
            })
            ->values()
            ->all();
    }

    protected function contractDriverOptions(): array
    {
        if (! Schema::hasTable('fleet_drivers')) {
            return [];
        }

        return FleetDriver::query()
            ->orderBy('name')
            ->orderBy('code')
            ->get()
            ->map(function (FleetDriver $driver): array {
                $payload = $driver->payload ?? [];
                $id = (string) ($payload['driverId'] ?? $driver->code);
                $name = (string) ($payload['fullName'] ?? $driver->name ?? $driver->code);
                $phone = (string) ($payload['contact'] ?? $payload['phone'] ?? $payload['mobile'] ?? '');
                $status = (string) ($payload['status'] ?? $driver->status ?? '');
                $area = (string) ($payload['presentAddress'] ?? $payload['area'] ?? 'Driver');

                return [
                    'id' => $id,
                    'name' => $name,
                    'label' => trim($id.' - '.$name, ' -'),
                    'phone' => $phone,
                    'status' => $status,
                    'area' => $area,
                    'note' => collect([$phone, $status ? 'Status: '.$status : null])->filter()->join(' • '),
                ];
            })
            ->values()
            ->all();
    }

    protected function fuelRechargeContracts(): array
    {
        if (! Schema::hasTable('fleet_contracts')) {
            return [];
        }

        $vehicleMap = $this->fuelRechargeVehicleMap();
        $latestRates = $this->latestActiveFuelRates();

        $contracts = FleetContract::query()
            ->whereNotIn('status', ['fuel_recharge', 'attendance'])
            ->latest('id')
            ->get()
            ->map(function (FleetContract $contract) use ($vehicleMap, $latestRates): array {
                $payload = $contract->payload ?? [];
                $contractId = (string) ($payload['contractId'] ?? $payload['id'] ?? $contract->code);
                $partyName = (string) ($payload['partyName'] ?? $payload['party'] ?? $contract->name ?? 'Contract Party');
                $contractWith = (string) ($payload['contractWith'] ?? 'Client');
                $assignments = collect($payload['assignments'] ?? []);
                $vehicles = $assignments
                    ->map(fn (array $assignment) => $this->fuelRechargeVehicleOption($assignment, $vehicleMap, $latestRates))
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->all();

                return [
                    'id' => $contractId,
                    'contractId' => $contractId,
                    'label' => trim($contractId.' | '.$partyName, ' |'),
                    'contractWith' => $contractWith,
                    'partyName' => $partyName,
                    'status' => (string) ($payload['status'] ?? $contract->status ?? ''),
                    'savedAs' => (string) ($payload['savedAs'] ?? $contract->status ?? ''),
                    'amount' => $payload['amount'] ?? null,
                    'startDate' => $payload['contractStart'] ?? null,
                    'endDate' => $payload['contractEnd'] ?? null,
                    'vehicles' => $vehicles,
                    'assignments' => $assignments->values()->all(),
                ];
            })
            ->values()
            ->all();

        if (count($contracts) > 0) {
            return $contracts;
        }

        return FleetContract::query()
            ->where('status', 'fuel_recharge')
            ->orderBy('id')
            ->get()
            ->map(function (FleetContract $contract) use ($latestRates): array {
                $payload = $contract->payload ?? [];
                $payload['vehicles'] = collect($payload['vehicles'] ?? [])
                    ->map(function (array $vehicle) use ($latestRates): array {
                        $primary = (string) ($vehicle['primary'] ?? '');
                        $secondary = (string) ($vehicle['secondary'] ?? '');
                        $primaryRate = $this->fuelRateFromLatestPrices($primary, $latestRates);
                        $secondaryRate = $this->fuelRateFromLatestPrices($secondary, $latestRates);

                        return array_merge($vehicle, [
                            'label' => $vehicle['label'] ?? $vehicle['name'] ?? $vehicle['id'] ?? '',
                            'primaryRate' => $primaryRate['price'] ?? 0,
                            'primaryUnit' => $primaryRate['unit'] ?? '',
                            'primaryRateInfo' => $primaryRate,
                            'secondaryRate' => $secondaryRate['price'] ?? 0,
                            'secondaryUnit' => $secondaryRate['unit'] ?? '',
                            'secondaryRateInfo' => $secondaryRate,
                            'secondaryAvailable' => filled($secondary),
                        ]);
                    })
                    ->values()
                    ->all();

                return $payload;
            })
            ->values()
            ->all();
    }

    protected function fuelRechargeVehicleMap(): array
    {
        if (! Schema::hasTable('fleet_vehicles')) {
            return [];
        }

        $latestOdoByVehicle = $this->latestFuelRechargeOdoByVehicle();

        return FleetVehicle::query()
            ->orderBy('name')
            ->orderBy('code')
            ->get()
            ->flatMap(function (FleetVehicle $vehicle) use ($latestOdoByVehicle): array {
                $payload = $vehicle->payload ?? [];
                $id = (string) ($payload['id'] ?? $vehicle->code);
                $name = (string) ($payload['name'] ?? $vehicle->name ?? $vehicle->code);
                $label = trim($id.' - '.$name, ' -');
                $latestOdo = $latestOdoByVehicle[$id]
                    ?? $latestOdoByVehicle[$vehicle->code]
                    ?? $latestOdoByVehicle[$name]
                    ?? $latestOdoByVehicle[$label]
                    ?? null;
                $baseOdo = $payload['odo'] ?? $payload['currentOdo'] ?? $payload['lastOdo'] ?? $payload['odoReading'] ?? '';

                $record = array_merge($payload, [
                    'id' => $id,
                    'name' => $name,
                    'label' => $label,
                    'regNo' => (string) ($payload['regNo'] ?? ''),
                    'status' => (string) ($payload['status'] ?? $vehicle->status ?? ''),
                    'driver' => (string) ($payload['driver'] ?? ''),
                    'odo' => $latestOdo ?? $baseOdo,
                    'startKm' => $latestOdo ?? $baseOdo,
                    'lastOdo' => $latestOdo ?? $baseOdo,
                ]);

                return collect([$id, $vehicle->code, $name, $label, $payload['regNo'] ?? null])
                    ->filter(fn ($key) => filled($key))
                    ->mapWithKeys(fn ($key) => [(string) $key => $record])
                    ->all();
            })
            ->all();
    }

    protected function fuelRechargeVehicleOption(array $assignment, array $vehicleMap, array $latestRates): ?array
    {
        $vehicleKey = (string) ($assignment['vehicleId'] ?? $assignment['vehicle'] ?? $assignment['vehicleName'] ?? '');
        if ($vehicleKey === '') {
            return null;
        }

        $vehicle = $vehicleMap[$vehicleKey] ?? null;
        if (! $vehicle) {
            $vehicleName = (string) ($assignment['vehicleName'] ?? $assignment['vehicle'] ?? $vehicleKey);
            $vehicle = [
                'id' => $vehicleKey,
                'name' => $vehicleName,
                'label' => $vehicleName,
                'fuels' => [],
                'regNo' => '',
                'status' => '',
            ];
        }

        $fuels = collect($vehicle['fuels'] ?? [])->filter(fn ($fuel) => is_array($fuel))->values();
        $primaryFuel = $fuels->first(fn (array $fuel) => strcasecmp((string) ($fuel['priority'] ?? ''), 'Primary') === 0) ?? $fuels->first();
        $secondaryFuel = $fuels->first(fn (array $fuel) => strcasecmp((string) ($fuel['priority'] ?? ''), 'Secondary') === 0)
            ?? $fuels->skip(1)->first();
        $primaryName = (string) ($primaryFuel['type'] ?? $vehicle['primaryFuel'] ?? $vehicle['fuelType'] ?? '');
        $secondaryName = (string) ($secondaryFuel['type'] ?? $vehicle['secondaryFuel'] ?? '');
        $primaryRate = $this->fuelRateFromLatestPrices($primaryName, $latestRates);
        $secondaryRate = $this->fuelRateFromLatestPrices($secondaryName, $latestRates);
        $driverId = (string) ($assignment['driverId'] ?? '');
        $driver = (string) ($assignment['driverName'] ?? $assignment['driver'] ?? $vehicle['driver'] ?? '');

        return [
            'id' => (string) ($vehicle['id'] ?? $vehicleKey),
            'name' => (string) ($vehicle['name'] ?? $vehicleKey),
            'label' => (string) ($vehicle['label'] ?? trim(($vehicle['id'] ?? $vehicleKey).' - '.($vehicle['name'] ?? $vehicleKey), ' -')),
            'regNo' => (string) ($vehicle['regNo'] ?? ''),
            'status' => (string) ($vehicle['status'] ?? ''),
            'odo' => $vehicle['odo'] ?? $vehicle['startKm'] ?? $vehicle['lastOdo'] ?? '',
            'startKm' => $vehicle['startKm'] ?? $vehicle['odo'] ?? $vehicle['lastOdo'] ?? '',
            'lastOdo' => $vehicle['lastOdo'] ?? $vehicle['odo'] ?? $vehicle['startKm'] ?? '',
            'driverId' => $driverId,
            'driver' => $driver,
            'primary' => $primaryName,
            'primaryRate' => $primaryRate['price'] ?? 0,
            'primaryUnit' => $primaryRate['unit'] ?? '',
            'primaryRateInfo' => $primaryRate,
            'secondary' => $secondaryName,
            'secondaryRate' => $secondaryRate['price'] ?? 0,
            'secondaryUnit' => $secondaryRate['unit'] ?? '',
            'secondaryRateInfo' => $secondaryRate,
            'secondaryAvailable' => filled($secondaryName),
        ];
    }

    protected function fuelRateFromLatestPrices(?string $fuelName, array $latestRates): ?array
    {
        $fuelName = trim((string) $fuelName);
        if ($fuelName === '') {
            return null;
        }

        if (isset($latestRates[$fuelName])) {
            return $latestRates[$fuelName];
        }

        $needle = $this->normalizedFuelName($fuelName);
        foreach ($latestRates as $name => $rate) {
            $candidate = $this->normalizedFuelName((string) $name);
            if ($candidate !== '' && ($candidate === $needle || str_contains($candidate, $needle) || str_contains($needle, $candidate))) {
                return $rate;
            }
        }

        return null;
    }

    protected function normalizedFuelName(string $name): string
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', $name) ?: '');

        if ($normalized === '') {
            return '';
        }

        if (str_contains($normalized, 'cng') || str_contains($normalized, 'compressednaturalgas') || $normalized === 'gas' || str_contains($normalized, 'naturalgas')) {
            return 'cng';
        }
        if (str_contains($normalized, 'lpg') || str_contains($normalized, 'liquefiedpetroleumgas')) {
            return 'lpg';
        }
        if (str_contains($normalized, 'diesel')) {
            return 'diesel';
        }
        if (str_contains($normalized, 'octane') || str_contains($normalized, 'octen') || str_contains($normalized, 'petrol') || str_contains($normalized, 'gasoline')) {
            return 'octane';
        }

        return str_replace(['petroloctane', 'octanepetrol'], 'octane', $normalized);
    }

    protected function tripMastersFromDatabase(): array
    {
        $vehicles = Schema::hasTable('fleet_vehicles')
            ? FleetVehicle::query()
                ->orderBy('name')
                ->orderBy('code')
                ->get()
                ->map(function (FleetVehicle $vehicle): array {
                    $payload = $vehicle->payload ?? [];
                    $id = (string) ($payload['id'] ?? $vehicle->code);
                    $name = (string) ($payload['name'] ?? $vehicle->name ?? $vehicle->code);
                    $type = (string) ($payload['subCategory'] ?? $payload['category'] ?? $payload['model'] ?? 'Vehicle');
                    $status = (string) ($payload['status'] ?? $vehicle->status ?? '');
                    $regNo = (string) ($payload['regNo'] ?? '');
                    $model = (string) ($payload['model'] ?? '');
                    $note = collect([$regNo, $model, $status ? 'Status: '.$status : null])
                        ->filter(fn ($value) => filled($value))
                        ->join(' • ');

                    return [
                        'id' => $id,
                        'name' => $name,
                        'label' => trim($id.' - '.$name, ' -'),
                        'type' => $type !== '' ? $type : 'Vehicle',
                        'note' => $note !== '' ? $note : 'From vehicle table',
                        'status' => $status,
                        'regNo' => $regNo,
                        'model' => $model,
                    ];
                })
                ->values()
                ->all()
            : [];

        $drivers = Schema::hasTable('fleet_drivers')
            ? FleetDriver::query()
                ->orderBy('name')
                ->orderBy('code')
                ->get()
                ->map(function (FleetDriver $driver): array {
                    $payload = $driver->payload ?? [];
                    $id = (string) ($payload['driverId'] ?? $driver->code);
                    $name = (string) ($payload['fullName'] ?? $driver->name ?? $driver->code);
                    $phone = (string) ($payload['contact'] ?? $payload['phone'] ?? $payload['mobile'] ?? '');
                    $area = (string) ($payload['presentAddress'] ?? $payload['area'] ?? $payload['duty'] ?? 'Driver');
                    $status = (string) ($payload['status'] ?? $driver->status ?? '');
                    $duty = (string) ($payload['duty'] ?? '');
                    $note = collect([$phone, $duty, $status ? 'Status: '.$status : null])
                        ->filter(fn ($value) => filled($value))
                        ->join(' • ');

                    return [
                        'id' => $id,
                        'name' => $name,
                        'label' => trim($id.' - '.$name, ' -'),
                        'phone' => $phone,
                        'area' => $area !== '' ? $area : 'Driver',
                        'duty' => $duty,
                        'status' => $status,
                        'note' => $note !== '' ? $note : 'From driver table',
                    ];
                })
                ->values()
                ->all()
            : [];

        $clients = Schema::hasTable('fleet_clients')
            ? FleetClient::query()
                ->orderBy('name')
                ->orderBy('code')
                ->get()
                ->map(function (FleetClient $client): array {
                    $payload = $client->payload ?? [];
                    $id = (string) ($payload['clientId'] ?? $client->code);
                    $name = (string) ($payload['clientName'] ?? $client->name ?? $client->code);
                    $phone = (string) ($payload['phone'] ?? '');
                    $email = (string) ($payload['email'] ?? '');
                    $status = (string) ($payload['status'] ?? $client->status ?? '');
                    $note = collect([$phone, $email, $status ? 'Status: '.$status : null])
                        ->filter(fn ($value) => filled($value))
                        ->join(' • ');

                    return [
                        'id' => $id,
                        'name' => $name,
                        'label' => trim($id.' - '.$name, ' -'),
                        'phone' => $phone,
                        'email' => $email,
                        'status' => $status,
                        'note' => $note !== '' ? $note : 'From client table',
                    ];
                })
                ->values()
                ->all()
            : [];

        return [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'clients' => $clients,
            'vehicle_types' => collect($vehicles)->pluck('type')->filter()->unique()->values()->all(),
            'driver_areas' => collect($drivers)->pluck('area')->filter()->unique()->values()->all(),
        ];
    }

    protected function attendanceMastersFromDatabase(): array
    {
        if (! Schema::hasTable('fleet_contracts')) {
            return [
                'contracts' => [],
                'vehicle_driver_map' => [],
                'drivers' => [],
            ];
        }

        $vehicleOptions = Schema::hasTable('fleet_vehicles')
            ? FleetVehicle::query()
                ->orderBy('name')
                ->orderBy('code')
                ->get()
                ->flatMap(function (FleetVehicle $vehicle): array {
                    $payload = $vehicle->payload ?? [];
                    $id = (string) ($payload['id'] ?? $vehicle->code);
                    $name = (string) ($payload['name'] ?? $vehicle->name ?? $vehicle->code);
                    $label = trim($id.' - '.$name, ' -');
                    $option = [
                        'id' => $id,
                        'name' => $name,
                        'label' => $label,
                        'regNo' => (string) ($payload['regNo'] ?? ''),
                        'status' => (string) ($payload['status'] ?? $vehicle->status ?? ''),
                    ];

                    return collect([$id, $vehicle->code, $name, $label, $payload['regNo'] ?? null])
                        ->filter(fn ($key) => filled($key))
                        ->mapWithKeys(fn ($key) => [(string) $key => $option])
                        ->all();
                })
                ->all()
            : [];

        $driverOptions = Schema::hasTable('fleet_drivers')
            ? FleetDriver::query()
                ->orderBy('name')
                ->orderBy('code')
                ->get()
                ->flatMap(function (FleetDriver $driver): array {
                    $payload = $driver->payload ?? [];
                    $id = (string) ($payload['driverId'] ?? $driver->code);
                    $name = (string) ($payload['fullName'] ?? $driver->name ?? $driver->code);
                    $label = trim($id.' - '.$name, ' -');
                    $option = [
                        'id' => $id,
                        'name' => $name,
                        'label' => $label,
                        'phone' => (string) ($payload['contact'] ?? $payload['phone'] ?? $payload['mobile'] ?? ''),
                        'status' => (string) ($payload['status'] ?? $driver->status ?? ''),
                    ];

                    return collect([$id, $driver->code, $name, $label, $payload['contact'] ?? null, $payload['phone'] ?? null, $payload['mobile'] ?? null])
                        ->filter(fn ($key) => filled($key))
                        ->mapWithKeys(fn ($key) => [(string) $key => $option])
                        ->all();
                })
                ->all()
            : [];

        $vehicleDriverMap = [];

        $contracts = FleetContract::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['fuel_recharge', 'attendance']);
            })
            ->latest('id')
            ->get()
            ->map(function (FleetContract $contract) use ($vehicleOptions, $driverOptions, &$vehicleDriverMap): ?array {
                $payload = $contract->payload ?? [];
                $contractId = (string) ($payload['contractId'] ?? $payload['id'] ?? $contract->code);
                $partyName = (string) ($payload['partyName'] ?? $payload['party'] ?? $contract->name ?? 'Contract Party');

                $assignments = collect($payload['assignments'] ?? [])
                    ->filter(fn ($assignment) => is_array($assignment))
                    ->map(function (array $assignment) use ($vehicleOptions, $driverOptions): ?array {
                        $vehicleId = (string) ($assignment['vehicleId'] ?? '');
                        $vehicleText = (string) ($assignment['vehicle'] ?? $assignment['vehicleName'] ?? $vehicleId);
                        $vehicle = $vehicleOptions[$vehicleId] ?? $vehicleOptions[$vehicleText] ?? null;

                        if (! $vehicle && filled($vehicleText)) {
                            $vehicle = [
                                'id' => $vehicleId !== '' ? $vehicleId : $vehicleText,
                                'name' => (string) ($assignment['vehicleName'] ?? $vehicleText),
                                'label' => $vehicleText,
                                'regNo' => '',
                                'status' => '',
                            ];
                        }

                        if (! $vehicle) {
                            return null;
                        }

                        $driverId = (string) ($assignment['driverId'] ?? '');
                        $driverText = (string) ($assignment['driver'] ?? $assignment['driverName'] ?? $driverId);
                        $driver = $driverOptions[$driverId] ?? $driverOptions[$driverText] ?? null;

                        if (! $driver && filled($driverText)) {
                            $driver = [
                                'id' => $driverId !== '' ? $driverId : $driverText,
                                'name' => (string) ($assignment['driverName'] ?? $driverText),
                                'label' => $driverText,
                                'phone' => '',
                                'status' => '',
                            ];
                        }

                        return [
                            'vehicleId' => (string) ($vehicle['id'] ?? $vehicleId),
                            'vehicle' => (string) ($vehicle['label'] ?? $vehicleText),
                            'vehicleName' => (string) ($vehicle['name'] ?? $vehicleText),
                            'vehicleLabel' => (string) ($vehicle['label'] ?? $vehicleText),
                            'driverId' => (string) ($driver['id'] ?? $driverId),
                            'driver' => (string) ($driver['label'] ?? $driverText),
                            'driverName' => (string) ($driver['name'] ?? $driverText),
                            'driverLabel' => (string) ($driver['label'] ?? $driverText),
                            'rate' => $assignment['rate'] ?? null,
                            'duty' => $assignment['duty'] ?? null,
                        ];
                    })
                    ->filter()
                    ->values();

                $assignments->each(function (array $assignment) use (&$vehicleDriverMap): void {
                    if (filled($assignment['vehicleLabel']) && filled($assignment['driverLabel'])) {
                        $vehicleDriverMap[$assignment['vehicleLabel']] = $assignment['driverLabel'];
                    }
                });

                if ($assignments->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $contractId,
                    'contractId' => $contractId,
                    'name' => $partyName,
                    'partyName' => $partyName,
                    'label' => trim($contractId.' - '.$partyName, ' -'),
                    'contractWith' => (string) ($payload['contractWith'] ?? ''),
                    'status' => (string) ($payload['status'] ?? $contract->status ?? ''),
                    'savedAs' => (string) ($payload['savedAs'] ?? $contract->status ?? ''),
                    'vehicles' => $assignments->pluck('vehicleLabel')->filter()->unique()->values()->all(),
                    'drivers' => $assignments->pluck('driverLabel')->filter()->unique()->values()->all(),
                    'assignments' => $assignments->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'contracts' => $contracts,
            'vehicle_driver_map' => $vehicleDriverMap,
            'drivers' => collect($contracts)
                ->flatMap(fn (array $contract) => $contract['drivers'] ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }


    protected function fuelStationOptions(): array
    {
        if (! Schema::hasTable('fleet_vendor_parties')) {
            return [];
        }

        $knownFuelTypes = collect($this->fuelTypeValues())
            ->filter(fn ($fuelType) => filled($fuelType))
            ->values();

        return FleetVendorParty::query()
            ->orderBy('name')
            ->orderBy('code')
            ->get()
            ->map(function (FleetVendorParty $party) use ($knownFuelTypes): ?array {
                $payload = $party->payload ?? [];
                $name = trim((string) ($payload['partyName'] ?? $payload['name'] ?? $party->name ?? ''));
                $type = strtolower((string) ($payload['partyType'] ?? $payload['type'] ?? ''));
                $business = strtolower((string) ($payload['businessType'] ?? $payload['category'] ?? ''));
                $status = strtolower((string) ($payload['status'] ?? $party->status ?? ''));
                $about = strtolower((string) ($payload['about'] ?? $payload['description'] ?? ''));
                $combined = strtolower($name.' '.$type.' '.$business.' '.$about);

                if ($name === '' || in_array($status, ['inactive', 'blacklisted', 'draft'], true)) {
                    return null;
                }

                $isFuelStation = str_contains($combined, 'fuel')
                    || str_contains($combined, 'station')
                    || str_contains($combined, 'petrol')
                    || str_contains($combined, 'octane')
                    || str_contains($combined, 'octen')
                    || str_contains($combined, 'diesel')
                    || str_contains($combined, 'cng')
                    || str_contains($combined, 'lpg')
                    || str_contains($combined, 'gas');

                if (! $isFuelStation) {
                    return null;
                }

                $configuredFuelTypes = collect(
                    $payload['fuelTypes']
                    ?? $payload['supportedFuelTypes']
                    ?? $payload['fuelsSold']
                    ?? []
                )
                    ->map(function ($fuel): string {
                        if (is_array($fuel)) {
                            return trim((string) ($fuel['type'] ?? $fuel['name'] ?? ''));
                        }

                        return trim((string) $fuel);
                    })
                    ->filter()
                    ->values();

                // Backward compatibility for old station records: infer only
                // when the station name/details explicitly mention a fuel.
                if ($configuredFuelTypes->isEmpty()) {
                    $configuredFuelTypes = $knownFuelTypes
                        ->filter(fn (string $fuelType): bool => $this->fuelTextMentionsType($combined, $fuelType))
                        ->values();
                }

                return [
                    'id' => (string) ($payload['partyId'] ?? $party->code),
                    'name' => $name,
                    'fuelTypes' => $configuredFuelTypes->unique()->values()->all(),
                    'status' => (string) ($payload['status'] ?? $party->status ?? ''),
                    'configured' => $configuredFuelTypes->isNotEmpty(),
                ];
            })
            ->filter()
            ->unique('name')
            ->values()
            ->all();
    }

    protected function fuelTextMentionsType(string $text, string $fuelType): bool
    {
        $fuel = $this->normalizedFuelName($fuelType);
        $normalizedText = strtolower(preg_replace('/[^a-z0-9]+/i', '', $text) ?: '');

        return match (true) {
            $fuel === 'diesel' => str_contains($normalizedText, 'diesel'),
            $fuel === 'octane' => str_contains($normalizedText, 'octane')
                || str_contains($normalizedText, 'octen')
                || str_contains($normalizedText, 'petrol')
                || str_contains($normalizedText, 'gasoline'),
            $fuel === 'cng' => str_contains($normalizedText, 'cng')
                || str_contains($normalizedText, 'compressednaturalgas'),
            $fuel === 'lpg' => str_contains($normalizedText, 'lpg')
                || str_contains($normalizedText, 'liquefiedpetroleumgas'),
            str_contains($fuel, 'electric') => str_contains($normalizedText, 'electric')
                || str_contains($normalizedText, 'evcharging'),
            default => $fuel !== '' && str_contains($normalizedText, $fuel),
        };
    }

    protected function latestFuelRechargeOdoByVehicle(): array
    {
        if (! Schema::hasTable('fleet_fuel_recharges')) {
            return [];
        }

        $latest = [];

        FleetFuelRecharge::query()
            ->latest('id')
            ->get()
            ->each(function (FleetFuelRecharge $recharge) use (&$latest): void {
                $payload = $recharge->payload ?? [];
                $vehicleKeys = collect([
                    $payload['vehicleId'] ?? null,
                    $payload['vehicle'] ?? null,
                    $payload['vehicleLabel'] ?? null,
                    $payload['car'] ?? null,
                ])->filter(fn ($key) => filled($key))->map(fn ($key) => (string) $key)->unique();

                $odo = $payload['endKm'] ?? $payload['odoReading'] ?? null;
                if (! is_numeric($odo)) {
                    return;
                }

                foreach ($vehicleKeys as $key) {
                    if (! array_key_exists($key, $latest)) {
                        $latest[$key] = (float) $odo;
                    }
                }
            });

        return $latest;
    }

    protected function photoRequirements(): array
    {
        // These are static UI requirements, not dropdown/master data.
        return config('fleetman.photo_requirements', []);
    }
}
