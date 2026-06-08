<?php

namespace Database\Seeders;

use App\Models\Fleet\FleetClient;
use App\Models\Fleet\FleetContract;
use App\Models\Fleet\FleetDocumentName;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetDriverContactType;
use App\Models\Fleet\FleetDriverAttendance;
use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetFuelPrice;
use App\Models\Fleet\FleetFuelType;
use App\Models\Fleet\FleetFuelUnit;
use App\Models\Fleet\FleetLookup;
use App\Models\Fleet\FleetPartyType;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVendorParty;
use App\Models\Fleet\FleetVehicleCategory;
use App\Models\Fleet\FleetVehicleSubCategory;
use Illuminate\Database\Seeder;

class FleetDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('fleetman');
        $options = $config['options'] ?? [];

        $this->seedSimple('vendor', $options['vendors'] ?? []);
        $this->seedSimple('driver_select', $options['drivers'] ?? []);
        $this->seedSimple('fuel_type', $options['fuel_types'] ?? []);
        $this->seedSimple('fuel_price_type', $options['fuel_price_types'] ?? []);
        $this->seedSimple('fuel_unit', $options['fuel_units'] ?? []);
        $this->seedMasterTable(FleetFuelType::class, array_values(array_unique(array_merge(
            $options['fuel_types'] ?? [],
            $options['fuel_price_types'] ?? []
        ))));
        $this->seedMasterTable(FleetFuelUnit::class, $options['fuel_units'] ?? []);
        $this->seedSimple('fuel_status', $options['fuel_statuses'] ?? []);
        $this->seedSimple('document_template', $options['document_templates'] ?? []);
        $this->seedDocumentNames([
            'Vehicles' => $options['document_templates'] ?? [],
            'Vendors & Parties' => $options['party_document_templates'] ?? [],
            'Drivers' => $options['driver_document_templates'] ?? [],
            'Employees' => $options['employee_document_templates'] ?? [],
        ]);
        $this->seedSimple('document_reminder', $options['document_reminders'] ?? []);
        $this->seedMasterTable(FleetPartyType::class, $options['party_types'] ?? []);
        $this->seedSimple('party_status', $options['party_statuses'] ?? []);
        $this->seedSimple('payment_term', $options['payment_terms'] ?? []);
        $this->seedSimple('party_document_template', $options['party_document_templates'] ?? []);
        $this->seedSimple('trip_status', $options['trip_statuses'] ?? []);
        $this->seedSimple('trip_around', $options['trip_around'] ?? []);
        $this->seedSimple('trip_period', $options['trip_periods'] ?? []);
        $this->seedSimple('trip_purpose', $options['trip_purposes'] ?? []);
        $this->seedSimple('driver_license_type', $options['driver_license_types'] ?? []);
        $this->seedMasterTable(FleetDriverContactType::class, $options['driver_contact_types'] ?? []);
        $this->seedSimple('driver_salary_tenure', $options['driver_salary_tenures'] ?? []);
        $this->seedSimple('driver_status', $options['driver_statuses'] ?? []);
        $this->seedSimple('driver_document_template', $options['driver_document_templates'] ?? []);
        $this->seedSimple('client_type', $options['client_types'] ?? []);
        $this->seedSimple('client_status', $options['client_statuses'] ?? []);
        $this->seedSimple('client_contact_method', $options['client_contact_methods'] ?? []);
        $this->seedSimple('attendance_status', $options['attendance_statuses'] ?? []);
        $this->seedSimple('employee_status', $options['employee_statuses'] ?? []);
        $this->seedSimple('employee_salary_tenure', $options['employee_salary_tenures'] ?? []);
        $this->seedSimple('employee_designation', $options['employee_designations'] ?? []);
        $this->seedSimple('employee_document_template', $options['employee_document_templates'] ?? []);

        $this->seedVehicleCategoryMasters($options['vehicle_categories'] ?? []);

        $sort = 1;
        foreach (($options['vehicle_categories'] ?? []) as $category => $subCategories) {
            $this->lookup('vehicle_category', $category, $category, ['sub_categories' => array_values($subCategories)], $sort++);
        }

        $this->seedChoiceGroup('usage_type', $options['usage_types'] ?? []);
        $this->seedChoiceGroup('driver_duty_type', $options['driver_duty_types'] ?? []);

        $tripMasters = $config['trip_masters'] ?? [];
        foreach (($tripMasters['vehicles'] ?? []) as $i => $vehicle) {
            $value = ($vehicle['id'] ?? '') . ' - ' . ($vehicle['name'] ?? '');
            $this->lookup('trip_vehicle', $value, $value, $vehicle, $i + 1);
        }
        foreach (($tripMasters['drivers'] ?? []) as $i => $driver) {
            $value = ($driver['id'] ?? '') . ' - ' . ($driver['name'] ?? '');
            $this->lookup('trip_driver', $value, $value, $driver, $i + 1);
        }
        $this->seedSimple('trip_vehicle_type', $tripMasters['vehicle_types'] ?? []);
        $this->seedSimple('trip_driver_area', $tripMasters['driver_areas'] ?? []);

        $attendanceMasters = $config['attendance_masters'] ?? [];
        foreach (($attendanceMasters['drivers'] ?? []) as $i => $driver) {
            $this->lookup('attendance_driver', $driver, $driver, [], $i + 1);
        }
        foreach (($attendanceMasters['yards'] ?? []) as $i => $yard) {
            $this->lookup('attendance_yard', $yard, $yard, [], $i + 1);
        }
        foreach (($attendanceMasters['vehicle_driver_map'] ?? []) as $vehicle => $driver) {
            $this->lookup('attendance_vehicle_driver', $vehicle, $driver, ['vehicle' => $vehicle, 'driver' => $driver], 0);
        }

        foreach (($config['contracts'] ?? []) as $contract) {
            FleetContract::updateOrCreate(
                ['code' => $contract['id']],
                [
                    'name' => $contract['label'] ?? $contract['id'],
                    'status' => 'fuel_recharge',
                    'payload' => $contract,
                ]
            );
        }

        foreach (($attendanceMasters['contracts'] ?? []) as $contract) {
            $code = $contract['id'];
            $payload = array_merge($contract, ['label' => $contract['id'] . ' - ' . $contract['name']]);
            FleetContract::updateOrCreate(
                ['code' => 'attendance_' . $code],
                [
                    'name' => $payload['label'],
                    'status' => 'attendance',
                    'payload' => $payload,
                ]
            );
        }

        $samples = $config['samples'] ?? [];
        $this->seedRecords(FleetVehicle::class, $samples['vehicles'] ?? [], 'id', 'name', 'status');
        $this->seedRecords(FleetFuelPrice::class, $samples['fuel_prices'] ?? [], 'fuelPriceId', 'name', 'status');
        $this->seedRecords(\App\Models\Fleet\FleetFuelRecharge::class, $samples['fuel_recharges'] ?? [], 'rechargeId', 'vehicle', 'status');
        $this->seedRecords(FleetVendorParty::class, $samples['parties'] ?? [], 'partyId', 'partyName', 'status');
        $this->seedRecords(FleetTrip::class, $samples['trips'] ?? [], 'tripId', 'purpose', 'status');
        $this->seedRecords(FleetDriver::class, $samples['drivers'] ?? [], 'driverId', 'fullName', 'status');
        $this->seedRecords(FleetClient::class, $samples['clients'] ?? [], 'clientId', 'clientName', 'status');
        $this->seedRecords(FleetDriverAttendance::class, $samples['driver_attendance'] ?? [], 'logId', 'driver', 'status');
        $this->seedRecords(FleetEmployee::class, $samples['employees'] ?? [], 'employeeId', 'fullName', 'status');
    }

    private function seedSimple(string $group, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $this->lookup($group, (string) $value, (string) $value, [], $index + 1);
        }
    }

    private function seedMasterTable(string $modelClass, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $name = trim((string) $value);

            if ($name === '') {
                continue;
            }

            $modelClass::updateOrCreate(
                ['code' => str($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                [
                    'name' => $name,
                    'description' => null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }


    private function seedDocumentNames(array $groups): void
    {
        $sort = 1;

        foreach ($groups as $documentType => $values) {
            foreach (array_values($values) as $value) {
                $name = trim((string) $value);

                if ($name === '') {
                    continue;
                }

                FleetDocumentName::updateOrCreate(
                    ['code' => str($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                    [
                        'name' => $name,
                        'document_type' => $documentType,
                        'description' => null,
                        'sort_order' => $sort++,
                        'is_active' => true,
                    ]
                );
            }
        }
    }


    private function seedVehicleCategoryMasters(array $categories): void
    {
        foreach ($categories as $categoryIndex => $subCategories) {
            $categoryName = trim((string) $categoryIndex);

            if ($categoryName === '') {
                continue;
            }

            $categoryCode = str($categoryName)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString();

            FleetVehicleCategory::updateOrCreate(
                ['code' => $categoryCode],
                [
                    'name' => $categoryName,
                    'description' => null,
                    'sort_order' => array_search($categoryName, array_keys($categories), true) + 1,
                    'is_active' => true,
                ]
            );

            foreach (array_values($subCategories) as $subIndex => $subCategoryName) {
                $subCategoryName = trim((string) $subCategoryName);

                if ($subCategoryName === '') {
                    continue;
                }

                FleetVehicleSubCategory::updateOrCreate(
                    ['code' => str($categoryCode . '_' . $subCategoryName)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                    [
                        'vehicle_category_code' => $categoryCode,
                        'name' => $subCategoryName,
                        'description' => null,
                        'sort_order' => $subIndex + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }


    private function seedChoiceGroup(string $group, array $choices): void
    {
        foreach (array_values($choices) as $index => $choice) {
            $value = (string) ($choice['value'] ?? $choice['title'] ?? '');
            if ($value === '') {
                continue;
            }
            $this->lookup($group, $choice['title'] ?? $value, $value, $choice, $index + 1);
        }
    }

    private function lookup(string $group, string $label, string $value, array $meta = [], int $sort = 0): void
    {
        FleetLookup::updateOrCreate(
            ['group' => $group, 'value' => $value],
            [
                'key' => str($value)->slug('_')->toString(),
                'label' => $label,
                'meta' => $meta,
                'sort_order' => $sort,
                'is_active' => true,
            ]
        );
    }

    private function seedRecords(string $modelClass, array $rows, string $codeKey, string $nameKey, string $statusKey): void
    {
        foreach ($rows as $row) {
            $code = (string) ($row[$codeKey] ?? '');
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
    }
}
