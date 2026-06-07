<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetVehicle;
use App\Services\FleetTemporaryUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

        $this->validateUniqueDocumentNames($rows, 'docs');
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
                            ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
                            4096
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
                        ['document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096']],
                        ['document.max' => 'Each vehicle document must not exceed 4 MB.']
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

    private function safeVehicleId(string $vehicleId): string
    {
        $safeId = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($vehicleId));

        return trim((string) $safeId, '-') ?: 'new-vehicle';
    }
}
