<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells HR an employee changed something in the portal.
 *
 * $event: approval_needed | applied | document
 * $changes: [field => ['old' => ..., 'new' => ...]]  (sensitive values are masked)
 */
class ProfileUpdateHrMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $event,
        public array $changes = [],
        public ?string $note = null,
        public array $documents = [],
    ) {}

    public function build()
    {
        $subject = match ($this->event) {
            'approval_needed' => "Approval needed: {$this->employee->full_name} updated their details",
            'document'        => "{$this->employee->full_name} uploaded " . count($this->documents) . ' document(s)',
            default           => "{$this->employee->full_name} updated their details",
        };

        return $this->subject($subject)->view('emails.portal.profile_update_hr', [
            'reviewUrl'  => route('profile-changes.index'),
            'profileUrl' => route('employees.show', $this->employee) . ($this->event === 'document' ? '#documents' : ''),
        ]);
    }
}
