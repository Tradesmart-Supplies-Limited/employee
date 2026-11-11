<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;

/**
 * Every leave workflow email.
 *
 * $event    : submitted | step_decision | final_decision | comment
 *            (older queued "supervisor_decision" jobs are read as step_decision)
 * $audience : employee  | supervisor | hr | director
 *
 * Templates: resources/views/emails/leave/workflow/{event}.blade.php
 */
class LeaveWorkflowMail extends Mailable
{
    public function __construct(
        public LeaveRequest $leave,
        public string $event,
        public string $audience,
        public ?\App\Models\LeaveComment $comment = null,
    ) {}

    public function build()
    {
        if ($this->event === 'supervisor_decision') {
            $this->event = 'step_decision';
        }

        $decided = $this->leave->lastDecidedStep();
        $next = $this->leave->currentStep();

        return $this->subject($this->subjectLine())
            ->view('emails.leave.workflow.' . $this->event, [
                'decided'     => $decided,
                'decidedBy'   => $decided ? $this->leave->{$decided . '_name'} : null,
                'decision'    => $decided ? $this->leave->{$decided . '_decision'} : null,
                'decisionNote'=> $decided ? $this->leave->{$decided . '_comments'} : null,
                'next'        => $next,
                'myTurn'      => $next !== null && $next === $this->audience,
                'stepWho'     => fn (?string $s) => self::who($s),
                'comment'     => $this->comment,
                'overridden'  => $this->leave->overridden_step && $this->leave->overridden_at && $this->leave->overridden_at->gt(now()->subMinutes(10)),
                'leave'       => $this->leave,
                'audience'    => $this->audience,
                'steps'       => $this->leave->timeline(),
                'actionUrl'   => $this->actionUrl(),
                'actionLabel' => $this->actionLabel(),
                'brand'       => config('company.name'),
                'logo'        => config('company.logo_url'),
            ]);
    }

    /** "your supervisor", "HR", "the Director" */
    public static function who(?string $step): string
    {
        return match ($step) {
            'supervisor' => 'the supervisor',
            'hr'         => 'HR',
            'director'   => 'Management',
            default      => 'HR',
        };
    }

    private function subjectLine(): string
    {
        $l    = $this->leave;
        $who  = $l->employee_name;
        $what = "{$l->leave_type} leave, {$l->date_from->format('d M')} – {$l->date_to->format('d M Y')}";
        $next = $l->currentStep();
        $myTurn = $next !== null && $next === $this->audience;
        $decided = $l->lastDecidedStep();

        $fresh = $l->overridden_at && $l->overridden_at->gt(now()->subMinutes(10));

        return match ($this->event) {
            'submitted' => match (true) {
                $this->audience === 'employee' => "We have received your leave application: {$what}",
                $myTurn                        => "Your approval, please: {$who}'s leave application",
                default                        => "New leave application from {$who} ({$what})",
            },
            'step_decision' => match (true) {
                $this->audience === 'employee' && $fresh => "Good news: your leave is moving forward again ({$what})",
                $this->audience === 'employee' => ucfirst(self::who($decided)) . " has approved your leave. It is now with " . self::who($next),
                $myTurn                        => "Your approval, please: {$who}'s leave ({$what})",
                $this->audience === $decided   => "Thank you: your approval of {$who}'s leave is recorded",
                default                        => "{$who}'s leave is now with " . self::who($next),
            },
            'comment' => $this->audience === 'employee'
                ? "New comment on your leave request ({$what})"
                : "New comment on {$who}'s leave request",
            default => match ($this->audience) {
                'employee' => $l->status === 'Approved'
                    ? "Your leave has been approved: {$what}"
                    : "Update on your leave request: it has been reverted ({$what})",
                default    => "{$who}'s leave has been " . ($l->status === 'Approved' ? 'approved' : 'reverted'),
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
                (bool) $l->supervisorEmployee?->user?->canUseAdmin() => route('leave.show', $l),
                (bool) $l->supervisorEmployee?->user    => route('portal.team-leave.show', $l),
                $l->hasAssignedSupervisor()             => null, // supervisor has no login yet
                default                                 => route('leave.show', $l), // department manager
            },
            default => route('leave.show', $l),
        };
    }

    private function actionLabel(): string
    {
        $next = $this->leave->currentStep();

        return match (true) {
            $this->event === 'comment' => 'View and reply',
            $this->event !== 'final_decision' && $next !== null && $next === $this->audience => 'Review the request',
            default => 'View the request',
        };
    }
}
