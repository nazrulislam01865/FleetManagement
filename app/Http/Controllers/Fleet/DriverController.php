<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetDriver;
use App\Services\FleetTemporaryUploadService;
use App\Support\FleetDocumentUploadPolicy;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $storedPaths = [];
        $temporaryTokens = [];

        try {
            $uploads = app(FleetTemporaryUploadService::class);
            $rowsInput = $request->input('rows', []);
            $rows = is_string($rowsInput) ? json_decode($rowsInput, true) : $rowsInput;

            if (! is_array($rows)) {
                throw ValidationException::withMessages([
                    'rows' => 'The driver data sent to the server is invalid. Please refresh the page and try again.',
                ]);
            }

            $strictValidationIndexes = $this->changedRowIndexesForSync(
                $rows,
                FleetDriver::class,
                $this->idKey
            );

            $this->validateDriverRows($rows, $request, $strictValidationIndexes);
            $this->validateUniqueDocumentNames(
                $this->syncRowsAtIndexes($rows, $strictValidationIndexes)
            );

            $userId = (int) $request->user()->id;
            $temporaryTokens = $this->validateDriverTemporaryUploads($rows, $uploads, $userId);

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
                        true,
                        false
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
                            FleetDocumentUploadPolicy::MAX_KILOBYTES,
                            false,
                            false
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
                    ['photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:100']],
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
                    $rows[$driverIndex]['documents'][$documentIndex]['file'] = $uploads->permanentPayload(
                        $storedPath,
                        [
                            'originalName' => $file->getClientOriginalName(),
                            'mimeType' => $file->getClientMimeType(),
                            'sizeBytes' => $file->getSize(),
                        ]
                    );
                }
            }

            $this->persistRows($rows);
            $savedRows = $this->syncResponseRows(FleetDriver::class, $rows, $this->idKey);

            // Consume temporary uploads only after every file and database row
            // has been saved successfully. Failed saves therefore remain retryable.
            $this->deleteDriverTemporaryUploads($uploads, $temporaryTokens, $userId);

            return response()->json([
                'ok' => true,
                'message' => 'Driver saved successfully.',
                'rows' => $savedRows,
                'can_view_list' => $this->currentUserCanViewPage(),
            ]);
        } catch (ValidationException $exception) {
            $this->deleteStoredDriverFiles($storedPaths);
            throw $exception;
        } catch (Throwable $exception) {
            $this->deleteStoredDriverFiles($storedPaths);

            return $this->driverSyncFailureResponse($request, $exception);
        }
    }

    /**
     * Validate every temporary file before copying any of them. This prevents a
     * stale photo/document token from causing a partially finalized driver save.
     *
     * @return array<int, string>
     */
    private function validateDriverTemporaryUploads(
        array $rows,
        FleetTemporaryUploadService $uploads,
        int $userId
    ): array {
        $errors = [];
        $tokens = [];

        foreach ($rows as $driverIndex => $driver) {
            if (! is_array($driver)) {
                continue;
            }

            $photo = is_array($driver['photo'] ?? null) ? $driver['photo'] : [];
            $photoToken = trim((string) ($photo['tempToken'] ?? ''));

            if ($photoToken !== '') {
                try {
                    $uploads->validateClaim($photo, $userId);
                    $tokens[] = $photoToken;
                } catch (ValidationException) {
                    $errors["rows.{$driverIndex}.photo"] = 'The uploaded Driver Photo is no longer available. Please upload the photo again.';
                }
            }

            foreach ((array) ($driver['documents'] ?? []) as $documentIndex => $document) {
                if (! is_array($document)) {
                    continue;
                }

                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                $token = trim((string) ($file['tempToken'] ?? ''));

                if ($token === '') {
                    continue;
                }

                try {
                    $uploads->validateClaim($file, $userId);
                    $tokens[] = $token;
                } catch (ValidationException) {
                    $name = trim((string) ($document['name'] ?? ''));
                    $label = $name !== '' ? "{$name} document" : 'driver document';
                    $errors["rows.{$driverIndex}.documents.{$documentIndex}.file"] = "The uploaded {$label} is no longer available. Please upload the document again.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return array_values(array_unique($tokens));
    }

    private function deleteDriverTemporaryUploads(
        FleetTemporaryUploadService $uploads,
        array $tokens,
        int $userId
    ): void {
        foreach (array_values(array_unique($tokens)) as $token) {
            try {
                $uploads->delete((string) $token, $userId);
            } catch (Throwable $cleanupException) {
                // The driver has already been saved. A failed temporary cleanup
                // must never turn a successful save into an error for the user.
                Log::warning('Failed to remove a temporary driver upload after save.', [
                    'user_id' => $userId,
                    'temp_token_suffix' => substr((string) $token, -8),
                    'exception_class' => $cleanupException::class,
                    'exception_message' => $cleanupException->getMessage(),
                ]);
            }
        }
    }

    private function deleteStoredDriverFiles(array $storedPaths): void
    {
        if ($storedPaths === []) {
            return;
        }

        try {
            Storage::disk('public')->delete(array_values(array_unique($storedPaths)));
        } catch (Throwable $cleanupException) {
            Log::warning('Failed to remove driver files after an unsuccessful save.', [
                'paths' => array_values(array_unique($storedPaths)),
                'exception_class' => $cleanupException::class,
                'exception_message' => $cleanupException->getMessage(),
            ]);
        }
    }

    private function driverSyncFailureResponse(
        Request $request,
        Throwable $exception
    ): JsonResponse {
        $reference = 'DRV-'
            .now('Asia/Dhaka')->format('Ymd-His')
            .'-'
            .Str::upper(Str::random(6));

        [$message, $status] = $this->meaningfulDriverSyncError($exception);
        $queryException = $this->findQueryException($exception);

        $context = [
            'error_reference' => $reference,
            'user_id' => $request->user()?->id,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
        ];

        if ($queryException instanceof QueryException) {
            $context = array_merge($context, [
                'database_connection' => $queryException->getConnectionName(),
                'sql_state' => $queryException->errorInfo[0] ?? null,
                'database_error_code' => $queryException->errorInfo[1] ?? null,
                'database_error_message' => $queryException->errorInfo[2]
                    ?? $queryException->getPrevious()?->getMessage()
                    ?? $queryException->getMessage(),
                // SQL placeholders are logged, but bindings are intentionally excluded.
                'sql' => $queryException->getSql(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            Log::channel('database')->error(
                'Driver database save failed.',
                $context
            );
        } else {
            $context['trace'] = $exception->getTraceAsString();

            Log::error(
                'Driver save failed because of a non-database error.',
                $context
            );
        }

        return response()->json([
            'ok' => false,
            'message' => $message,
            'error_reference' => $reference,
        ], $status);
    }

    private function meaningfulDriverSyncError(Throwable $exception): array
    {
        $queryException = $this->findQueryException($exception);
        $message = strtolower($exception->getMessage().' '.$exception->getPrevious()?->getMessage());

        if ($queryException instanceof QueryException) {
            $databaseCode = (int) ($queryException->errorInfo[1] ?? 0);
            $sqlState = strtoupper((string) ($queryException->errorInfo[0] ?? ''));

            if (
                in_array($databaseCode, [2002, 2003, 2006, 2013], true)
                || str_contains($message, 'connection refused')
                || str_contains($message, 'server has gone away')
                || str_contains($message, 'lost connection')
                || str_contains($message, 'connection timed out')
            ) {
                return [
                    'The server temporarily lost its database connection, so the driver was not saved. Please try again.',
                    503,
                ];
            }

            if ($databaseCode === 1062 || $sqlState === '23000' && str_contains($message, 'duplicate')) {
                return [
                    'A driver already exists with the same Driver ID, NID, licence number, or another unique value. Please correct the duplicate information and save again.',
                    409,
                ];
            }

            if (in_array($databaseCode, [1205, 1213], true)) {
                return [
                    'The database was busy processing another update. No driver was saved. Please try again.',
                    503,
                ];
            }

            if (in_array($databaseCode, [1054, 1146], true)) {
                return [
                    'The production database structure is not up to date. Please run the pending database migrations and try again.',
                    500,
                ];
            }

            if (in_array($databaseCode, [1048, 1366, 1406], true)) {
                return [
                    'One of the submitted driver values is missing or is not accepted by the database. Please review the entered information and try again.',
                    422,
                ];
            }

            if (in_array($databaseCode, [1451, 1452], true)) {
                return [
                    'The driver could not be saved because one of the selected related records no longer exists or is still in use. Refresh the page and select the value again.',
                    409,
                ];
            }

            if (
                $databaseCode === 1114
                || str_contains($message, 'no space left on device')
                || str_contains($message, 'disk full')
            ) {
                return [
                    'The server does not currently have enough storage space to save this driver. Please contact the Super Admin.',
                    507,
                ];
            }

            return [
                'The database rejected the driver information. No data was saved. Please try again or give the error reference to the Super Admin.',
                500,
            ];
        }

        if (
            str_contains($message, 'temporary upload')
            || str_contains($message, 'temp token')
            || str_contains($message, 'upload has expired')
            || str_contains($message, 'upload token')
        ) {
            return [
                'A temporary photo or document upload expired before the driver was saved. Please upload the affected file again.',
                422,
            ];
        }

        if (
            str_contains($message, 'permission denied')
            || str_contains($message, 'unable to write')
            || str_contains($message, 'failed to open stream')
            || str_contains($message, 'read-only file system')
        ) {
            return [
                'The server could not store the driver photo or document because of a storage permission problem. Please contact the Super Admin.',
                500,
            ];
        }

        if (
            str_contains($message, 'post content-length')
            || str_contains($message, 'request entity too large')
            || str_contains($message, 'payload too large')
        ) {
            return [
                'The driver photo or document request is larger than the server allows. Reduce the file size and try again.',
                413,
            ];
        }

        return [
            'The driver could not be saved because of an unexpected server error. No data was saved. Please give the error reference to the Super Admin.',
            500,
        ];
    }

    private function findQueryException(Throwable $exception): ?QueryException
    {
        do {
            if ($exception instanceof QueryException) {
                return $exception;
            }

            $exception = $exception->getPrevious();
        } while ($exception instanceof Throwable);

        return null;
    }

    private function validateDriverRows(array &$rows, Request $request, ?array $strictValidationIndexes = null): void
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
        $strictValidationLookup = $strictValidationIndexes === null
            ? null
            : array_fill_keys($strictValidationIndexes, true);

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

            if (($strictValidationLookup !== null && ! isset($strictValidationLookup[$index]))
                || (int) ($row['driverValidationVersion'] ?? 0) < 1
                || strcasecmp((string) ($row['status'] ?? ''), 'Draft') === 0) {
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
                'whatsapp' => ['nullable', 'regex:/^\d{11}$/'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'dob' => ['required', 'date', 'before_or_equal:today'],
                'age' => ['required', 'integer', 'between:0,120'],
                'nid' => ['required', 'regex:/^\d{1,17}$/'],
                'reference' => ['required', 'string', 'max:255'],
                'licenseNo' => ['required', 'string'],
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
                'photo' => ['nullable', 'array'],
            ], [
                'whatsapp.regex' => 'WhatsApp Number must be exactly 11 digits.',
                'nid.regex' => 'NID must contain digits only and cannot exceed 17 digits.',
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
            if ($this->hasStoredFile($photo) && (int) ($photo['sizeBytes'] ?? 0) > 100 * 1024) {
                $errors["rows.{$index}.photo"] = 'Driver Photo must not exceed 100 KB.';
            } elseif ($directPhoto instanceof UploadedFile && $directPhoto->getSize() > 100 * 1024) {
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
                    'payload' => $this->withoutRecordMetadata($row),
                ]);
            }
        });
    }
}
