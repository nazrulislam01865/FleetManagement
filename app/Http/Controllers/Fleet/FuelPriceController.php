<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetFuelPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FuelPriceController extends FleetBaseController
{
    protected string $activeMenu = 'fuel-prices';
    protected string $view = 'fleetman.fuel-prices';
    protected string $page = 'fuel-prices';
    protected string $resource = 'fuel_prices';
    protected string $idKey = 'fuelPriceId';
    protected string $nameKey = 'name';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetFuelPrice::class;

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['present', 'array'],
            'rows.*' => ['array'],
        ]);

        $rows = $validated['rows'];
        $errors = [];
        $seenIds = [];

        foreach ($rows as $index => &$row) {
            if ((int) ($row['fuelPriceValidationVersion'] ?? 0) < 1) {
                continue;
            }

            $prefix = "rows.{$index}";
            $id = trim((string) ($row['fuelPriceId'] ?? ''));
            $fuelType = trim((string) ($row['fuelType'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $price = $row['price'] ?? null;
            $unit = trim((string) ($row['unit'] ?? ''));
            $status = trim((string) ($row['status'] ?? ''));
            $effectiveDate = trim((string) ($row['effectiveDate'] ?? ''));
            $reference = trim((string) ($row['reference'] ?? ''));
            $remarks = trim((string) ($row['remarks'] ?? ''));

            if ($id === '') {
                $errors["{$prefix}.fuelPriceId"] = 'Fuel Price ID is required.';
            } elseif (isset($seenIds[strtolower($id)])) {
                $errors["{$prefix}.fuelPriceId"] = 'Fuel Price ID must be unique.';
            } else {
                $seenIds[strtolower($id)] = true;
            }

            if ($fuelType === '') {
                $errors["{$prefix}.fuelType"] = 'Fuel Type is required.';
            }
            if ($name === '') {
                $errors["{$prefix}.name"] = 'Name is required.';
            } elseif ($this->textLength($name) > 160) {
                $errors["{$prefix}.name"] = 'Name cannot exceed 160 characters.';
            }
            if ($price === null || $price === '' || ! is_numeric($price) || (float) $price <= 0) {
                $errors["{$prefix}.price"] = 'Price per Unit is required and must be greater than zero.';
            }
            if ($unit === '') {
                $errors["{$prefix}.unit"] = 'Unit is required.';
            }
            if ($status === '') {
                $errors["{$prefix}.status"] = 'Status is required.';
            }
            if ($effectiveDate === '' || strtotime($effectiveDate) === false) {
                $errors["{$prefix}.effectiveDate"] = 'A valid Effective Date is required.';
            }
            if ($reference === '') {
                $errors["{$prefix}.reference"] = 'Reference is required.';
            } elseif ($this->textLength($reference) > 160) {
                $errors["{$prefix}.reference"] = 'Reference cannot exceed 160 characters.';
            }
            if ($remarks === '') {
                $errors["{$prefix}.remarks"] = 'Remarks are required.';
            } elseif ($this->textLength($remarks) > 1000) {
                $errors["{$prefix}.remarks"] = 'Remarks cannot exceed 1000 characters.';
            }

            $row['fuelPriceId'] = $id;
            $row['fuelType'] = $fuelType;
            $row['name'] = $name;
            $row['unit'] = $unit;
            $row['status'] = $status;
            $row['effectiveDate'] = $effectiveDate;
            $row['reference'] = $reference;
            $row['remarks'] = $remarks;
            $row['price'] = is_numeric($price) ? round((float) $price, 2) : $price;
        }
        unset($row);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($rows) {
            $incomingCodes = collect($rows)
                ->map(fn (array $row): string => (string) ($row['fuelPriceId'] ?? ''))
                ->filter()
                ->values();

            FleetFuelPrice::query()->whereNotIn('code', $incomingCodes)->delete();

            foreach ($rows as $row) {
                $code = (string) ($row['fuelPriceId'] ?? '');
                if ($code === '') {
                    continue;
                }

                FleetFuelPrice::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $row['name'] ?? $code,
                        'status' => $row['status'] ?? null,
                        'payload' => $row,
                    ]
                );
            }
        });

        return response()->json([
            'ok' => true,
            'rows' => $this->recordsFor(FleetFuelPrice::class),
        ]);
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
