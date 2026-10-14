<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Announcement $announcement, public Employee $employee) {}

    public function build()
    {
        $p = $this->announcement->priority;

        return $this->subject(($p === 'urgent' ? 'URGENT: ' : ($p === 'important' ? 'Important: ' : 'Announcement: ')) . $this->announcement->title)
            ->view('emails.announcement', ['a' => $this->announcement, 'url' => route('portal.announcements.show', $this->announcement)]);
    }
}
