<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;

/**
 * Every leave workflow email.
 *
 * $event    : submitted | supervisor_decision | final_decision
 * $audience : employee  | supervisor          | hr
 *
 * Templates: resources/views/emails/leave/workflow/{event}.blade.php
 */
class LeaveWorkflowMail extends Mailable
{
    public function __construct(
        public LeaveRequest $leave,
        public string $event,
        public string $audience,
    ) {}

    public function build()
    {
        return $this->subject($this->subjectLine())
            ->view('emails.leave.workflow.' . $this->event, [
                'leave'       => $this->leave,
                'audience'    => $this->audience,
                'steps'       => $this->leave->timeline(),
                'actionUrl'   => $this->actionUrl(),
                'actionLabel' => $this->actionLabel(),
                'brand'       => config('company.name'),
                'logo'        => config('company.logo_url'),
            ]);
    }

    private function subjectLine(): string
    {
        $l    = $this->leave;
        $who  = $l->employee_name;
        $what = "{$l->leave_type} leave, {$l->date_from->format('d M')} – {$l->date_to->format('d M Y')}";

        return match ($this->event) {
            'submitted' => match ($this->audience) {
                'employee'   => "Leave application received — {$what}",
                'supervisor' => "Action required: {$who}'s leave application",
                default      => "New leave application: {$who} ({$what})",
            },
            'supervisor_decision' => match ($this->audience) {
                'employee'   => "Supervisor review complete — {$what}",
                'supervisor' => "Recorded: you " . ($l->supervisor_decision === 'Approved' ? 'recommended' : 'did not recommend') . " {$who}'s leave",
                default      => "Awaiting final approval: {$who}'s leave",
            },
            default => match ($this->audience) {
                'employee' => "Your leave has been " . strtolower($l->status) . " — {$what}",
                default    => "{$who}'s leave was " . strtolower($l->status),
            },
        };
    }

    private function actionUrl(): ?string
    {
        $l = $this->leave;

        return match ($this->audience) {
            'employee' => $l->employee?->user ? route('portal.leave.show', $l) : null,
            'supervisor' => match (true) {
                (bool) $l->supervisorUser               => route('leave.show', $l),
                (bool) $l->supervisorEmployee?->user    => route('portal.team-leave.show', $l),
                default                                 => null,
            },
            default => route('leave.show', $l),
        };
    }

    private function actionLabel(): string
    {
        $needsAction = ($this->event === 'submitted' && $this->audience === 'supervisor')
            || ($this->event === 'supervisor_decision' && $this->audience === 'hr')
            || ($this->event === 'submitted' && $this->audience === 'hr');

        return $needsAction ? 'Review application' : 'View application';
    }
}
