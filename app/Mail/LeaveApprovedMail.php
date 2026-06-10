<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;

class LeaveApprovedMail extends Mailable
{
    public $leave;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    public function build()
    {
        return $this->subject('Leave Application Approved')
            ->view('emails.leave.approved');
    }
}