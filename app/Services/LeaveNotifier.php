<?php

namespace App\Services;

use App\Services\Jobs\SendLeaveWorkflowEmail;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Works out who should hear about a leave event and queues one email per person.
 *
 * Audiences: employee (the applicant), supervisor (assigned approver), hr.
 * Each address gets ONE email per event; if someone is both the supervisor and on
 * the HR list they get the supervisor version.
 */
class LeaveNotifier
{
    public const SUBMITTED           = 'submitted';
    public const SUPERVISOR_DECISION = 'supervisor_decision';
    public const FINAL_DECISION      = 'final_decision';

    public static function send(LeaveRequest $leave, string $event): void
    {
        $leave->loadMissing(['supervisorEmployee.user', 'supervisorUser']);

        $sent = [];

        $queue = function (?string $email, string $audience) use (&$sent, $leave, $event) {
            $email = strtolower(trim((string) $email));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($sent[$email])) {
                return;
            }

            $sent[$email] = true;

            SendLeaveWorkflowEmail::dispatch($leave->id, $event, $audience, $email);
        };

        $queue($leave->email, 'employee');
        $queue($leave->supervisorEmail(), 'supervisor');

        foreach (self::hrEmails() as $email) {
            $queue($email, 'hr');
        }
    }

    /** HR_NOTIFICATION_EMAILS from .env, or every admin / HR user as a fallback. */
    public static function hrEmails(): array
    {
        $configured = config('company.hr_emails', []);

        if (! empty($configured)) {
            return $configured;
        }

        return User::whereIn(DB::raw('LOWER(role)'), ['admin', 'hr'])
            ->pluck('email')
            ->all();
    }
}
