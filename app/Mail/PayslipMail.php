<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\CompanyLogoResolver;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public Payroll $payroll;
    public string $companyName;
    public ?string $companyLogo;

    /*
    |--------------------------------------------------------------------------
    | The PDF is generated INSIDE the mailable (in build()), not passed in
    | pre-rendered. This matters for queued bulk sends: only the Payroll ID
    | gets serialized onto the queue, not a multi-KB PDF binary. Each worker
    | regenerates the PDF fresh when the job actually runs.
    |--------------------------------------------------------------------------
    */
    public function __construct(Payroll $payroll, string $companyName, ?string $companyLogo = null)
    {
        $this->payroll     = $payroll;
        $this->companyName = $companyName;
        $this->companyLogo = $companyLogo;
    }

    public function build()
    {
        $employee = $this->payroll->employee;

        // Reuse the exact same template/data shape as the PDF download route —
        // single-employee run, built the same way as PayrollController::downloadPdf()
        $run = $this->payroll->run;
        $run->setRelation('payrolls', collect([$this->payroll]));

        $pdf = Pdf::loadView('dashboard.payroll.runs.payslip_pdf', [
            'run'         => $run,
            'companyName' => $this->companyName,
            'companyLogo' => CompanyLogoResolver::resolve(),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'payslip-%s-%s.pdf',
            $employee->employee_id,
            str_replace(' ', '-', strtolower($this->payroll->pay_period))
        );

        return $this->subject("Your Payslip — {$this->payroll->pay_period}")
            ->markdown('emails.payslip', [
                'employee'    => $employee,
                'payroll'     => $this->payroll,
                'companyName' => $this->companyName,
            ])
            ->attachData($pdf->output(), $filename, [
                'mime' => 'application/pdf',
            ]);
    }
}