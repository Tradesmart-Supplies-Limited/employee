<?php

namespace App\Jobs;

use App\Models\LeaveRequest;
use App\Mail\LeaveAdminNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaveAdminNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private int $leaveId) {}

    public function handle(): void
    {
        $leave = LeaveRequest::find($this->leaveId);

        if (!$leave) {
            Log::warning("SendLeaveAdminNotificationEmail: LeaveRequest {$this->leaveId} not found — skipped.");
            return;
        }

        $recipients = config('company.hr_emails', []);

        if (empty($recipients)) {
            Log::warning("SendLeaveAdminNotificationEmail: no hr_emails configured — skipped for leave {$this->leaveId}.");
            return;
        }

        Mail::to($recipients)->send(new LeaveAdminNotificationMail($leave));

        Log::info("SendLeaveAdminNotificationEmail: sent to " . implode(', ', $recipients), [
            'leave_id' => $leave->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendLeaveAdminNotificationEmail: permanently failed for leave {$this->leaveId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}