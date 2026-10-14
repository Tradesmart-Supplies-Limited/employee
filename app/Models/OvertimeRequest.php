<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    public const DAY_TYPES = [
        'weekday'        => 'Weekday',
        'saturday'       => 'Saturday',
        'sunday'         => 'Sunday',
        'public_holiday' => 'Public Holiday',
    ];

    protected $fillable = [
        'employee_id',
        'work_date',
        'start_time',
        'end_time',
        'hours',
        'day_type',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
    ];

    protected $casts = [
        'work_date'   => 'date',
        'hours'       => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function getDayTypeLabelAttribute(): string
    {
        return self::DAY_TYPES[$this->day_type] ?? ucfirst($this->day_type);
    }

    /** Absolute start moment of the overtime window. */
    public function getStartsAtAttribute(): Carbon
    {
        return Carbon::parse($this->work_date->toDateString() . ' ' . $this->start_time);
    }

    /** Absolute end moment; rolls to the next day when the shift crosses midnight. */
    public function getEndsAtAttribute(): Carbon
    {
        $end = Carbon::parse($this->work_date->toDateString() . ' ' . $this->end_time);

        return $end->lessThanOrEqualTo($this->starts_at) ? $end->addDay() : $end;
    }
}
