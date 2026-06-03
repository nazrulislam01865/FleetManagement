<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetContract;
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
     * Saves contract rows. Contract document files are stored only when the
     * user clicks Save Draft / Submit Contract, and folders are grouped by the
     * Contract ID so files can be retrieved later.
     */
    public function sync(Request $request): JsonResponse
    {
        if (! is_string($request->input('rows')) && ! $request->hasFile('contract_document_files')) {
            return parent::sync($request);
        }

        $rows = json_decode((string) $request->input('rows', '[]'), true);
        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'The contract rows payload is invalid.',
            ]);
        }

        $storedPaths = [];

        try {
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
                        ['document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240']]
                    );

                    if ($validator->fails()) {
                        throw new ValidationException($validator);
                    }

                    $contract = $rows[$contractIndex] ?? [];
                    $contractId = Str::slug((string) ($contract['contractId'] ?? 'new-contract')) ?: 'new-contract';
                    $directory = 'fleet/contracts/'.$contractId.'/documents';
                    $storedPath = $file->store($directory, 'public');
                    $storedPaths[] = $storedPath;

                    if (! isset($rows[$contractIndex]['documents']) || ! is_array($rows[$contractIndex]['documents'])) {
                        $rows[$contractIndex]['documents'] = [];
                    }

                    if (! isset($rows[$contractIndex]['documents'][$documentIndex]) || ! is_array($rows[$contractIndex]['documents'][$documentIndex])) {
                        $rows[$contractIndex]['documents'][$documentIndex] = [];
                    }

                    $rows[$contractIndex]['documents'][$documentIndex]['file'] = $this->filePayload($storedPath, $file);
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
