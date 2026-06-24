<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Jobs\SendPayslipEmail;
use Illuminate\Http\Request;

class PayslipMailController extends Controller
{
    private const COMPANY_NAME = 'TRADESMART SUPPLIES LIMITED';
    private const COMPANY_LOGO = 'http://misc.tradesmartzm.com/logo.png';

    /*
    |==========================================================================
    | SEND ONE PAYSLIP — single employee button
    |==========================================================================
    |
    | Dispatched onto the queue rather than sent inline, so the request
    | returns instantly even if the mail server is slow. Same job class
    | used here as in the bulk send, so behavior is identical either way.
    |
    */
    public function sendSingle(Payroll $payroll)
    {
        \Log::info('sendSingle: Starting payslip send for payroll ID: ' . $payroll->id);
        
        $payroll->load('employee');
        \Log::info('sendSingle: Loaded employee data for payroll ID: ' . $payroll->id);

        if (empty($payroll->employee->personal_email)) {
            \Log::warning('sendSingle: No email on file for employee ' . $payroll->employee->first_name . ' ' . $payroll->employee->last_name);
            return back()->with('error',
                "{$payroll->employee->first_name} {$payroll->employee->last_name} has no email address on file."
            );
        }

        \Log::info('sendSingle: Dispatching payslip email to ' . $payroll->employee->personal_email);
        SendPayslipEmail::dispatch(
            $payroll->id,
            self::COMPANY_NAME,
            self::COMPANY_LOGO
        );

        \Log::info('sendSingle: Successfully queued payslip for payroll ID: ' . $payroll->id);
        return back()->with('success',
            "Payslip is being sent to {$payroll->employee->personal_email}."
        );
    }

    /*
    |==========================================================================
    | BULK SEND — all payslips in a run, to all employees
    |==========================================================================
    |
    | Dispatches one job PER employee rather than looping + sending inline.
    | This means:
    |   - The request returns immediately (no browser timeout waiting on
    |     50+ emails to send one by one)
    |   - One employee's bad email or mail failure doesn't block the rest
    |   - The queue worker naturally rate-limits sending instead of hammering
    |     the mail server with a burst of simultaneous connections
    |
    | Requires a queue worker running: php artisan queue:work
    | (or Horizon / supervisor in production)
    |
    */
    public function sendBulk(PayrollRun $run)
    {
        \Log::info('sendBulk: Starting bulk payslip send for payroll run ID: ' . $run->id);

        $payrolls = $run->payrolls()->with('employee')->get();
        \Log::info('sendBulk: Loaded ' . $payrolls->count() . ' payroll records');

        if ($payrolls->isEmpty()) {
            \Log::warning('sendBulk: No payslips found for payroll run ID: ' . $run->id);
            return back()->with('error', 'No payslips found for this run.');
        }

        $dispatched = 0;
        $skipped    = [];

        foreach ($payrolls as $payroll) {
            if (empty($payroll->employee->personal_email)) {
                \Log::warning('sendBulk: Skipping employee ' . $payroll->employee->first_name . ' ' . $payroll->employee->last_name . ' - no email on file');
                $skipped[] = $payroll->employee->first_name . ' ' . $payroll->employee->last_name;
                continue;
            }

            \Log::info('sendBulk: Dispatching payslip email to ' . $payroll->employee->personal_email . ' for payroll ID: ' . $payroll->id);
            SendPayslipEmail::dispatch(
                $payroll->id,
                self::COMPANY_NAME,
                self::COMPANY_LOGO
            );

            $dispatched++;
        }

        $message = "{$dispatched} payslip(s) queued for sending.";

        if (!empty($skipped)) {
            $message .= ' Skipped (no email on file): ' . implode(', ', $skipped) . '.';
        }

        \Log::info('sendBulk: Completed bulk send - dispatched: ' . $dispatched . ', skipped: ' . count($skipped));
        return back()->with($dispatched > 0 ? 'success' : 'error', $message);
    }
}