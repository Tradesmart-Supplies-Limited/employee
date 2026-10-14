<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScan extends Model
{
    protected $fillable = ['employee_id', 'attendance_kiosk_id', 'scanned_at', 'date', 'action', 'method', 'code_hint', 'message', 'photo', 'ip'];

    protected $casts = [
        'scanned_at' => 'datetime',
        'date'       => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function kiosk(): BelongsTo
    {
        return $this->belongsTo(AttendanceKiosk::class, 'attendance_kiosk_id');
    }
}
