<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetContract;
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

class ContractController extends FleetBaseController
{
    protected string $activeMenu = 'contracts';
    protected string $view = 'fleetman.contracts';
    protected string $page = 'contracts';
    protected string $resource = 'contracts';
    protected string $idKey = 'contractId';
    protected string $nameKey = 'partyName';
    protected string $statusKey = 'savedAs';
    protected string $modelClass = FleetContract::class;

    /**
     * Temporary document uploads are finalized only when the contract itself is
     * saved. Direct multipart uploads remain supported for older clients.
     */
    public function sync(Request $request): JsonResponse
    {
        $uploads = app(FleetTemporaryUploadService::class);
        $rawRows = $request->input('rows', []);
        $rows = is_string($rawRows) ? json_decode($rawRows, true) : $rawRows;

        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The contract rows payload is invalid.',
            ]);
        }

        $validateContractId = trim((string) $request->input('validateContractId', ''));
        $this->validateContractRows($rows, $request, $validateContractId);
        $this->validateContractDocumentNames($rows, $validateContractId);
        $rows = $this->normalizeContractRows($rows);

        $storedPaths = [];
        $userId = (int) $request->user()->id;

        try {
            foreach ($rows as $contractIndex => &$contract) {
                if (! is_array($contract)) {
                    continue;
                }

                $contractId = Str::slug((string) ($contract['contractId'] ?? 'new-contract')) ?: 'new-contract';
                $documents = is_array($contract['documents'] ?? null) ? $contract['documents'] : [];

                foreach ($documents as $documentIndex => &$document) {
                    if (! is_array($document)) {
                        continue;
                    }

                    $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                    if (! empty($file['tempToken'])) {
                        $payload = $uploads->claim(
                            $file,
                            $userId,
                            'fleet/contracts/'.$contractId.'/documents',
                            FleetDocumentUploadPolicy::EXTENSIONS,
                            FleetDocumentUploadPolicy::MAX_KILOBYTES
                        );
                        $document['file'] = $payload;
                        $storedPaths[] = $payload['filePath'];
                    }
                }
                unset($document);

                $contract['documents'] = $documents;
            }
            unset($contract);

            foreach ($request->file('contract_document_files', []) as $contractIndex => $documentFiles) {
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

                    $contract = $rows[$contractIndex] ?? [];
                    $contractId = Str::slug((string) ($contract['contractId'] ?? 'new-contract')) ?: 'new-contract';
                    $directory = 'fleet/contracts/'.$contractId.'/documents';
                    $storedPath = $file->store($directory, 'public');

                    if (! $storedPath) {
                        throw ValidationException::withMessages([
                            'contract_document_files' => 'A contract document could not be stored.',
                        ]);
                    }

                    $storedPaths[] = $storedPath;
                    $rows[$contractIndex]['documents'] ??= [];
                    $rows[$contractIndex]['documents'][$documentIndex] ??= [];
                    $rows[$contractIndex]['documents'][$documentIndex]['file'] = $uploads->permanentPayload($storedPath, [
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
            'rows' => $this->currentUserCanViewPage()
                ? $this->contractRows()
                : $this->syncResponseRows(FleetContract::class, $rows, $this->idKey),
            'can_view_list' => $this->currentUserCanViewPage(),
        ]);
    }

    private function validateContractRows(array $rows, Request $request, string $validateContractId): void
    {
        $errors = [];
        $documentReminders = $this->values('document_reminder');
        $vehicles = collect($this->contractVehicleOptions())->keyBy(fn (array $vehicle): string => (string) ($vehicle['id'] ?? ''));
        $drivers = collect($this->contractDriverOptions())->keyBy(fn (array $driver): string => (string) ($driver['id'] ?? ''));
        $availableDriverIds = $drivers
            ->filter(fn (array $driver): bool => strcasecmp((string) ($driver['status'] ?? ''), 'Active') === 0)
            ->keys()
            ->map(fn ($id): string => strtolower((string) $id))
            ->all();
        $shifts = collect($this->contractShiftOptions())->keyBy(fn (array $shift): string => (string) ($shift['id'] ?? ''));

        $matchedValidationTarget = $validateContractId === '';

        foreach ($rows as $contractIndex => $row) {
            if (! is_array($row)) {
                $errors["rows.{$contractIndex}"] = 'Each contract row must be a valid object.';
                continue;
            }

            if ($validateContractId === '' || (string) ($row['contractId'] ?? '') !== $validateContractId) {
                continue;
            }

            $matchedValidationTarget = true;
            $reservedDriverIds = $this->reservedDriverIdsForContract(
                (string) ($row['contractId'] ?? ''),
                (string) ($row['contractStart'] ?? ''),
                (string) ($row['contractEnd'] ?? '')
            );

            if (strcasecmp((string) ($row['savedAs'] ?? ''), 'Draft') === 0) {
                continue;
            }

            $validator = Validator::make($row, [
                'contractId' => ['required', 'string', 'max:100'],
                'contractWith' => ['required', 'in:Client,Vendor'],
                'partyId' => ['required', 'string', 'max:100'],
                'partyName' => ['required', 'string', 'max:255'],
                'amount' => ['required', 'numeric', 'gt:0'],
                'status' => ['required', 'in:Initiated,Active,Completed'],
                'contractStart' => ['required', 'date'],
                'contractEnd' => ['required', 'date', 'after_or_equal:contractStart'],
                'details' => ['required', 'string', 'max:5000'],
                'assignments' => ['required', 'array', 'min:1'],
                'assignments.*.shiftType' => ['nullable', Rule::in(['Single', 'Double'])],
                'assignments.*.driverId' => ['nullable', 'string', 'max:100'],
                'assignments.*.vehicleId' => ['required', 'string', 'max:100'],
                'assignments.*.rate' => ['required', 'numeric', 'gt:0'],
                'assignments.*.duty' => ['required', 'numeric', 'gt:0'],
                'assignments.*.drivers' => ['nullable', 'array', 'max:2'],
                'assignments.*.drivers.*.driverId' => ['nullable', 'string', 'max:100'],
                'assignments.*.drivers.*.shiftId' => ['nullable', 'string', 'max:120'],
                'documents' => ['required', 'array', 'min:1'],
                'documents.*.name' => ['required', 'string', 'max:255'],
                'documents.*.expiry' => ['nullable', 'date'],
                'documents.*.reminder' => ['nullable', Rule::in($documentReminders)],
                'documents.*.file' => ['nullable', 'array'],
            ], [
                'contractEnd.after_or_equal' => 'Contract End cannot be earlier than Contract Start.',
                'assignments.min' => 'At least one vehicle and driver assignment is required.',
                'documents.min' => 'At least one contract document is required.',
                'assignments.*.rate.gt' => 'Vehicle Hourly Rate must be greater than zero.',
                'assignments.*.duty.gt' => 'Vehicle Duty Hour/Daily must be greater than zero.',
            ]);

            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors["rows.{$contractIndex}.{$key}"] = $messages;
            }

            $usedVehicleIds = [];
            $usedDriverIds = [];
            foreach ((array) ($row['assignments'] ?? []) as $assignmentIndex => $assignment) {
                if (! is_array($assignment)) {
                    continue;
                }

                $vehicleId = trim((string) ($assignment['vehicleId'] ?? ''));
                $vehicle = $vehicles->get($vehicleId);
                $vehicleIsDouble = (bool) ($vehicle['isDoubleShift'] ?? false);
                $shiftType = $vehicleIsDouble ? 'Double' : 'Single';

                if ($vehicleId !== '' && ! $vehicle) {
                    $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.vehicleId"] = 'Select a valid saved vehicle.';
                }

                if ($vehicleId !== '' && in_array(strtolower($vehicleId), $usedVehicleIds, true)) {
                    $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.vehicleId"] = 'Each vehicle can be assigned only once in a contract.';
                }
                if ($vehicleId !== '') {
                    $usedVehicleIds[] = strtolower($vehicleId);
                }

                if ($shiftType === 'Single') {
                    $driverId = trim((string) ($assignment['driverId'] ?? data_get($assignment, 'drivers.0.driverId', '')));
                    if ($driverId === '') {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.driverId"] = 'Driver is required.';
                    } elseif (! $drivers->has($driverId)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.driverId"] = 'Select a valid saved driver.';
                    }

                    $assignedDriverId = trim((string) ($vehicle['assignedDriverId'] ?? ''));
                    if ($assignedDriverId !== '' && strtolower($driverId) !== strtolower($assignedDriverId)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.driverId"] = 'Use the driver currently assigned to this vehicle.';
                    } elseif ($assignedDriverId === '' && $driverId !== '' && ! in_array(strtolower($driverId), $availableDriverIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.driverId"] = 'Select an active available driver.';
                    }

                    if ($driverId !== '' && in_array(strtolower($driverId), $reservedDriverIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.driverId"] = 'This driver is already assigned to another active contract.';
                    } elseif ($driverId !== '' && in_array(strtolower($driverId), $usedDriverIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.driverId"] = 'This driver is already assigned elsewhere in this contract.';
                    }
                    if ($driverId !== '') {
                        $usedDriverIds[] = strtolower($driverId);
                    }

                    continue;
                }

                $driverAssignments = array_values(array_filter((array) ($assignment['drivers'] ?? []), 'is_array'));
                if (count($driverAssignments) !== 2) {
                    $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers"] = 'Double Shift requires exactly two drivers.';
                    continue;
                }

                if ($shifts->isEmpty()) {
                    $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers"] = 'Add active shifts from Master Data → Shifts before creating a double-shift assignment.';
                }

                $assignmentDriverIds = [];
                $assignmentShiftIds = [];
                foreach ($driverAssignments as $driverIndex => $driverAssignment) {
                    $driverId = trim((string) ($driverAssignment['driverId'] ?? ''));
                    $shiftId = trim((string) ($driverAssignment['shiftId'] ?? ''));

                    $vehicleAssignedDriverId = $driverIndex === 0
                        ? trim((string) ($vehicle['assignedDriverId'] ?? ''))
                        : trim((string) ($vehicle['assignedSecondDriverId'] ?? ''));

                    if ($driverId === '') {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.driverId"] = 'Driver is required for both shifts.';
                    } elseif (! $drivers->has($driverId)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.driverId"] = 'Select a valid saved driver.';
                    } elseif ($vehicleAssignedDriverId !== '' && strtolower($driverId) !== strtolower($vehicleAssignedDriverId)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.driverId"] = 'Use the driver currently assigned to this vehicle.';
                    } elseif ($vehicleAssignedDriverId === '' && ! in_array(strtolower($driverId), $availableDriverIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.driverId"] = 'Select an active available driver.';
                    } elseif (in_array(strtolower($driverId), $reservedDriverIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.driverId"] = 'This driver is already assigned to another active contract.';
                    }

                    if ($shiftId === '') {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.shiftId"] = 'Shift is required for both drivers.';
                    } elseif (! $shifts->has($shiftId)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.shiftId"] = 'Select a valid active shift.';
                    }

                    if ($driverId !== '' && in_array(strtolower($driverId), $assignmentDriverIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.driverId"] = 'The two shifts must use different drivers.';
                    }
                    if ($shiftId !== '' && in_array(strtolower($shiftId), $assignmentShiftIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.shiftId"] = 'Select a different shift for each driver.';
                    }
                    if ($driverId !== '' && in_array(strtolower($driverId), $usedDriverIds, true)) {
                        $errors["rows.{$contractIndex}.assignments.{$assignmentIndex}.drivers.{$driverIndex}.driverId"] = 'This driver is already assigned elsewhere in this contract.';
                    }

                    if ($driverId !== '') {
                        $assignmentDriverIds[] = strtolower($driverId);
                    }
                    if ($shiftId !== '') {
                        $assignmentShiftIds[] = strtolower($shiftId);
                    }
                }

                $usedDriverIds = array_values(array_unique(array_merge($usedDriverIds, $assignmentDriverIds)));
            }

            foreach ((array) ($row['documents'] ?? []) as $documentIndex => $document) {
                if (! is_array($document)) {
                    continue;
                }

                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                $hasFile = ! empty($file['tempToken'])
                    || ! empty($file['filePath'])
                    || ! empty($file['fileUrl'])
                    || ! empty($file['previewUrl'])
                    || $request->hasFile("contract_document_files.{$contractIndex}.{$documentIndex}");

                if (! $hasFile) {
                    $errors["rows.{$contractIndex}.documents.{$documentIndex}.file"] = 'Please upload the contract document before submitting.';
                }

                if ((int) ($file['sizeBytes'] ?? 0) > 4 * 1024 * 1024) {
                    $errors["rows.{$contractIndex}.documents.{$documentIndex}.file"] = 'Each contract document must not exceed 4 MB.';
                }
            }
        }

        if (! $matchedValidationTarget) {
            $errors['validateContractId'] = 'The contract selected for validation was not found.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function normalizeContractRows(array $rows): array
    {
        $vehicles = collect($this->contractVehicleOptions())->keyBy(fn (array $vehicle): string => (string) ($vehicle['id'] ?? ''));
        $drivers = collect($this->contractDriverOptions())->keyBy(fn (array $driver): string => (string) ($driver['id'] ?? ''));
        $shifts = collect($this->contractShiftOptions())->keyBy(fn (array $shift): string => (string) ($shift['id'] ?? ''));

        foreach ($rows as &$row) {
            if (! is_array($row)) {
                continue;
            }

            $normalizedAssignments = [];
            foreach ((array) ($row['assignments'] ?? []) as $assignment) {
                if (! is_array($assignment)) {
                    continue;
                }

                $vehicleId = trim((string) ($assignment['vehicleId'] ?? ''));
                $vehicle = $vehicles->get($vehicleId);
                $shiftType = (bool) ($vehicle['isDoubleShift'] ?? false) ? 'Double' : 'Single';

                $driverRows = $shiftType === 'Double'
                    ? array_values(array_filter((array) ($assignment['drivers'] ?? []), 'is_array'))
                    : [[
                        'driverId' => $assignment['driverId'] ?? data_get($assignment, 'drivers.0.driverId'),
                        'shiftId' => null,
                    ]];

                $normalizedDrivers = collect($driverRows)
                    ->take($shiftType === 'Double' ? 2 : 1)
                    ->map(function (array $driverRow) use ($drivers, $shifts): array {
                        $driverId = trim((string) ($driverRow['driverId'] ?? ''));
                        $driver = $drivers->get($driverId);
                        $shiftId = trim((string) ($driverRow['shiftId'] ?? ''));
                        $shift = $shifts->get($shiftId);

                        return [
                            'driverId' => $driverId,
                            'driver' => (string) ($driver['label'] ?? $driverRow['driver'] ?? $driverId),
                            'driverName' => (string) ($driver['name'] ?? $driverRow['driverName'] ?? $driverId),
                            'shiftId' => $shiftId !== '' ? $shiftId : null,
                            'shift' => $shiftId !== '' ? (string) ($shift['label'] ?? $driverRow['shift'] ?? $shiftId) : null,
                            'shiftName' => $shiftId !== '' ? (string) ($shift['name'] ?? $driverRow['shiftName'] ?? $shiftId) : null,
                            'shiftStartTime' => $shiftId !== '' ? (string) ($shift['startTime'] ?? '') : null,
                            'shiftEndTime' => $shiftId !== '' ? (string) ($shift['endTime'] ?? '') : null,
                        ];
                    })
                    ->values()
                    ->all();

                $primaryDriver = $normalizedDrivers[0] ?? [];
                $secondaryDriver = $normalizedDrivers[1] ?? [];
                $normalizedAssignments[] = array_merge($assignment, [
                    'shiftType' => $shiftType,
                    'vehicleId' => $vehicleId,
                    'vehicle' => (string) ($vehicle['label'] ?? $assignment['vehicle'] ?? $vehicleId),
                    'vehicleName' => (string) ($vehicle['name'] ?? $assignment['vehicleName'] ?? $vehicleId),
                    'vehicleUsage' => (string) ($vehicle['usage'] ?? $assignment['vehicleUsage'] ?? ''),
                    'drivers' => $normalizedDrivers,
                    // Keep the original single-driver fields for all existing
                    // reports, fuel and attendance integrations.
                    'driverId' => (string) ($primaryDriver['driverId'] ?? ''),
                    'driver' => (string) ($primaryDriver['driver'] ?? ''),
                    'driverName' => (string) ($primaryDriver['driverName'] ?? ''),
                    'shiftId' => $primaryDriver['shiftId'] ?? null,
                    'shift' => $primaryDriver['shift'] ?? null,
                    'secondDriverId' => (string) ($secondaryDriver['driverId'] ?? ''),
                    'secondDriver' => (string) ($secondaryDriver['driver'] ?? ''),
                    'secondDriverName' => (string) ($secondaryDriver['driverName'] ?? ''),
                    'secondShiftId' => $secondaryDriver['shiftId'] ?? null,
                    'secondShift' => $secondaryDriver['shift'] ?? null,
                ]);
            }

            $row['assignments'] = $normalizedAssignments;
        }
        unset($row);

        return $rows;
    }

    private function reservedDriverIdsForContract(string $contractId, string $startDate, string $endDate): array
    {
        return collect($this->contractDriverReservations())
            ->reject(fn (array $reservation): bool => strcasecmp((string) ($reservation['contractId'] ?? ''), $contractId) === 0)
            ->filter(function (array $reservation) use ($startDate, $endDate): bool {
                $reservedStart = trim((string) ($reservation['contractStart'] ?? ''));
                $reservedEnd = trim((string) ($reservation['contractEnd'] ?? ''));

                if ($startDate === '' || $endDate === '' || $reservedStart === '' || $reservedEnd === '') {
                    return true;
                }

                return $reservedStart <= $endDate && $reservedEnd >= $startDate;
            })
            ->flatMap(fn (array $reservation): array => (array) ($reservation['driverIds'] ?? []))
            ->map(fn ($id): string => strtolower(trim((string) $id)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function validateContractDocumentNames(array $rows, string $validateContractId): void
    {
        $errors = [];

        if ($validateContractId === '') {
            return;
        }

        foreach ($rows as $contractIndex => $row) {
            if (! is_array($row) || (string) ($row['contractId'] ?? '') !== $validateContractId) {
                continue;
            }

            $seen = [];
            foreach ((array) ($row['documents'] ?? []) as $documentIndex => $document) {
                $name = trim((string) (is_array($document) ? ($document['name'] ?? '') : ''));
                if ($name === '') {
                    continue;
                }

                $key = Str::lower($name);
                if (array_key_exists($key, $seen)) {
                    $errors["rows.{$contractIndex}.documents.{$documentIndex}.name"] = 'Each contract document name can be selected only once.';
                    $firstIndex = $seen[$key];
                    $errors["rows.{$contractIndex}.documents.{$firstIndex}.name"] = 'Each contract document name can be selected only once.';
                    continue;
                }

                $seen[$key] = $documentIndex;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function persistRows(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            $incomingCodes = collect($rows)
                ->map(fn (array $row) => (string) ($row[$this->idKey] ?? ''))
                ->filter()
                ->values();

            $this->deleteMissingRecords(
                FleetContract::query()->whereNotIn('status', ['fuel_recharge', 'attendance']),
                $incomingCodes
            );

            foreach ($rows as $row) {
                $code = (string) ($row[$this->idKey] ?? '');
                if ($code === '') {
                    continue;
                }

                /** @var Model $model */
                FleetContract::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $row[$this->nameKey] ?? $code,
                        'status' => $row['savedAs'] ?? ($row['status'] ?? null),
                        'payload' => $this->withoutRecordMetadata($row),
                    ]
                );
            }
        });
    }

    private function contractRows(): array
    {
        return FleetContract::query()
            ->whereNotIn('status', ['fuel_recharge', 'attendance'])
            ->latest('id')
            ->get()
            ->map(fn (FleetContract $row) => $row->payload ?? [])
            ->values()
            ->all();
    }
}
