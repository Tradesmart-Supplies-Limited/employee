<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveForwardedToHRMail extends Mailable
{
    public $leave;

    public function __construct($leave)
    {
        $this->leave = $leave;
    }

    public function build()
    {
        return $this->subject('Leave Approved by Supervisor - Awaiting HR Approval')
            ->view('emails.leave.forwarded_to_hr');
    }
}
