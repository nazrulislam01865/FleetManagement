<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetFuelRecharge;
use App\Services\FleetTemporaryUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class FuelRechargeController extends FleetBaseController
{
    protected string $activeMenu = 'fuel-recharge';
    protected string $view = 'fleetman.fuel-recharge';
    protected string $page = 'fuel-recharge';
    protected string $resource = 'fuel_recharges';
    protected string $idKey = 'rechargeId';
    protected string $nameKey = 'vehicle';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetFuelRecharge::class;

    /**
     * Camera photos upload to private temporary storage immediately and become
     * permanent only after Save Draft / Submit succeeds.
     */
    public function sync(Request $request): JsonResponse
    {
        $uploads = app(FleetTemporaryUploadService::class);
        if (! $request->has('rows') && ! $request->hasFile('fuel_recharge_photos')) {
            return parent::sync($request);
        }

        $inputRows = $request->input('rows', []);
        $rows = is_string($inputRows) ? json_decode($inputRows, true) : $inputRows;
        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The fuel recharge rows payload is invalid.',
            ]);
        }

        $uploadedPhotos = $request->file('fuel_recharge_photos', []);
        $uploadedPhotos = is_array($uploadedPhotos) ? $uploadedPhotos : [];
        $rows = $this->normalizeAndValidateRechargeRows($rows, $uploadedPhotos);
        $storedPaths = [];
        $userId = (int) $request->user()->id;

        try {
            foreach ($rows as $rowIndex => &$row) {
                if (! is_array($row)) {
                    continue;
                }

                $photos = is_array($row['photos'] ?? null) ? $row['photos'] : [];
                foreach ($photos as $photoKey => &$photo) {
                    if (! is_array($photo)) {
                        continue;
                    }

                    $temporaryFile = is_array($photo['file'] ?? null) ? $photo['file'] : [];
                    if (empty($temporaryFile['tempToken'])) {
                        continue;
                    }

                    $rechargeId = $this->rechargeFolderName($row);
                    $safePhotoKey = Str::slug((string) $photoKey) ?: 'photo';
                    $payload = $uploads->claim(
                        $temporaryFile,
                        $userId,
                        "fleet/fuel-recharges/{$rechargeId}/photos/{$safePhotoKey}",
                        ['jpg', 'jpeg', 'png', 'webp'],
                        8192,
                        true
                    );
                    $photo['file'] = $payload;
                    $storedPaths[] = $payload['filePath'];
                }
                unset($photo);
                $row['photos'] = $photos;
            }
            unset($row);

            foreach ($uploadedPhotos as $rowIndex => $photos) {
                if (! is_array($photos)) {
                    continue;
                }

                foreach ($photos as $photoKey => $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $validator = Validator::make(
                        ['photo' => $file],
                        ['photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192']]
                    );

                    if ($validator->fails()) {
                        throw new ValidationException($validator);
                    }

                    $rechargeId = $this->rechargeFolderName($rows[$rowIndex] ?? []);
                    $safePhotoKey = Str::slug((string) $photoKey) ?: 'photo';
                    $directory = "fleet/fuel-recharges/{$rechargeId}/photos/{$safePhotoKey}";
                    $storedPath = $file->store($directory, 'public');
                    $storedPaths[] = $storedPath;

                    if (! isset($rows[$rowIndex]['photos']) || ! is_array($rows[$rowIndex]['photos'])) {
                        $rows[$rowIndex]['photos'] = [];
                    }

                    if (! isset($rows[$rowIndex]['photos'][$photoKey]) || ! is_array($rows[$rowIndex]['photos'][$photoKey])) {
                        $rows[$rowIndex]['photos'][$photoKey] = [];
                    }

                    $rows[$rowIndex]['photos'][$photoKey]['file'] = $uploads->permanentPayload($storedPath, [
                        'originalName' => $file->getClientOriginalName(),
                        'mimeType' => $file->getClientMimeType(),
                        'sizeBytes' => $file->getSize(),
                    ]);
                }
            }

            $rows = $this->stripCoordinateData($rows);
            $this->persistRows($rows);
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return response()->json([
            'ok' => true,
            'rows' => $this->recordsFor(FleetFuelRecharge::class),
        ]);
    }

    private function normalizeAndValidateRechargeRows(array $rows, array $uploadedPhotos = []): array
    {
        $stationOptions = collect($this->fuelStationOptions());
        $contractOptions = collect($this->fuelRechargeContracts());
        $requiredPhotoKeys = collect($this->photoRequirements())
            ->filter(fn (array $photo): bool => (bool) ($photo['required'] ?? false))
            ->pluck('key')
            ->filter()
            ->map(fn ($key): string => (string) $key)
            ->values();
        $errors = [];

        foreach ($rows as $index => &$row) {
            if (! is_array($row)) {
                $errors["rows.{$index}"] = 'Each fuel recharge row must be a valid object.';
                continue;
            }

            unset($row['submittedAt'], $row['submittedLocation']);

            // Versioned rows are created by the fuel-aware Add Fuel form. Old
            // saved rows remain readable and are not rejected during a full sync.
            $strictFuelRules = (int) ($row['stationFuelFilterVersion'] ?? 0) >= 1;
            $status = strtolower((string) ($row['status'] ?? 'draft'));
            $isSubmitted = $status === 'submitted';

            if (! $strictFuelRules) {
                continue;
            }

            $primary = $this->normalizeFuelSegment($row, 'primary');
            $secondaryEnabled = (bool) ($row['hasSecondaryFuel'] ?? filled($row['secondaryFuelName'] ?? null));
            $secondary = $secondaryEnabled
                ? $this->normalizeFuelSegment($row, 'secondary')
                : $this->emptyFuelSegment();

            foreach ($primary as $key => $value) {
                $row['primary'.ucfirst($key)] = $value;
            }
            foreach ($secondary as $key => $value) {
                $row['secondary'.ucfirst($key)] = $value;
            }

            $row['primaryFuelStation'] = $primary['station'];
            $row['fuelStation'] = $primary['station'];
            $row['secondaryFuelStation'] = $secondary['station'];
            $row['hasSecondaryFuel'] = $secondaryEnabled;
            $row['liquidFuelLitres'] = round((float) $primary['liquidLitres'] + (float) $secondary['liquidLitres'], 2);
            $row['gas'] = round((float) $primary['gasAmount'] + (float) $secondary['gasAmount'], 2);
            $row['diesel'] = round($this->fuelTypeIs($primary['fuelName'], 'diesel') ? (float) $primary['qty'] : 0, 2)
                + round($this->fuelTypeIs($secondary['fuelName'], 'diesel') ? (float) $secondary['qty'] : 0, 2);
            $row['octane'] = round($this->fuelTypeIs($primary['fuelName'], 'octane') ? (float) $primary['qty'] : 0, 2)
                + round($this->fuelTypeIs($secondary['fuelName'], 'octane') ? (float) $secondary['qty'] : 0, 2);
            $row['totalAmount'] = round((float) $primary['amount'] + (float) $secondary['amount'], 2);

            $startKm = is_numeric($row['startKm'] ?? null) ? (float) $row['startKm'] : 0;
            $endKm = is_numeric($row['endKm'] ?? null) ? (float) $row['endKm'] : 0;
            $totalKm = $startKm >= 0 && $endKm > $startKm ? $endKm - $startKm : 0;
            $row['startKm'] = $startKm;
            $row['endKm'] = $endKm;
            $row['odoReading'] = $endKm;
            $row['totalKm'] = round($totalKm, 2);
            $row['mileage'] = $totalKm > 0 && $row['liquidFuelLitres'] > 0
                ? round($totalKm / $row['liquidFuelLitres'], 2)
                : 0;
            $row['tkKm'] = $totalKm > 0 && $row['totalAmount'] > 0
                ? round($row['totalAmount'] / $totalKm, 2)
                : 0;

            if (! $isSubmitted) {
                continue;
            }

            if ((int) ($row['rechargeValidationVersion'] ?? 0) >= 2) {
                $this->validateSubmittedRechargeRow(
                    $row,
                    $index,
                    $contractOptions,
                    $requiredPhotoKeys,
                    $uploadedPhotos[$index] ?? [],
                    $errors
                );
            }
            $this->validateFuelSegment($primary, 'primary', $index, $stationOptions, $errors, true);
            if ($secondaryEnabled) {
                $this->validateFuelSegment($secondary, 'secondary', $index, $stationOptions, $errors, false);
            }
        }
        unset($row);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $rows;
    }

    private function validateSubmittedRechargeRow(
        array $row,
        int $rowIndex,
        $contractOptions,
        $requiredPhotoKeys,
        mixed $uploadedPhotos,
        array &$errors
    ): void {
        $prefix = "rows.{$rowIndex}";
        $rechargeId = trim((string) ($row['rechargeId'] ?? ''));
        $contractId = trim((string) ($row['contractId'] ?? ''));
        $vehicleId = trim((string) ($row['vehicleId'] ?? ''));
        $submittedBy = trim((string) ($row['submittedBy'] ?? ''));
        $date = trim((string) ($row['date'] ?? ''));

        if ($rechargeId === '') {
            $errors["{$prefix}.rechargeId"] = 'Fuel recharge ID is required.';
        }

        if ($date === '' || strtotime($date) === false) {
            $errors["{$prefix}.date"] = 'A valid recharge date is required.';
        }

        $contract = null;
        if ($contractId === '') {
            $errors["{$prefix}.contractId"] = 'Contract is required.';
        } else {
            $contract = $contractOptions->first(function (array $option) use ($contractId): bool {
                return in_array($contractId, [
                    (string) ($option['id'] ?? ''),
                    (string) ($option['contractId'] ?? ''),
                ], true);
            });
            if (! $contract) {
                $errors["{$prefix}.contractId"] = 'The selected contract is not valid.';
            }
        }

        $vehicle = null;
        if ($vehicleId === '') {
            $errors["{$prefix}.vehicleId"] = 'Vehicle is required.';
        } elseif ($contract) {
            $vehicle = collect($contract['vehicles'] ?? [])->first(function (array $option) use ($vehicleId): bool {
                return in_array($vehicleId, [
                    (string) ($option['id'] ?? ''),
                    (string) ($option['vehicleId'] ?? ''),
                ], true);
            });
            if (! $vehicle) {
                $errors["{$prefix}.vehicleId"] = 'The selected vehicle is not assigned to the selected contract.';
            }
        }

        if ($submittedBy === '') {
            $errors["{$prefix}.submittedBy"] = 'Submitted By is required.';
        }

        $startKmRaw = $row['startKm'] ?? null;
        $endKmRaw = $row['endKm'] ?? null;
        if ($startKmRaw === null || $startKmRaw === '' || ! is_numeric($startKmRaw) || (float) $startKmRaw < 0) {
            $errors["{$prefix}.startKm"] = 'A valid Start KM is required.';
        }
        if ($endKmRaw === null || $endKmRaw === '' || ! is_numeric($endKmRaw) || (float) $endKmRaw <= 0) {
            $errors["{$prefix}.endKm"] = 'End KM is required and must be greater than zero.';
        } elseif (is_numeric($startKmRaw) && (float) $endKmRaw <= (float) $startKmRaw) {
            $errors["{$prefix}.endKm"] = 'Ending KM must be greater than Starting KM.';
        }

        if ($this->textLength((string) ($row['remarks'] ?? '')) > 2000) {
            $errors["{$prefix}.remarks"] = 'Remarks cannot exceed 2000 characters.';
        }

        if ($vehicle) {
            $configuredPrimary = trim((string) ($vehicle['primary'] ?? ''));
            $submittedPrimary = trim((string) ($row['primaryFuelName'] ?? ''));
            if ($configuredPrimary !== '' && $this->canonicalFuelKey($configuredPrimary) !== $this->canonicalFuelKey($submittedPrimary)) {
                $errors["{$prefix}.primaryFuelName"] = 'The main fuel does not match the selected vehicle setup.';
            }

            if ((bool) ($row['hasSecondaryFuel'] ?? false)) {
                $configuredSecondary = trim((string) ($vehicle['secondary'] ?? ''));
                $submittedSecondary = trim((string) ($row['secondaryFuelName'] ?? ''));
                if ($configuredSecondary === '' || $this->canonicalFuelKey($configuredSecondary) !== $this->canonicalFuelKey($submittedSecondary)) {
                    $errors["{$prefix}.secondaryFuelName"] = 'The second fuel does not match the selected vehicle setup.';
                }
            }
        }

        $uploadedPhotos = is_array($uploadedPhotos) ? $uploadedPhotos : [];
        foreach ($requiredPhotoKeys as $photoKey) {
            $photo = data_get($row, "photos.{$photoKey}", []);
            $storedFile = filled(data_get($photo, 'file.filePath')) || filled(data_get($photo, 'file.fileUrl')) || filled(data_get($photo, 'file.tempToken'));
            $uploadedFile = $uploadedPhotos[$photoKey] ?? null;
            $hasValidUpload = $uploadedFile instanceof UploadedFile && $uploadedFile->isValid();

            if (! $storedFile && ! $hasValidUpload) {
                $errors["{$prefix}.photos.{$photoKey}"] = ucfirst(str_replace(['_', '-'], ' ', $photoKey)).' photo is required.';
            }
        }
    }

    private function textLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        $count = preg_match_all('/./us', $value, $matches);
        return $count === false ? strlen($value) : $count;
    }

    private function normalizeFuelSegment(array $row, string $prefix): array
    {
        $fuelName = trim((string) ($row[$prefix.'FuelName'] ?? ''));
        $station = trim((string) ($row[$prefix.'Station'] ?? $row[$prefix.'FuelStation'] ?? ''));
        $directAmount = $this->isDirectAmountFuel($fuelName);
        $pricingMode = $directAmount ? 'direct_amount' : 'per_liter';
        $enteredValue = (float) ($row[$prefix.'EnteredValue']
            ?? ($directAmount ? ($row[$prefix.'Amount'] ?? $row[$prefix.'Qty'] ?? 0) : ($row[$prefix.'Qty'] ?? 0)));
        $rate = (float) ($row[$prefix.'Rate'] ?? 0);
        $qty = $directAmount ? 0.0 : max(0, $enteredValue);
        $amount = $directAmount ? max(0, $enteredValue) : max(0, $qty * $rate);

        return [
            'fuelName' => $fuelName,
            'station' => $station,
            'enteredValue' => round(max(0, $enteredValue), 2),
            'qty' => round($qty, 2),
            'rate' => round($rate, 2),
            'amount' => round($amount, 2),
            'pricingMode' => $pricingMode,
            'entryUnit' => $directAmount ? 'Taka' : 'Liter',
            'fuelUnit' => $directAmount ? 'Taka' : 'Liter',
            'liquidLitres' => $directAmount ? 0.0 : round($qty, 2),
            'gasAmount' => $directAmount ? round($amount, 2) : 0.0,
        ];
    }

    private function emptyFuelSegment(): array
    {
        return [
            'fuelName' => '',
            'station' => '',
            'enteredValue' => 0,
            'qty' => 0,
            'rate' => 0,
            'amount' => 0,
            'pricingMode' => '',
            'entryUnit' => '',
            'fuelUnit' => '',
            'liquidLitres' => 0,
            'gasAmount' => 0,
        ];
    }

    private function validateFuelSegment(array $segment, string $prefix, int $rowIndex, $stationOptions, array &$errors, bool $required): void
    {
        $label = $prefix === 'primary' ? 'main' : 'second';
        $fuelName = (string) $segment['fuelName'];

        if ($required && $fuelName === '') {
            $errors["rows.{$rowIndex}.{$prefix}FuelName"] = 'The main fuel type is missing.';
            return;
        }

        if ($fuelName === '') {
            return;
        }

        if ((string) $segment['station'] === '') {
            $errors["rows.{$rowIndex}.{$prefix}Station"] = "Please select a {$label} fuel station.";
        } elseif (! $this->stationSupportsFuel($stationOptions, (string) $segment['station'], $fuelName)) {
            $errors["rows.{$rowIndex}.{$prefix}Station"] = "The selected station is not configured to sell {$fuelName}.";
        }

        if ((float) $segment['enteredValue'] <= 0) {
            $unit = $this->isDirectAmountFuel($fuelName) ? 'amount in Taka' : 'quantity in liters';
            $errors["rows.{$rowIndex}.{$prefix}EnteredValue"] = "Please enter the {$label} fuel {$unit}.";
        }

        if (! $this->isDirectAmountFuel($fuelName) && (float) $segment['rate'] <= 0) {
            $errors["rows.{$rowIndex}.{$prefix}Rate"] = "An active per-liter rate is required for {$fuelName}.";
        }
    }

    private function stationSupportsFuel($stationOptions, string $stationName, string $fuelName): bool
    {
        $station = $stationOptions->first(fn (array $option): bool => strcasecmp((string) ($option['name'] ?? ''), $stationName) === 0);
        if (! $station) {
            return false;
        }

        $fuelKey = $this->canonicalFuelKey($fuelName);

        return collect($station['fuelTypes'] ?? [])
            ->contains(fn ($stationFuel): bool => $this->canonicalFuelKey((string) $stationFuel) === $fuelKey);
    }

    private function isDirectAmountFuel(string $fuelName): bool
    {
        $key = $this->canonicalFuelKey($fuelName);
        return in_array($key, ['cng', 'lpg', 'gas'], true);
    }

    private function fuelTypeIs(string $fuelName, string $expected): bool
    {
        return $this->canonicalFuelKey($fuelName) === $expected;
    }

    private function canonicalFuelKey(string $fuelName): string
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', $fuelName) ?: '');
        if (str_contains($normalized, 'cng') || str_contains($normalized, 'compressednaturalgas') || $normalized === 'gas' || str_contains($normalized, 'naturalgas')) return 'cng';
        if (str_contains($normalized, 'lpg') || str_contains($normalized, 'liquefiedpetroleumgas')) return 'lpg';
        if (str_contains($normalized, 'diesel')) return 'diesel';
        if (str_contains($normalized, 'octane') || str_contains($normalized, 'octen') || str_contains($normalized, 'petrol') || str_contains($normalized, 'gasoline')) return 'octane';
        return $normalized;
    }

    private function rechargeFolderName(array $row): string
    {
        return Str::slug((string) ($row['rechargeId'] ?? 'new-recharge')) ?: 'new-recharge';
    }

    /**
     * Fuel recharge should store readable place names only. The browser may use
     * device coordinates to resolve the place, but coordinates are removed
     * before the payload is persisted.
     */
    private function stripCoordinateData(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach (['latitude', 'longitude', 'lat', 'lng', 'accuracy', 'coordinates', 'gps'] as $coordinateKey) {
            unset($value[$coordinateKey]);
        }

        foreach ($value as $key => $child) {
            $value[$key] = $this->stripCoordinateData($child);
        }

        return $value;
    }

    private function persistRows(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            $incomingCodes = collect($rows)
                ->map(fn (array $row) => (string) ($row[$this->idKey] ?? ''))
                ->filter()
                ->values();

            $this->deleteMissingRecords(FleetFuelRecharge::query(), $incomingCodes);

            foreach ($rows as $row) {
                $code = (string) ($row[$this->idKey] ?? '');
                if ($code === '') {
                    continue;
                }

                /** @var Model $model */
                FleetFuelRecharge::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $row[$this->nameKey] ?? $code,
                        'status' => $row[$this->statusKey] ?? null,
                        'payload' => $row,
                    ]
                );

                // Accounting Hook: Generate Due for Fuel Recharge
                if (($row[$this->statusKey] ?? '') === 'Submitted') {
                    $amount = (float) ($row['totalAmount'] ?? 0);
                    if ($amount > 0) {
                        // Find vehicle to get owner/vendor
                        $vehicleId = $row['vehicleId'] ?? null;
                        $partyType = 'Driver';
                        $partyId = $row['driverId'] ?? null;
                        
                        if ($vehicleId) {
                            $vehicle = \App\Models\Fleet\FleetVehicle::where('code', $vehicleId)->first();
                            if ($vehicle && !empty($vehicle->payload['vendor'])) {
                                $partyType = 'Vendor';
                                $partyId = $vehicle->payload['vendor'];
                            }
                        }

                        \App\Models\Fleet\FleetDue::updateOrCreate(
                            ['code' => 'DUE-FUEL-' . $code],
                            [
                                'type' => 'Fuel Recharge',
                                'party_type' => $partyType,
                                'party_id' => $partyId,
                                'source_type' => 'FuelRecharge',
                                'source_id' => $code,
                                'amount' => $amount,
                                'status' => 'Pending',
                                'due_date' => $row['date'] ?? null,
                                'payload' => [
                                    'vehicleId' => $vehicleId,
                                    'fuelType' => $row['fuelType'] ?? null,
                                    'qty' => $row['primaryQty'] ?? 0,
                                    'entryValue' => $row['primaryEnteredValue'] ?? $row['primaryQty'] ?? 0,
                                    'entryUnit' => $row['primaryEntryUnit'] ?? $row['primaryFuelUnit'] ?? '',
                                    'pricingMode' => $row['primaryPricingMode'] ?? '',
                                ]
                            ]
                        );
                    }
                }
            }
        });
    }
}
