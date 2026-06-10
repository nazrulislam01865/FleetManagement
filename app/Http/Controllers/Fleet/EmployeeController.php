<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetEmployee;
use App\Services\FleetTemporaryUploadService;
use App\Support\FleetDocumentUploadPolicy;
use Illuminate\Database\Eloquent\Model;
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

    public function sync(Request $request): JsonResponse
    {
        $uploads = app(FleetTemporaryUploadService::class);
        $rowsInput = $request->input('rows', []);
        $rows = is_string($rowsInput) ? json_decode($rowsInput, true) : $rowsInput;
        if (! is_array($rows)) {
            throw ValidationException::withMessages(['rows' => 'The employee rows payload is invalid.']);
        }

        $strictValidationIndexes = $this->changedRowIndexesForSync($rows, FleetEmployee::class, $this->idKey);
        $this->validateEmployeeRows($rows, $request, $strictValidationIndexes);
        $this->validateUniqueDocumentNames($this->syncRowsAtIndexes($rows, $strictValidationIndexes));
        $storedPaths = [];
        $userId = (int) $request->user()->id;

        try {
            foreach ($rows as $employeeIndex => &$employee) {
                if (! is_array($employee)) {
                    continue;
                }

                $employeeId = $this->employeeFolderName($employee);
                $photo = $employee['photo'] ?? [];
                if (is_array($photo) && filled($photo['tempToken'] ?? null)) {
                    $employee['photo'] = $uploads->claim(
                        $photo,
                        $userId,
                        "fleet/employees/{$employeeId}/photo",
                        ['jpg', 'jpeg', 'png', 'webp'],
                        100,
                        true
                    );
                    $employee['photoName'] = $employee['photo']['originalName'];
                    $storedPaths[] = $employee['photo']['filePath'];
                }

                foreach (($employee['documents'] ?? []) as $documentIndex => $document) {
                    $file = is_array($document) ? ($document['file'] ?? []) : [];
                    if (is_array($file) && filled($file['tempToken'] ?? null)) {
                        $employee['documents'][$documentIndex]['file'] = $uploads->claim(
                            $file,
                            $userId,
                            "fleet/employees/{$employeeId}/documents",
                            FleetDocumentUploadPolicy::EXTENSIONS,
                            FleetDocumentUploadPolicy::MAX_KILOBYTES
                        );
                        $storedPaths[] = $employee['documents'][$documentIndex]['file']['filePath'];
                    }
                }
            }
            unset($employee);

            foreach ($request->file('employee_photo_files', []) as $employeeIndex => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $validator = Validator::make(
                    ['photo' => $file],
                    ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:100']],
                    ['photo.max' => 'The employee photo must not exceed 100 KB.']
                );
                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $employeeId = $this->employeeFolderName($rows[$employeeIndex] ?? []);
                $storedPath = $file->store("fleet/employees/{$employeeId}/photo", 'public');
                $storedPaths[] = $storedPath;
                $rows[$employeeIndex]['photo'] = $uploads->permanentPayload($storedPath, [
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType' => $file->getClientMimeType(),
                    'sizeBytes' => $file->getSize(),
                ]);
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
                        ['document' => FleetDocumentUploadPolicy::rules()],
                        FleetDocumentUploadPolicy::messages('document')
                    );
                    if ($validator->fails()) {
                        throw new ValidationException($validator);
                    }

                    $employeeId = $this->employeeFolderName($rows[$employeeIndex] ?? []);
                    $storedPath = $file->store("fleet/employees/{$employeeId}/documents", 'public');
                    $storedPaths[] = $storedPath;
                    $rows[$employeeIndex]['documents'][$documentIndex]['file'] = $uploads->permanentPayload($storedPath, [
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

        return response()->json(['ok' => true, 'rows' => $this->recordsFor(FleetEmployee::class)]);
    }

    private function validateEmployeeRows(array $rows, Request $request, ?array $strictValidationIndexes = null): void
    {
        $errors = [];
        $seenIds = [];
        $seenNids = [];
        $contactTypes = ['Office', 'Home', 'Relative', 'Other'];
        $employeeDocuments = $this->documentNameValues('Employees', 'employee_document_template');
        $documentReminders = $this->values('document_reminder');
        $salaryTenures = $this->values('employee_salary_tenure');
        $statuses = $this->values('employee_status');
        $strictValidationLookup = $strictValidationIndexes === null
            ? null
            : array_fill_keys($strictValidationIndexes, true);

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $errors["rows.{$index}"] = 'Each employee row must be a valid object.';
                continue;
            }

            $employeeId = trim((string) ($row['employeeId'] ?? ''));
            $nid = trim((string) ($row['nid'] ?? ''));
            $this->trackUniqueEmployeeValue($seenIds, $errors, $employeeId, "rows.{$index}.employeeId", 'Employee ID');
            $this->trackUniqueEmployeeValue($seenNids, $errors, $nid, "rows.{$index}.nid", 'NID');

            if (($strictValidationLookup !== null && ! isset($strictValidationLookup[$index]))
                || (int) ($row['employeeValidationVersion'] ?? 0) < 1
                || strcasecmp((string) ($row['status'] ?? ''), 'Draft') === 0) {
                continue;
            }

            $validator = Validator::make($row, [
                'employeeId' => ['required', 'string', 'max:100'],
                'fullName' => ['required', 'string', 'max:255'],
                'fatherName' => ['required', 'string', 'max:255'],
                'motherName' => ['required', 'string', 'max:255'],
                'nid' => ['required', 'regex:/^\d{1,17}$/'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'reference' => ['nullable', 'string', 'max:255'],
                'designation' => ['required', 'string', 'max:255'],
                'joiningDate' => ['required', 'date'],
                'status' => ['required', Rule::in($statuses)],
                'socialMedia' => ['nullable', 'string', 'max:500'],
                'age' => ['nullable', 'integer', 'between:0,120'],
                'salary' => ['required', 'numeric', 'min:0'],
                'salaryTenure' => ['required', Rule::in($salaryTenures)],
                'overtimeRate' => ['nullable', 'numeric', 'min:0'],
                'presentAddress' => ['required', 'string', 'max:1500'],
                'permanentAddress' => ['required', 'string', 'max:1500'],
                'about' => ['nullable', 'string', 'max:2000'],
                'contacts' => ['required', 'array', 'min:1'],
                'contacts.*.type' => ['required', Rule::in($contactTypes)],
                'contacts.*.number' => ['required', 'regex:/^\d{11}$/'],
                'documents' => ['required', 'array', 'min:1'],
                'documents.*.name' => ['required', Rule::in($employeeDocuments)],
                'documents.*.reference' => ['nullable', 'string', 'max:255'],
                'documents.*.expiry' => ['nullable', 'date'],
                'documents.*.reminder' => ['nullable', Rule::in($documentReminders)],
                'photo' => ['required', 'array'],
            ], [
                'nid.regex' => 'NID must contain digits only and cannot exceed 17 digits.',
                'contacts.*.number.regex' => 'Each contact phone number must be exactly 11 digits.',
                'email.email' => 'Enter a valid employee email address.',
            ]);

            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors["rows.{$index}.{$key}"] = $messages;
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
            $directPhoto = $request->file("employee_photo_files.{$index}");
            if (! $this->hasStoredFile($photo) && ! $directPhoto instanceof UploadedFile) {
                $errors["rows.{$index}.photo"] = 'Employee Photo is required.';
            } elseif ((int) ($photo['sizeBytes'] ?? 0) > 100 * 1024) {
                $errors["rows.{$index}.photo"] = 'Employee Photo must not exceed 100 KB.';
            }

            foreach ((array) ($row['documents'] ?? []) as $documentIndex => $document) {
                if (! is_array($document)) {
                    continue;
                }

                $file = is_array($document['file'] ?? null) ? $document['file'] : [];
                $directDocument = $request->file("employee_document_files.{$index}.{$documentIndex}");
                if (! $this->hasStoredFile($file) && ! $directDocument instanceof UploadedFile) {
                    $errors["rows.{$index}.documents.{$documentIndex}.file"] = 'Upload File is required for each employee document.';
                } elseif ((int) ($file['sizeBytes'] ?? 0) > 4 * 1024 * 1024) {
                    $errors["rows.{$index}.documents.{$documentIndex}.file"] = 'Each employee document must not exceed 4 MB.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function trackUniqueEmployeeValue(array &$seen, array &$errors, string $value, string $errorKey, string $label): void
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

    private function employeeFolderName(array $row): string
    {
        return Str::slug((string) ($row['employeeId'] ?? 'new-employee')) ?: 'new-employee';
    }

    private function persistRows(array $rows): void
    {
        $modelClass = $this->modelClass;
        DB::transaction(function () use ($modelClass, $rows) {
            $incomingCodes = collect($rows)->map(fn (array $row) => (string) ($row[$this->idKey] ?? ''))->filter()->values();
            $this->deleteMissingRecords($modelClass::query(), $incomingCodes);
            foreach ($rows as $row) {
                $code = (string) ($row[$this->idKey] ?? '');
                if ($code === '') {
                    continue;
                }
                /** @var Model $model */
                $modelClass::updateOrCreate(['code' => $code], [
                    'name' => $row[$this->nameKey] ?? $code,
                    'status' => $row[$this->statusKey] ?? null,
                    'payload' => $row,
                ]);
            }
        });
    }
}
