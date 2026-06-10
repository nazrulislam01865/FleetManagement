<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClientController extends FleetBaseController
{
    protected string $activeMenu = 'clients';
    protected string $view = 'fleetman.clients';
    protected string $page = 'clients';
    protected string $resource = 'clients';
    protected string $idKey = 'clientId';
    protected string $nameKey = 'clientName';
    protected string $statusKey = 'status';
    protected string $modelClass = FleetClient::class;

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['present', 'array'],
            'rows.*' => ['array'],
        ]);

        $rows = $validated['rows'];
        $errors = [];
        $seenIds = [];

        foreach ($rows as $index => &$row) {
            if ((int) ($row['clientValidationVersion'] ?? 0) < 1) {
                continue;
            }

            $clientId = trim((string) ($row['clientId'] ?? ''));
            if ($clientId !== '') {
                $idKey = strtolower($clientId);
                if (isset($seenIds[$idKey])) {
                    $errors["rows.{$index}.clientId"] = 'Client ID must be unique.';
                }
                $seenIds[$idKey] = true;
            }

            if (strcasecmp((string) ($row['status'] ?? ''), 'Draft') !== 0) {
                $validator = Validator::make($row, [
                    'clientId' => ['required', 'string', 'max:100'],
                    'clientName' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email:rfc', 'max:255'],
                    'phone' => ['required', 'regex:/^\d{11}$/'],
                    'whatsapp' => ['required', 'regex:/^\d{11}$/'],
                    'reference' => ['required', 'string', 'max:255'],
                    'clientType' => ['required', 'string', 'max:100'],
                    'status' => ['required', 'string', 'max:100'],
                    'contactMethod' => ['required', 'string', 'max:100'],
                    'address' => ['required', 'string', 'max:1000'],
                    'about' => ['required', 'string', 'max:2000'],
                    'contacts' => ['required', 'array', 'min:1'],
                    'contacts.*.name' => ['required', 'string', 'max:255'],
                    'contacts.*.role' => ['required', 'string', 'max:255'],
                    'contacts.*.phone' => ['required', 'regex:/^\d{11}$/'],
                ], [
                    'phone.regex' => 'Phone Number must be exactly 11 digits.',
                    'whatsapp.regex' => 'WhatsApp Number must be exactly 11 digits.',
                    'contacts.*.phone.regex' => 'Each contact person phone number must be exactly 11 digits.',
                ]);

                foreach ($validator->errors()->messages() as $key => $messages) {
                    $errors["rows.{$index}.{$key}"] = $messages;
                }

                foreach ((array) ($row['contacts'] ?? []) as $contactIndex => $contact) {
                    if (! is_array($contact)) {
                        continue;
                    }

                    $whatsapp = trim((string) ($contact['whatsapp'] ?? ''));
                    $email = trim((string) ($contact['email'] ?? ''));
                    if ($whatsapp === '' && $email === '') {
                        $errors["rows.{$index}.contacts.{$contactIndex}.contact"] = 'Each contact person must have a WhatsApp number or email address.';
                    } elseif ($whatsapp !== '' && ! preg_match('/^\d{11}$/', $whatsapp)) {
                        $errors["rows.{$index}.contacts.{$contactIndex}.whatsapp"] = 'Each contact person WhatsApp number must be exactly 11 digits.';
                    } elseif ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors["rows.{$index}.contacts.{$contactIndex}.email"] = 'Each contact person email address must be valid.';
                    }
                }
            }

            $row['clientId'] = $clientId;
            $row['clientName'] = trim((string) ($row['clientName'] ?? ''));
            $row['email'] = trim((string) ($row['email'] ?? ''));
            $row['phone'] = trim((string) ($row['phone'] ?? ''));
            $row['whatsapp'] = trim((string) ($row['whatsapp'] ?? ''));
            $row['reference'] = trim((string) ($row['reference'] ?? ''));
            $row['clientType'] = trim((string) ($row['clientType'] ?? ''));
            $row['status'] = trim((string) ($row['status'] ?? ''));
            $row['contactMethod'] = trim((string) ($row['contactMethod'] ?? ''));
            $row['address'] = trim((string) ($row['address'] ?? ''));
            $row['about'] = trim((string) ($row['about'] ?? ''));
        }
        unset($row);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($rows) {
            $incomingCodes = collect($rows)
                ->map(fn (array $row): string => (string) ($row['clientId'] ?? ''))
                ->filter()
                ->values();

            $this->deleteMissingRecords(FleetClient::query(), $incomingCodes);

            foreach ($rows as $row) {
                $code = (string) ($row['clientId'] ?? '');
                if ($code === '') {
                    continue;
                }

                FleetClient::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $row['clientName'] ?? $code,
                        'status' => $row['status'] ?? null,
                        'payload' => $row,
                    ]
                );
            }
        });

        return response()->json([
            'ok' => true,
            'rows' => $this->recordsFor(FleetClient::class),
        ]);
    }
}
