<?php

namespace App\Console\Commands;

use App\Services\ContractMonitor;
use Illuminate\Console\Command;

class SendContractReminders extends Command
{
    /**
     * php artisan reminders:contract           (once per day; skips if already run today)
     * php artisan reminders:contract --force   (run again now; still never sends duplicates)
     */
    protected $signature = 'reminders:contract {--force : Run even if it already ran today}';

    protected $description = 'Email reminders for contract and probation end dates of active employees (with catch-up for missed days).';

    public function handle(ContractMonitor $monitor): int
    {
        $setting = $monitor->setting();

        if (! $setting->is_active) {
            $this->info('Contract reminders are paused.');

            return self::SUCCESS;
        }

        $result = $this->option('force') ? $monitor->run() : $monitor->runIfDue();

        if ($result === null) {
            $this->info('Already ran today (' . $setting->last_run_at?->format('H:i') . '). Use --force to run again.');

            return self::SUCCESS;
        }

        $this->info("Alerts: {$result['alerts']} · digest rows: {$result['digest']} · emails sent: {$result['emails']}");
        foreach ($result['errors'] as $err) {
            $this->warn($err);
        }

        return self::SUCCESS;
    }
}
