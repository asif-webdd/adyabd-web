<?php

namespace App\Support;

use App\Models\TrackingLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Tracking Log পেজের সব হিসাব (তারিখ-রেঞ্জ, চার্ট, ফানেল, টপ ইভেন্ট) এখানে।
 * সব ডেটা সরাসরি tracking_logs টেবিল থেকে আসে।
 */
class TrackingAnalytics
{
    /** দিন গোনার টাইমজোন (Bangladesh) */
    public const TZ = 'Asia/Dhaka';

    public const RANGES = [
        'today'        => 'Today',
        'yesterday'    => 'Yesterday',
        'last_7_days'  => 'Last 7 days',
        'last_30_days' => 'Last 30 days',
        'last_90_days' => 'Last 90 days',
        'this_month'   => 'This month',
        'last_month'   => 'Last month',
        'this_year'    => 'This year',
        'last_year'    => 'Last year',
        'all_time'     => 'All time',
        'custom'       => 'Custom range...',
    ];

    /** ফানেলের ক্রম (PageView শুধু থাকলে দেখাবে) */
    public const FUNNEL = ['PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'AddPaymentInfo', 'Purchase'];

    public const COLORS = [
        'PageView'         => '#94a3b8',
        'ViewContent'      => '#818cf8',
        'AddToCart'        => '#a78bfa',
        'InitiateCheckout' => '#38bdf8',
        'AddPaymentInfo'   => '#2dd4bf',
        'Purchase'         => '#4ade80',
        'Other'            => '#64748b',
    ];

    public const VALUE_COLOR = '#f59e0b';
    public const LINE_COLOR  = '#3b82f6';

    // ───────────────────────── তারিখ-রেঞ্জ ─────────────────────────

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable}  (Asia/Dhaka সময়ে শুরু ও শেষ) */
    public static function resolve(string $range, ?string $from = null, ?string $until = null): array
    {
        $now = CarbonImmutable::now(self::TZ);

        switch ($range) {
            case 'today':
                return [$now->startOfDay(), $now];
            case 'yesterday':
                $y = $now->subDay();
                return [$y->startOfDay(), $y->endOfDay()];
            case 'last_7_days':
                return [$now->subDays(6)->startOfDay(), $now];
            case 'last_90_days':
                return [$now->subDays(89)->startOfDay(), $now];
            case 'this_month':
                return [$now->startOfMonth(), $now];
            case 'last_month':
                $m = $now->subMonthNoOverflow();
                return [$m->startOfMonth(), $m->endOfMonth()];
            case 'this_year':
                return [$now->startOfYear(), $now];
            case 'last_year':
                $y = $now->subYear();
                return [$y->startOfYear(), $y->endOfYear()];
            case 'all_time':
                $min   = TrackingLog::min('created_at');
                $start = $min
                    ? CarbonImmutable::parse($min, config('app.timezone', 'UTC'))->setTimezone(self::TZ)->startOfDay()
                    : $now->startOfDay();
                return [$start, $now];
            case 'custom':
                return self::customRange($from, $until, $now);
            case 'last_30_days':
            default:
                return [$now->subDays(29)->startOfDay(), $now];
        }
    }

    private static function customRange(?string $from, ?string $until, CarbonImmutable $now): array
    {
        $parse = function (?string $v): ?CarbonImmutable {
            if (! $v) {
                return null;
            }
            try {
                return CarbonImmutable::parse($v, self::TZ);
            } catch (\Throwable) {
                return null;
            }
        };

        $f = $parse($from);
        $u = $parse($until);

        // দুটো ঘর ফাঁকা থাকলে আপাতত Last 30 days দেখাই
        if (! $f && ! $u) {
            return [$now->subDays(29)->startOfDay(), $now];
        }
        $f ??= $u->subDays(29);
        $u ??= $now;

        $start = $f->startOfDay();
        $end   = $u->endOfDay();
        if ($start > $end) {
            [$start, $end] = [$u->startOfDay(), $f->endOfDay()];
        }
        if ($end > $now) {
            $end = $now;
        }
        if ($start > $end) {
            $start = $end->startOfDay();
        }

        return [$start, $end];
    }

    /** ডাটাবেসে খোঁজার জন্য অ্যাপের টাইমজোনে [শুরু, শেষ] */
    public static function dbRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $tz = config('app.timezone', 'UTC');

