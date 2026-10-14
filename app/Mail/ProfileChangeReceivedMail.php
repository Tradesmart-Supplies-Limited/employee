<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\ProfileChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Employee's receipt: "we got your change" — what changed now, what HR is checking. */
class ProfileChangeReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public array $applied = [],
        public array $pending = [],
        public ?ProfileChangeRequest $change = null,
    ) {}

    public function build()
    {
        return $this->subject($this->pending ? 'We received your request to update your details' : 'Your details were updated')
            ->view('emails.portal.profile_change_received', ['url' => route('portal.profile.show')]);
    }
}
