<?php

namespace App\Jobs;

use App\Models\LeaveRequest;
use App\Mail\LeaveSubmittedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaveSubmittedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private int $leaveId) {}

    public function handle(): void
    {
        $leave = LeaveRequest::find($this->leaveId);

        if (!$leave) {
            Log::warning("SendLeaveSubmittedEmail: LeaveRequest {$this->leaveId} not found — skipped.");
            return;
        }

        // NOTE: column is `email` on LeaveRequest, not `employee_email`.
        // (Blade templates referencing $leave->employee_email need the
        // same correction — see notes below.)
        if (empty($leave->email)) {
            Log::warning("SendLeaveSubmittedEmail: LeaveRequest {$this->leaveId} has no email — skipped.", [
                'employee_name' => $leave->employee_name ?? null,
            ]);
            return;
        }

        Mail::to($leave->email)->send(new LeaveSubmittedMail($leave));

        Log::info("SendLeaveSubmittedEmail: sent to {$leave->email}", [
            'leave_id' => $leave->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendLeaveSubmittedEmail: permanently failed for leave {$this->leaveId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}