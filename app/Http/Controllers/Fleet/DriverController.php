<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends FleetBaseController
{
    protected string $activeMenu = 'drivers';
    protected string $view = 'fleetman.drivers';
    protected string $page = 'drivers';
    protected string $resource = 'drivers';
    protected string $idKey = 'driverId';
    protected string $nameKey = 'fullName';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetDriver::class;
    /**
     * Saves driver rows. Driver photo and document files are stored only
     * inside this save request, under a folder named with the driver ID.
     */
    public function sync(Request $request): JsonResponse
    {
        if (
            ! is_string($request->input('rows'))
            && ! $request->hasFile('driver_document_files')
            && ! $request->hasFile('driver_photo_files')
        ) {
            return parent::sync($request);
        }

        $rows = json_decode((string) $request->input('rows', '[]'), true);
        if (! is_array($rows)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'rows' => 'The driver rows payload is invalid.',
            ]);
        }

        $storedPaths = [];

        try {
            foreach ($request->file('driver_photo_files', []) as $driverIndex => $file) {
                if (! $file instanceof \Illuminate\Http\UploadedFile) {
                    continue;
                }

                $validator = \Illuminate\Support\Facades\Validator::make(
                    ['photo' => $file],
                    ['photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120']]
                );

                if ($validator->fails()) {
                    throw new \Illuminate\Validation\ValidationException($validator);
                }

                $driverId = $this->driverFolderName($rows[$driverIndex] ?? []);
                $storedPath = $file->store("fleet/drivers/{$driverId}/photo", 'public');
                $storedPaths[] = $storedPath;

                $rows[$driverIndex]['photo'] = [
                    'filePath' => $storedPath,
                    'fileUrl' => \Illuminate\Support\Facades\Storage::disk('public')->url($storedPath),
                    'fileName' => basename($storedPath),
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType' => $file->getClientMimeType(),
                    'sizeBytes' => $file->getSize(),
                    'uploadedAt' => now()->toDateTimeString(),
                ];
                $rows[$driverIndex]['photoName'] = $file->getClientOriginalName();
            }

            foreach ($request->file('driver_document_files', []) as $driverIndex => $documentFiles) {
                if (! is_array($documentFiles)) {
                    continue;
                }

                foreach ($documentFiles as $documentIndex => $file) {
                    if (! $file instanceof \Illuminate\Http\UploadedFile) {
                        continue;
                    }

                    $validator = \Illuminate\Support\Facades\Validator::make(
                        ['document' => $file],
                        ['document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']]
                    );

                    if ($validator->fails()) {
                        throw new \Illuminate\Validation\ValidationException($validator);
                    }

                    $driverId = $this->driverFolderName($rows[$driverIndex] ?? []);
                    $storedPath = $file->store("fleet/drivers/{$driverId}/documents", 'public');
                    $storedPaths[] = $storedPath;

                    if (! isset($rows[$driverIndex]['documents']) || ! is_array($rows[$driverIndex]['documents'])) {
                        $rows[$driverIndex]['documents'] = [];
                    }

                    if (! isset($rows[$driverIndex]['documents'][$documentIndex]) || ! is_array($rows[$driverIndex]['documents'][$documentIndex])) {
                        $rows[$driverIndex]['documents'][$documentIndex] = [];
                    }

                    $rows[$driverIndex]['documents'][$documentIndex]['file'] = [
                        'filePath' => $storedPath,
                        'fileUrl' => \Illuminate\Support\Facades\Storage::disk('public')->url($storedPath),
                        'fileName' => basename($storedPath),
                        'originalName' => $file->getClientOriginalName(),
                        'mimeType' => $file->getClientMimeType(),
                        'sizeBytes' => $file->getSize(),
                        'uploadedAt' => now()->toDateTimeString(),
                    ];
                }
            }

            $this->persistRows($rows);
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return response()->json([
            'ok' => true,
            'rows' => $this->recordsFor(FleetDriver::class),
        ]);
    }

    private function driverFolderName(array $row): string
    {
        return \Illuminate\Support\Str::slug((string) ($row['driverId'] ?? 'new-driver')) ?: 'new-driver';
    }

    private function persistRows(array $rows): void
    {
        $modelClass = $this->modelClass;
        $idKey = $this->idKey;
        $nameKey = $this->nameKey;
        $statusKey = $this->statusKey;

        \Illuminate\Support\Facades\DB::transaction(function () use ($modelClass, $rows, $idKey, $nameKey, $statusKey) {
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