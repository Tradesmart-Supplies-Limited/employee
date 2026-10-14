<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceShift extends Model
{
    public const DAY_TYPES = [
        'weekday'  => 'Mon – Fri',
        'saturday' => 'Saturday',
        'sunday'   => 'Sunday',
        'holiday'  => 'Public holiday',
    ];

    protected $fillable = [
        'name', 'code', 'color', 'is_default', 'is_active', 'sort',
        'weekday_start', 'weekday_end', 'saturday_start', 'saturday_end',
        'sunday_start', 'sunday_end', 'holiday_start', 'holiday_end',
    ];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    /** [start, end] for a kind of day, or [null, null] when the shift doesn't run then. */
    public function timesFor(string $dayType): array
    {
        $s = $this->{"{$dayType}_start"} ?? null;
        $e = $this->{"{$dayType}_end"} ?? null;

        return $s && $e ? [$s, $e] : [null, null];
    }

    /** Times for a day, falling back to the Mon–Fri hours when that day has none set. */
    public function timesOrWeekday(string $dayType): array
    {
        [$s, $e] = $this->timesFor($dayType);

        return $s ? [$s, $e] : $this->timesFor('weekday');
    }

    public function label(string $dayType): string
    {
        [$s, $e] = $this->timesOrWeekday($dayType);

        return $this->name . ($s ? " {$s}–{$e}" : '');
    }

    public static function ordered()
    {
        return static::orderBy('sort')->orderBy('id')->get();
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first() ?? static::orderBy('sort')->first();
    }
}
