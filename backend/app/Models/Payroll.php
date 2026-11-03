<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id', 'month', 'year', 'basic_salary', 'house_rent', 'medical_allowance',
        'transport_allowance', 'bonus', 'overtime_pay', 'gross_salary', 'tax_deduction',
        'other_deduction', 'net_salary', 'present_days', 'absent_days', 'leave_days',
        'payment_method', 'status', 'paid_at', 'note',
    ];

    protected $casts = [
        'paid_at'    => 'datetime',
        'net_salary' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
