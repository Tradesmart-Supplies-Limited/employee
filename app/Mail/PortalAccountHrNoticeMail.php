<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells HR / admins that a portal account was created, reset or removed.
 * Never contains the password. Safe to queue.
 *
 * $event: created | reset | removed
 */
class PortalAccountHrNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $event,
        public string $loginEmail,
        public string $actorName,
        public bool $emailDelivered = true,
    ) {}

    public function build()
    {
        $verb = ['created' => 'created', 'reset' => 'password reset', 'removed' => 'removed'][$this->event] ?? $this->event;

        return $this->subject("Portal account {$verb}: {$this->employee->full_name}")
            ->view('emails.portal.hr_notice', [
                'verb'       => $verb,
                'profileUrl' => route('employees.show', $this->employee),
                'when'       => now(),
            ]);
    }
}
