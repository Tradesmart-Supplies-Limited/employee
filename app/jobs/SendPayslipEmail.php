<?php

namespace App\Jobs;

use App\Models\Payroll;
use App\Mail\PayslipMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\CompanyLogoResolver;


class SendPayslipEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds between retries

    private int $payrollId;
    private string $companyName;
    private ?string $companyLogo;

    /*
    | Store the ID, not the model — Payroll is re-fetched fresh inside
    | handle() so the job survives queue restarts / serialization safely
    | and always emails the latest payslip data, not a stale snapshot
    | taken at dispatch time.
    */
    public function __construct(int $payrollId, string $companyName, ?string $companyLogo = null)
    {
        $this->payrollId   = $payrollId;
        $this->companyName = $companyName;
        $this->companyLogo = CompanyLogoResolver::resolve(); 
    }

    public function handle(): void
    {
        $payroll = Payroll::with('employee')->find($this->payrollId);

        if (!$payroll) {
            Log::warning("SendPayslipEmail: Payroll {$this->payrollId} not found — skipped.");
            return;
        }

        $employee = $payroll->employee;

        if (!$employee || empty($employee->personal_email)) {
            Log::warning("SendPayslipEmail: Employee for payroll {$this->payrollId} has no email — skipped.", [
                'employee_id' => $employee->id ?? null,
            ]);
            return;
        }

        Mail::to($employee->personal_email)->send(
            new PayslipMail($payroll, $this->companyName, $this->companyLogo)
        );

        Log::info("SendPayslipEmail: sent payslip to {$employee->personal_email}", [
            'payroll_id'  => $payroll->id,
            'employee_id' => $employee->id,
            'period'      => $payroll->pay_period,
        ]);
    }

    /*
    | Called automatically by the queue worker if all retries are exhausted.
    | Logged so a failed bulk send doesn't silently disappear — surface
    | this in your payroll dashboard if you want a visible failure count.
    */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendPayslipEmail: permanently failed for payroll {$this->payrollId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}