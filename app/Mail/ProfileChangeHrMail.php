<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\ProfileChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * HR side of the portal "update my details" workflow.
 *
 * $event: submitted  (employee changed details: some applied now, some waiting for approval)
 *         decided    (another HR user approved / rejected a request — keeps the team in the loop)
 *         withdrawn  (employee cancelled their pending request)
 * $applied / $pending: [field => ['old' => ..., 'new' => ...]] — sensitive values are masked in the view.
 */
class ProfileChangeHrMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $event,
        public array $applied = [],
        public array $pending = [],
        public ?ProfileChangeRequest $change = null,
        public ?string $note = null,
        public ?string $actor = null,
    ) {}

    public function build()
    {
        $name = $this->employee->full_name;
        $subject = match ($this->event) {
            'decided'   => 'Detail change ' . ($this->change?->status === 'approved' ? 'approved' : 'rejected') . ": {$name}" . ($this->actor ? " (by {$this->actor})" : ''),
            'withdrawn' => "Detail change withdrawn: {$name}",
            default     => $this->pending ? "Approval needed: {$name} requested a change to their details" : "{$name} updated their details in the portal",
        };

        return $this->subject($subject)->view('emails.portal.profile_change_hr', [
            'reviewUrl'  => route('profile-changes.index') . ($this->change ? '#req-' . $this->change->id : ''),
            'profileUrl' => route('employees.show', $this->employee),
        ]);
    }
}
