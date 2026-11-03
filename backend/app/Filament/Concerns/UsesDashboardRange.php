<?php

namespace App\Filament\Concerns;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Date range chosen in the dashboard filter bar (defaults to the last 30 days). */
trait UsesDashboardRange
{
    use InteractsWithPageFilters;

    protected function rangeStart(): Carbon
    {
        $v = $this->filters['startDate'] ?? null;
        return $v ? Carbon::parse($v)->startOfDay() : now()->subDays(29)->startOfDay();
    }

    protected function rangeEnd(): Carbon
    {
        $v = $this->filters['endDate'] ?? null;
        $end = $v ? Carbon::parse($v)->endOfDay() : now()->endOfDay();
        return $end->lt($this->rangeStart()) ? $this->rangeStart()->copy()->endOfDay() : $end;
    }

    protected function rangeLabel(): string
    {
        return $this->rangeStart()->format('d M') . ' – ' . $this->rangeEnd()->format('d M Y');
    }
}
