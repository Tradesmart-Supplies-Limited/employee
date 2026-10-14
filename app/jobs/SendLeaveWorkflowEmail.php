<?php

namespace App\Jobs;

use App\Mail\LeaveWorkflowMail;
use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * One leave-workflow email to one recipient. One job per recipient means a bad
 * address only retries itself, never re-sends to everyone else.
 */
class SendLeaveWorkflowEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private int $leaveId,
        private string $event,
        private string $audience,
        private string $email,
    ) {}

    public function handle(): void
    {
        $leave = LeaveRequest::with(['employee.user', 'supervisorEmployee.user', 'supervisorUser'])
            ->find($this->leaveId);

        if (! $leave) {
            Log::warning("SendLeaveWorkflowEmail: leave {$this->leaveId} no longer exists — skipped.");
            return;
        }

        Mail::to($this->email)->send(new LeaveWorkflowMail($leave, $this->event, $this->audience));

        Log::info("SendLeaveWorkflowEmail: {$this->event} → {$this->audience} <{$this->email}>", [
            'leave_id' => $leave->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendLeaveWorkflowEmail: failed {$this->event} → {$this->email} for leave {$this->leaveId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
