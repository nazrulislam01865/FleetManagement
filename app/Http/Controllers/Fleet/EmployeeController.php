<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetEmployee;
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

class EmployeeController extends FleetBaseController
{
    protected string $activeMenu = 'employees';
    protected string $view = 'fleetman.employees';
    protected string $page = 'employees';
    protected string $resource = 'employees';
    protected string $idKey = 'employeeId';
    protected string $nameKey = 'fullName';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetEmployee::class;

    /**
     * Saves employee rows. Employee photo and document files are stored only
     * inside this save request, under a folder named with the employee ID.
     */
    public function sync(Request $request): JsonResponse
    {
        if (
            ! is_string($request->input('rows'))
            && ! $request->hasFile('employee_document_files')
            && ! $request->hasFile('employee_photo_files')
        ) {
            return parent::sync($request);
        }

        $rows = json_decode((string) $request->input('rows', '[]'), true);
        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The employee rows payload is invalid.',
            ]);
        }

        $storedPaths = [];

        try {
            foreach ($request->file('employee_photo_files', []) as $employeeIndex => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $validator = Validator::make(
                    ['photo' => $file],
                    ['photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120']]
                );

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $employeeId = $this->employeeFolderName($rows[$employeeIndex] ?? []);
                $storedPath = $file->store("fleet/employees/{$employeeId}/photo", 'public');
                $storedPaths[] = $storedPath;

                $rows[$employeeIndex]['photo'] = [
                    'filePath' => $storedPath,
                    'fileUrl' => Storage::disk('public')->url($storedPath),
                    'fileName' => basename($storedPath),
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType' => $file->getClientMimeType(),
                    'sizeBytes' => $file->getSize(),
                    'uploadedAt' => now()->toDateTimeString(),
                ];
                $rows[$employeeIndex]['photoName'] = $file->getClientOriginalName();
            }

            foreach ($request->file('employee_document_files', []) as $employeeIndex => $documentFiles) {
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

                    $employeeId = $this->employeeFolderName($rows[$employeeIndex] ?? []);
                    $storedPath = $file->store("fleet/employees/{$employeeId}/documents", 'public');
                    $storedPaths[] = $storedPath;

                    if (! isset($rows[$employeeIndex]['documents']) || ! is_array($rows[$employeeIndex]['documents'])) {
                        $rows[$employeeIndex]['documents'] = [];
                    }

                    if (! isset($rows[$employeeIndex]['documents'][$documentIndex]) || ! is_array($rows[$employeeIndex]['documents'][$documentIndex])) {
                        $rows[$employeeIndex]['documents'][$documentIndex] = [];
                    }

                    $rows[$employeeIndex]['documents'][$documentIndex]['file'] = [
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
            'rows' => $this->recordsFor(FleetEmployee::class),
        ]);
    }

    private function employeeFolderName(array $row): string
    {
        return Str::slug((string) ($row['employeeId'] ?? 'new-employee')) ?: 'new-employee';
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
