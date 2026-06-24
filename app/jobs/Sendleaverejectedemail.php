<?php

namespace App\Jobs;

use App\Models\LeaveRequest;
use App\Mail\LeaveRejectedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaveRejectedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private int $leaveId) {}

    public function handle(): void
    {
        $leave = LeaveRequest::find($this->leaveId);

        if (!$leave) {
            Log::warning("SendLeaveRejectedEmail: LeaveRequest {$this->leaveId} not found — skipped.");
            return;
        }

        if (empty($leave->email)) {
            Log::warning("SendLeaveRejectedEmail: LeaveRequest {$this->leaveId} has no email — skipped.", [
                'employee_name' => $leave->employee_name ?? null,
            ]);
            return;
        }

        Mail::to($leave->email)->send(new LeaveRejectedMail($leave));

        Log::info("SendLeaveRejectedEmail: sent to {$leave->email}", [
            'leave_id' => $leave->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendLeaveRejectedEmail: permanently failed for leave {$this->leaveId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}