        return [
            $start->setTimezone($tz)->format('Y-m-d H:i:s'),
            $end->setTimezone($tz)->format('Y-m-d H:i:s'),
        ];
    }

    public static function query(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return TrackingLog::query()->whereBetween('created_at', self::dbRange($start, $end));
    }

    // ───────────────────────── মূল হিসাব ─────────────────────────

    public static function compute(string $range, ?string $from = null, ?string $until = null): array
    {
        [$start, $end] = self::resolve($range, $from, $until);
        $gran = self::granularity($start, $end);

        $bucketSql = self::bucketSql($gran);
        $rows = self::query($start, $end)
            ->toBase()
            ->selectRaw("{$bucketSql} as bucket, event_name, COUNT(*) as c, "
                . "SUM(CASE WHEN event_name = 'Purchase' THEN COALESCE(value, 0) ELSE 0 END) as v, "
                . "SUM(CASE WHEN has_error = 1 THEN 1 ELSE 0 END) as f")
            ->groupByRaw('bucket, event_name')
            ->get();

        // event => [count, value] এবং bucket => event => [count, value]
        $events = [];
        $grid   = [];
        foreach ($rows as $r) {
            $name = (string) $r->event_name;
            $events[$name]['count'] = ($events[$name]['count'] ?? 0) + (int) $r->c;
            $events[$name]['value'] = ($events[$name]['value'] ?? 0) + (float) $r->v;
            $grid[$r->bucket][$name] = ['c' => (int) $r->c, 'v' => (float) $r->v, 'f' => (int) $r->f];
        }
        uasort($events, fn ($a, $b) => $b['count'] <=> $a['count']);

        // চার্টের স্ট্যাক: ফানেলের ইভেন্ট যেগুলোর ডেটা আছে + বাকি সব "Other"
        $stackKeys = [];
        foreach (self::FUNNEL as $name) {
            if (isset($events[$name])) {
                $stackKeys[] = $name;
            }
        }
        $hasOther = count(array_diff(array_keys($events), self::FUNNEL)) > 0;
        if ($hasOther) {
            $stackKeys[] = 'Other';
        }

        $buckets = [];
        foreach (self::bucketList($start, $end, $gran) as $key => $lab) {
            $by = array_fill_keys($stackKeys, 0);
            $value = 0.0;
            $total = 0;
            $failedHere = 0;
            foreach ($grid[$key] ?? [] as $name => $cell) {
                $k = in_array($name, self::FUNNEL, true) ? $name : 'Other';
                $by[$k] += $cell['c'];
                $total += $cell['c'];
                $value += $cell['v'];
                $failedHere += $cell['f'];
            }
            $buckets[] = [
                'key'    => $key,
                'label'  => $lab['label'],
                'full'   => $lab['full'],
                'total'  => $total,
                'by'     => $by,
                'value'  => $value,
                'failed' => $failedHere,
            ];
        }

        $totalEvents = array_sum(array_column($events, 'count'));
        $purchases   = (int) ($events['Purchase']['count'] ?? 0);
        $revenue     = (float) ($events['Purchase']['value'] ?? 0);
        $addToCart   = (int) ($events['AddToCart']['count'] ?? 0);

        $failed = self::query($start, $end)->where('has_error', true)->count();
        $serverPurchases = self::query($start, $end)
            ->where('event_name', 'Purchase')->where('source', 'server')->count();

        // আগের সমান সময়ের সাথে তুলনা (All time বাদে)
        $previous = null;
        if ($range !== 'all_time') {
            [$prevStart, $prevEnd] = self::previousWindow($range, $start, $end);
            $previous = self::aggregate($prevStart, $prevEnd);
        }

        // ফানেল
        $stages = [];
        foreach (self::FUNNEL as $name) {
            if ($name === 'PageView' && ! isset($events['PageView'])) {
                continue;
            }
            $stages[] = ['event' => $name, 'count' => (int) ($events[$name]['count'] ?? 0)];
        }
        $firstCount = $stages ? $stages[0]['count'] : 0;
        if ($firstCount === 0 && $stages) {
            $firstCount = max(array_column($stages, 'count'));
        }
        foreach ($stages as $i => &$s) {
            $prev = $i > 0 ? $stages[$i - 1]['count'] : null;
            $s['of_previous'] = ($prev !== null && $prev > 0) ? round($s['count'] / $prev * 100, 1) : null;
            $s['of_first']    = $firstCount > 0 ? round($s['count'] / $firstCount * 100, 1) : 0.0;
            $s['color']       = self::COLORS[$s['event']] ?? self::COLORS['Other'];
        }
        unset($s);

        // ডিস্ট্রিবিউশন (সব ইভেন্টের ভাগ)
        $distribution = [];
        foreach ($events as $name => $e) {
            $distribution[] = [
                'event' => $name,
                'count' => $e['count'],
                'share' => $totalEvents > 0 ? round($e['count'] / $totalEvents * 100, 1) : 0.0,
                'color' => self::COLORS[$name] ?? self::COLORS['Other'],
            ];
        }

        return [
            'start'        => $start,
            'end'          => $end,
            'gran'         => $gran,
            'stack_keys'   => $stackKeys,
            'buckets'      => $buckets,
            'events'       => $events,
            'funnel'       => $stages,
            'distribution' => $distribution,
            'totals'       => [
                'events'           => $totalEvents,
                'purchases'        => $purchases,
                'server_purchases' => $serverPurchases,
                'revenue'          => $revenue,
                'failed'           => $failed,
                'success_rate'     => $totalEvents > 0 ? round(100 * ($totalEvents - $failed) / $totalEvents, 1) : 100.0,
                'conversion'       => $addToCart > 0 ? round($purchases / $addToCart * 100, 1) : null,
                'add_to_cart'      => $addToCart,
            ],
            'previous'     => $previous,
            'change'       => [
                'events'  => $previous ? self::change($totalEvents, $previous['events']) : null,
                'revenue' => $previous ? self::change($revenue, $previous['revenue']) : null,
            ],
        ];
    }

    /**
     * তুলনার জন্য "আগের সমান সময়":
     * Today -> গতকালের একই সময় পর্যন্ত, This month -> গত মাসের একই তারিখ পর্যন্ত,
     * Last 7/30/90 days -> আগের ৭/৩০/৯০ দিন, Custom -> ঠিক আগের সমান দৈর্ঘ্য।
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function previousWindow(string $range, CarbonImmutable $start, CarbonImmutable $end): array
    {
        switch ($range) {
            case 'today':
            case 'yesterday':
                return [$start->subDay(), $end->subDay()];
            case 'last_7_days':
                return [$start->subDays(7), $end->subDays(7)];
            case 'last_30_days':
                return [$start->subDays(30), $end->subDays(30)];
            case 'last_90_days':
                return [$start->subDays(90), $end->subDays(90)];
            case 'this_month':
                return [$start->subMonthNoOverflow()->startOfMonth(), $end->subMonthNoOverflow()];
            case 'last_month':
                $m = $start->subMonthNoOverflow();
                return [$m->startOfMonth(), $m->endOfMonth()];
            case 'this_year':
            case 'last_year':
                return [$start->subYear(), $end->subYear()];
            default: // custom
                $seconds = max(1, $end->getTimestamp() - $start->getTimestamp());
                $prevEnd = $start->subSecond();

                return [$prevEnd->subSeconds($seconds), $prevEnd];
        }
    }

    private static function aggregate(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $r = self::query($start, $end)->toBase()->selectRaw(
            "COUNT(*) as events, "
            . "SUM(CASE WHEN event_name = 'Purchase' THEN COALESCE(value, 0) ELSE 0 END) as revenue, "
            . "SUM(CASE WHEN event_name = 'Purchase' THEN 1 ELSE 0 END) as purchases"
        )->first();

        return [
            'events'    => (int) ($r->events ?? 0),
            'revenue'   => (float) ($r->revenue ?? 0),
            'purchases' => (int) ($r->purchases ?? 0),
        ];
    }

    private static function change(float|int $cur, float|int $prev): ?float
    {
        return $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : null;
    }

    // ───────────────────────── বালতি (দিন/ঘণ্টা/মাস) ─────────────────────────

    private static function granularity(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $days = (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

        return $days <= 2 ? 'hour' : ($days <= 92 ? 'day' : 'month');
    }

    /** অ্যাপ টাইমজোন থেকে Asia/Dhaka-তে সরানোর মিনিট (UTC → +360) */
    private static function shiftMinutes(): int
    {
        $dhaka = CarbonImmutable::now(self::TZ)->utcOffset();
        $app   = CarbonImmutable::now(config('app.timezone', 'UTC'))->utcOffset();

        return (int) round($dhaka - $app);
    }

    private static function bucketSql(string $gran): string
    {
        $shift = self::shiftMinutes();
        $fmt = ['hour' => '%Y-%m-%d %H:00', 'day' => '%Y-%m-%d', 'month' => '%Y-%m'][$gran];

        if (DB::connection()->getDriverName() === 'sqlite') {
            $mod = sprintf("'%+d minutes'", $shift);

            return "strftime('{$fmt}', created_at, {$mod})";
        }

        return "DATE_FORMAT(DATE_ADD(created_at, INTERVAL {$shift} MINUTE), '{$fmt}')";
    }

    /** @return array<string,array{label:string,full:string}> key => [label, full] (ফাঁকা বালতি সহ) */
    private static function bucketList(CarbonImmutable $start, CarbonImmutable $end, string $gran): array
    {
        $list = [];
        $cur  = match ($gran) {
            'hour'  => $start->startOfHour(),
            'day'   => $start->startOfDay(),
            default => $start->startOfMonth(),
        };

        $guard = 0;
        while ($cur <= $end && $guard++ < 800) {
            if ($gran === 'hour') {
                $list[$cur->format('Y-m-d H:00')] = ['label' => $cur->format('h A'), 'full' => $cur->format('d M Y, h A')];
                $cur = $cur->addHour();
            } elseif ($gran === 'day') {
                $list[$cur->format('Y-m-d')] = ['label' => $cur->format('d M'), 'full' => $cur->format('D, d M Y')];
                $cur = $cur->addDay();
            } else {
                $list[$cur->format('Y-m')] = ['label' => $cur->format('M Y'), 'full' => $cur->format('F Y')];
                $cur = $cur->addMonthNoOverflow();
            }
        }

        return $list;
    }

    // ───────────────────────── চার্টের জ্যামিতি (SVG) ─────────────────────────

    public static function geometry(array $a): array
    {
        $W = 1000; $H = 320; $L = 54; $R = 60; $T = 12; $B = 40;
        $pw = $W - $L - $R;
        $ph = $H - $T - $B;

        $buckets = $a['buckets'];
        $n       = max(1, count($buckets));
        $slot    = $pw / $n;
        $barW    = min($slot * 0.62, 38);

        $maxTotal = max(array_merge([0], array_column($buckets, 'total')));
        $maxValue = max(array_merge([0], array_column($buckets, 'value')));
        $stepL    = self::niceStep($maxTotal / 4);
        $stepR    = self::niceStep($maxValue / 4);
        $yMax     = $stepL * 4;
        $yMaxV    = $stepR * 4;
        $base     = $T + $ph;

        $rects = [];
        $vrects = [];
        $dots = [];
        $points = [];
        $xlabels = [];
        $columns = [];
        $labelEvery = (int) ceil($n / 12);

        foreach ($buckets as $i => $b) {
            $cx = $L + $slot * ($i + 0.5);
            $cursor = $base;

            // হোভারের জন্য পুরো উচ্চতার অদৃশ্য কলাম
            $columns[] = ['x' => round($L + $slot * $i, 1), 'w' => round($slot, 1), 'y' => $T, 'h' => $ph];

            foreach ($a['stack_keys'] as $key) {
                $c = $b['by'][$key] ?? 0;
                if ($c <= 0) {
                    continue;
                }
                $h = $c / $yMax * $ph;
                $cursor -= $h;
                $rects[] = [
                    'x' => round($cx - $barW / 2, 1), 'y' => round($cursor, 1),
                    'w' => round($barW, 1), 'h' => round($h, 1),
                    'color' => self::COLORS[$key] ?? self::COLORS['Other'],
                    'title' => "{$b['label']} — {$key}: " . number_format($c),
                ];
            }

            if ($b['value'] > 0) {
                $vh = $b['value'] / $yMaxV * $ph;
                $vw = max(3, $barW * 0.38);
                $vrects[] = [
                    'x' => round($cx + $barW * 0.5 - $vw / 2, 1), 'y' => round($base - $vh, 1),
                    'w' => round($vw, 1), 'h' => round($vh, 1),
                    'title' => "{$b['label']} — Purchase value: ৳" . number_format($b['value']),
                ];
            }

            $y = $base - $b['total'] / $yMax * $ph;
            $points[] = round($cx, 1) . ',' . round($y, 1);
            $dots[] = [
                'cx' => round($cx, 1), 'cy' => round($y, 1),
                'title' => "{$b['label']} — total events: " . number_format($b['total']),
            ];

            if ($i % $labelEvery === 0) {
                $xlabels[] = ['x' => round($cx, 1), 'text' => $b['label']];
            }
        }

        $ticksL = [];
        $ticksR = [];
        for ($t = 0; $t <= 4; $t++) {
            $y = round($base - $t / 4 * $ph, 1);
            $ticksL[] = ['y' => $y, 'text' => self::compact($stepL * $t)];
            $ticksR[] = ['y' => $y, 'text' => self::compact($stepR * $t)];
        }

        return compact('W', 'H', 'L', 'R', 'T', 'B', 'pw', 'ph', 'rects', 'vrects', 'dots', 'xlabels', 'ticksL', 'ticksR', 'columns')
            + ['line' => implode(' ', $points), 'base' => $base];
    }

    private static function niceStep(float $v): float
    {
        if ($v <= 1) {
            return 1;
        }
        $exp = floor(log10($v));
        $f   = $v / (10 ** $exp);
        $nf  = $f <= 1 ? 1 : ($f <= 2 ? 2 : ($f <= 5 ? 5 : 10));

        return (float) ($nf * (10 ** $exp));
    }

    public static function compact(float|int $n): string
    {
        $n = (float) $n;
        if ($n >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
        }
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
        }

        return number_format($n, 0);
    }
}
