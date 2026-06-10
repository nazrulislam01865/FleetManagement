<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollMonthRangeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_current_month_and_previous_two_months_are_allowed(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-28 12:00:00', 'Asia/Dhaka'));

        foreach (['2026-06', '2026-05', '2026-04'] as $month) {
            $this
                ->withoutMiddleware()
                ->postJson(route('fleet.dues.generate-payroll'), ['month' => $month])
                ->assertOk()
                ->assertJson(['ok' => true]);
        }
    }

    public function test_month_older_than_previous_two_months_is_rejected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-28 12:00:00', 'Asia/Dhaka'));

        $this
            ->withoutMiddleware()
            ->postJson(route('fleet.dues.generate-payroll'), ['month' => '2026-03'])
            ->assertUnprocessable()
            ->assertJson([
                'ok' => false,
                'code' => 'PAYROLL_MONTH_OUT_OF_RANGE',
            ]);
    }
}
