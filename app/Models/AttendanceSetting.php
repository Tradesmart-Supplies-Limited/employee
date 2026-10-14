<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'work_start', 'work_end', 'grace_minutes', 'working_days', 'half_day_minutes', 'scan_cooldown_minutes',
        'count_holidays_off', 'report_enabled', 'report_include_hr', 'report_recipients', 'report_attach_csv',
        'last_closed_date', 'last_report_at',
        'rota_enabled', 'rota_departments', 'overtime_auto', 'comp_off_required',
    ];

    protected $casts = [
        'working_days'       => 'array',
        'report_recipients'  => 'array',
        'count_holidays_off' => 'boolean',
        'report_enabled'     => 'boolean',
        'report_include_hr'  => 'boolean',
        'report_attach_csv'  => 'boolean',
        'last_closed_date'   => 'date',
        'last_report_at'     => 'datetime',
        'rota_enabled'       => 'boolean',
        'rota_departments'   => 'array',
        'overtime_auto'      => 'boolean',
        'comp_off_required'  => 'boolean',
    ];

    /** Does this department plan its week with a rota? */
    public function usesRota(?string $department): bool
    {
        return $this->rota_enabled && $department !== null && in_array($department, $this->rota_departments ?? [], true);
    }

    public const DAY_NAMES = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    public static function current(): self
    {
        return static::first() ?? static::create([
            'work_start'            => '08:00',
            'work_end'              => '17:00',
            'grace_minutes'         => 15,
            'working_days'          => [1, 2, 3, 4, 5],
            'half_day_minutes'      => 240,
            'scan_cooldown_minutes' => 5,
            'count_holidays_off'    => true,
            'report_enabled'        => true,
            'report_include_hr'     => true,
            'report_recipients'     => [],
            'report_attach_csv'     => true,
            'rota_enabled'          => true,
            'rota_departments'      => ['Operations'],
            'overtime_auto'         => true,
            'comp_off_required'     => true,
            // Start clean: nothing before today gets marked absent.
            'last_closed_date'      => now(config('attendance.timezone', 'Africa/Lusaka'))->subDay()->toDateString(),
        ]);
    }

    public function isWorkingDay(\Carbon\CarbonInterface $d): bool
    {
        return in_array($d->isoWeekday(), array_map('intval', $this->working_days ?: [1, 2, 3, 4, 5]), true);
    }
}
