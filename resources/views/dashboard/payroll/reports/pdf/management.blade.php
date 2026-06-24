<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
    @page { size: A4; margin: 14mm 12mm; }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1c1c1c; margin: 0; }

    .doc-header {
        display: table;
        width: 100%;
        border-bottom: 2px solid #1c1c1c;
        padding-bottom: 10px;
        margin-bottom: 16px;
    }
    .doc-header-left, .doc-header-right { display: table-cell; vertical-align: top; }
    .doc-header-right { text-align: right; }
    .company-name { font-size: 15px; font-weight: bold; }
    .report-title { font-size: 13px; color: #444; margin-top: 2px; }
    .meta-line { font-size: 10px; color: #555; margin-top: 1px; }

    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.data-table th {
        background: #f2f2f2;
        border: 1px solid #ccc;
        padding: 5px 7px;
        text-align: left;
        font-size: 10px;
        text-transform: uppercase;
    }
    table.data-table td {
        border: 1px solid #ddd;
        padding: 4px 7px;
        font-size: 10.5px;
    }
    table.data-table td.num { text-align: right; font-family: 'Courier New', monospace; }
    table.data-table tr.total-row td { font-weight: bold; border-top: 2px solid #1c1c1c; background: #f7f7f7; }

    .section-title { font-size: 12px; font-weight: bold; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #999; }

    .summary-strip { display: table; width: 100%; margin-bottom: 16px; }
    .summary-box { display: table-cell; border: 1px solid #ccc; padding: 8px 10px; text-align: center; }
    .summary-box-label { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 3px; }
    .summary-box-value { font-size: 13px; font-weight: bold; }

    .footer-note { margin-top: 20px; font-size: 9px; color: #888; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; }

    .green { color: #1c6b2e; }
    .red { color: #a32f26; }
</style>
</head>
<body>

    {{-- ════════════════════════════════════════════════════════
         DOCUMENT HEADER (shared across all report types)
    ════════════════════════════════════════════════════════ --}}
    <div class="doc-header">
        <div class="doc-header-left">
            <div class="company-name">{{ $companyName }}</div>
            <div class="report-title">{{ $title }}</div>
        </div>
        <div class="doc-header-right">
            <div class="meta-line"><strong>Pay period:</strong> {{ $run->period }}</div>
            <div class="meta-line"><strong>Employees:</strong> {{ $summary['employee_count'] }}</div>
            <div class="meta-line"><strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}</div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         REPORT: TOTAL EARNINGS
    ════════════════════════════════════════════════════════ --}}
    @if($report === 'total_earnings')

        <div class="section-title">Earnings breakdown by type</div>
        <table class="data-table">
            <thead>
                <tr><th>Code</th><th>Description</th><th>Employees</th><th style="text-align:right">Total amount</th></tr>
            </thead>
            <tbody>
                @foreach($summary['earning_breakdown'] as $code => $line)
                    <tr>
                        <td>{{ $code }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td>{{ $line['count'] }}</td>
                        <td class="num">K {{ number_format($line['total'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total earnings</td>
                    <td class="num">K {{ number_format($summary['total_earnings'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Per-employee earnings</div>
        <table class="data-table">
            <thead>
                <tr><th>Employee ID</th><th>Name</th><th>Department</th><th style="text-align:right">Total earnings</th></tr>
            </thead>
            <tbody>
                @foreach($run->payrolls as $payroll)
                    <tr>
                        <td>{{ $payroll->employee->employee_id }}</td>
                        <td>{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
                        <td>{{ $payroll->cost_centre }}</td>
                        <td class="num">K {{ number_format($payroll->total_income, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total</td>
                    <td class="num">K {{ number_format($summary['total_earnings'], 2) }}</td>
                </tr>
            </tbody>
        </table>

    {{-- ════════════════════════════════════════════════════════
         REPORT: TOTAL DEDUCTIONS
    ════════════════════════════════════════════════════════ --}}
    @elseif($report === 'total_deductions')

        <div class="section-title">Deductions breakdown by type</div>
        <table class="data-table">
            <thead>
                <tr><th>Code</th><th>Description</th><th>Employees</th><th style="text-align:right">Total amount</th></tr>
            </thead>
            <tbody>
                @foreach($summary['deduction_breakdown'] as $code => $line)
                    <tr>
                        <td>{{ $code }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td>{{ $line['count'] }}</td>
                        <td class="num">K {{ number_format($line['total'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total deductions</td>
                    <td class="num">K {{ number_format($summary['total_deductions'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Per-employee deductions</div>
        <table class="data-table">
            <thead>
                <tr><th>Employee ID</th><th>Name</th><th>Department</th><th style="text-align:right">Total deductions</th></tr>
            </thead>
            <tbody>
                @foreach($run->payrolls as $payroll)
                    <tr>
                        <td>{{ $payroll->employee->employee_id }}</td>
                        <td>{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
                        <td>{{ $payroll->cost_centre }}</td>
                        <td class="num">K {{ number_format($payroll->total_deductions, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total</td>
                    <td class="num">K {{ number_format($summary['total_deductions'], 2) }}</td>
                </tr>
            </tbody>
        </table>

    {{-- ════════════════════════════════════════════════════════
         REPORT: NET PAYABLE
    ════════════════════════════════════════════════════════ --}}
    @elseif($report === 'net_payable')

        <div class="summary-strip">
            <div class="summary-box">
                <div class="summary-box-label">Average net pay</div>
                <div class="summary-box-value">K {{ number_format($summary['average_net'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Highest net pay</div>
                <div class="summary-box-value">K {{ number_format($summary['highest_net'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Lowest net pay</div>
                <div class="summary-box-value">K {{ number_format($summary['lowest_net'], 2) }}</div>
            </div>
        </div>

        <div class="section-title">Net payable per employee</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee ID</th><th>Name</th><th>Department</th>
                    <th style="text-align:right">Earnings</th>
                    <th style="text-align:right">Deductions</th>
                    <th style="text-align:right">Net pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($run->payrolls->sortByDesc('net_pay') as $payroll)
                    <tr>
                        <td>{{ $payroll->employee->employee_id }}</td>
                        <td>{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
                        <td>{{ $payroll->cost_centre }}</td>
                        <td class="num">K {{ number_format($payroll->total_income, 2) }}</td>
                        <td class="num">K {{ number_format($payroll->total_deductions, 2) }}</td>
                        <td class="num"><strong>K {{ number_format($payroll->net_pay, 2) }}</strong></td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total payable</td>
                    <td class="num">K {{ number_format($summary['total_earnings'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['total_deductions'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['total_net'], 2) }}</td>
                </tr>
            </tbody>
        </table>

    {{-- ════════════════════════════════════════════════════════
         REPORT: STATUTORY SUMMARY
    ════════════════════════════════════════════════════════ --}}
    @elseif($report === 'statutory_summary')

        <div class="summary-strip">
            <div class="summary-box">
                <div class="summary-box-label">PAYE</div>
                <div class="summary-box-value red">K {{ number_format($summary['paye'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">NAPSA</div>
                <div class="summary-box-value red">K {{ number_format($summary['napsa'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">NHIMA</div>
                <div class="summary-box-value red">K {{ number_format($summary['nhima'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Other deductions</div>
                <div class="summary-box-value">K {{ number_format($summary['other_deductions'], 2) }}</div>
            </div>
        </div>

        <div class="section-title">Per-employee statutory contributions</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee ID</th><th>Name</th>
                    <th style="text-align:right">Gross</th>
                    <th style="text-align:right">PAYE</th>
                    <th style="text-align:right">NAPSA</th>
                    <th style="text-align:right">NHIMA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($run->payrolls as $payroll)
                    @php
                        $paye  = $payroll->items->whereIn('code', ['D00','PAYE'])->sum('amount');
                        $napsa = $payroll->items->whereIn('code', ['D02','NAPSA'])->sum('amount');
                        $nhima = $payroll->items->whereIn('code', ['D11','NHIS','NHIMA'])->sum('amount');
                    @endphp
                    <tr>
                        <td>{{ $payroll->employee->employee_id }}</td>
                        <td>{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
                        <td class="num">K {{ number_format($payroll->total_income, 2) }}</td>
                        <td class="num">K {{ number_format($paye, 2) }}</td>
                        <td class="num">K {{ number_format($napsa, 2) }}</td>
                        <td class="num">K {{ number_format($nhima, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td class="num">K {{ number_format($summary['total_earnings'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['paye'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['napsa'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['nhima'], 2) }}</td>
                </tr>
            </tbody>
        </table>

    {{-- ════════════════════════════════════════════════════════
         REPORT: COMPREHENSIVE
    ════════════════════════════════════════════════════════ --}}
    @elseif($report === 'comprehensive')

        <div class="summary-strip">
            <div class="summary-box">
                <div class="summary-box-label">Total earnings</div>
                <div class="summary-box-value green">K {{ number_format($summary['total_earnings'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Total deductions</div>
                <div class="summary-box-value red">K {{ number_format($summary['total_deductions'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-box-label">Net payable</div>
                <div class="summary-box-value">K {{ number_format($summary['total_net'], 2) }}</div>
            </div>
        </div>

        @foreach($run->payrolls as $payroll)
            <div class="section-title">
                {{ $payroll->employee->employee_id }} — {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}
                <span style="font-weight:normal;color:#666;">({{ $payroll->cost_centre }}, {{ $payroll->branch }})</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th style="width:50%">Earnings</th><th style="text-align:right">Amount</th><th style="width:30%">Deductions</th><th style="text-align:right">Amount</th></tr>
                </thead>
                <tbody>
                    @php
                        $earnings = $payroll->items->where('type','earning')->values();
                        $deductions = $payroll->items->where('type','deduction')->values();
                        $rows = max($earnings->count(), $deductions->count());
                    @endphp
                    @for($i = 0; $i < $rows; $i++)
                        <tr>
                            <td>{{ $earnings->get($i)?->description }}</td>
                            <td class="num">{{ $earnings->get($i) ? number_format($earnings->get($i)->amount, 2) : '' }}</td>
                            <td>{{ $deductions->get($i)?->description }}</td>
                            <td class="num">{{ $deductions->get($i) ? number_format($deductions->get($i)->amount, 2) : '' }}</td>
                        </tr>
                    @endfor
                    <tr class="total-row">
                        <td>Total earnings</td>
                        <td class="num">K {{ number_format($payroll->total_income, 2) }}</td>
                        <td>Total deductions</td>
                        <td class="num">K {{ number_format($payroll->total_deductions, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3">NET PAY</td>
                        <td class="num">K {{ number_format($payroll->net_pay, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach

    {{-- ════════════════════════════════════════════════════════
         REPORT: BY DEPARTMENT
    ════════════════════════════════════════════════════════ --}}
    @elseif($report === 'department')

        <div class="section-title">Payroll cost by department</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Department</th><th>Employees</th>
                    <th style="text-align:right">Earnings</th>
                    <th style="text-align:right">Deductions</th>
                    <th style="text-align:right">Net pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['by_department'] as $dept => $line)
                    <tr>
                        <td>{{ $dept ?: 'Unassigned' }}</td>
                        <td>{{ $line['employee_count'] }}</td>
                        <td class="num">K {{ number_format($line['total_earnings'], 2) }}</td>
                        <td class="num">K {{ number_format($line['total_deductions'], 2) }}</td>
                        <td class="num">K {{ number_format($line['net_pay'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td class="num">K {{ number_format($summary['total_earnings'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['total_deductions'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['total_net'], 2) }}</td>
                </tr>
            </tbody>
        </table>

    {{-- ════════════════════════════════════════════════════════
         REPORT: BY BRANCH
    ════════════════════════════════════════════════════════ --}}
    @elseif($report === 'branch')

        <div class="section-title">Payroll cost by branch</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch</th><th>Employees</th>
                    <th style="text-align:right">Earnings</th>
                    <th style="text-align:right">Deductions</th>
                    <th style="text-align:right">Net pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['by_branch'] as $branch => $line)
                    <tr>
                        <td>{{ $branch ?: 'Unassigned' }}</td>
                        <td>{{ $line['employee_count'] }}</td>
                        <td class="num">K {{ number_format($line['total_earnings'], 2) }}</td>
                        <td class="num">K {{ number_format($line['total_deductions'], 2) }}</td>
                        <td class="num">K {{ number_format($line['net_pay'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td class="num">K {{ number_format($summary['total_earnings'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['total_deductions'], 2) }}</td>
                    <td class="num">K {{ number_format($summary['total_net'], 2) }}</td>
                </tr>
            </tbody>
        </table>

    @endif

    <div class="footer-note">
        This report was system-generated and is intended for internal management, HR, and finance use only.
    </div>

</body>
</html>