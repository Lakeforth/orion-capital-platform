<?php

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadAccountStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', Rule::in([
                'all_time',
                'today',
                'last_7_days',
                'last_30_days',
                'this_month',
                'last_month',
                'this_year',
                'custom',
            ])],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:start_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasStartDate = filled($this->input('start_date'));
            $hasEndDate = filled($this->input('end_date'));

            if ($hasStartDate xor $hasEndDate) {
                $validator->errors()->add('start_date', 'Both start_date and end_date are required when using a custom date range.');
            }
        });
    }

    public function resolvedStartDate(): Carbon
    {
        return $this->resolvedDateRange()['start'];
    }

    public function resolvedEndDate(): Carbon
    {
        return $this->resolvedDateRange()['end'];
    }

    public function resolvedPeriodLabel(): string
    {
        $range = $this->resolvedDateRange();

        return $range['label'].' ('.$range['start']->format('M d, Y').' - '.$range['end']->format('M d, Y').')';
    }

    public function resolvedPeriodKey(): string
    {
        if (filled($this->input('period'))) {
            return (string) $this->input('period');
        }

        if (filled($this->input('start_date')) && filled($this->input('end_date'))) {
            return 'custom';
        }

        return 'all_time';
    }

    private function resolvedDateRange(): array
    {
        $period = $this->resolvedPeriodKey();

        if ($period === 'custom') {
            $start = Carbon::parse((string) $this->input('start_date'))->startOfDay();
            $end = Carbon::parse((string) $this->input('end_date'))->endOfDay();

            return [
                'start' => $start,
                'end' => $end,
                'label' => 'Custom Range',
            ];
        }

        $now = now();

        return match ($period) {
            'all_time' => [
                'start' => Carbon::create(1970, 1, 1)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'All Time',
            ],
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Today',
            ],
            'last_7_days' => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Last 7 Days',
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => 'This Month',
            ],
            'last_month' => [
                'start' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'end' => $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'label' => 'Last Month',
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => 'This Year',
            ],
            default => [
                'start' => $now->copy()->subDays(29)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Last 30 Days',
            ],
        };
    }
}
