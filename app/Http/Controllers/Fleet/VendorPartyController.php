<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetVendorParty;
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

class VendorPartyController extends FleetBaseController
{
    protected string $activeMenu = 'vendors';
    protected string $view = 'fleetman.vendor-parties';
    protected string $page = 'vendors';
    protected string $resource = 'parties';
    protected string $idKey = 'partyId';
    protected string $nameKey = 'partyName';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetVendorParty::class;

    /**
     * Files selected in the browser are first kept in private temporary storage.
     * They are moved to permanent vendor storage only after this save succeeds.
     */
    public function sync(Request $request): JsonResponse
    {
        $uploads = app(FleetTemporaryUploadService::class);
        $inputRows = $request->input('rows', []);
        $rows = is_string($inputRows) ? json_decode($inputRows, true) : $inputRows;
        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The vendor / party rows payload is invalid.',
            ]);
        }

        $this->validateVendorRows($rows, $request);
        $this->validateFuelStationRows($rows);
        $this->validateUniqueDocumentNames($rows);

        $storedPaths = [];
        $userId = (int) $request->user()->id;

        try {
            foreach ($rows as $partyIndex => &$party) {
                if (! is_array($party)) {
                    continue;
                }
                $partyId = Str::slug((string) ($party['partyId'] ?? 'new-party')) ?: 'new-party';
                foreach (($party['documents'] ?? []) as $documentIndex => $document) {
                    if (! is_array($document)) {
                        continue;
                    }
                    $file = $document['file'] ?? [];
                    if (is_array($file) && filled($file['tempToken'] ?? null)) {
                        $party['documents'][$documentIndex]['file'] = $uploads->claim(
                            $file,
                            $userId,
                            'fleet/vendor-party-documents/'.$partyId.'/'.now()->format('Y/m'),
                            FleetDocumentUploadPolicy::EXTENSIONS,
                            FleetDocumentUploadPolicy::MAX_KILOBYTES
                        );
                        $storedPaths[] = $party['documents'][$documentIndex]['file']['filePath'];
                    }
                }
            }
            unset($party);

            foreach ($request->file('document_files', []) as $partyIndex => $documentFiles) {
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

                    $party = $rows[$partyIndex] ?? [];
                    $partyId = Str::slug((string) ($party['partyId'] ?? 'new-party')) ?: 'new-party';
                    $directory = 'fleet/vendor-party-documents/'.$partyId.'/'.now()->format('Y/m');
                    $storedPath = $file->store($directory, 'public');
                    $storedPaths[] = $storedPath;

                    if (! isset($rows[$partyIndex]['documents']) || ! is_array($rows[$partyIndex]['documents'])) {
                        $rows[$partyIndex]['documents'] = [];
                    }

                    if (! isset($rows[$partyIndex]['documents'][$documentIndex]) || ! is_array($rows[$partyIndex]['documents'][$documentIndex])) {
                        $rows[$partyIndex]['documents'][$documentIndex] = [];
                    }

                    $rows[$partyIndex]['documents'][$documentIndex]['file'] = $uploads->permanentPayload($storedPath, [
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
            'rows' => $this->recordsFor(FleetVendorParty::class),
        ]);
    }

    private function validateVendorRows(array $rows, Request $request): void
    {
        $errors = [];
        $partyTypes = $this->partyTypeValues();
        $statuses = $this->values('party_status');
        $paymentTerms = $this->values('payment_term');
        $vendorContractorTypes = $this->vendorContractorTypeValues();
        $documentNames = $this->documentNameValues(['Vendors', 'Vendors & Parties'], 'party_document_template');
        $documentReminders = $this->values('document_reminder');

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $errors["rows.{$index}"] = 'Each vendor / party row must be a valid object.';
                continue;
            }

            // Existing rows created before these rules remain readable. The
            // version is added whenever the updated form creates or edits a row.
            if ((int) ($row['vendorValidationVersion'] ?? 0) < 1 || strcasecmp((string) ($row['status'] ?? ''), 'Draft') === 0) {
                continue;
            }

            $version = (int) ($row['vendorValidationVersion'] ?? 0);
            $validator = Validator::make($row, [
                'partyId' => ['required', 'string', 'max:100'],
                'partyName' => ['required', 'string', 'max:255'],
                'partyType' => ['required', Rule::in($partyTypes)],
                'vendorContractorType' => [$version >= 2 ? 'required' : 'nullable', Rule::in($vendorContractorTypes)],
                'status' => ['required', Rule::in($statuses)],
                'phone' => ['required', 'regex:/^\d{11}$/'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'whatsapp' => ['nullable', 'regex:/^\d{11}$/'],
                'tradeLicense' => ['nullable', 'regex:/^\d+$/', 'max:100'],
                'tinBin' => ['nullable', 'string', 'max:100'],
                'paymentTerms' => ['nullable', Rule::in($paymentTerms)],
                'address' => ['required', 'string', 'max:1500'],
                'about' => ['nullable', 'string', 'max:2000'],
                'contacts' => ['required', 'array', 'min:1'],
                'contacts.*.name' => ['required', 'string', 'max:255'],
                'contacts.*.role' => ['nullable', 'string', 'max:255'],
                'contacts.*.phone' => ['required', 'regex:/^\d{11}$/'],
                'contacts.*.email' => ['nullable', 'email:rfc', 'max:255'],
                'contacts.*.whatsapp' => ['nullable', 'regex:/^\d{11}$/'],
                'documents' => ['required', 'array', 'min:1'],
                'documents.*.name' => ['required', Rule::in($documentNames)],
                'documents.*.number' => ['nullable', 'string', 'max:255'],
                'documents.*.expiry' => ['nullable', 'date'],
                'documents.*.reminder' => ['nullable', Rule::in($documentReminders)],
            ], [
                'phone.regex' => 'Phone Number must be exactly 11 digits.',
                'whatsapp.regex' => 'WhatsApp Number must be exactly 11 digits.',
                'email.email' => 'Enter a valid vendor email address.',
                'tradeLicense.regex' => 'Trade License No. must contain digits only.',
                'vendorContractorType.required' => 'Vendor / Contractor Type is required.',
                'vendorContractorType.in' => 'Select a valid Vendor / Contractor Type.',
                'contacts.*.phone.regex' => 'Each contact phone number must be exactly 11 digits.',
                'contacts.*.email.email' => 'Enter a valid contact-person email address.',
                'contacts.*.whatsapp.regex' => 'Each contact-person WhatsApp number must be exactly 11 digits.',
            ]);

            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors["rows.{$index}.{$key}"] = $messages;
            }

            foreach ((array) ($row['documents'] ?? []) as $documentIndex => $document) {
                if (! is_array($document)) {
                    continue;
                }

                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                $directFile = $request->file("document_files.{$index}.{$documentIndex}");
                if (! $this->hasStoredVendorFile($file) && ! $directFile instanceof UploadedFile) {
                    $errors["rows.{$index}.documents.{$documentIndex}.file"] = 'Upload File is required for each vendor document.';
                } elseif ((int) ($file['sizeBytes'] ?? 0) > 4 * 1024 * 1024) {
                    $errors["rows.{$index}.documents.{$documentIndex}.file"] = 'Each vendor document must not exceed 4 MB.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function hasStoredVendorFile(array $file): bool
    {
        return filled($file['tempToken'] ?? null)
            || filled($file['filePath'] ?? null)
            || filled($file['fileUrl'] ?? null)
            || filled($file['previewUrl'] ?? null);
    }

    private function validateFuelStationRows(array $rows): void
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $errors["rows.{$index}"] = 'Each vendor / party row must be a valid object.';
                continue;
            }

            // Legacy vendor rows are kept readable and syncable. The version
            // is added whenever a vendor is created or edited in the updated form.
            if ((int) ($row['fuelStationCapabilityVersion'] ?? 0) < 1) {
                continue;
            }

            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if ($status === 'draft') {
                continue;
            }

            $name = trim((string) ($row['partyName'] ?? $row['name'] ?? ''));
            $type = trim((string) ($row['partyType'] ?? $row['type'] ?? ''));
            $about = trim((string) ($row['about'] ?? $row['description'] ?? ''));
            $combined = strtolower($name.' '.$type.' '.$about);
            $isFuelStation = preg_match('/fuel|station|petrol|octane|octen|diesel|cng|lpg|gas/i', $combined) === 1;

            if (! $isFuelStation) {
                continue;
            }

            $fuelTypes = $row['fuelTypes'] ?? $row['supportedFuelTypes'] ?? $row['fuelsSold'] ?? [];
            if (! is_array($fuelTypes) || collect($fuelTypes)->filter(fn ($fuel): bool => filled(is_array($fuel) ? ($fuel['type'] ?? $fuel['name'] ?? null) : $fuel))->isEmpty()) {
                $errors["rows.{$index}.fuelTypes"] = "Select at least one fuel type sold by {$name}.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function uploadDocument(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $validated = $request->validate([
            'document' => FleetDocumentUploadPolicy::rules(),
            'party_id' => ['nullable', 'string', 'max:80'],
            'document_name' => ['nullable', 'string', 'max:160'],
        ], FleetDocumentUploadPolicy::messages('document'));

        $file = $validated['document'];
        $partyId = Str::slug($validated['party_id'] ?? 'new-party') ?: 'new-party';
        $directory = 'fleet/vendor-party-documents/'.$partyId.'/'.now()->format('Y/m');
        $storedPath = $file->store($directory, 'public');

        return response()->json([
            'ok' => true,
            'file' => array_merge($uploads->permanentPayload($storedPath, [
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getClientMimeType(),
                'sizeBytes' => $file->getSize(),
            ]), [
                'documentName' => $validated['document_name'] ?? null,
            ]),
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

            $modelClass::query()->whereNotIn('code', $incomingCodes)->delete();

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
                        'payload' => $row,
                    ]
                );
            }
        });
    }
}
