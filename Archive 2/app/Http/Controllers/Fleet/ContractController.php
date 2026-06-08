<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetContract;
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
                            ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
                            4096
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
                        ['document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:4096']]
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
            'rows' => $this->contractRows(),
        ]);
    }

    private function validateContractRows(array $rows, Request $request, string $validateContractId): void
    {
        $errors = [];

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
                'assignments.*.driverId' => ['required', 'string', 'max:100'],
                'assignments.*.vehicleId' => ['required', 'string', 'max:100'],
                'assignments.*.rate' => ['required', 'numeric', 'gt:0'],
                'assignments.*.duty' => ['required', 'numeric', 'gt:0'],
                'documents' => ['required', 'array', 'min:1'],
                'documents.*.name' => ['required', 'string', 'max:255'],
                'documents.*.expiry' => ['required', 'date'],
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

            FleetContract::query()
                ->whereNotIn('status', ['fuel_recharge', 'attendance'])
                ->whereNotIn('code', $incomingCodes)
                ->delete();

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
                        'payload' => $row,
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
