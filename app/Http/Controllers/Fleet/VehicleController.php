<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetVehicle;
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
     * Saves vehicle rows. Vehicle images and document files are uploaded only
     * when Save Vehicle is clicked, and are stored under the vehicle ID.
     */
    public function sync(Request $request): JsonResponse
    {
        if (! is_string($request->input('rows')) && ! $request->hasFile('vehicle_image_files') && ! $request->hasFile('vehicle_document_files')) {
            return parent::sync($request);
        }

        $rows = json_decode((string) $request->input('rows', '[]'), true);
        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The vehicle rows payload is invalid.',
            ]);
        }

        $storedPaths = [];

        try {
            foreach ($request->file('vehicle_image_files', []) as $vehicleIndex => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $validator = Validator::make(
                    ['image' => $file],
                    ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]
                );

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $vehicle = $rows[$vehicleIndex] ?? [];
                $vehicleId = $this->safeVehicleId((string) ($vehicle['id'] ?? 'new-vehicle'));
                $directory = 'fleet/vehicles/'.$vehicleId.'/images';
                $storedPath = $file->store($directory, 'public');
                $storedPaths[] = $storedPath;

                $rows[$vehicleIndex]['image'] = $this->filePayload($storedPath, $file);
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
                        ['document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']]
                    );

                    if ($validator->fails()) {
                        throw new ValidationException($validator);
                    }

                    $vehicle = $rows[$vehicleIndex] ?? [];
                    $vehicleId = $this->safeVehicleId((string) ($vehicle['id'] ?? 'new-vehicle'));
                    $directory = 'fleet/vehicles/'.$vehicleId.'/documents';
                    $storedPath = $file->store($directory, 'public');
                    $storedPaths[] = $storedPath;

                    if (! isset($rows[$vehicleIndex]['docs']) || ! is_array($rows[$vehicleIndex]['docs'])) {
                        $rows[$vehicleIndex]['docs'] = [];
                    }

                    if (! isset($rows[$vehicleIndex]['docs'][$documentIndex]) || ! is_array($rows[$vehicleIndex]['docs'][$documentIndex])) {
                        $rows[$vehicleIndex]['docs'][$documentIndex] = [];
                    }

                    $rows[$vehicleIndex]['docs'][$documentIndex]['file'] = $this->filePayload($storedPath, $file);
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

    private function filePayload(string $storedPath, UploadedFile $file): array
    {
        return [
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
