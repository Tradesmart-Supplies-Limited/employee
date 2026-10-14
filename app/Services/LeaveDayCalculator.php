<?php

namespace App\Services;

use App\Models\PublicHoliday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Counts chargeable leave days between two dates and works out the return date.
 *
 * Options (defaults from config/leave.php):
 *   exclude_sundays   — Sundays are not counted
 *   exclude_saturdays — Saturdays are not counted
 *   exclude_holidays  — Zambian public holidays (public_holidays table) are not counted
 *
 * The same rules run in the browser (portal leave form) for the live preview;
 * this class is the source of truth when the request is saved.
 */
class LeaveDayCalculator
{
    /** @var array<int, bool> years already loaded into the holidays table */
    private array $seeded = [];

    public static function defaultOptions(): array
    {
        return [
            'exclude_sundays'   => (bool) config('leave.exclude_sundays', true),
            'exclude_saturdays' => (bool) config('leave.exclude_saturdays', false),
            'exclude_holidays'  => (bool) config('leave.exclude_holidays', true),
        ];
    }

    /**
     * @return array{calendar_days:int, working_days:int, excluded:array<int,array{date:string,reason:string}>, return_date:Carbon, options:array}
     */
    public function calculate(Carbon $from, Carbon $to, array $options = []): array
    {
        $o = array_merge(self::defaultOptions(), array_intersect_key($options, self::defaultOptions()));
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $holidays = $o['exclude_holidays'] ? $this->holidays($from->year, $to->year + 1) : [];

        $working = 0;
        $excluded = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $reason = $this->nonWorkingReason($day, $o, $holidays);
            if ($reason) {
                $excluded[] = ['date' => $day->toDateString(), 'reason' => $reason];
            } else {
                $working++;
            }
        }

        return [
            'calendar_days' => (int) $from->diffInDays($to) + 1,
            'working_days'  => $working,
            'excluded'      => $excluded,
            'return_date'   => $this->nextWorkingDay($to, $o, $holidays),
            'options'       => $o,
        ];
    }

    /** First working day after $date. */
    public function nextWorkingDay(Carbon $date, array $options = [], ?array $holidays = null): Carbon
    {
        $o = array_merge(self::defaultOptions(), $options);
        $holidays ??= $o['exclude_holidays'] ? $this->holidays($date->year, $date->year + 1) : [];

        $d = $date->copy()->startOfDay()->addDay();
        for ($i = 0; $i < 30 && $this->nonWorkingReason($d, $o, $holidays); $i++) {
            $d->addDay();
        }

        return $d;
    }

    /** Active holidays 'Y-m-d' => name for a year range (generates missing years). */
    public function holidays(int $fromYear, int $toYear): array
    {
        for ($y = $fromYear; $y <= $toYear; $y++) {
            $this->ensureYear($y);
        }

        return PublicHoliday::where('is_active', true)
            ->whereDate('date', '>=', "{$fromYear}-01-01")->whereDate('date', '<=', "{$toYear}-12-31")
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn ($h) => [$h->date->toDateString() => $h->name])
            ->all();
    }

    /** Load the Zambian calendar for a year once (idempotent; never re-adds disabled days). */
    public function ensureYear(int $year, bool $force = false): int
    {
        if (! $force && isset($this->seeded[$year])) {
            return 0;
        }
        $this->seeded[$year] = true;

        if (! $force && PublicHoliday::where('source', 'generated')->whereYear('date', $year)->exists()) {
            return 0;
        }

        // Compare as Y-m-d strings so it works whatever format the DB returns.
        $existing = PublicHoliday::whereYear('date', $year)->get()
            ->map(fn ($h) => $h->date->toDateString())
            ->flip();

        $added = 0;
        foreach (ZambianHolidays::forYear($year) as $date => $name) {
            if (! isset($existing[$date])) {
                PublicHoliday::create(['date' => $date, 'name' => $name, 'source' => 'generated', 'is_active' => true]);
                $added++;
            }
        }

        return $added;
    }

    private function nonWorkingReason(Carbon $day, array $o, array $holidays): ?string
    {
        $key = $day->toDateString();

        if ($o['exclude_holidays'] && isset($holidays[$key])) {
            return $holidays[$key];
        }
        if ($o['exclude_sundays'] && $day->isSunday()) {
            return 'Sunday';
        }
        if ($o['exclude_saturdays'] && $day->isSaturday()) {
            return 'Saturday';
        }

        return null;
    }
}
