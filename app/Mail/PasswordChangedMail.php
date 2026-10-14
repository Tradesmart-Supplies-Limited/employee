<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

/** Security confirmation after a password is reset. */
class PasswordChangedMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $ip = '',
    ) {}

    public function build()
    {
        return $this->subject('Your password was changed')
            ->view('emails.password.changed', [
                'when'      => now(),
                'forgotUrl' => route('password.request'),
            ]);
    }
}
