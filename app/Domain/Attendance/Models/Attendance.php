<?php

namespace App\Domain\Attendance\Models;

use App\Domain\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'work_date',
        'check_in_at',
        'check_out_at',
        'status',
        'note',
        'check_in_photo_path',
        'check_out_photo_path',
        'check_in_lat',
        'check_in_lng',
        'check_in_accuracy',
        'check_out_lat',
        'check_out_lng',
        'check_out_accuracy',
        'location_status',
        'distance_m',
    ];

    protected $casts = [
        'work_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'check_in_lat' => 'decimal:7',
        'check_in_lng' => 'decimal:7',
        'check_out_lat' => 'decimal:7',
        'check_out_lng' => 'decimal:7',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
