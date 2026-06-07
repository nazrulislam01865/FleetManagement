<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetVendorParty;
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

        $this->validateVendorContractorTypes($rows);
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
                            ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
                            5120
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
                        ['document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']]
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

    private function validateVendorContractorTypes(array $rows): void
    {
        $errors = [];
        $allowed = config('fleetman.options.vendor_contractor_types', ['Car Related', 'Non-Car Related']);

        foreach ($rows as $index => $row) {
            if (! is_array($row) || (int) ($row['vendorTypeVersion'] ?? 0) < 1) {
                continue;
            }

            if (strcasecmp(trim((string) ($row['status'] ?? '')), 'Draft') === 0) {
                continue;
            }

            $type = trim((string) ($row['vendorContractorType'] ?? ''));
            if (! in_array($type, $allowed, true)) {
                $errors["rows.{$index}.vendorContractorType"] = 'Select a valid Vendor / Contractor Type.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'party_id' => ['nullable', 'string', 'max:80'],
            'document_name' => ['nullable', 'string', 'max:160'],
        ]);

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
