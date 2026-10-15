<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Jobs\SendLeaveWorkflowEmail;
use App\Support\HrRecipients;
use Illuminate\Support\Facades\Log;

/**
 * Works out who should hear about a leave event and sends one email per person.
 *
 * Audiences:
 *   employee   – the applicant
 *   supervisor – the person they report to; if nobody is assigned, the
 *                Manager login(s) for the employee's department
 *   hr         – every HR (and Admin) login + HR_NOTIFICATION_EMAILS from .env
 *
 * Each address gets ONE email per event; if someone is both the supervisor and
 * in HR they get the supervisor version.
 *
 * Delivery: by default the emails go out straight after the page has loaded,
 * so they don't depend on a queue worker running. Set LEAVE_EMAILS_QUEUED=true
 * in .env if you run "php artisan queue:work" and want them queued instead.
 */
class LeaveNotifier
{
    public const SUBMITTED           = 'submitted';
    public const SUPERVISOR_DECISION = 'supervisor_decision';
    public const FINAL_DECISION      = 'final_decision';

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

        foreach (self::supervisorEmails($leave) as $email) {
            $queue($email, 'supervisor');
        }

        foreach (self::hrEmails() as $email) {
            $queue($email, 'hr');
        }

        Log::info("LeaveNotifier: {$event} for leave {$leave->id}", ['to' => $sent]);
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
