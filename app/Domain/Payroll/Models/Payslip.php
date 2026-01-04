<?php

namespace App\Domain\Payroll\Models;

use App\Domain\Employee\Models\Employee;
use App\Domain\Shared\Enums\PayslipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'basic_salary',
        'allowances',
        'deductions',
        'net_pay',
        'status',
        'issued_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issued_at' => 'datetime',
        'allowances' => 'array',
        'deductions' => 'array',
        'basic_salary' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
