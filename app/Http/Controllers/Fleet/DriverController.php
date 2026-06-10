<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDriver;
use App\Services\FleetTemporaryUploadService;
use App\Support\FleetDocumentUploadPolicy;
use Carbon\Carbon;
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

    public function sync(Request $request): JsonResponse
    {
        $uploads = app(FleetTemporaryUploadService::class);
        $rowsInput = $request->input('rows', []);
        $rows = is_string($rowsInput) ? json_decode($rowsInput, true) : $rowsInput;
        if (! is_array($rows)) {
            throw ValidationException::withMessages(['rows' => 'The driver rows payload is invalid.']);
        }

        $this->validateDriverRows($rows, $request);
        $this->validateUniqueDocumentNames($rows);
        $storedPaths = [];
        $userId = (int) $request->user()->id;

        try {
            foreach ($rows as $driverIndex => &$driver) {
                if (! is_array($driver)) {
                    continue;
                }

                $driverId = $this->driverFolderName($driver);
                $photo = $driver['photo'] ?? [];
                if (is_array($photo) && filled($photo['tempToken'] ?? null)) {
                    $driver['photo'] = $uploads->claim(
                        $photo,
                        $userId,
                        "fleet/drivers/{$driverId}/photo",
                        ['jpg', 'jpeg', 'png', 'webp'],
                        100,
                        true
                    );
                    $driver['photoName'] = $driver['photo']['originalName'];
                    $storedPaths[] = $driver['photo']['filePath'];
                }

                foreach (($driver['documents'] ?? []) as $documentIndex => $document) {
                    $file = is_array($document) ? ($document['file'] ?? []) : [];
                    if (is_array($file) && filled($file['tempToken'] ?? null)) {
                        $driver['documents'][$documentIndex]['file'] = $uploads->claim(
                            $file,
                            $userId,
                            "fleet/drivers/{$driverId}/documents",
                            FleetDocumentUploadPolicy::EXTENSIONS,
                            FleetDocumentUploadPolicy::MAX_KILOBYTES
                        );
                        $storedPaths[] = $driver['documents'][$documentIndex]['file']['filePath'];
                    }
                }
            }
            unset($driver);

            foreach ($request->file('driver_photo_files', []) as $driverIndex => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $validator = Validator::make(
                    ['photo' => $file],
                    ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:100']],
                    ['photo.max' => 'The driver photo must not exceed 100 KB.']
                );
                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $driverId = $this->driverFolderName($rows[$driverIndex] ?? []);
                $storedPath = $file->store("fleet/drivers/{$driverId}/photo", 'public');
                $storedPaths[] = $storedPath;
                $rows[$driverIndex]['photo'] = $uploads->permanentPayload($storedPath, [
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType' => $file->getClientMimeType(),
                    'sizeBytes' => $file->getSize(),
                ]);
                $rows[$driverIndex]['photoName'] = $file->getClientOriginalName();
            }

            foreach ($request->file('driver_document_files', []) as $driverIndex => $documentFiles) {
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

                    $driverId = $this->driverFolderName($rows[$driverIndex] ?? []);
                    $storedPath = $file->store("fleet/drivers/{$driverId}/documents", 'public');
                    $storedPaths[] = $storedPath;
                    $rows[$driverIndex]['documents'][$documentIndex]['file'] = $uploads->permanentPayload($storedPath, [
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

        return response()->json(['ok' => true, 'rows' => $this->recordsFor(FleetDriver::class)]);
    }

    private function validateDriverRows(array &$rows, Request $request): void
    {
        $errors = [];
        $seenIds = [];
        $seenNids = [];
        $seenLicences = [];
        $contactTypes = $this->driverContactTypeValues();
        $driverDocuments = $this->documentNameValues('Drivers', 'driver_document_template');
        $vendors = array_values(array_unique(array_merge(['Own Payroll'], $this->driverVendorValues())));
        $licenceTypes = $this->driverLicenseTypeValues();
        $salaryTenures = $this->values('driver_salary_tenure');
        $statuses = $this->values('driver_status');
        $duties = collect($this->choiceValues('driver_duty_type'))->pluck('value')->all();
        $reminders = $this->values('document_reminder');

        foreach ($rows as $index => &$row) {
            if (! is_array($row)) {
                $errors["rows.{$index}"] = 'Each driver row must be a valid object.';
                continue;
            }

            $driverId = trim((string) ($row['driverId'] ?? ''));
            $nid = trim((string) ($row['nid'] ?? ''));
            $licence = trim((string) ($row['licenseNo'] ?? ''));

            $this->trackUniqueDriverValue($seenIds, $errors, $driverId, "rows.{$index}.driverId", 'Driver ID');
            $this->trackUniqueDriverValue($seenNids, $errors, $nid, "rows.{$index}.nid", 'NID');
            $this->trackUniqueDriverValue($seenLicences, $errors, $licence, "rows.{$index}.licenseNo", 'Driving License No.');

            if ((int) ($row['driverValidationVersion'] ?? 0) < 1 || strcasecmp((string) ($row['status'] ?? ''), 'Draft') === 0) {
                continue;
            }

            if (filled($row['dob'] ?? null)) {
                try {
                    $row['age'] = Carbon::parse((string) $row['dob'])->age;
                } catch (Throwable) {
                    // The date validator below will provide the user-facing error.
                }
            }

            $validator = Validator::make($row, [
                'driverId' => ['required', 'string', 'max:100'],
                'fullName' => ['required', 'string', 'max:255'],
                'fatherName' => ['required', 'string', 'max:255'],
                'motherName' => ['required', 'string', 'max:255'],
                'whatsapp' => ['required', 'regex:/^\d{11}$/'],
                'email' => ['required', 'email:rfc', 'max:255'],
                'dob' => ['required', 'date', 'before_or_equal:today'],
                'age' => ['required', 'integer', 'between:0,120'],
                'nid' => ['required', 'regex:/^\d{1,17}$/'],
                'reference' => ['required', 'string', 'max:255'],
                'licenseNo' => ['required', 'regex:/^[A-Za-z0-9]{14,15}$/'],
                'licenseType' => ['required', Rule::in($licenceTypes)],
                'licenseValidity' => ['required', 'date', 'after_or_equal:today'],
                'salary' => ['required', 'numeric', 'min:0'],
                'salaryTenure' => ['required', Rule::in($salaryTenures)],
                'otRate' => ['required', 'numeric', 'min:0'],
                'workingHour' => ['required', 'numeric', 'gt:0'],
                'vendor' => ['required', Rule::in($vendors)],
                'status' => ['required', Rule::in($statuses)],
                'duty' => ['required', Rule::in($duties)],
                'presentAddress' => ['required', 'string', 'max:1500'],
                'permanentAddress' => ['required', 'string', 'max:1500'],
                'about' => ['required', 'string', 'max:2000'],
                'contacts' => ['required', 'array', 'min:1'],
                'contacts.*.type' => ['required', Rule::in($contactTypes)],
                'contacts.*.phone' => ['required', 'regex:/^\d{11}$/'],
                'documents' => ['required', 'array', 'min:1'],
                'documents.*.name' => ['required', Rule::in($driverDocuments)],
                'documents.*.expiry' => ['nullable', 'date'],
                'documents.*.reminder' => ['nullable', Rule::in($reminders)],
                'photo' => ['required', 'array'],
            ], [
                'whatsapp.regex' => 'WhatsApp Number must be exactly 11 digits.',
                'nid.regex' => 'NID must contain digits only and cannot exceed 17 digits.',
                'licenseNo.regex' => 'Driving License No. must be 14 or 15 alphanumeric characters.',
                'contacts.*.phone.regex' => 'Each contact phone number must be exactly 11 digits.',
                'licenseValidity.after_or_equal' => 'License Validity Date cannot be in the past.',
            ]);

            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors["rows.{$index}.{$key}"] = $messages;
            }

            if (! $validator->errors()->has('dob')) {
                $age = (int) ($row['age'] ?? 0);
                if ($age < 18 || $age > 80) {
                    $errors["rows.{$index}.dob"] = 'Driver age calculated from Date of Birth must be between 18 and 80 years.';
                }
            }
            foreach ((array) ($row['contacts'] ?? []) as $contactIndex => $contact) {
                if (! is_array($contact)) {
                    continue;
                }
                $type = trim((string) ($contact['type'] ?? ''));
                if (strcasecmp($type, 'Relative') === 0 && trim((string) ($contact['relationship'] ?? '')) === '') {
                    $errors["rows.{$index}.contacts.{$contactIndex}.relationship"] = 'Relationship is required for a Relative contact.';
                }
            }

            $photo = is_array($row['photo'] ?? null) ? $row['photo'] : [];
            $directPhoto = $request->file("driver_photo_files.{$index}");
            if (! $this->hasStoredFile($photo) && ! $directPhoto instanceof UploadedFile) {
                $errors["rows.{$index}.photo"] = 'Driver Photo is required.';
            } elseif ((int) ($photo['sizeBytes'] ?? 0) > 100 * 1024) {
                $errors["rows.{$index}.photo"] = 'Driver Photo must not exceed 100 KB.';
            }

            foreach ((array) ($row['documents'] ?? []) as $documentIndex => $document) {
                if (! is_array($document)) {
                    continue;
                }
                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                $directDocument = $request->file("driver_document_files.{$index}.{$documentIndex}");
                if (! $this->hasStoredFile($file) && ! $directDocument instanceof UploadedFile) {
                    $errors["rows.{$index}.documents.{$documentIndex}.file"] = 'Upload File is required for each driver document.';
                } elseif ((int) ($file['sizeBytes'] ?? 0) > 4 * 1024 * 1024) {
                    $errors["rows.{$index}.documents.{$documentIndex}.file"] = 'Each driver document must not exceed 4 MB.';
                }
            }
        }
        unset($row);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function trackUniqueDriverValue(array &$seen, array &$errors, string $value, string $errorKey, string $label): void
    {
        if ($value === '') {
            return;
        }

        $normalized = strtolower($value);
        if (isset($seen[$normalized])) {
            $errors[$errorKey] = "{$label} must be unique.";
        }

        $seen[$normalized] = true;
    }

    private function hasStoredFile(array $file): bool
    {
        return filled($file['tempToken'] ?? null)
            || filled($file['filePath'] ?? null)
            || filled($file['fileUrl'] ?? null);
    }

    private function driverFolderName(array $row): string
    {
        return Str::slug((string) ($row['driverId'] ?? 'new-driver')) ?: 'new-driver';
    }

    private function persistRows(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            $incomingCodes = collect($rows)->map(fn (array $row) => (string) ($row[$this->idKey] ?? ''))->filter()->values();
            $this->deleteMissingRecords(FleetDriver::query(), $incomingCodes);
            foreach ($rows as $row) {
                $code = (string) ($row[$this->idKey] ?? '');
                if ($code === '') {
                    continue;
                }
                FleetDriver::updateOrCreate(['code' => $code], [
                    'name' => $row[$this->nameKey] ?? $code,
                    'status' => $row[$this->statusKey] ?? null,
                    'payload' => $row,
                ]);
            }
        });
    }
}
