<?php

namespace App\Modules\TicketManagement\Services;

use App\Modules\TicketManagement\Models\SlaRule;
use Carbon\Carbon;

class SlaDeadlineCalculator
{
    public function calculate(string $priority, Carbon $start): Carbon
    {
        $rule = SlaRule::where('priority', $priority)->firstOrFail();
        $days = array_map('intval', explode(',', $rule->business_days));
        $remaining = $rule->resolution_minutes;
        $current = $start->copy();

        while ($remaining > 0) {
            if (!in_array($current->dayOfWeek, $days, true)) {
                $current->addDay()->startOfDay();
                continue;
            }

            $open = Carbon::parse($current->toDateString().' '.$rule->business_start, $current->timezone);
            $close = Carbon::parse($current->toDateString().' '.$rule->business_end, $current->timezone);
            if ($current->lt($open)) $current = $open;
            if ($current->gte($close)) {
                $current->addDay()->startOfDay();
                continue;
            }

            $available = $current->diffInMinutes($close);
            $used = min($remaining, $available);
            $current->addMinutes($used);
            $remaining -= $used;
        }

        return $current;
    }
}
