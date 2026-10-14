<?php

namespace App\Mail;

use App\Models\ProfileChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Tells the employee HR approved / rejected their requested change. */
class ProfileChangeDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProfileChangeRequest $change) {}

    public function build()
    {
        $ok = $this->change->status === 'approved';

        return $this->subject($ok ? 'Your updated details have been approved' : 'Your requested change was not approved')
            ->view('emails.portal.profile_decision', [
                'ok'       => $ok,
                'employee' => $this->change->employee,
                'url'      => route('portal.profile.show'),
            ]);
    }
}
