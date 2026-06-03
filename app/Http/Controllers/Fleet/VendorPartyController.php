<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetVendorParty;
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
     * Saves Vendor / Party rows. Document files are stored only inside this save
     * request, so choosing a file does not create a storage file by itself.
     */
    public function sync(Request $request): JsonResponse
    {
        if (! is_string($request->input('rows')) && ! $request->hasFile('document_files')) {
            return parent::sync($request);
        }

        $rows = json_decode((string) $request->input('rows', '[]'), true);
        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The vendor / party rows payload is invalid.',
            ]);
        }

        $storedPaths = [];

        try {
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

                    $rows[$partyIndex]['documents'][$documentIndex]['file'] = [
                        'filePath' => $storedPath,
                        'fileUrl' => Storage::disk('public')->url($storedPath),
                        'fileName' => basename($storedPath),
                        'originalName' => $file->getClientOriginalName(),
                        'mimeType' => $file->getClientMimeType(),
                        'sizeBytes' => $file->getSize(),
                        'uploadedAt' => now()->toDateTimeString(),
                    ];
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

    public function uploadDocument(Request $request): JsonResponse
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
            'file' => [
                'filePath' => $storedPath,
                'fileUrl' => Storage::disk('public')->url($storedPath),
                'fileName' => basename($storedPath),
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getClientMimeType(),
                'sizeBytes' => $file->getSize(),
                'documentName' => $validated['document_name'] ?? null,
                'uploadedAt' => now()->toDateTimeString(),
            ],
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
