<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $run->period }} Payslips — {{ $companyName ?? 'TRADESMART SUPPLIES LIMITED' }}</title>

<style>
    @page {
        size: A4;
        margin: 12mm 14mm;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'Helvetica Neue', Arial, sans-serif;
        color: #1c1c1c;
        background: #fff;
        margin: 0;
        padding: 0;
        font-size: 12px;
    }

    .print-bar {
        max-width: 900px;
        margin: 0 auto 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .print-bar h2 { font-size: 18px; margin: 0; color: #2b2b2b; }
    .print-bar button {
        background: #3b3b8f;
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 5px;
        font-size: 13px;
        cursor: pointer;
    }
    .print-bar button:hover { background: #2f2f73; }

    /* ════════════════════════════════════════════════
       PAYSLIP SHEET
    ════════════════════════════════════════════════ */
    .payslip {
        max-width: 900px;
        margin: 0 auto 26px;
        border: 1.5px solid #4c4cae;
        page-break-after: always;
        font-size: 11.5px;
    }
    .payslip:last-child { page-break-after: auto; }

    /* ── Top identity block ─────────────────────────── */
    .identity {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-bottom: 1.5px solid #4c4cae;
    }
    .identity-col { padding: 10px 16px; }
    .identity-col + .identity-col { border-left: 1.5px solid #4c4cae; }

    .id-row { display: flex; margin-bottom: 4px; }
    .id-label { width: 110px; font-weight: 700; color: #1c1c1c; flex-shrink: 0; }
    .id-value { color: #1c1c1c; }
    .id-value.company-name { font-weight: 700; color: #2b2b8f; font-size: 13px; }

    .identity-col .id-row:last-child { margin-bottom: 0; }

    .logo-row { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
    .logo-row img { height: 34px; width: auto; }

    /* ── YTD summary strip ──────────────────────────── */
    .ytd-strip {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        background: #f4f4fb;
        border-bottom: 1.5px solid #4c4cae;
        text-align: center;
        padding: 8px 0;
    }
    .ytd-strip > div { border-right: 1px solid #d8d8ee; padding: 0 6px; }
    .ytd-strip > div:last-child { border-right: none; }
    .ytd-label { font-weight: 700; font-size: 10.5px; color: #2b2b2b; margin-bottom: 2px; }
    .ytd-value { font-size: 11.5px; color: #1c1c1c; }

    /* ── Earnings / deductions table ────────────────── */
    .lines-table {
        width: 100%;
        border-collapse: collapse;
    }
    .lines-table thead th {
        text-align: left;
        font-weight: 700;
        font-size: 10.5px;
        padding: 8px 14px 6px;
        border-bottom: 1px solid #4c4cae;
    }
    .lines-table thead th.num { text-align: right; }

    .lines-table td {
        padding: 3px 14px;
        vertical-align: top;
        font-size: 11.5px;
    }
    .lines-table td.amount-col {
        text-align: right;
        background: #ebebf3;
        font-variant-numeric: tabular-nums;
        width: 110px;
    }
    .lines-table td.hours-col {
        text-align: right;
        width: 90px;
        font-variant-numeric: tabular-nums;
    }
    .lines-table td.earning-amount {
        text-align: right;
        background: #ebebf3;
        font-variant-numeric: tabular-nums;
        width: 120px;
    }

    .code-cell { display: flex; gap: 8px; }
    .code-cell .code { font-weight: 700; width: 32px; flex-shrink: 0; }

    .lines-spacer-row td { height: 6px; padding: 0; background: transparent; }

    /* Fill remaining vertical space so short payslips still look balanced */
    .filler-row td { height: 14px; padding: 0; }
    .filler-row td.amount-col,
    .filler-row td.earning-amount { background: #ebebf3; }

    /* ── Totals row inside the earnings/deductions table ─ */
    .deduction-total-row td {
        border-top: 1.5px solid #4c4cae;
        font-weight: 700;
        padding-top: 5px;
    }
    .deduction-total-row td.amount-col { background: #ebebf3; }

    /* ── Footer block: banking + totals ─────────────── */
    .footer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-top: 1.5px solid #4c4cae;
    }
    .footer-col { padding: 10px 16px; }
    .footer-col + .footer-col { border-left: 1.5px solid #4c4cae; }

    .foot-row { display: flex; margin-bottom: 4px; }
    .foot-label { width: 110px; font-weight: 700; flex-shrink: 0; }
    .foot-value { font-variant-numeric: tabular-nums; }

    .totals-block { display: flex; flex-direction: column; gap: 4px; align-items: flex-end; }
    .totals-block .t-row {
        display: flex;
        justify-content: space-between;
        width: 220px;
        font-size: 11.5px;
    }
    .totals-block .t-label { font-weight: 700; }
    .totals-block .t-value { font-variant-numeric: tabular-nums; }

    .totals-block .net-row {
        border-top: 1.5px solid #1c1c1c;
        margin-top: 4px;
        padding-top: 5px;
        font-size: 13.5px;
        font-weight: 700;
    }
    .totals-block .net-row .t-value { color: #1c6b2e; }

    .deductions-box {
        text-align: right;
        font-size: 11.5px;
        font-weight: 700;
        margin-top: 8px;
    }
    .deductions-box .box-value {
        display: inline-block;
        background: #ebebf3;
        border: 1px solid #c6c6dd;
        padding: 4px 14px;
        margin-top: 3px;
    }

    /* ── Provider strap line ────────────────────────── */
    .strap-line {
        text-align: right;
        padding: 4px 16px 8px;
        font-size: 9.5px;
        color: #5b5bb0;
        font-style: italic;
    }

    @media print {
        .print-bar { display: none; }
        .payslip { border: 1.5px solid #4c4cae; }
    }

    @media screen {
        body { background: #eef0f3; padding: 24px 0; }
        .payslip { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    }
</style>
</head>

<body>

    <div class="print-bar">
        <h2>{{ $run->period }} — Payslips</h2>
        <div>
            <button onclick="window.print()">Print</button>
            <button type="button" onclick="window.location.href='{{ route('payroll.pdf', $payroll) }}'">Download</button>
        </div>
    </div>

    @php
        $companyName = $companyName ?? ($run->payrolls->first()->company ?? 'TRADESMART SUPPLIES LIMITED');
        $companyLogo = $companyLogo ?? null; // pass a public URL or base64 data URI from the controller
    @endphp

    @foreach($run->payrolls as $index => $payroll)

        @php
            $employee   = $payroll->employee;
            $earnings   = $payroll->items->where('type', 'earning');
            $deductions = $payroll->items->where('type', 'deduction');

            // Pad short item lists so every payslip keeps a consistent visual height
            $maxRows   = max($earnings->count(), $deductions->count(), 5);
            $fillerE   = max(0, $maxRows - $earnings->count());
            $fillerD   = max(0, $maxRows - $deductions->count());

            // YTD figures — fall back gracefully if your Payroll model doesn't carry these yet
            $ytdIncome   = $employee->ytd_income   ?? $payroll->total_income;
            $ytdTax      = $employee->ytd_tax      ?? optional($deductions->firstWhere('code', 'D00'))->amount ?? 0;
            $ytdNetForTax= $employee->ytd_net_for_tax ?? ($ytdIncome - $ytdTax);
            $ytdNapsa    = $employee->ytd_napsa    ?? optional($deductions->firstWhere('code', 'D02'))->amount ?? 0;
            $leaveDays   = $employee->leave_days_balance ?? 0;
            $leaveValue  = $employee->leave_days_value   ?? 0;

            $daysWorked  = $payroll->days_worked ?? 26.00;
        @endphp

        <div class="payslip">

            {{-- ════════════════════════════════════════
                 IDENTITY: employee details | company details
            ════════════════════════════════════════ --}}
            <div class="identity">

                <div class="identity-col">
                    <div class="id-row">
                        <span class="id-label">Employee ID</span>
                        <span class="id-value">{{ $employee->employee_id }}</span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Emp Name:</span>
                        <span class="id-value">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Date Engaged</span>
                        <span class="id-value">{{ \Carbon\Carbon::parse($employee->contract_start)->format('d/m/Y') }}</span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Salary Rate</span>
                        <span class="id-value">K {{ number_format($employee->salary ?? 0, 2) }}</span>
                    </div>
                </div>

                <div class="identity-col">
                    @if($companyLogo)
                        <div class="logo-row">
                            <img src="{{ $companyLogo }}" alt="{{ $companyName }} logo">
                        </div>
                    @endif
                    <div class="id-row">
                        <span class="id-label">Company</span>
                        <span class="id-value company-name">{{ $companyName }}</span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Pay Period</span>
                        <span class="id-value">{{ strtoupper($run->period) }}</span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Branch</span>
                        <span class="id-value">{{ $payroll->branch }}</span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Cost/Centre:</span>
                        <span class="id-value">{{ $payroll->cost_centre }}</span>
                    </div>
                </div>

            </div>

            {{-- ════════════════════════════════════════
                 YTD SUMMARY STRIP
            ════════════════════════════════════════ --}}
            <div class="ytd-strip">
                <div>
                    <div class="ytd-label">Total Income YTD</div>
                    <div class="ytd-value">K {{ number_format($ytdIncome, 2) }}</div>
                </div>
                <div>
                    <div class="ytd-label">Net for Tax YTD</div>
                    <div class="ytd-value">K {{ number_format($ytdNetForTax, 2) }}</div>
                </div>
                <div>
                    <div class="ytd-label">Tax YTD</div>
                    <div class="ytd-value">K {{ number_format($ytdTax, 2) }}</div>
                </div>
                <div>
                    <div class="ytd-label">Napsa YTD</div>
                    <div class="ytd-value">K {{ number_format($ytdNapsa, 2) }}</div>
                </div>
                <div>
                    <div class="ytd-label">Leave Days</div>
                    <div class="ytd-value">{{ number_format($leaveDays, 4) }}</div>
                </div>
            </div>

            {{-- ════════════════════════════════════════
                 EARNINGS  |  DEDUCTIONS  side by side
            ════════════════════════════════════════ --}}
            <table class="lines-table">
                <thead>
                    <tr>
                        <th style="width:30%">Code / Description</th>
                        <th class="num hours-col">Days/Hrs</th>
                        <th class="num earning-amount">Amount</th>
                        <th style="width:30%">Code / Description</th>
                        <th class="num amount-col">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $earningsArr   = $earnings->values();
                        $deductionsArr = $deductions->values();
                        $rowCount      = max($earningsArr->count(), $deductionsArr->count());
                    @endphp

                    @for($i = 0; $i < $rowCount; $i++)
                        @php
                            $e = $earningsArr->get($i);
                            $d = $deductionsArr->get($i);
                        @endphp
                        <tr>
                            <td>
                                @if($e)
                                    <span class="code-cell">
                                        <span class="code">{{ $e->code }}</span>
                                        <span>{{ strtoupper($e->description) }}</span>
                                    </span>
                                @endif
                            </td>
                            <td class="hours-col">
                                {{ $i === 0 ? number_format($daysWorked, 2) : '' }}
                            </td>
                            <td class="earning-amount">
                                {{ $e ? number_format($e->amount, 2) : '' }}
                            </td>
                            <td>
                                @if($d)
                                    <span class="code-cell">
                                        <span class="code">{{ $d->code }}</span>
                                        <span>{{ strtoupper($d->description) }}</span>
                                    </span>
                                @endif
                            </td>
                            <td class="amount-col">
                                {{ $d ? number_format($d->amount, 2) : '' }}
                            </td>
                        </tr>
                    @endfor

                    {{-- Filler rows keep short payslips visually consistent --}}
                    @for($i = 0; $i < 3; $i++)
                        <tr class="filler-row">
                            <td></td><td class="hours-col"></td><td class="earning-amount"></td>
                            <td></td><td class="amount-col"></td>
                        </tr>
                    @endfor

                    <tr class="deduction-total-row">
                        <td></td>
                        <td class="hours-col"></td>
                        <td class="earning-amount"></td>
                        <td style="font-weight:700">Total Deductions</td>
                        <td class="amount-col">K {{ number_format($payroll->total_deductions, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- ════════════════════════════════════════
                 FOOTER: banking details | totals
            ════════════════════════════════════════ --}}
            <div class="footer-grid">

                <div class="footer-col">
                    <div class="foot-row">
                        <span class="foot-label">Job Title:</span>
                        <span class="foot-value">{{ $employee->position }}</span>
                    </div>
                    <div class="foot-row">
                        <span class="foot-label">Pay Method:</span>
                        <span class="foot-value">{{ $employee->pay_method ?? 'BANK TRANSFER' }}</span>
                    </div>
                    <div class="foot-row">
                        <span class="foot-label">Bank A/C No:</span>
                        <span class="foot-value">{{ $employee->bank_account_no ?? '—' }}</span>
                    </div>
                    <div class="foot-row">
                        <span class="foot-label">NRC No:</span>
                        <span class="foot-value">{{ $employee->nrc_no ?? '—' }}</span>
                    </div>
                    <div class="foot-row">
                        <span class="foot-label">NHIS No:</span>
                        <span class="foot-value">{{ $employee->nhis_no ?? '—' }}</span>
                    </div>
                    <div class="foot-row">
                        <span class="foot-label">Social S No:</span>
                        <span class="foot-value">{{ $employee->social_security_no ?? '—' }}</span>
                    </div>
                </div>

                <div class="footer-col">
                    <div class="totals-block">
                        <div class="t-row">
                            <span class="t-label">Total Income</span>
                            <span class="t-value">K {{ number_format($payroll->total_income, 2) }}</span>
                        </div>
                        <div class="t-row">
                            <span class="t-label">Total Deductions</span>
                            <span class="t-value">K {{ number_format($payroll->total_deductions, 2) }}</span>
                        </div>
                        <div class="t-row net-row">
                            <span class="t-label">Net Pay</span>
                            <span class="t-value">K {{ number_format($payroll->net_pay, 2) }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="strap-line">
                Generated by Tradesmart Internal Payroll System
            </div>

        </div>

    @endforeach

</body>
</html>