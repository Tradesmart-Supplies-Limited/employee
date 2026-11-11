<?php

namespace App\Models;

use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** One day of a Management user's own clock-in / clock-out. */
class ManagementAttendance extends Model
{
    protected $table = 'management_attendance';

    protected $fillable = ['user_id', 'date', 'clock_in', 'clock_out', 'in_ip', 'out_ip', 'note', 'in_source', 'out_source', 'edited_at'];

    /** Stored for a midnight auto clock-out; shown as 00:00 and counted to midnight. */
    public const MIDNIGHT = '23:59:59';

    protected $casts = ['date' => 'date', 'edited_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getInTimeAttribute(): ?string
    {
        return $this->clock_in ? substr($this->clock_in, 0, 5) : null;
    }

    public function getOutTimeAttribute(): ?string
    {
        if (! $this->clock_out) {
            return null;
        }

        return $this->clock_out === self::MIDNIGHT ? '00:00' : substr($this->clock_out, 0, 5);
    }

    public function getAutoClosedAttribute(): bool
    {
        return $this->out_source === 'auto';
    }

    /** Minutes between clock-in and clock-out (null while still clocked in). */
    public function getMinutesAttribute(): ?int
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return null;
        }
        $in = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->clock_in, AttendanceService::tz());
        $out = $this->clock_out === self::MIDNIGHT
            ? Carbon::parse($this->date->format('Y-m-d'), AttendanceService::tz())->addDay()
            : Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->clock_out, AttendanceService::tz());

        return max(0, (int) $in->diffInMinutes($out));
    }

    public static function hm(?int $minutes): string
    {
        return $minutes === null ? '—' : intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
    }

    /**
     * Anyone from Management still clocked in from an earlier day is clocked
     * out automatically at midnight of that day. Returns how many were closed.
     */
    public static function closeOpenDays(?int $userId = null): int
    {
        $today = AttendanceService::today()->toDateString();
        $open = self::whereNotNull('clock_in')->whereNull('clock_out')
            ->whereDate('date', '<', $today)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->get();

        foreach ($open as $r) {
            $r->forceFill(['clock_out' => self::MIDNIGHT, 'out_source' => 'auto'])->save();
        }

        return $open->count();
    }
}
