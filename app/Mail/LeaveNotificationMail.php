<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;

class LeaveNotificationMail extends Mailable
{
    public $leave;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    public function build()
    {
        return $this->subject('New Leave Application')
            ->view('emails.leave.notification');
    }
}