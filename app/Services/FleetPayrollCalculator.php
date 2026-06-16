<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class FleetPayrollCalculator
{
    /**
     * Convert the saved salary/rent rate into one aggregate payable amount for
     * the selected month. This keeps exactly one payroll due per entity/month
     * while still respecting Monthly, Weekly, Daily, Hourly, Contract and Other.
     *
     * @return array{amount:float,tenure:string,units:float,unit_label:string,formula:string}|null
     */
    public function monthlyAmount(
        float $rate,
        string $tenure,
        CarbonImmutable $month,
        ?float $hourlyUnits = null
    ): ?array {
        $rate = round(max(0, $rate), 2);
        $normalized = strtolower(trim($tenure));
        $days = $month->daysInMonth;

        if ($rate <= 0) {
            return null;
        }

        return match ($normalized) {
            'weekly' => $this->result($rate * ($days / 7), $tenure, $days / 7, 'week', 'weekly rate × days in month ÷ 7'),
            'daily' => $this->result($rate * $days, $tenure, (float) $days, 'day', 'daily rate × days in month'),
            'hourly' => $hourlyUnits === null
                ? null
                : $this->result($rate * max(0, $hourlyUnits), $tenure, max(0, $hourlyUnits), 'hour', 'hourly rate × payable hours'),
            'monthly' => $this->result($rate, $tenure, 1, 'month', 'monthly rate'),
            'contract' => $this->result($rate, $tenure, 1, 'contract month', 'contract amount for selected month'),
            'other' => $this->result($rate, $tenure, 1, 'month', 'saved amount for selected month'),
            default => $this->result($rate, $tenure !== '' ? $tenure : 'Monthly', 1, 'month', 'saved amount for selected month'),
        };
    }

    private function result(float $amount, string $tenure, float $units, string $unitLabel, string $formula): array
    {
        return [
            'amount' => round($amount, 2),
            'tenure' => trim($tenure) !== '' ? trim($tenure) : 'Monthly',
            'units' => round($units, 4),
            'unit_label' => $unitLabel,
            'formula' => $formula,
        ];
    }
}
