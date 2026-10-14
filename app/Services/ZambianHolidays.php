<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Zambian public holidays for any year (Public Holidays Act, Cap. 272).
 *
 * - Fixed-date and moveable feasts (Easter) are computed.
 * - A holiday that falls on a Sunday is observed on the following Monday
 *   (or the next free weekday if Monday is already a holiday).
 * - National Day of Prayer: 18 October up to 2025; from 2026 it is observed on
 *   29 December as the "National Day of Prayer and Christian Heritage".
 * - One-off declared days (e.g. election days) are listed in EXTRA. HR can also
 *   add / disable days on the Public Holidays page.
 */
class ZambianHolidays
{
    /** Declared one-off public holidays. */
    public const EXTRA = [
        '2026-08-13' => 'General Elections Day',
        '2026-09-01' => 'Presidential Inauguration Day',
    ];

    /** @return array<string, string>  'Y-m-d' => name, sorted */
    public static function forYear(int $year): array
    {
        $d = fn (int $m, int $day) => Carbon::create($year, $m, $day)->startOfDay();
        $easter = self::easterSunday($year);

        $list = [
            [$d(1, 1), "New Year's Day"],
            [$d(3, 8), "International Women's Day"],
            [$d(3, 12), 'Youth Day'],
            [$easter->copy()->subDays(2), 'Good Friday'],
            [$easter->copy()->subDay(), 'Holy Saturday'],
            [$easter->copy()->addDay(), 'Easter Monday'],
            [$d(5, 1), 'Labour Day'],
            [$d(5, 25), 'Africa Freedom Day'],
            [self::nthWeekday($year, 7, Carbon::MONDAY, 1), "Heroes' Day"],
            [self::nthWeekday($year, 7, Carbon::MONDAY, 1)->addDay(), 'Unity Day'],
            [self::nthWeekday($year, 8, Carbon::MONDAY, 1), "Farmers' Day"],
            [$d(10, 24), 'Independence Day'],
            [$d(12, 25), 'Christmas Day'],
        ];

        if ($year >= 2022) {
            $list[] = [$d(4, 28), 'Kenneth Kaunda Day'];
        }

        $list[] = $year >= 2026
            ? [$d(12, 29), 'National Day of Prayer and Christian Heritage']
            : [$d(10, 18), 'National Day of Prayer'];

        $out = [];
        foreach ($list as [$date, $name]) {
            $out[$date->toDateString()] = $name;
        }

        foreach (self::EXTRA as $date => $name) {
            if (str_starts_with($date, (string) $year)) {
                $out[$date] = $name;
            }
        }

        // Sunday → observed on the next free weekday.
        foreach ($out as $date => $name) {
            $c = Carbon::parse($date);
            if ($c->isSunday()) {
                $obs = $c->copy()->addDay();
                while (isset($out[$obs->toDateString()]) || $obs->isSunday()) {
                    $obs->addDay();
                }
                $out[$obs->toDateString()] = $name . ' (observed)';
            }
        }

        ksort($out);

        return $out;
    }

    /** Gregorian Easter Sunday (Anonymous / Meeus algorithm). */
    public static function easterSunday(int $y): Carbon
    {
        $a = $y % 19;
        $b = intdiv($y, 100);
        $c = $y % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($y, $month, $day)->startOfDay();
    }

    private static function nthWeekday(int $year, int $month, int $weekday, int $n): Carbon
    {
        $c = Carbon::create($year, $month, 1)->startOfDay();
        while ($c->dayOfWeek !== $weekday) {
            $c->addDay();
        }

        return $c->addWeeks($n - 1);
    }
}
