<?php

namespace App\Http\Controllers\Api;

use App\Models\AppSetting;
use App\Models\Trim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends ApiController
{
    /** POST /finance/calculate — mirrors the app's slider calculator. */
    public function calculate(Request $request): JsonResponse
    {
        $config = AppSetting::get('finance', []);
        $minDp = $config['min_down_payment_percent'] ?? 20;
        $maxDp = $config['max_down_payment_percent'] ?? 50;
        $maxMonths = $config['max_duration_months'] ?? 84;
        $step = $config['duration_step_months'] ?? 12;

        $validated = $request->validate([
            'trim_id' => ['required', 'integer', 'exists:trims,id'],
            'down_payment_percent' => ['required', 'numeric', "min:$minDp", "max:$maxDp"],
            'duration_months' => ['required', 'integer', "min:$step", "max:$maxMonths", "multiple_of:$step"],
        ]);

        $trim = Trim::findOrFail($validated['trim_id']);
        $rate = (float) ($config['interest_rate_percent'] ?? 0);

        $downPayment = (int) round($trim->price_egp * $validated['down_payment_percent'] / 100);
        $loan = $trim->price_egp - $downPayment;
        $years = $validated['duration_months'] / 12;
        $totalWithInterest = $loan * (1 + $rate / 100 * $years);
        $monthly = (int) ceil($totalWithInterest / $validated['duration_months']);

        return $this->ok([
            'vehicle_price_egp' => $trim->price_egp,
            'down_payment_egp' => $downPayment,
            'down_payment_percent' => $validated['down_payment_percent'],
            'loan_amount_egp' => $loan,
            'duration_months' => $validated['duration_months'],
            'monthly_installment_egp' => $monthly,
            'total_paid_egp' => $downPayment + $monthly * $validated['duration_months'],
            'interest_rate_percent' => $rate,
            'note' => 'Approximate value based on current rates',
        ]);
    }

    /** POST /finance/eligibility — pre-approval check from the home banner. */
    public function eligibility(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trim_id' => ['nullable', 'integer', 'exists:trims,id'],
            'monthly_income_egp' => ['required', 'integer', 'min:1'],
            'employment_type' => ['required', 'in:employed,self_employed,business_owner'],
        ]);

        $config = AppSetting::get('finance', []);
        $ratio = (float) ($config['min_eligible_income_ratio'] ?? 0.35);
        $maxMonths = (int) ($config['max_duration_months'] ?? 84);
        $defaultDp = (int) ($config['default_down_payment_percent'] ?? 30);

        // Max affordable installment → max loan → max vehicle price.
        $maxMonthly = (int) floor($validated['monthly_income_egp'] * $ratio);
        $maxLoan = $maxMonthly * $maxMonths;
        $maxPrice = (int) floor($maxLoan / (1 - $defaultDp / 100));

        $trim = isset($validated['trim_id']) ? Trim::find($validated['trim_id']) : null;
        $eligible = $trim === null || $trim->price_egp <= $maxPrice;

        if (! $eligible) {
            return $this->ok([
                'eligible' => false,
                'reason' => 'Insufficient income for the requested vehicle price.',
                'max_vehicle_price_egp' => $maxPrice,
                'suggested_vehicles_url' => "/vehicles?max_price={$maxPrice}",
            ]);
        }

        $estimated = $trim
            ? (int) ceil($trim->price_egp * (1 - $defaultDp / 100) / ($config['default_duration_months'] ?? 60))
            : null;

        return $this->ok([
            'eligible' => true,
            'pre_approved_amount_egp' => $maxLoan,
            'suggested_down_payment_percent' => $defaultDp,
            'estimated_monthly_egp' => $estimated,
            'next_step' => 'Visit any branch with your national ID and salary certificate.',
        ]);
    }
}
