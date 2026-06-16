<?php

namespace App\Services;

use App\Models\Fleet\FleetDue;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class FleetDueService
{
    /**
     * Create a generated due exactly once.
     *
     * The fleet_dues.code column already has a database UNIQUE constraint. The
     * deterministic code therefore remains the final protection against two
     * users generating the same payroll/source due at the same time.
     *
     * @return array{due:FleetDue,created:bool}
     */
    public function createOnce(array $attributes, int $creatorUserId = 0): array
    {
        $code = trim((string) ($attributes['code'] ?? ''));
        if ($code === '') {
            throw new \InvalidArgumentException('A deterministic due code is required.');
        }

        $attributes = $this->normalizedAttributes($attributes);
        $created = false;

        try {
            $due = FleetDue::query()->create($attributes);
            $created = true;
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $due = FleetDue::query()->where('code', $code)->first();
            if (! $due) {
                throw $exception;
            }
        }

        if ($created && $creatorUserId > 0) {
            app(FleetRecordOwnershipService::class)->claimRecord('dues', $code, $creatorUserId);
        }

        return compact('due', 'created');
    }

    /**
     * Keep one live due for a source such as a trip, attendance log, or fuel
     * recharge. Paid records remain paid while their amount is unchanged.
     */
    public function syncSourceDue(array $attributes, int $creatorUserId = 0): FleetDue
    {
        $attributes = $this->normalizedAttributes($attributes);
        $code = (string) $attributes['code'];

        return DB::transaction(function () use ($attributes, $code, $creatorUserId): FleetDue {
            $due = FleetDue::query()->where('code', $code)->lockForUpdate()->first();
            $created = false;

            if (! $due) {
                try {
                    $due = FleetDue::query()->create($attributes);
                    $created = true;
                } catch (QueryException $exception) {
                    if (! $this->isUniqueConstraintViolation($exception)) {
                        throw $exception;
                    }

                    $due = FleetDue::query()->where('code', $code)->lockForUpdate()->firstOrFail();
                }
            }

            if (! $created) {
                $incomingAmount = round((float) ($attributes['amount'] ?? 0), 2);
                $storedAmount = round((float) $due->amount, 2);
                if (strcasecmp((string) $due->status, 'Paid') === 0 && abs($incomingAmount - $storedAmount) < 0.01) {
                    $attributes['status'] = 'Paid';
                }

                $due->fill($attributes)->save();
            }

            $this->removeDuplicateSourceRows($due);

            if ($created && $creatorUserId > 0) {
                app(FleetRecordOwnershipService::class)->claimRecord('dues', $code, $creatorUserId);
            }

            return $due->refresh();
        }, 3);
    }

    public function deleteByCode(string $code): void
    {
        $code = trim($code);
        if ($code === '') {
            return;
        }

        FleetDue::query()->where('code', $code)->delete();
        app(FleetRecordOwnershipService::class)->forgetRecord('dues', $code);
    }

    private function normalizedAttributes(array $attributes): array
    {
        return [
            'code' => trim((string) ($attributes['code'] ?? '')),
            'type' => trim((string) ($attributes['type'] ?? 'General')) ?: 'General',
            'party_type' => $this->nullableString($attributes['party_type'] ?? null),
            'party_id' => $this->nullableString($attributes['party_id'] ?? null),
            'source_type' => $this->nullableString($attributes['source_type'] ?? null),
            'source_id' => $this->nullableString($attributes['source_id'] ?? null),
            'amount' => round((float) ($attributes['amount'] ?? 0), 2),
            'status' => trim((string) ($attributes['status'] ?? 'Pending')) ?: 'Pending',
            'due_date' => $attributes['due_date'] ?? null,
            'payload' => is_array($attributes['payload'] ?? null) ? $attributes['payload'] : [],
        ];
    }

    private function removeDuplicateSourceRows(FleetDue $canonical): void
    {
        if (blank($canonical->source_type) || blank($canonical->source_id)) {
            return;
        }

        $duplicates = FleetDue::query()
            ->where('source_type', $canonical->source_type)
            ->where('source_id', $canonical->source_id)
            ->where('type', $canonical->type)
            ->where(function ($query) use ($canonical): void {
                if ($canonical->party_type === null) {
                    $query->whereNull('party_type');
                } else {
                    $query->where('party_type', $canonical->party_type);
                }
            })
            ->where(function ($query) use ($canonical): void {
                if ($canonical->party_id === null) {
                    $query->whereNull('party_id');
                } else {
                    $query->where('party_id', $canonical->party_id);
                }
            })
            ->where('code', '!=', $canonical->code)
            ->pluck('code');

        if ($duplicates->isEmpty()) {
            return;
        }

        FleetDue::query()->whereIn('code', $duplicates->all())->delete();
        $ownership = app(FleetRecordOwnershipService::class);
        foreach ($duplicates as $duplicateCode) {
            $ownership->forgetRecord('dues', (string) $duplicateCode);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, [1062, 1555, 2067], true);
    }
}
