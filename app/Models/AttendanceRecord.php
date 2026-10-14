<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUSES = [
        'present'  => ['label' => 'Present',  'color' => '#16a34a', 'bg' => '#dcfce7', 'icon' => 'bi-check-circle'],
        'late'     => ['label' => 'Late',     'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'bi-alarm'],
        'half_day' => ['label' => 'Half day', 'color' => '#0891b2', 'bg' => '#cffafe', 'icon' => 'bi-circle-half'],
        'absent'   => ['label' => 'Absent',   'color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'bi-x-circle'],
        'on_leave' => ['label' => 'On leave', 'color' => '#7c3aed', 'bg' => '#ede9fe', 'icon' => 'bi-airplane'],
        'holiday'  => ['label' => 'Holiday',  'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'bi-flag'],
        'off'      => ['label' => 'Day off',  'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'bi-moon'],
    ];

    /** Statuses that count as "came to work". */
    public const ATTENDED = ['present', 'late', 'half_day'];

    public const SOURCES = [
        'qr'        => ['label' => 'QR kiosk',   'icon' => 'bi-qr-code-scan'],
        'import'    => ['label' => 'Import',     'icon' => 'bi-file-earmark-spreadsheet'],
        'manual'    => ['label' => 'Manual',     'icon' => 'bi-pencil'],
        'biometric' => ['label' => 'Biometric',  'icon' => 'bi-fingerprint'],
        'system'    => ['label' => 'System',     'icon' => 'bi-gear'],
    ];

    protected $fillable = [
        'employee_id', 'date', 'clock_in', 'clock_out', 'status', 'minutes_late', 'worked_minutes',
        'left_early', 'missing_out', 'source_in', 'source_out', 'in_photo', 'out_photo', 'notes', 'updated_by',
        'shift_id', 'scheduled_start', 'scheduled_end', 'day_type', 'rostered', 'overtime_minutes', 'overtime_request_id',
    ];

    protected $casts = [
        'date'        => 'date',
        'left_early'  => 'boolean',
        'missing_out' => 'boolean',
        'rostered'    => 'boolean',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(AttendanceShift::class, 'shift_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** "08:05" (no seconds) or null. */
    public function getInTimeAttribute(): ?string
    {
        return $this->clock_in ? substr($this->clock_in, 0, 5) : null;
    }

    public function getOutTimeAttribute(): ?string
    {
        return $this->clock_out ? substr($this->clock_out, 0, 5) : null;
    }

    public function getHoursAttribute(): string
    {
        return self::formatMinutes($this->worked_minutes);
    }

    public function getMetaAttribute(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['present'];
    }

    public static function formatMinutes(?int $m): string
    {
        if (! $m) {
            return '—';
        }

        return intdiv($m, 60) . 'h ' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT) . 'm';
    }
}
