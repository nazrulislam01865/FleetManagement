<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetClientType;
use App\Models\Fleet\FleetContactMethod;
use App\Models\Fleet\FleetDocumentName;
use App\Models\Fleet\FleetDriverContactType;
use App\Models\Fleet\FleetLicenceType;
use App\Models\Fleet\FleetPartyType;
use App\Models\Fleet\FleetVehicleCategory;
use App\Models\Fleet\FleetVehicleSubCategory;
use App\Models\Fleet\FleetFuelType;
use App\Models\Fleet\FleetFuelUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterDataController extends FleetBaseController
{
    public function index(): View
    {
        return $this->partyTypes();
    }

    public function vehicleCategories(): View
    {
        return view('fleetman.master-data.vehicle-categories', $this->masterViewData('master-data-vehicle-categories', [
            'page' => 'master-data',
            'masterSection' => 'vehicle_categories',
            'masterTitle' => 'Vehicle Category Master',
            'masterSubtitle' => 'Manage vehicle categories for dropdowns across the application.',
        ]));
    }

    public function vehicleSubCategories(): View
    {
        return view('fleetman.master-data.vehicle-sub-categories', $this->masterViewData('master-data-vehicle-sub-categories', [
            'page' => 'master-data',
            'masterSection' => 'vehicle_sub_categories',
            'masterTitle' => 'Vehicle Sub Category Master',
            'masterSubtitle' => 'Manage vehicle sub categories and map each one to a vehicle category.',
        ]));
    }

    public function partyTypes(): View
    {
        return view('fleetman.master-data.party-types', $this->masterViewData('master-data-party-types', [
            'page' => 'master-data',
            'masterSection' => 'party_types',
            'masterTitle' => 'Party Type Master',
            'masterSubtitle' => 'Add party types once and use them in vendor / party related dropdowns across the app.',
        ]));
    }

    public function documentNames(): View
    {
        return view('fleetman.master-data.document-names', $this->masterViewData('master-data-document-names', [
            'page' => 'master-data',
            'masterSection' => 'document_names',
            'masterTitle' => 'Document Name Master',
            'masterSubtitle' => 'Add document names once and reuse them in document dropdowns across FleetMan forms.',
        ]));
    }

    public function licenceTypes(): View
    {
        return view('fleetman.master-data.licence-types', $this->masterViewData('master-data-licence-types', [
            'page' => 'master-data',
            'masterSection' => 'licence_types',
            'masterTitle' => 'Licence Type Master',
            'masterSubtitle' => 'Manage driver licence types for dropdowns across the application.',
        ]));
    }

    public function driverContactTypes(): View
    {
        return view('fleetman.master-data.driver-contact-types', $this->masterViewData('master-data-driver-contact-types', [
            'page' => 'master-data',
            'masterSection' => 'driver_contact_types',
            'masterTitle' => 'Driver Contact Type Master',
            'masterSubtitle' => 'Manage the contact-number types available on the Driver page.',
        ]));
    }

    public function clientTypes(): View
    {
        return view('fleetman.master-data.client-types', $this->masterViewData('master-data-client-types', [
            'page' => 'master-data',
            'masterSection' => 'client_types',
            'masterTitle' => 'Client Type Master',
            'masterSubtitle' => 'Manage client types for dropdowns across the application.',
        ]));
    }

    public function contactMethods(): View
    {
        return view('fleetman.master-data.contact-methods', $this->masterViewData('master-data-contact-methods', [
            'page' => 'master-data',
            'masterSection' => 'contact_methods',
            'masterTitle' => 'Contact Method Master',
            'masterSubtitle' => 'Manage preferred contact methods for dropdowns across the application.',
        ]));
    }

    public function fuelTypes(): View
    {
        return view('fleetman.master-data.fuel-types', $this->masterViewData('master-data-fuel-types', [
            'page' => 'master-data',
            'masterSection' => 'fuel_types',
            'masterTitle' => 'Fuel Type Master',
            'masterSubtitle' => 'Manage fuel types for dropdowns across the application.',
        ]));
    }

    public function fuelUnits(): View
    {
        return view('fleetman.master-data.fuel-units', $this->masterViewData('master-data-fuel-units', [
            'page' => 'master-data',
            'masterSection' => 'fuel_units',
            'masterTitle' => 'Fuel Unit Master',
            'masterSubtitle' => 'Manage fuel units for dropdowns across the application.',
        ]));
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_categories' => ['present', 'array'],
            'vehicle_categories.*' => ['array'],
            'vehicle_sub_categories' => ['present', 'array'],
            'vehicle_sub_categories.*' => ['array'],
            'party_types' => ['present', 'array'],
            'party_types.*' => ['array'],
            'document_names' => ['present', 'array'],
            'document_names.*' => ['array'],
            'licence_types' => ['present', 'array'],
            'licence_types.*' => ['array'],
            'driver_contact_types' => ['present', 'array'],
            'driver_contact_types.*' => ['array'],
            'client_types' => ['present', 'array'],
            'client_types.*' => ['array'],
            'contact_methods' => ['present', 'array'],
            'contact_methods.*' => ['array'],
            'fuel_types' => ['present', 'array'],
            'fuel_types.*' => ['array'],
            'fuel_units' => ['present', 'array'],
            'fuel_units.*' => ['array'],
        ]);

        DB::transaction(function () use ($validated) {
            $this->syncMasterTable(FleetVehicleCategory::class, $validated['vehicle_categories']);
            $this->syncVehicleSubCategories($validated['vehicle_sub_categories']);
            $this->syncMasterTable(FleetPartyType::class, $validated['party_types']);
            $this->syncDocumentNames($validated['document_names']);
            $this->syncMasterTable(FleetLicenceType::class, $validated['licence_types']);
            $this->syncMasterTable(FleetDriverContactType::class, $validated['driver_contact_types']);
            $this->syncMasterTable(FleetClientType::class, $validated['client_types']);
            $this->syncMasterTable(FleetContactMethod::class, $validated['contact_methods']);
            $this->syncMasterTable(FleetFuelType::class, $validated['fuel_types']);
            $this->syncMasterTable(FleetFuelUnit::class, $validated['fuel_units']);
        });

        return response()->json([
            'ok' => true,
            'masterData' => $this->masterDataPayload(),
            'options' => $this->optionsFromDatabase(),
        ]);
    }

    private function masterViewData(string $activeMenu, array $pageData): array
    {
        return $this->shared($activeMenu, array_merge([
            'masterData' => $this->masterDataPayload(),
            'resources' => array_merge($this->resourceUrls(), [
                'master_data' => ['sync' => route('fleet.master-data.sync')],
            ]),
        ], $pageData));
    }

    private function masterDataPayload(): array
    {
        return [
            'vehicle_categories' => $this->masterRows(FleetVehicleCategory::class),
            'vehicle_sub_categories' => $this->vehicleSubCategoryRows(),
            'party_types' => $this->masterRows(FleetPartyType::class),
            'document_names' => $this->masterRows(FleetDocumentName::class),
            'licence_types' => $this->masterRows(FleetLicenceType::class),
            'driver_contact_types' => $this->masterRows(FleetDriverContactType::class),
            'client_types' => $this->masterRows(FleetClientType::class),
            'contact_methods' => $this->masterRows(FleetContactMethod::class),
            'fuel_types' => $this->masterRows(FleetFuelType::class),
            'fuel_units' => $this->masterRows(FleetFuelUnit::class),
        ];
    }

    private function masterRows(string $modelClass): array
    {
        return $modelClass::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Model $row) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'label' => $row->name,
                'description' => $row->description ?? '',
                'documentType' => $row->getAttribute('document_type') ?: null,
                'sortOrder' => (int) $row->sort_order,
                'status' => $row->is_active ? 'Active' : 'Inactive',
                'createdAt' => optional($row->created_at)->toDateTimeString(),
                'updatedAt' => optional($row->updated_at)->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    private function vehicleSubCategoryRows(): array
    {
        $categoryNames = FleetVehicleCategory::query()
            ->pluck('name', 'code')
            ->all();

        return FleetVehicleSubCategory::query()
            ->orderBy('vehicle_category_code')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FleetVehicleSubCategory $row) => [
                'id' => $row->id,
                'code' => $row->code,
                'vehicleCategoryCode' => $row->vehicle_category_code,
                'vehicleCategoryName' => $categoryNames[$row->vehicle_category_code] ?? $row->vehicle_category_code,
                'name' => $row->name,
                'label' => $row->name,
                'description' => $row->description ?? '',
                'sortOrder' => (int) $row->sort_order,
                'status' => $row->is_active ? 'Active' : 'Inactive',
                'createdAt' => optional($row->created_at)->toDateTimeString(),
                'updatedAt' => optional($row->updated_at)->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    private function syncMasterTable(string $modelClass, array $rows): void
    {
        $cleanRows = collect($rows)
            ->map(fn (array $row) => $this->cleanMasterRow($row))
            ->filter(fn (?array $row) => $row !== null)
            ->unique('code')
            ->values();

        $codes = $cleanRows->pluck('code')->all();

        $modelClass::query()
            ->when(count($codes) > 0, fn ($query) => $query->whereNotIn('code', $codes))
            ->delete();

        if (count($codes) === 0) {
            $modelClass::query()->delete();
            return;
        }

        foreach ($cleanRows as $row) {
            $modelClass::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'sort_order' => $row['sortOrder'],
                    'is_active' => $row['status'] === 'Active',
                ]
            );
        }
    }

    private function syncDocumentNames(array $rows): void
    {
        $allowedTypes = ['All Modules', 'Vehicles', 'Drivers', 'Vendors & Parties', 'Employees', 'Clients', 'Contracts'];
        $cleanRows = collect($rows)
            ->map(function (array $row) use ($allowedTypes): ?array {
                $clean = $this->cleanMasterRow($row);
                if ($clean === null) {
                    return null;
                }

                $documentType = trim((string) ($row['documentType'] ?? $row['document_type'] ?? 'All Modules'));
                $clean['documentType'] = in_array($documentType, $allowedTypes, true) ? $documentType : 'All Modules';

                return $clean;
            })
            ->filter(fn (?array $row) => $row !== null)
            ->unique('code')
            ->values();

        $codes = $cleanRows->pluck('code')->all();
        FleetDocumentName::query()
            ->when(count($codes) > 0, fn ($query) => $query->whereNotIn('code', $codes))
            ->delete();

        if (count($codes) === 0) {
            FleetDocumentName::query()->delete();
            return;
        }

        foreach ($cleanRows as $row) {
            FleetDocumentName::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'document_type' => $row['documentType'],
                    'description' => $row['description'],
                    'sort_order' => $row['sortOrder'],
                    'is_active' => $row['status'] === 'Active',
                ]
            );
        }
    }

    private function syncVehicleSubCategories(array $rows): void
    {
        $cleanRows = collect($rows)
            ->map(fn (array $row) => $this->cleanVehicleSubCategoryRow($row))
            ->filter(fn (?array $row) => $row !== null)
            ->unique('code')
            ->values();

        $codes = $cleanRows->pluck('code')->all();

        FleetVehicleSubCategory::query()
            ->when(count($codes) > 0, fn ($query) => $query->whereNotIn('code', $codes))
            ->delete();

        if (count($codes) === 0) {
            FleetVehicleSubCategory::query()->delete();
            return;
        }

        foreach ($cleanRows as $row) {
            FleetVehicleSubCategory::updateOrCreate(
                ['code' => $row['code']],
                [
                    'vehicle_category_code' => $row['vehicleCategoryCode'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'sort_order' => $row['sortOrder'],
                    'is_active' => $row['status'] === 'Active',
                ]
            );
        }
    }

    private function cleanVehicleSubCategoryRow(array $row): ?array
    {
        $name = trim((string) ($row['name'] ?? $row['label'] ?? ''));
        $vehicleCategoryCode = trim((string) ($row['vehicleCategoryCode'] ?? $row['vehicle_category_code'] ?? ''));

        if ($name === '' || $vehicleCategoryCode === '') {
            return null;
        }

        $vehicleCategoryCode = $this->codeFrom($vehicleCategoryCode);
        $code = trim((string) ($row['code'] ?? ''));
        $code = $code !== '' ? $this->codeFrom($code) : $this->codeFrom($vehicleCategoryCode . '_' . $name);
        $status = (($row['status'] ?? 'Active') === 'Inactive') ? 'Inactive' : 'Active';
        $description = trim((string) ($row['description'] ?? ''));
        $sortOrder = max(0, (int) ($row['sortOrder'] ?? $row['sort_order'] ?? 0));

        return [
            'code' => $code,
            'vehicleCategoryCode' => $vehicleCategoryCode,
            'name' => $name,
            'description' => $description,
            'sortOrder' => $sortOrder,
            'status' => $status,
        ];
    }

    private function cleanMasterRow(array $row): ?array
    {
        $name = trim((string) ($row['name'] ?? $row['label'] ?? ''));

        if ($name === '') {
            return null;
        }

        $code = trim((string) ($row['code'] ?? ''));
        $code = $code !== '' ? $this->codeFrom($code) : $this->codeFrom($name);
        $status = (($row['status'] ?? 'Active') === 'Inactive') ? 'Inactive' : 'Active';
        $description = trim((string) ($row['description'] ?? ''));
        $sortOrder = max(0, (int) ($row['sortOrder'] ?? $row['sort_order'] ?? 0));

        return [
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'sortOrder' => $sortOrder,
            'status' => $status,
        ];
    }

    private function codeFrom(string $value): string
    {
        $code = Str::of($value)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return $code !== '' ? $code : 'MASTER_' . Str::upper(Str::random(6));
    }
}
