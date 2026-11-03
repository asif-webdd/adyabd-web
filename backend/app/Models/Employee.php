<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'user_id', 'department_id', 'designation_id', 'name', 'name_bn',
        'email', 'phone', 'emergency_phone', 'address', 'nid_number', 'avatar',
        'date_of_birth', 'gender', 'blood_group', 'join_date', 'resignation_date',
        'employment_type', 'status', 'basic_salary', 'house_rent', 'medical_allowance',
        'transport_allowance', 'bank_account', 'bank_name', 'bkash_number',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date'     => 'date',
        'basic_salary'  => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function getGrossSalaryAttribute(): float
    {
        return $this->basic_salary + $this->house_rent + $this->medical_allowance + $this->transport_allowance;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(
            fn($e) => $e->employee_id ?? $e->employee_id = 'EMP-' . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT)
        );
    }
}
