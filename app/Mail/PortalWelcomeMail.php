<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\User;
use App\Support\HrRecipients;
use Illuminate\Mail\Mailable;

/**
 * Sent to the employee when HR creates (or resets) their self-service login.
 * Contains the temporary password, so it is sent immediately — never queued —
 * and the password is not written to the jobs table.
 */
class PortalWelcomeMail extends Mailable
{
    public function __construct(
        public User $user,
        public Employee $employee,
        public string $tempPassword,
        public bool $isReset = false,
    ) {}

    public function build()
    {
        return $this->subject($this->isReset
                ? 'Your new temporary password for the Employee Self-Service portal'
                : 'Welcome to the ' . config('company.name') . ' Employee Self-Service portal')
            ->view('emails.portal.welcome', [
                'loginUrl'   => route('login'),
                'forgotUrl'  => route('password.request'),
                'hrContact'  => HrRecipients::contact(),
                'supervisor' => $this->employee->supervisor_name,
            ]);
    }
}
