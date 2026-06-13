<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetVehicle;
use App\Services\FleetTemporaryUploadService;
use App\Support\FleetDocumentUploadPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class VehicleController extends FleetBaseController
{
    protected string $activeMenu = 'vehicles';
    protected string $view = 'fleetman.vehicles';
    protected string $page = 'vehicles';
    protected string $resource = 'vehicles';
    protected string $idKey = 'id';
    protected string $nameKey = 'name';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetVehicle::class;

    /**
     * Selected files are uploaded to private temporary storage immediately.
     * They become permanent only when Save Vehicle succeeds.
     */
    public function sync(Request $request): JsonResponse
    {
        $uploads = app(FleetTemporaryUploadService::class);
        $rowsInput = $request->input('rows', []);
        $rows = is_string($rowsInput) ? json_decode($rowsInput, true) : $rowsInput;

        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The vehicle rows payload is invalid.',
            ]);
        }

        $strictValidationIndexes = $this->changedRowIndexesForSync($rows, FleetVehicle::class, $this->idKey);
        $rows = $this->normalizeVehicleRows($rows);
        $this->validateVehicleRows($rows, $request, $strictValidationIndexes);
        $this->validateUniqueDocumentNames($this->syncRowsAtIndexes($rows, $strictValidationIndexes), 'docs');
        $storedPaths = [];
        $userId = (int) $request->user()->id;

        try {
            foreach ($rows as $vehicleIndex => &$vehicle) {
                if (! is_array($vehicle)) {
                    continue;
                }

                $vehicleId = $this->safeVehicleId((string) ($vehicle['id'] ?? 'new-vehicle'));
                $image = $vehicle['image'] ?? [];
                if (is_array($image) && filled($image['tempToken'] ?? null)) {
                    $vehicle['image'] = $uploads->claim(
                        $image,
                        $userId,
                        'fleet/vehicles/'.$vehicleId.'/images',
                        ['jpg', 'jpeg', 'png', 'webp'],
                        100,
                        true
                    );
                    $storedPaths[] = $vehicle['image']['filePath'];
                }

                foreach (($vehicle['docs'] ?? []) as $documentIndex => $document) {
                    if (! is_array($document)) {
                        continue;
                    }
                    $file = $document['file'] ?? [];
                    if (is_array($file) && filled($file['tempToken'] ?? null)) {
                        $vehicle['docs'][$documentIndex]['file'] = $uploads->claim(
                            $file,
                            $userId,
                            'fleet/vehicles/'.$vehicleId.'/documents',
                            FleetDocumentUploadPolicy::EXTENSIONS,
                            FleetDocumentUploadPolicy::MAX_KILOBYTES
                        );
                        $storedPaths[] = $vehicle['docs'][$documentIndex]['file']['filePath'];
                    }
                }
            }
            unset($vehicle);

            // Backward-compatible support for older browser code that sends
            // files inside the final save request.
            foreach ($request->file('vehicle_image_files', []) as $vehicleIndex => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $validator = Validator::make(
                    ['image' => $file],
                    ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:100']],
                    ['image.max' => 'The vehicle image must not exceed 100 KB.']
                );
                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $vehicle = $rows[$vehicleIndex] ?? [];
                $vehicleId = $this->safeVehicleId((string) ($vehicle['id'] ?? 'new-vehicle'));
                $storedPath = $file->store('fleet/vehicles/'.$vehicleId.'/images', 'public');
                $storedPaths[] = $storedPath;
                $rows[$vehicleIndex]['image'] = $uploads->permanentPayload($storedPath, [
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType' => $file->getClientMimeType(),
                    'sizeBytes' => $file->getSize(),
                ]);
            }

            foreach ($request->file('vehicle_document_files', []) as $vehicleIndex => $documentFiles) {
                if (! is_array($documentFiles)) {
                    continue;
                }

                foreach ($documentFiles as $documentIndex => $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $validator = Validator::make(
                        ['document' => $file],
                        ['document' => FleetDocumentUploadPolicy::rules()],
                        FleetDocumentUploadPolicy::messages('document')
                    );
                    if ($validator->fails()) {
                        throw new ValidationException($validator);
                    }

                    $vehicle = $rows[$vehicleIndex] ?? [];
                    $vehicleId = $this->safeVehicleId((string) ($vehicle['id'] ?? 'new-vehicle'));
                    $storedPath = $file->store('fleet/vehicles/'.$vehicleId.'/documents', 'public');
                    $storedPaths[] = $storedPath;
                    $rows[$vehicleIndex]['docs'][$documentIndex]['file'] = $uploads->permanentPayload($storedPath, [
                        'originalName' => $file->getClientOriginalName(),
                        'mimeType' => $file->getClientMimeType(),
                        'sizeBytes' => $file->getSize(),
                    ]);
                }
            }

            $this->persistRows($rows);
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }
            throw $exception;
        }

        return response()->json([
            'ok' => true,
            'rows' => $this->recordsFor(FleetVehicle::class),
        ]);
    }

    private function persistRows(array $rows): void
    {
        $modelClass = $this->modelClass;
        $idKey = $this->idKey;
        $nameKey = $this->nameKey;
        $statusKey = $this->statusKey;

        DB::transaction(function () use ($modelClass, $rows, $idKey, $nameKey, $statusKey) {
            $incomingCodes = collect($rows)
                ->map(fn (array $row) => (string) ($row[$idKey] ?? ''))
                ->filter()
                ->values();

            $this->deleteMissingRecords($modelClass::query(), $incomingCodes);

            foreach ($rows as $row) {
                $code = (string) ($row[$idKey] ?? '');
                if ($code === '') {
                    continue;
                }

                /** @var Model $model */
                $modelClass::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $row[$nameKey] ?? $code,
                        'status' => $row[$statusKey] ?? null,
                        'payload' => $this->withoutRecordMetadata($row),
                    ]
                );
            }
        });
    }

    private function normalizeVehicleRows(array $rows): array
    {
        $usedIds = collect($rows)
            ->map(fn ($row) => is_array($row) ? trim((string) ($row['id'] ?? '')) : '')
            ->filter()
            ->all();

        foreach ($rows as &$row) {
            if (! is_array($row)) {
                continue;
            }

            if (blank($row['id'] ?? null)) {
                $row['id'] = $this->nextVehicleId($usedIds);
                $usedIds[] = $row['id'];
            }

            $row['id'] = trim((string) $row['id']);
            $row['regNo'] = strtoupper(trim((string) ($row['regNo'] ?? '')));
            $row['engineNo'] = strtoupper(trim((string) ($row['engineNo'] ?? '')));
            $driver = trim((string) ($row['driver'] ?? ''));
            $row['driver'] = $driver !== '' ? $driver : null;
            $row['rentalType'] = trim((string) ($row['rentalType'] ?? ''));
            $row['driverPaymentAmount'] = round((float) ($row['driverPaymentAmount'] ?? 0), 2);
            $row['vehicleRentalAmount'] = round((float) ($row['vehicleRentalAmount'] ?? $row['rent'] ?? 0), 2);
            $row['totalRentalAmount'] = round(
                $row['vehicleRentalAmount'] + ($row['rentalType'] === 'With Driver' ? $row['driverPaymentAmount'] : 0),
                2
            );
            $row['rent'] = $row['totalRentalAmount'];
        }
        unset($row);

        return $rows;
    }

    private function validateVehicleRows(array $rows, Request $request, ?array $strictValidationIndexes = null): void
    {
        $errors = [];
        $vehicleVendors = $this->vehicleVendorValues();
        $drivers = $this->optionsFromDatabase()['drivers'] ?? [];
        $categories = array_keys($this->vehicleCategoryOptions());
        $usageTypes = collect($this->choiceValues('usage_type'))->pluck('value')->filter()->values()->all();
        $fuelTypes = $this->fuelTypeValues();
        $paymentCycles = config('fleetman.options.rental_payment_cycles', ['Daily', 'Weekly', 'Monthly', 'Contract']);
        $documentNames = $this->documentNameValues('Vehicles', 'document_template');
        $documentReminders = $this->values('document_reminder');
        $strictValidationLookup = $strictValidationIndexes === null
            ? null
            : array_fill_keys($strictValidationIndexes, true);

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $errors["rows.{$index}"] = 'Each vehicle row must be a valid object.';
                continue;
            }

            // Older and unchanged records remain readable and deletable. Only
            // the row being created or edited receives current master-data validation.
            if (($strictValidationLookup !== null && ! isset($strictValidationLookup[$index]))
                || (int) ($row['vehicleValidationVersion'] ?? 0) < 1) {
                continue;
            }

            $rules = [
                'id' => ['required', 'string', 'max:100'],
                'name' => ['required', 'string', 'max:255'],
                'regNo' => ['required', 'string', 'not_regex:/[@#$%^&*()!`~]/'],
                'vendor' => ['nullable', Rule::in($vehicleVendors)],
                'model' => ['required', 'string', 'max:255'],
                'color' => ['nullable', 'string', 'max:100'],
                'engineNo' => ['required', 'string', 'max:22'],
                'mileage' => ['nullable', 'numeric', 'min:0'],
                'odo' => ['nullable', 'numeric', 'min:0'],
                'category' => ['required', Rule::in($categories)],
                'subCategory' => ['nullable', 'string', 'max:255'],
                'usage' => ['required', Rule::in($usageTypes)],
                'rentalType' => ['required', Rule::in(['With Driver', 'Without Driver'])],
                'driver' => ['nullable', Rule::in($drivers)],
                'driverPaymentAmount' => [Rule::requiredIf(($row['rentalType'] ?? '') === 'With Driver'), 'nullable', 'numeric', 'min:0'],
                'driverPaymentCycle' => [Rule::requiredIf(($row['rentalType'] ?? '') === 'With Driver'), 'nullable', Rule::in($paymentCycles)],
                'vehicleRentalAmount' => ['required', 'numeric', 'min:0'],
                'vehiclePaymentCycle' => ['required', Rule::in($paymentCycles)],
                'totalRentalAmount' => ['required', 'numeric', 'min:0'],
                'fuels' => ['required', 'array', 'min:1'],
                'fuels.*.type' => ['required', Rule::in($fuelTypes)],
                'fuels.*.priority' => ['required', Rule::in(['Primary', 'Secondary', 'Tertiary'])],
                'fuels.*.rate' => ['required', 'numeric', 'gt:0'],
                'docs' => ['nullable', 'array'],
                'docs.*.name' => ['required', Rule::in($documentNames)],
                'docs.*.expiry' => ['nullable', 'date'],
                'docs.*.reminder' => ['nullable', Rule::in($documentReminders)],
            ];

            $validator = Validator::make($row, $rules, [
                'regNo.not_regex' => 'Registration Number cannot contain these special characters: @ # $ % ^ & * ( ) ! ` ~.',
                'vendor.in' => 'Select an active vehicle or driver service vendor / owner.',
                'driver.in' => 'Select a valid driver.',
            ]);

            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors["rows.{$index}.{$key}"] = $messages;
            }

            $fuelRows = collect($row['fuels'] ?? [])->filter(fn ($fuel) => is_array($fuel));
            $normalizedFuelNames = $fuelRows->map(fn (array $fuel) => strtolower(trim((string) ($fuel['type'] ?? ''))))->filter();
            if ($normalizedFuelNames->duplicates()->isNotEmpty()) {
                $errors["rows.{$index}.fuels"] = 'Each fuel type can be selected only once.';
            }
            $normalizedFuelPriorities = $fuelRows->map(fn (array $fuel) => trim((string) ($fuel['priority'] ?? '')))->filter();
            if ($normalizedFuelPriorities->duplicates()->isNotEmpty()) {
                $errors["rows.{$index}.fuels"] = 'Each fuel priority can be selected only once.';
            }
            if (! $fuelRows->contains(fn (array $fuel) => ($fuel['priority'] ?? '') === 'Primary')) {
                $errors["rows.{$index}.fuels"] = 'One fuel type must be marked as Primary.';
            }

            $image = is_array($row['image'] ?? null) ? $row['image'] : [];
            $directImage = $request->file("vehicle_image_files.{$index}");
            if ((int) ($image['sizeBytes'] ?? 0) > 100 * 1024 || ($directImage instanceof UploadedFile && $directImage->getSize() > 100 * 1024)) {
                $errors["rows.{$index}.image"] = 'The vehicle image must not exceed 100 KB.';
            }

            foreach ((array) ($row['docs'] ?? []) as $documentIndex => $document) {
                if (! is_array($document)) {
                    continue;
                }
                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                $directFile = $request->file("vehicle_document_files.{$index}.{$documentIndex}");
                $hasFile = filled($file['tempToken'] ?? null)
                    || filled($file['filePath'] ?? null)
                    || filled($file['fileUrl'] ?? null)
                    || filled($file['previewUrl'] ?? null)
                    || $directFile instanceof UploadedFile;
                if (! $hasFile) {
                    $errors["rows.{$index}.docs.{$documentIndex}.file"] = 'Upload a file for each added vehicle document.';
                } elseif ((int) ($file['sizeBytes'] ?? 0) > 4 * 1024 * 1024 || ($directFile instanceof UploadedFile && $directFile->getSize() > 4 * 1024 * 1024)) {
                    $errors["rows.{$index}.docs.{$documentIndex}.file"] = 'Each vehicle document must not exceed 4 MB.';
                }
            }
        }

        $ids = collect($rows)->pluck('id')->filter();
        foreach ($ids->duplicates() as $duplicate) {
            $errors['rows'] = "Vehicle ID {$duplicate} is duplicated.";
        }
        $registrations = collect($rows)
            ->filter(fn ($row) => is_array($row) && (int) ($row['vehicleValidationVersion'] ?? 0) >= 1)
            ->pluck('regNo')
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter();
        if ($registrations->duplicates()->isNotEmpty()) {
            $errors['rows'] = 'Registration Number must be unique.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function nextVehicleId(array $usedIds = []): string
    {
        do {
            $id = 'VHL'.now()->format('ymd').strtoupper(Str::random(4));
        } while (in_array($id, $usedIds, true) || FleetVehicle::query()->where('code', $id)->exists());

        return $id;
    }

    private function safeVehicleId(string $vehicleId): string
    {
        $safeId = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($vehicleId));

        return trim((string) $safeId, '-') ?: 'new-vehicle';
    }
}
