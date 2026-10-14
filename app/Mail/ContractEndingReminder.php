<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Contract / probation reminder. $rows are plain arrays (see ContractMonitor::rows)
 * so the email can be queued safely.
 *
 * $kind: alert | digest | supervisor | test
 */
class ContractEndingReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $rows,
        public string $label,
        public string $kind = 'alert',
    ) {}

    public function build()
    {
        $prefix = [
            'digest'     => 'Contract digest',
            'supervisor' => 'Team reminder',
            'test'       => 'TEST',
        ][$this->kind] ?? 'Action needed';

        return $this->subject("{$prefix}: {$this->label}")
            ->view('emails.contract-reminder', [
                'groups'   => collect($this->rows)->groupBy('state'),
                'settings' => route('settings.contract-reminders.index'),
            ]);
    }
}
