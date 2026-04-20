<?php

namespace App\Services\Thinkion\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DateRangeChunker
{
    /**
     * Split a date range into chunks of maximum $maxDays each.
     *
     * Thinkion API requires date ranges ≤ 30 days.
     * This utility ensures we never exceed that limit.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int $maxDays Maximum days per chunk (default 30)
     * @return array Array of ['start' => Carbon, 'end' => Carbon]
     */
    public static function chunk(Carbon $start, Carbon $end, int $maxDays = 30): array
    {
        if ($start->gt($end)) {
            throw new \InvalidArgumentException("Start date must be before or equal to end date.");
        }

        $chunks = [];
        $diffDays = $start->diffInDays($end);

        // If within the limit, return single chunk
        if ($diffDays <= $maxDays) {
            return [
                ['start' => $start->copy(), 'end' => $end->copy()]
            ];
        }

        $current = $start->copy();

        while ($current->lte($end)) {
            $chunkEnd = $current->copy()->addDays($maxDays - 1);

            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunks[] = [
                'start' => $current->copy(),
                'end' => $chunkEnd->copy(),
            ];

            $current = $chunkEnd->copy()->addDay();
        }

        return $chunks;
    }
}
