<?php

namespace App\Jobs;

use App\Models\LeaveRequest;
use App\Mail\LeaveNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaveNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /*
    | NOTE: LeaveNotificationMail and LeaveAdminNotificationMail currently
    | render the exact same view (admin_notification.blade.php and
    | notification.blade.php are identical) and both go to HR/admin.
    | Kept as a separate job here since the underlying Mailables are
    | separate classes, but worth checking with whoever built the leave
    | module whether one of these two is dead code left over from a
    | refactor — sending the same notification twice to the same inbox
    | for one leave submission is the likely symptom if both call sites
    | are still wired up.
    */
    public function __construct(private int $leaveId) {}

    public function handle(): void
    {
        $leave = LeaveRequest::find($this->leaveId);

        if (!$leave) {
            Log::warning("SendLeaveNotificationEmail: LeaveRequest {$this->leaveId} not found — skipped.");
            return;
        }

        $recipients = config('company.hr_emails', []);

        if (empty($recipients)) {
            Log::warning("SendLeaveNotificationEmail: no hr_emails configured — skipped for leave {$this->leaveId}.");
            return;
        }

        Mail::to($recipients)->send(new LeaveNotificationMail($leave));

        Log::info("SendLeaveNotificationEmail: sent to " . implode(', ', $recipients), [
            'leave_id' => $leave->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendLeaveNotificationEmail: permanently failed for leave {$this->leaveId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}