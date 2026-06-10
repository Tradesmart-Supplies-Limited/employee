<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;

class LeaveAdminNotificationMail extends Mailable
{
    public $leave;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    public function build()
    {
        return $this->subject('New Leave Application')
            ->view('emails.leave.admin_notification');
    }
}