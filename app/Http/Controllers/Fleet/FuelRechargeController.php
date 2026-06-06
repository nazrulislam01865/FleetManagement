<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetFuelRecharge;
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
     * Saves fuel recharge rows. Camera photos are stored only after Save Draft /
     * Submit and are grouped by recharge ID for retrieval.
     */
    public function sync(Request $request): JsonResponse
    {
        if (! is_string($request->input('rows')) && ! $request->hasFile('fuel_recharge_photos')) {
            return parent::sync($request);
        }

        $rows = json_decode((string) $request->input('rows', '[]'), true);
        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The fuel recharge rows payload is invalid.',
            ]);
        }

        $storedPaths = [];

        try {
            foreach ($request->file('fuel_recharge_photos', []) as $rowIndex => $photos) {
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

                    $rows[$rowIndex]['photos'][$photoKey]['file'] = $this->filePayload($storedPath, $file);
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

    private function persistRows(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            $incomingCodes = collect($rows)
                ->map(fn (array $row) => (string) ($row[$this->idKey] ?? ''))
                ->filter()
                ->values();

            FleetFuelRecharge::query()->whereNotIn('code', $incomingCodes)->delete();

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
                                ]
                            ]
                        );
                    }
                }
            }
        });
    }
}
