<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * End-of-day attendance report for HR and management.
 * Sent directly (not queued) so it goes out even if no queue worker runs.
 */
class AttendanceDailyReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $report, public ?string $csv = null, public bool $test = false) {}

    public function build()
    {
        $t = $this->report['totals'];
        $rate = $t['rate'] !== null ? $t['rate'] . '%' : 'n/a';

        $mail = $this->subject(($this->test ? 'TEST · ' : '') . 'Attendance report ' . date('D d M Y', strtotime($this->report['date'])) . " · {$t['attended']}/{$t['expected']} in ({$rate})")
            ->view('emails.attendance-daily', ['r' => $this->report, 'test' => $this->test]);

        if ($this->csv) {
            $mail->attachData($this->csv, 'attendance-' . $this->report['date'] . '.csv', ['mime' => 'text/csv']);
        }

        return $mail;
    }
}
