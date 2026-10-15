<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * First-time setup: the 6-digit code that confirms the Admin's email address.
 * Sent immediately (never queued) so the code doesn't sit in the jobs table.
 */
class SetupVerifyMail extends Mailable
{
    public function __construct(
        public string $name,
        public string $email,
        public string $code,
        public int $minutes = 15,
    ) {}

    public function build()
    {
        return $this->subject("{$this->code} is your HR system setup code")
            ->view('emails.setup-verify');
    }
}
