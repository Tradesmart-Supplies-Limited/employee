@component('mail::message')

# Hi {{ $employee->first_name }},

Your payslip for **{{ $payroll->pay_period }}** is ready and attached to this email as a PDF.

@component('mail::table')
| | |
|:---|---:|
| Total earnings | K {{ number_format($payroll->total_income, 2) }} |
| Total deductions | K {{ number_format($payroll->total_deductions, 2) }} |
| **Net pay** | **K {{ number_format($payroll->net_pay, 2) }}** |
@endcomponent

If anything on your payslip looks incorrect, please contact HR before the next pay run.

Thanks,<br>
{{ $companyName }}

@component('mail::subcopy')
This is an automated message. Please do not reply directly to this email.
@endcomponent

@endcomponent