<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollReportController extends Controller
{
    /*
    |==========================================================================
    | MAIN REPORTS DASHBOARD
    |==========================================================================
    */
    public function index(Request $request)
    {
        $runs = PayrollRun::orderByDesc('id')->get();

        $selectedRunId = $request->get('run_id', $runs->first()?->id);
        $run = $selectedRunId
            ? PayrollRun::with(['payrolls.employee', 'payrolls.items'])->find($selectedRunId)
            : null;

        $summary = $run ? $this->buildRunSummary($run) : null;

        return view('dashboard.payroll.reports.index', compact('runs', 'run', 'summary'));
    }

    /*
    |==========================================================================
    | SHARED: build the headline summary numbers used across every report
    |==========================================================================
    */
    private function buildRunSummary(PayrollRun $run): array
    {
        $payrolls = $run->payrolls;

        $allItems = $payrolls->flatMap(fn($p) => $p->items);

        $totalEarnings   = $payrolls->sum('total_income');
        $totalDeductions = $payrolls->sum('total_deductions');
        $totalNet        = $payrolls->sum('net_pay');

        $paye  = $allItems->whereIn('code', ['D00', 'PAYE'])->sum('amount');
        $napsa = $allItems->whereIn('code', ['D02', 'NAPSA'])->sum('amount');
        $nhima = $allItems->whereIn('code', ['D11', 'NHIS', 'NHIMA'])->sum('amount');

        // Group every distinct earning/deduction line by code for the
        // comprehensive breakdown report
        $earningBreakdown = $allItems->where('type', 'earning')
            ->groupBy('code')
            ->map(fn($items) => [
                'description' => $items->first()->description,
                'count'       => $items->count(),
                'total'       => $items->sum('amount'),
            ])
            ->sortByDesc('total');

        $deductionBreakdown = $allItems->where('type', 'deduction')
            ->groupBy('code')
            ->map(fn($items) => [
                'description' => $items->first()->description,
                'count'       => $items->count(),
                'total'       => $items->sum('amount'),
            ])
            ->sortByDesc('total');

        // Departmental / branch cost-centre rollups — useful for HR & Finance
        $byDepartment = $payrolls->groupBy('cost_centre')->map(function ($group) {
            return [
                'employee_count'   => $group->count(),
                'total_earnings'   => $group->sum('total_income'),
                'total_deductions' => $group->sum('total_deductions'),
                'net_pay'          => $group->sum('net_pay'),
            ];
        });

        $byBranch = $payrolls->groupBy('branch')->map(function ($group) {
            return [
                'employee_count'   => $group->count(),
                'total_earnings'   => $group->sum('total_income'),
                'total_deductions' => $group->sum('total_deductions'),
                'net_pay'          => $group->sum('net_pay'),
            ];
        });

        return [
            'employee_count'      => $payrolls->count(),
            'total_earnings'      => $totalEarnings,
            'total_deductions'    => $totalDeductions,
            'total_net'           => $totalNet,
            'paye'                => $paye,
            'napsa'                => $napsa,
            'nhima'                => $nhima,
            'other_deductions'    => $totalDeductions - $paye - $napsa - $nhima,
            'earning_breakdown'   => $earningBreakdown,
            'deduction_breakdown' => $deductionBreakdown,
            'by_department'       => $byDepartment,
            'by_branch'           => $byBranch,
            'average_net'         => $payrolls->count() ? $totalNet / $payrolls->count() : 0,
            'highest_net'         => $payrolls->max('net_pay'),
            'lowest_net'          => $payrolls->min('net_pay'),
        ];
    }

    /*
    |==========================================================================
    | MANAGEMENT REPORTS — PDF EXPORT
    |==========================================================================
    |
    | report = total_earnings | total_deductions | net_payable |
    |          statutory_summary | comprehensive | department | branch
    |
    */
    public function exportManagementPdf(Request $request, PayrollRun $run, string $report)
    {
        $run->load(['payrolls.employee', 'payrolls.items']);
        $summary = $this->buildRunSummary($run);

        $titles = [
            'total_earnings'    => 'Total Earnings Report' . ' ' . $run->alias,
            'total_deductions'  => 'Total Deductions Report' . ' ' . $run->alias,
            'net_payable'       => 'Net Payable Report' . ' ' . $run->alias,
            'statutory_summary' => 'Statutory Summary Report' . ' ' . $run->alias,
            'comprehensive'     => 'Comprehensive Payroll Report' . ' ' . $run->alias,
            'department'        => 'Payroll by Department Report' . ' ' . $run->alias,
            'branch'            => 'Payroll by Branch Report' . ' ' . $run->alias,
        ];

        if (!isset($titles[$report])) {
            abort(404, 'Unknown report type.');
        }

        $companyName = 'TRADESMART SUPPLIES LIMITED';

        $pdf = Pdf::loadView('dashboard.payroll.reports.pdf.management', [
            'run'         => $run,
            'summary'     => $summary,
            'report'      => $report,
            'title'       => $titles[$report],
            'companyName' => $companyName,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            '%s-%s.pdf',
            str_replace(' ', '-', strtolower($titles[$report])),
            str_replace(' ', '-', strtolower($run->period))
        );

        return $pdf->download($filename);
    }

    /*
    |==========================================================================
    | STATUTORY EXPORT — NAPSA (CSV)
    |==========================================================================
    |
    | Column order confirmed against live NAPSA returns template:
    |
    |   company napsa number, year, month, ssn, nrc, lastName, first name,
    |   middle name, dob, gross, employee tax payable, company tax payable, [flag]
    |
    | NAPSA = 5% employee + 5% employer (matched), capped per statutory ceiling.
    | The "I" flag in the final column denotes an individual/active record —
    | kept for compatibility with the existing NAPSA upload format.
    |
    */
    public function exportNapsaCsv(PayrollRun $run)
    {
        $run->load(['payrolls.employee', 'payrolls.items']);

        [$year, $month] = $this->parsePeriod($run->period);

        $filename = "napsa-returns-{$run->period}.csv";

        return response()->streamDownload(function () use ($run, $year, $month) {

            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'company napsa number', 'year', 'month', 'ssn', 'nrc',
                'lastName', 'first name', 'middle name', 'dob',
                'gross', 'employee tax payable', 'company tax payable', '',
            ]);

            foreach ($run->payrolls as $payroll) {
                $employee = $payroll->employee;

                $napsaAmount = $payroll->items
                    ->whereIn('code', ['D02', 'NAPSA'])
                    ->sum('amount');

                fputcsv($handle, [
                    $employee->napsa_employer_no ?? '',
                    $year,
                    $month,
                    // Force as string to prevent Excel/PHP casting long SSNs
                    // to scientific notation (e.g. 5.56704E+13) — this exact
                    // bug is visible in one of the reference files.
                    (string) ($employee->ssn ?? ''),
                    $employee->nrc_no ?? '',
                    $employee->last_name,
                    $employee->first_name,
                    $employee->middle_name ?? '',
                    $employee->dob ? \Carbon\Carbon::parse($employee->dob)->format('d/m/Y') : '',
                    number_format($payroll->total_income, 2, '.', ''),
                    number_format($napsaAmount, 2, '.', ''),
                    number_format($napsaAmount, 2, '.', ''), // employer matches employee contribution
                    'I',
                ]);
            }

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |==========================================================================
    | STATUTORY EXPORT — ZRA PAYE (Excel)
    |==========================================================================
    |
    | Column order confirmed against live ZRA template:
    |
    |   tpin, fullName, employmentNature, grossEmoluments,
    |   chargeableEmoluments, totalTaxCredit, taxDeducted, taxAdjusted
    |
    */
    public function exportZraExcel(PayrollRun $run)
    {
        $run->load(['payrolls.employee', 'payrolls.items']);

        $rows = $run->payrolls->map(function ($payroll) {
            $employee = $payroll->employee;

            $paye = $payroll->items
                ->whereIn('code', ['D00', 'PAYE'])
                ->sum('amount');

            return [
                'tpin'                 => $employee->tpin ?? '',
                'fullName'             => strtoupper($employee->first_name . ' ' . $employee->last_name),
                'employmentNature'     => $employee->employment_nature ?? 'PERMANENT',
                'grossEmoluments'      => round($payroll->total_income, 2),
                'chargeableEmoluments' => round($payroll->total_income, 2),
                'totalTaxCredit'       => 0,
                'taxDeducted'          => round($paye, 2),
                'taxAdjusted'          => 0,
            ];
        });

        return $this->downloadXlsx(
            $rows,
            ['tpin', 'fullName', 'employmentNature', 'grossEmoluments', 'chargeableEmoluments', 'totalTaxCredit', 'taxDeducted', 'taxAdjusted'],
            "zra-paye-{$run->period}.xlsx",
            'PayeeEmployeeDetails'
        );
    }

    /*
    |==========================================================================
    | STATUTORY EXPORT — NHIMA / NHIS (CSV)
    |==========================================================================
    |
    | Same 13-column shape as NAPSA but with the NHIMA rate (1%) and the
    | NHIMA employer number instead of the NAPSA one. Reference file has
    | no header row, so this export matches that (headerless) by default,
    | with an optional header toggle.
    |
    */
    public function exportNhimaCsv(Request $request, PayrollRun $run)
    {
        $run->load(['payrolls.employee', 'payrolls.items']);

        [$year, $month] = $this->parsePeriod($run->period);
        $includeHeader = $request->boolean('with_header', false);

        $filename = "nhima-returns-{$run->period}.csv";

        return response()->streamDownload(function () use ($run, $year, $month, $includeHeader) {

            $handle = fopen('php://output', 'w');

            if ($includeHeader) {
                fputcsv($handle, [
                    'nhima employer number', 'year', 'month', 'ssn', 'nrc',
                    'lastName', 'first name', 'middle name', 'dob',
                    'gross', 'employee contribution', 'company contribution', '',
                ]);
            }

            foreach ($run->payrolls as $payroll) {
                $employee = $payroll->employee;

                $nhimaAmount = $payroll->items
                    ->whereIn('code', ['D11', 'NHIS', 'NHIMA'])
                    ->sum('amount');

                fputcsv($handle, [
                    $employee->nhima_no ?? '',
                    $year,
                    $month,
                    (string) ($employee->ssn ?? ''),
                    $employee->nrc_no ?? '',
                    $employee->last_name,
                    $employee->first_name,
                    $employee->middle_name ?? '',
                    $employee->dob ? \Carbon\Carbon::parse($employee->dob)->format('d/m/Y') : '',
                    number_format($payroll->total_income, 2, '.', ''),
                    number_format($nhimaAmount, 2, '.', ''),
                    number_format($nhimaAmount, 2, '.', ''),
                    'I',
                ]);
            }

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |==========================================================================
    | STATUTORY EXPORT — WORKERS' COMPENSATION FUND (CSV)
    |==========================================================================
    |
    | Workers' Compensation Fund Control Board (WCFCB) returns in Zambia
    | are typically based on annual insurable earnings; this export gives
    | a per-period contribution line so it can be accumulated/filed annually.
    | No official reference template was supplied — this mirrors the NAPSA/
    | NHIMA shape since WCFCB returns follow the same general convention.
    | Confirm the exact column order with WCFCB/your broker before filing.
    |
    */
    public function exportWcfcbCsv(PayrollRun $run)
    {
        $run->load(['payrolls.employee', 'payrolls.items']);

        [$year, $month] = $this->parsePeriod($run->period);

        $filename = "wcfcb-returns-{$run->period}.csv";

        return response()->streamDownload(function () use ($run, $year, $month) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'employer registration number', 'year', 'month', 'nrc',
                'lastName', 'first name', 'job title', 'gross earnings',
                'insurable earnings',
            ]);

            foreach ($run->payrolls as $payroll) {
                $employee = $payroll->employee;

                fputcsv($handle, [
                    $employee->wcfcb_employer_no ?? '',
                    $year,
                    $month,
                    $employee->nrc_no ?? '',
                    $employee->last_name,
                    $employee->first_name,
                    $employee->position ?? '',
                    number_format($payroll->total_income, 2, '.', ''),
                    number_format($payroll->total_income, 2, '.', ''),
                ]);
            }

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |==========================================================================
    | BANKING — BULK PAYMENT CSV
    |==========================================================================
    |
    | Column order confirmed against live bank upload template:
    |
    |   # Account Number, Amount, Beneficiary Name, My Reference,
    |   Beneficiary Reference, # Bank sort Code, #Bank reference Number, Address
    |
    | The "#" prefixes are literal — they appear in both the header row and
    | populated data rows in the reference files, so they're reproduced
    | exactly here rather than treated as comment markers.
    |
    */
    public function exportBankPaymentsCsv(Request $request, PayrollRun $run)
    {
        $run->load(['payrolls.employee']);

        $reference = $request->get('reference', strtoupper($run->period) . ' SALARY');

        $filename = "bank-bulk-payments-{$run->period}.csv";

        return response()->streamDownload(function () use ($run, $reference) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                '# Account Number', 'Amount', 'Beneficiary Name', 'My Reference',
                'Beneficiary Reference', '# Bank sort Code', '#Bank reference Number', 'Address',
            ]);

            foreach ($run->payrolls as $payroll) {
                $employee = $payroll->employee;
                $fullName = $employee->first_name . ' ' . $employee->last_name;

                fputcsv($handle, [
                    '#' . ($employee->bank_account_no ?? ''),
                    number_format($payroll->net_pay, 2, '.', ''),
                    $fullName,
                    $reference,
                    $fullName,
                    '#' . ($employee->bank_sort_code ?? ''),
                    '#' . ($employee->id ?? ''),
                    $employee->bank_branch ?? ($payroll->branch ?? ''),
                ]);
            }

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |==========================================================================
    | SHARED HELPERS
    |==========================================================================
    */

    /*
    | "period" is typically stored as a free-text label like "JUNE 2026" —
    | this resolves it to numeric [year, month] for statutory exports that
    | require those as separate fields.
    */
    private function parsePeriod(string $period): array
    {
        try {
            $date = \Carbon\Carbon::parse($period);
            return [$date->year, $date->month];
        } catch (\Exception $e) {
            // Fallback: try "Month YYYY" format manually
            if (preg_match('/([A-Za-z]+)\s+(\d{4})/', $period, $matches)) {
                $date = \Carbon\Carbon::parse($matches[1] . ' 1, ' . $matches[2]);
                return [$date->year, $date->month];
            }
            return [now()->year, now()->month];
        }
    }

    /*
    | Minimal xlsx writer using PhpSpreadsheet (already a Laravel/Composer
    | staple via maatwebsite/excel or phpoffice/phpspreadsheet). Adjust the
    | namespace below to match whichever package you have installed.
    */
    private function downloadXlsx(\Illuminate\Support\Collection $rows, array $headers, string $filename, string $sheetName = 'Sheet1')
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        // Header row
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Data rows
        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowIndex + 2, $row[$header] ?? '');
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}