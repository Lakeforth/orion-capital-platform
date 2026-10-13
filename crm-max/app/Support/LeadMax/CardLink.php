<?php

namespace App\Support\LeadMax;

use Carbon\CarbonInterface;

/** Destinations for clickable KPI cards: the list behind the card's number, over the card's own date range. */
class CardLink
{
    /**
     * URL for a LeadMax route with the card's range. Report filters (source, country, agent) carry over
     * as the matching lead filters; destinations ignore the ones they don't support.
     */
    public static function to(string $route, array $params = [], ?CarbonInterface $from = null, ?CarbonInterface $to = null): string
    {
        $request = request();
        $carried = [];
        foreach (['source' => 'source', 'country' => 'country', 'agent' => 'manager_id'] as $param => $target) {
            if ($request->filled($param) && $request->input($param) !== 'all') {
                $carried[$target] = $request->input($param);
            }
        }
        $range = $from ? ['from' => $from->toDateString(), 'to' => ($to ?? $from)->toDateString()] : [];

        return route('admin.leadmax.'.$route, array_filter([...$range, ...$carried, ...$params], fn ($value) => $value !== null && $value !== ''));
    }

    /** The current page's period params, with its own default spelled out so the destination doesn't fall back to a different one. */
    public static function range(string $default): array
    {
        $request = request();
        $custom = $request->filled('from') || $request->filled('to');

        return array_filter([
            'period' => $request->input('period') ?: ($custom ? 'custom' : $default),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ]);
    }
}
