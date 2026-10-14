<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Runs at midnight (Africa/Lusaka): closes yesterday, marks absences and
 * emails the daily report to HR + management. Safe to run any number of
 * times — each day is closed and reported once.
 *
 *   php artisan attendance:close-day                 # close any finished day not yet closed
 *   php artisan attendance:close-day --date=2026-10-13 --resend   # re-send one day's report
 */
class CloseAttendanceDay extends Command
{
    protected $signature = 'attendance:close-day {--date= : Re-close / re-send a specific day (Y-m-d)} {--resend : Send the report again for --date}';

    protected $description = 'Close the previous attendance day, mark absences and email the daily report';

    public function handle(AttendanceService $att): int
    {
        if ($d = $this->option('date')) {
            $date = Carbon::parse($d, AttendanceService::tz());
            $res = $att->closeDay($date);
            $this->info("Closed {$date->toDateString()}: {$res['written']} absence/leave rows, {$res['missing_out']} missing clock-outs.");
            if ($this->option('resend')) {
                $this->info($att->sendReport($date, null, true) ? 'Report sent.' : 'Report NOT sent (check recipients / mail settings).');
            }

            return self::SUCCESS;
        }

        $done = $att->runIfDue();
        if (! $done) {
            $this->info('Nothing to close: every finished day is already closed.');
        }
        foreach ($done as $day => $r) {
            $this->info("{$day}: {$r['written']} absence/leave rows, {$r['missing_out']} missing clock-outs, report " . ($r['report'] ? 'sent' : 'not sent'));
        }
        $pruned = $att->prunePhotos();
        if ($pruned) {
            $this->info("Removed {$pruned} old kiosk photo(s).");
        }

        return self::SUCCESS;
    }
}
