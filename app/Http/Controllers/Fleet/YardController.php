<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetEmployee;
use App\Models\Fleet\FleetYard;
use App\Models\Fleet\FleetVehicleCategory;
use App\Services\FleetTemporaryUploadService;
use App\Support\FleetDocumentUploadPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class YardController extends FleetBaseController
{
    protected string $activeMenu = 'yards';
    protected string $view = 'fleetman.yards';
    protected string $page = 'yards';
    protected string $resource = 'yards';
    protected string $idKey = 'yardId';
    protected string $nameKey = 'yardName';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetYard::class;

    public function index(): View
    {
        return view($this->view, $this->shared($this->activeMenu, [
            'page' => $this->page,
            'supervisors' => $this->supervisorOptions(),
            'yardVehicleCategories' => $this->yardVehicleCategories(),
            'yardDocumentReminders' => $this->values('document_reminder'),
            'nextYardId' => $this->nextYardId(),
        ]));
    }

    public function store(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $payload = $this->validatedPayload($request);
        $payload['yardId'] = $this->nextYardId();

        return $this->persistYard($request, $uploads, $payload);
    }

    public function update(Request $request, string $code, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $yard = FleetYard::query()->where('code', $code)->firstOrFail();
        $payload = $this->validatedPayload($request, $yard);
        $payload['yardId'] = $yard->code;

        return $this->persistYard($request, $uploads, $payload, $yard);
    }

    public function destroy(string $code): JsonResponse
    {
        $yard = FleetYard::query()->where('code', $code)->firstOrFail();
        $paths = $this->documentPaths($yard->payload['documents'] ?? []);

        DB::transaction(fn () => $yard->delete());
        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Yard deleted successfully.',
        ]);
    }

    private function validatedPayload(Request $request, ?FleetYard $yard = null): array
    {
        $savedAs = trim((string) $request->input('savedAs', 'Submitted'));
        $isDraft = strcasecmp($savedAs, 'Draft') === 0;
        $vehicleCategories = $this->yardVehicleCategories();
        $documentReminders = $this->values('document_reminder');

        $rules = [
            'yardName' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'supervisor' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'phone' => [$isDraft ? 'nullable' : 'required', 'nullable', 'regex:/^01\d{9}$/'],
            'secondaryPhone' => ['nullable', 'regex:/^01\d{9}$/'],
            'whatsapp' => ['nullable', 'regex:/^01\d{9}$/'],
            'parkingSlots' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'monthlyCharge' => [$isDraft ? 'nullable' : 'required', 'nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => [$isDraft ? 'nullable' : 'required', Rule::in(['Active', 'Inactive'])],
            'address' => [$isDraft ? 'nullable' : 'required', 'string', 'max:1500'],
            'city' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'savedAs' => ['required', Rule::in(['Draft', 'Submitted'])],
            'zones' => ['nullable', 'array', 'max:100'],
            'zones.*.name' => ['required_with:zones.*.capacity,zones.*.vehicleType', 'nullable', 'string', 'max:255'],
            'zones.*.vehicleType' => ['required_with:zones.*.name,zones.*.capacity', 'nullable', Rule::in($vehicleCategories)],
            'zones.*.capacity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'documents' => ['nullable', 'array', 'max:50'],
            'documents.*.name' => ['required_with:documents.*.file', 'nullable', 'string', 'max:255'],
            'documents.*.expiry' => ['nullable', 'date'],
            'documents.*.reminder' => ['nullable', Rule::in($documentReminders)],
            'documents.*.type' => ['nullable', Rule::in(['PDF', 'DOC', 'DOCX', 'XLS', 'XLSX'])],
            'documents.*.file' => ['nullable', 'array'],
        ];

        $validated = $request->validate($rules, [
            'phone.regex' => 'Phone Number must be a valid 11-digit Bangladesh mobile number.',
            'secondaryPhone.regex' => 'Secondary Contact must be a valid 11-digit Bangladesh mobile number.',
            'whatsapp.regex' => 'WhatsApp Number must be a valid 11-digit Bangladesh mobile number.',
            'zones.*.vehicleType.in' => 'Vehicle Type must be selected from the active Vehicle Categories in Master Data.',
            'zones.*.vehicleType.required_with' => 'Select a Vehicle Type from Master Data for each added zone.',
        ]);

        $zones = collect($validated['zones'] ?? [])
            ->filter(fn ($zone): bool => is_array($zone) && collect($zone)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->map(fn (array $zone): array => [
                'name' => trim((string) ($zone['name'] ?? '')),
                'vehicleType' => trim((string) ($zone['vehicleType'] ?? '')),
                'capacity' => (int) ($zone['capacity'] ?? 0),
            ])
            ->values()
            ->all();

        $parkingSlots = (int) ($validated['parkingSlots'] ?? 0);
        $zoneCapacity = collect($zones)->sum('capacity');
        if ($parkingSlots > 0 && $zoneCapacity > $parkingSlots) {
            throw ValidationException::withMessages([
                'zones' => 'The total zone capacity cannot be greater than the yard parking slots.',
            ]);
        }

        $documents = collect($validated['documents'] ?? [])
            ->filter(fn ($document): bool => is_array($document) && collect($document)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->map(function (array $document): array {
                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                if ($file === [] && trim((string) ($document['name'] ?? '')) !== '') {
                    throw ValidationException::withMessages([
                        'documents' => 'Upload a file for every document row that you add.',
                    ]);
                }

                return [
                    'name' => trim((string) ($document['name'] ?? '')),
                    'expiry' => trim((string) ($document['expiry'] ?? '')),
                    'reminder' => trim((string) ($document['reminder'] ?? '')),
                    'type' => strtoupper(trim((string) ($document['type'] ?? ''))),
                    'file' => $file,
                ];
            })
            ->values()
            ->all();

        $documentNames = collect($documents)
            ->pluck('name')
            ->filter()
            ->map(fn (string $name): string => strtolower($name));
        if ($documentNames->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'documents' => 'Each yard document name can be added only once.',
            ]);
        }

        $existingDocumentPaths = $this->documentPaths($yard?->payload['documents'] ?? []);
        foreach ($documents as $index => $document) {
            $file = $document['file'];
            $tempToken = trim((string) ($file['tempToken'] ?? ''));
            $filePath = trim((string) ($file['filePath'] ?? ''));
            $usesExistingFile = $tempToken === ''
                && $filePath !== ''
                && in_array($filePath, $existingDocumentPaths, true);

            if ($tempToken === '' && ! $usesExistingFile) {
                throw ValidationException::withMessages([
                    "documents.{$index}.file" => 'Upload a file for each added yard document.',
                ]);
            }

            $originalName = (string) ($file['originalName'] ?? $file['fileName'] ?? basename($filePath));
            if ($originalName !== '' && ! FleetDocumentUploadPolicy::extensionAllowed($originalName)) {
                throw ValidationException::withMessages([
                    "documents.{$index}.file" => 'Yard documents must be PDF, DOC, DOCX, XLS or XLSX files.',
                ]);
            }

            if ((int) ($file['sizeBytes'] ?? 0) > FleetDocumentUploadPolicy::MAX_BYTES) {
                throw ValidationException::withMessages([
                    "documents.{$index}.file" => 'Each yard document must not exceed 4 MB.',
                ]);
            }
        }

        return [
            'yardId' => $yard?->code,
            'yardName' => trim((string) ($validated['yardName'] ?? '')),
            'supervisor' => trim((string) ($validated['supervisor'] ?? '')),
            'phone' => trim((string) ($validated['phone'] ?? '')),
            'secondaryPhone' => trim((string) ($validated['secondaryPhone'] ?? '')),
            'whatsapp' => trim((string) ($validated['whatsapp'] ?? '')),
            'parkingSlots' => $parkingSlots,
            'monthlyCharge' => round((float) ($validated['monthlyCharge'] ?? 0), 2),
            'status' => $isDraft ? 'Draft' : (string) ($validated['status'] ?? 'Active'),
            'address' => trim((string) ($validated['address'] ?? '')),
            'city' => trim((string) ($validated['city'] ?? '')),
            'area' => trim((string) ($validated['area'] ?? '')),
            'remarks' => trim((string) ($validated['remarks'] ?? '')),
            'zones' => $zones,
            'documents' => $documents,
            'savedAs' => $isDraft ? 'Draft' : 'Submitted',
        ];
    }

    private function persistYard(
        Request $request,
        FleetTemporaryUploadService $uploads,
        array $payload,
        ?FleetYard $yard = null
    ): JsonResponse {
        $storedPaths = [];
        $oldPaths = $this->documentPaths($yard?->payload['documents'] ?? []);
        $yardFolder = Str::slug((string) $payload['yardId']) ?: 'yard';
        $userId = (int) $request->user()->id;

        try {
            foreach ($payload['documents'] as $index => $document) {
                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                if (filled($file['tempToken'] ?? null)) {
                    $payload['documents'][$index]['file'] = $uploads->claim(
                        $file,
                        $userId,
                        "fleet/yards/{$yardFolder}/documents",
                        FleetDocumentUploadPolicy::EXTENSIONS,
                        FleetDocumentUploadPolicy::MAX_KILOBYTES
                    );
                    $storedPaths[] = $payload['documents'][$index]['file']['filePath'];
                }

                $extension = strtoupper(pathinfo(
                    (string) ($payload['documents'][$index]['file']['originalName']
                        ?? $payload['documents'][$index]['file']['fileName']
                        ?? ''),
                    PATHINFO_EXTENSION
                ));
                if ($extension !== '') {
                    $payload['documents'][$index]['type'] = $extension;
                }
            }

            $yard = DB::transaction(function () use ($yard, $payload): FleetYard {
                if ($yard) {
                    $yard->update([
                        'name' => $payload['yardName'] ?: $payload['yardId'],
                        'status' => $payload['status'],
                        'payload' => $payload,
                    ]);

                    return $yard->refresh();
                }

                return FleetYard::query()->create([
                    'code' => $payload['yardId'],
                    'name' => $payload['yardName'] ?: $payload['yardId'],
                    'status' => $payload['status'],
                    'payload' => $payload,
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }
            throw $exception;
        }

        $newPaths = $this->documentPaths($yard->payload['documents'] ?? []);
        $removedPaths = array_values(array_diff($oldPaths, $newPaths));
        if ($removedPaths !== []) {
            Storage::disk('public')->delete($removedPaths);
        }

        return response()->json([
            'ok' => true,
            'message' => $yard->wasRecentlyCreated ? 'Yard created successfully.' : 'Yard updated successfully.',
            'record' => $yard->payload,
            'nextYardId' => $this->nextYardId(),
        ]);
    }

    private function nextYardId(): string
    {
        $lastNumber = FleetYard::query()
            ->pluck('code')
            ->map(function ($code): int {
                return preg_match('/^YRD(\d+)$/i', (string) $code, $matches) === 1
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        do {
            $lastNumber++;
            $code = 'YRD'.str_pad((string) $lastNumber, 5, '0', STR_PAD_LEFT);
        } while (FleetYard::query()->where('code', $code)->exists());

        return $code;
    }

    private function yardVehicleCategories(): array
    {
        return FleetVehicleCategory::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name): string => trim((string) $name))
            ->filter()
            ->unique(fn (string $name): string => strtolower($name))
            ->values()
            ->all();
    }

    private function supervisorOptions(): array
    {
        return FleetEmployee::query()
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', 'Active');
            })
            ->orderBy('name')
            ->get()
            ->map(fn (FleetEmployee $employee): string => trim((string) ($employee->payload['fullName'] ?? $employee->name ?? '')))
            ->filter()
            ->unique(fn (string $name): string => strtolower($name))
            ->values()
            ->all();
    }

    private function documentPaths(array $documents): array
    {
        return collect($documents)
            ->map(fn ($document) => is_array($document) ? data_get($document, 'file.filePath') : null)
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->values()
            ->all();
    }
}
