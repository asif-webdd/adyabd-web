<?php

namespace App\Services;

use App\Models\{Attendance, Employee, Leave, Payroll};
use Carbon\Carbon;

/**
 * Builds the monthly payroll rows from salary + attendance + approved leave.
 *
 *  - working days = days of the month minus Fridays (Bangladesh weekly holiday)
 *  - if the month has NO attendance rows at all, nobody is penalised (HR may not use the attendance module)
 *  - otherwise: absent = working days - present(+late, half day = 0.5) - paid leave - holiday rows
 *  - unpaid leave counts as absent
 *  - deduction = gross / working days x absent days
 */
class PayrollService
{
    /** @return array{created:int, skipped:int} */
    public function generate(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();
        $created = $skipped = 0;

        Employee::where('status', 'active')
            ->where(fn($q) => $q->whereNull('join_date')->orWhere('join_date', '<=', $end->toDateString()))
            ->each(function (Employee $e) use ($month, $year, $start, $end, &$created, &$skipped) {
                if (Payroll::where(['employee_id' => $e->id, 'month' => $month, 'year' => $year])->exists()) {
                    $skipped++;
                    return;
                }
                Payroll::create($this->build($e, $month, $year, $start, $end));
                $created++;
            });

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function build(Employee $e, int $month, int $year, Carbon $start, Carbon $end): array
    {
        $workingDays = $this->workingDays($start, $end);
        $gross = (float) $e->basic_salary + (float) $e->house_rent + (float) $e->medical_allowance + (float) $e->transport_allowance;

        $rows = Attendance::where('employee_id', $e->id)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();
        $presentEq = $rows->whereIn('status', ['present', 'late'])->count() + 0.5 * $rows->where('status', 'half_day')->count();
        $holidays  = $rows->where('status', 'holiday')->count();

        [$paidLeave, $unpaidLeave] = $this->leaveDays($e, $start, $end);

        $absent = 0.0;
        if ($rows->isNotEmpty()) {
            $absent = max(0.0, $workingDays - $presentEq - $paidLeave - $unpaidLeave - $holidays) + $unpaidLeave;
        } elseif ($unpaidLeave > 0) {
            $absent = (float) $unpaidLeave;
        }

        $deduction = $workingDays > 0 ? round($gross / $workingDays * $absent, 2) : 0.0;
        $deduction = min($deduction, $gross);

        return [
            'employee_id' => $e->id, 'month' => $month, 'year' => $year,
            'basic_salary' => $e->basic_salary, 'house_rent' => $e->house_rent,
            'medical_allowance' => $e->medical_allowance, 'transport_allowance' => $e->transport_allowance,
            'bonus' => 0, 'overtime_pay' => 0,
            'gross_salary' => $gross, 'tax_deduction' => 0, 'other_deduction' => $deduction,
            'net_salary' => round($gross - $deduction, 2),
            'present_days' => (int) round($presentEq), 'absent_days' => (int) ceil($absent), 'leave_days' => $paidLeave,
            'payment_method' => $e->bank_account ? 'bank' : ($e->bkash_number ? 'bkash' : 'cash'),
            'status' => 'pending',
            'note' => $deduction > 0 ? "Auto-generated. Absent {$absent} of {$workingDays} working days." : 'Auto-generated.',
        ];
    }

    public function workingDays(Carbon $start, Carbon $end): int
    {
        $n = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!$d->isFriday()) {
                $n++;
            }
        }
        return $n;
    }

    /** @return array{0:int,1:int} [paid leave working days, unpaid leave working days] inside the month */
    private function leaveDays(Employee $e, Carbon $start, Carbon $end): array
    {
        $paid = $unpaid = 0;
        Leave::where('employee_id', $e->id)->where('status', 'approved')
            ->whereDate('from_date', '<=', $end->toDateString())->whereDate('to_date', '>=', $start->toDateString())
            ->each(function (Leave $l) use ($start, $end, &$paid, &$unpaid) {
                $from = $l->from_date->copy()->max($start);
                $to   = $l->to_date->copy()->min($end);
                $days = $this->workingDays($from->startOfDay(), $to->endOfDay());
                $l->type === 'unpaid' ? $unpaid += $days : $paid += $days;
            });
        return [$paid, $unpaid];
    }
}
