<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        // Database-agnostic list of years that have released payslips.
        $years = Payroll::where('employee_id', $employee->id)
            ->whereIn('status', DashboardController::RELEASED_STATUSES)
            ->pluck('created_at')
            ->map(fn ($d) => $d->year)
            ->unique()
            ->sortDesc()
            ->values();

        $payslips = Payroll::with('run')
            ->where('employee_id', $employee->id)
            ->whereIn('status', DashboardController::RELEASED_STATUSES)
            ->when($request->filled('year'), fn ($q) => $q->whereYear('created_at', $request->year))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('portal.payslips.index', compact('payslips', 'years'));
    }

    public function show(Request $request, Payroll $payroll)
    {
        $this->authorizeOwner($request, $payroll);

        $payroll->load(['items', 'run', 'employee']);

        return view('portal.payslips.show', [
            'payroll'    => $payroll,
            'earnings'   => $payroll->items->where('type', 'earning'),
            'deductions' => $payroll->items->where('type', 'deduction'),
        ]);
    }

    public function pdf(Request $request, Payroll $payroll)
    {
        $this->authorizeOwner($request, $payroll);

        $payroll->load(['employee', 'items', 'run']);

        // Same approach as the admin PayrollController::downloadPdf()
        $run = $payroll->run;
        $run->setRelation('payrolls', collect([$payroll]));

        $pdf = Pdf::loadView('dashboard.payroll.runs.payslip_pdf', [
            'run'         => $run,
            'companyName' => config('company.name'),
            'companyLogo' => config('company.logo_url'),
            'payroll'     => $payroll,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'payslip-%s-%s.pdf',
            $payroll->employee->employee_id,
            str_replace(' ', '-', strtolower($payroll->pay_period))
        );

        return $pdf->download($filename);
    }

    /** Own, released payslips only. 404 (not 403) so IDs can't be probed. */
    private function authorizeOwner(Request $request, Payroll $payroll): void
    {
        abort_unless(
            $payroll->employee_id === $request->user()->employee_id
                && in_array($payroll->status, DashboardController::RELEASED_STATUSES, true),
            404
        );
    }
}
