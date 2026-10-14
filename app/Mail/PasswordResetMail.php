<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

/**
 * Forgot-password email. Either a reset link ($url) or a 6-digit code ($code).
 * Sent immediately (never queued) so tokens / codes don't sit in the jobs table.
 */
class PasswordResetMail extends Mailable
{
    public function __construct(
        public User $user,
        public ?string $url = null,
        public ?string $code = null,
        public int $minutes = 60,
    ) {}

    public function build()
    {
        return $this->subject($this->code
                ? "{$this->code} is your password reset code"
                : 'Reset your password')
            ->view('emails.password.reset');
    }
}
