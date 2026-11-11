<?php

namespace App\Services;

use App\Models\LeaveApprovalSetting;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Jobs\SendLeaveWorkflowEmail;
use App\Support\HrRecipients;
use Illuminate\Support\Facades\Log;

/**
 * Works out who should hear about a leave event and sends one email per person.
 *
 * Events:
 *   submitted      – the request was made
 *   step_decision  – a step approved and the request moved on to the next approver
 *   final_decision – approved at the last step, or reverted at any step
 *
 * Audiences:
 *   employee   – the applicant
 *   supervisor – the person they report to; if nobody is assigned, the
 *                Manager login(s) for the employee's department
 *   hr         – every HR (and Admin) login + HR_NOTIFICATION_EMAILS from .env
 *   director   – the Director named in the approval chain settings, or every
 *                Director login
 *
 * The approver whose turn it is always comes first, so if someone is in two
 * groups they get the "action required" version. Each address gets ONE email
 * per event.
 *
 * Delivery: by default the emails go out straight after the page has loaded,
 * so they don't depend on a queue worker running. Set LEAVE_EMAILS_QUEUED=true
 * in .env if you run "php artisan queue:work" and want them queued instead.
 */
class LeaveNotifier
{
    public const SUBMITTED      = 'submitted';
    public const STEP_DECISION  = 'step_decision';
    public const FINAL_DECISION = 'final_decision';
    public const COMMENT        = 'comment';

    /** @deprecated older name for STEP_DECISION */
    public const SUPERVISOR_DECISION = self::STEP_DECISION;

    /** null = ask Laravel whether this is a console command (tests can force it). */
    public static ?bool $runningInConsole = null;

    public static function send(LeaveRequest $leave, string $event): void
    {
        $leave->loadMissing(['employee', 'supervisorEmployee.user', 'supervisorUser']);

        $sent = [];

        $queue = function (?string $email, string $audience) use (&$sent, $leave, $event) {
            $email = strtolower(trim((string) $email));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($sent[$email])) {
                return;
            }

            $sent[$email] = $audience;
            self::deliver(new SendLeaveWorkflowEmail($leave->id, $event, $audience, $email));
        };

        $queue($leave->email, 'employee');

        // Whoever must act next hears first.
        if ($event !== self::FINAL_DECISION && ($step = $leave->currentStep())) {
            foreach (self::stepEmails($leave, $step) as $email) {
                $queue($email, $step);
            }
        }

        $supervisorInvolved = $leave->inChain('supervisor') && $leave->supervisor_decision !== 'Skipped';
        // Management hears the final outcome when they took part, and also after
        // a revert (so they can comment, or override it).
        $directorInvolved = $leave->inChain('director') && $leave->director_decision !== 'Skipped'
            && (filled($leave->director_decision) || $leave->status === 'Rejected');

        if ($supervisorInvolved && ($event !== self::STEP_DECISION || $leave->lastDecidedStep() === 'supervisor')) {
            foreach (self::supervisorEmails($leave) as $email) {
                $queue($email, 'supervisor');
            }
        }

        foreach (self::hrEmails() as $email) {
            $queue($email, 'hr');
        }

        if ($event === self::FINAL_DECISION && $directorInvolved) {
            foreach (self::directorEmails() as $email) {
                $queue($email, 'director');
            }
        }

        Log::info("LeaveNotifier: {$event} for leave {$leave->id}", ['to' => $sent]);
    }

    /**
     * A new comment: tell the applicant and everyone in the chain, except the author.
     */
    public static function comment(LeaveRequest $leave, \App\Models\LeaveComment $comment): void
    {
        $leave->loadMissing(['employee', 'supervisorEmployee.user', 'supervisorUser']);
        $author = strtolower((string) $comment->user?->email);
        $sent = [$author => true];

        $queue = function (?string $email, string $audience) use (&$sent, $leave, $comment) {
            $email = strtolower(trim((string) $email));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($sent[$email])) {
                return;
            }
            $sent[$email] = $audience;
            self::deliver(new SendLeaveWorkflowEmail($leave->id, self::COMMENT, $audience, $email, $comment->id));
        };

        $queue($leave->email, 'employee');
        foreach ($leave->chain() as $step) {
            if ($leave->{$step . '_decision'} === 'Skipped') {
                continue;
            }
            foreach (self::stepEmails($leave, $step) as $email) {
                $queue($email, $step);
            }
        }

        unset($sent[$author]);
        Log::info("LeaveNotifier: comment {$comment->id} on leave {$leave->id}", ['to' => $sent]);
    }

    /** @return string[] the people who decide $step */
    public static function stepEmails(LeaveRequest $leave, string $step): array
    {
        return match ($step) {
            'supervisor' => self::supervisorEmails($leave),
            'hr'         => self::hrEmails(),
            'director'   => self::directorEmails(),
            default      => [],
        };
    }

    /**
     * The assigned supervisor; when there is none (or they have no email),
     * the Manager login(s) responsible for the employee's department.
     *
     * @return string[]
     */
    public static function supervisorEmails(LeaveRequest $leave): array
    {
        if ($email = $leave->supervisorEmail()) {
            return [$email];
        }

        $employee = $leave->employee;
        $dept = trim((string) $employee?->department);
        if ($dept === '') {
            return [];
        }

        return User::whereRaw('LOWER(role) = ?', ['manager'])->with('employee')->get()
            ->filter(fn (User $u) => in_array($dept, $u->managedDepartments() ?? [], true))
            ->reject(fn (User $u) => $employee && $u->employee_id === $employee->id) // not their own leave
            ->pluck('email')->values()->all();
    }

    /** HR: every HR / Admin login plus HR_NOTIFICATION_EMAILS from .env. */
    public static function hrEmails(): array
    {
        return HrRecipients::all();
    }

    /** The Director named in the approval settings, otherwise every Director login. */
    public static function directorEmails(): array
    {
        $named = LeaveApprovalSetting::current()->director;
        $users = $named ? collect([$named]) : User::whereRaw('LOWER(role) = ?', ['director'])->get();

        return $users->pluck('email')->filter()->map(fn ($e) => strtolower(trim($e)))->unique()->values()->all();
    }

    private static function deliver(SendLeaveWorkflowEmail $job): void
    {
        if (config('leave.queue_emails')) {
            dispatch($job);
        } elseif (self::$runningInConsole ?? app()->runningInConsole()) {
            dispatch_sync($job);
        } else {
            dispatch($job)->afterResponse();
        }
    }
}
