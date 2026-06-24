<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $run->period }} Payslips — {{ $companyName ?? 'TRADESMART SUPPLIES LIMITED' }}</title>

<style>
    @page {
        size: A4;
        margin: 10mm 10mm;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'Helvetica Neue', Arial, sans-serif;
        color: #1a1a1a;
        background: #fff;
        margin: 0;
        padding: 0;
        font-size: 12px;
    }

    .print-bar {
        max-width: 880px;
        margin: 0 auto 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .print-bar h2 { font-size: 18px; margin: 0; color: #2b2b2b; }
    .print-bar button {
        background: #1e2a4a;
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 5px;
        font-size: 13px;
        cursor: pointer;
    }
    .print-bar button:hover { background: #16213c; }

    /* ════════════════════════════════════════════════
       PAYSLIP SHEET
    ════════════════════════════════════════════════ */
    .payslip {
        position: relative;
        max-width: 880px;
        margin: 0 auto 26px;
        border: 1px solid #1e2a4a;
        border-radius: 4px;
        overflow: hidden;
        page-break-after: always;
        font-size: 11.5px;
    }
    .payslip:last-child { page-break-after: auto; }

    /* ── Confidential watermark — diagonal, behind all content ───── */
    .confidential-watermark {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 0;
        pointer-events: none;
    }
    .confidential-watermark span {
        transform: rotate(-32deg);
        font-size: 64px;
        font-weight: 800;
        letter-spacing: 4px;
        color: rgba(30, 42, 74, 0.06);
        white-space: nowrap;
        text-transform: uppercase;
    }

    /* Everything else sits above the watermark */
    .payslip-content { position: relative; z-index: 1; }

    /* ── Confidential ribbon — top strip, unmistakable ──────────── */
    .confidential-ribbon {
        background: #1e2a4a;
        color: #fff;
        text-align: center;
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 4px 0;
    }

    /* ── Header band: logo + company identity ───────────────────── */
    .doc-header {
        display: table;
        width: 100%;
        background: #f6f7fb;
        border-bottom: 2px solid #1e2a4a;
    }
    .doc-header-cell {
        display: table-cell;
        vertical-align: middle;
        padding: 14px 18px;
    }
    .doc-header-logo-cell { width: 110px; text-align: center; }

    .logo-frame {
        display: inline-block;
        background: #fff;
        border: 1px solid #d8dae8;
        border-radius: 6px;
        padding: 8px 10px;
    }
    .logo-frame img { display: block; height: 48px; width: auto; max-width: 90px; }
    .logo-fallback {
        width: 70px; height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 18px;
        color: #1e2a4a;
        letter-spacing: 0.5px;
    }

    .doc-header-name { font-size: 17px; font-weight: 800; color: #1e2a4a; letter-spacing: 0.2px; }
    .doc-header-tag { font-size: 10.5px; color: #5a5f73; margin-top: 1px; text-transform: uppercase; letter-spacing: 0.6px; }

    .period-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px; color: #8a8fa0; }
    .period-value { font-size: 14px; font-weight: 700; color: #1e2a4a; }

    /* ── Identity block: employee | branch details ───────────────── */
    .identity {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-bottom: 1.5px solid #1e2a4a;
    }
    .identity-col {
        display: table-cell;
        width: 50%;
        padding: 12px 18px;
        vertical-align: top;
    }
    .identity-col + .identity-col { border-left: 1.5px solid #d8dae8; }

    .id-row { display: flex; margin-bottom: 5px; }
    .id-row:last-child { margin-bottom: 0; }
    .id-label { width: 112px; font-weight: 700; color: #444a5c; flex-shrink: 0; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.3px; }
    .id-value { color: #1a1a1a; font-size: 11.5px; font-weight: 500; }

    /* ── YTD summary strip ──────────────────────────── */
    .ytd-strip {
        display: table;
        width: 100%;
        table-layout: fixed;
        background: #eef0f9;
        border-bottom: 1.5px solid #1e2a4a;
    }
    .ytd-cell {
        display: table-cell;
        text-align: center;
        padding: 9px 6px;
        border-right: 1px solid #d8dae8;
    }
    .ytd-cell:last-child { border-right: none; }
    .ytd-label { font-weight: 700; font-size: 9px; color: #5a5f73; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
    .ytd-value { font-size: 11.5px; color: #1a1a1a; font-weight: 600; }

    /* ── Earnings / deductions table ────────────────── */
    .lines-table {
        width: 100%;
        border-collapse: collapse;
    }
    .lines-table thead th {
        text-align: left;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #444a5c;
        padding: 9px 16px 7px;
        border-bottom: 1.5px solid #1e2a4a;
        background: #fafbfd;
    }
    .lines-table thead th.num { text-align: right; }

    .lines-table td {
        padding: 4px 16px;
        vertical-align: top;
        font-size: 11.5px;
    }
    .lines-table td.amount-col {
        text-align: right;
        background: #f0f1f7;
        font-variant-numeric: tabular-nums;
        width: 110px;
    }
    .lines-table td.hours-col {
        text-align: right;
        width: 80px;
        font-variant-numeric: tabular-nums;
        color: #5a5f73;
    }
    .lines-table td.earning-amount {
        text-align: right;
        background: #f0f1f7;
        font-variant-numeric: tabular-nums;
        width: 120px;
    }

    .code-cell { display: flex; gap: 8px; }
    .code-cell .code { font-weight: 700; width: 30px; flex-shrink: 0; color: #444a5c; }

    .filler-row td { height: 14px; padding: 0; }
    .filler-row td.amount-col,
    .filler-row td.earning-amount { background: #f0f1f7; }

    .deduction-total-row td {
        border-top: 1.5px solid #1e2a4a;
        font-weight: 700;
        padding-top: 6px;
    }
    .deduction-total-row td.amount-col { background: #f0f1f7; }

    /* ── Footer block: banking + totals ─────────────── */
    .footer-grid {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-top: 1.5px solid #1e2a4a;
    }
    .footer-col { display: table-cell; width: 50%; padding: 12px 18px; vertical-align: top; }
    .footer-col + .footer-col { border-left: 1.5px solid #d8dae8; }

    .foot-row { display: flex; margin-bottom: 5px; }
    .foot-row:last-child { margin-bottom: 0; }
    .foot-label { width: 112px; font-weight: 700; flex-shrink: 0; font-size: 10.5px; color: #444a5c; text-transform: uppercase; letter-spacing: 0.3px; }
    .foot-value { font-variant-numeric: tabular-nums; font-size: 11.5px; }

    .totals-block { display: flex; flex-direction: column; gap: 5px; align-items: flex-end; }
    .totals-block .t-row {
        display: flex;
        justify-content: space-between;
        width: 230px;
        font-size: 11.5px;
    }
    .totals-block .t-label { font-weight: 700; color: #444a5c; }
    .totals-block .t-value { font-variant-numeric: tabular-nums; }

    .totals-block .net-row {
        border-top: 1.5px solid #1e2a4a;
        margin-top: 5px;
        padding-top: 7px;
        font-size: 15px;
        font-weight: 800;
    }
    .totals-block .net-row .t-label { color: #1a1a1a; }
    .totals-block .net-row .t-value { color: #1c6b2e; }

    /* ── Confidentiality footer note ────────────────── */
    .confidential-footer {
        background: #f6f7fb;
        border-top: 1px solid #d8dae8;
        padding: 8px 18px;
        text-align: center;
        font-size: 8.5px;
        color: #6b7080;
        letter-spacing: 0.3px;
    }
    .confidential-footer strong { color: #1e2a4a; }

    .strap-line {
        text-align: right;
        padding: 5px 18px 9px;
        font-size: 9px;
        color: #8a8fa0;
        font-style: italic;
    }

    @media print {
        .print-bar { display: none; }
        .payslip { border: 1px solid #1e2a4a; }
    }

    @media screen {
        body { background: #ffffff; padding: 24px 0; }
        .payslip { background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,0.1); }
    }
</style>
</head>

<body>

    @php
        $companyName = $companyName ?? ($run->payrolls->first()->company ?? 'TRADESMART SUPPLIES LIMITED');
        $companyLogo = $companyLogo ?? null; // pass a public URL or base64 data URI from the controller
        $companyInitials = collect(explode(' ', $companyName))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    @endphp


    @foreach($run->payrolls as $index => $payroll)

        @php
            $employee   = $payroll->employee;
            $earnings   = $payroll->items->where('type', 'earning');
            $deductions = $payroll->items->where('type', 'deduction');

            $maxRows   = max($earnings->count(), $deductions->count(), 5);
            $fillerE   = max(0, $maxRows - $earnings->count());
            $fillerD   = max(0, $maxRows - $deductions->count());

            $ytdIncome    = $employee->ytd_income      ?? $payroll->total_income;
            $ytdTax       = $employee->ytd_tax         ?? optional($deductions->firstWhere('code', 'D00'))->amount ?? 0;
            $ytdNetForTax = $employee->ytd_net_for_tax ?? ($ytdIncome - $ytdTax);
            $ytdNapsa     = $employee->ytd_napsa       ?? optional($deductions->firstWhere('code', 'D02'))->amount ?? 0;
            $leaveDays    = $employee->leave_days_balance ?? 0;

            $daysWorked = $payroll->days_worked ?? 26.00;
        @endphp

        <div class="payslip">

            {{-- Watermark sits behind everything --}}
            <div class="confidential-watermark"><span>Confidential</span></div>

            <div class="payslip-content">

                {{-- ════════════════════════════════════
                     CONFIDENTIAL RIBBON
                ════════════════════════════════════ --}}
                <div class="confidential-ribbon">
                    Confidential — Personal Payroll Document
                </div>

                {{-- ════════════════════════════════════
                     HEADER: logo + company + period
                ════════════════════════════════════ --}}
                <div class="doc-header">
                    <div class="doc-header-cell doc-header-logo-cell">
                        <div class="logo-frame">
                            @if($companyLogo)
                                <img src="{{ $companyLogo }}" alt="{{ $companyName }} logo">
                            @else
                                <div class="logo-fallback">{{ $companyInitials }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="doc-header-cell">
                        <div class="doc-header-name">{{ $companyName }}</div>
                        <div class="doc-header-tag">Official Employee Payslip</div>
                    </div>
                    <div class="doc-header-cell" style="text-align:right; width:160px;">
                        <div class="period-label">Pay Period</div>
                        <div class="period-value">{{ strtoupper($run->period) }}</div>
                    </div>
                </div>

                {{-- ════════════════════════════════════
                     IDENTITY: employee | branch details
                ════════════════════════════════════ --}}
                <div class="identity">

                    <div class="identity-col">
                        <div class="id-row">
                            <span class="id-label">Employee ID</span>
                            <span class="id-value">{{ $employee->employee_id }}</span>
                        </div>
                        <div class="id-row">
                            <span class="id-label">Name</span>
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
                        <div class="id-row">
                            <span class="id-label">Branch</span>
                            <span class="id-value">{{ $payroll->branch }}</span>
                        </div>
                        <div class="id-row">
                            <span class="id-label">Cost Centre</span>
                            <span class="id-value">{{ $payroll->cost_centre }}</span>
                        </div>
                        <div class="id-row">
                            <span class="id-label">Job Title</span>
                            <span class="id-value">{{ $employee->position }}</span>
                        </div>
                        <div class="id-row">
                            <span class="id-label">Pay Method</span>
                            <span class="id-value">{{ $employee->pay_method ?? 'BANK TRANSFER' }}</span>
                        </div>
                    </div>

                </div>

                {{-- ════════════════════════════════════
                     YTD SUMMARY STRIP
                ════════════════════════════════════ --}}
                <div class="ytd-strip">
                    <div class="ytd-cell">
                        <div class="ytd-label">Income YTD</div>
                        <div class="ytd-value">K {{ number_format($ytdIncome, 2) }}</div>
                    </div>
                    <div class="ytd-cell">
                        <div class="ytd-label">Net for Tax YTD</div>
                        <div class="ytd-value">K {{ number_format($ytdNetForTax, 2) }}</div>
                    </div>
                    <div class="ytd-cell">
                        <div class="ytd-label">Tax YTD</div>
                        <div class="ytd-value">K {{ number_format($ytdTax, 2) }}</div>
                    </div>
                    <div class="ytd-cell">
                        <div class="ytd-label">Napsa YTD</div>
                        <div class="ytd-value">K {{ number_format($ytdNapsa, 2) }}</div>
                    </div>
                    <div class="ytd-cell">
                        <div class="ytd-label">Leave Days</div>
                        <div class="ytd-value">{{ number_format($leaveDays, 4) }}</div>
                    </div>
                </div>

                {{-- ════════════════════════════════════
                     EARNINGS | DEDUCTIONS
                ════════════════════════════════════ --}}
                <table class="lines-table">
                    <thead>
                        <tr>
                            <th style="width:30%">Earnings</th>
                            <th class="num hours-col">Days/Hrs</th>
                            <th class="num earning-amount">Amount</th>
                            <th style="width:30%">Deductions</th>
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

                {{-- ════════════════════════════════════
                     FOOTER: banking details | totals
                ════════════════════════════════════ --}}
                <div class="footer-grid">

                    <div class="footer-col">
                        <div class="foot-row">
                            <span class="foot-label">Bank A/C No</span>
                            <span class="foot-value">{{ $employee->bank_account_no ?? '—' }}</span>
                        </div>
                        <div class="foot-row">
                            <span class="foot-label">NRC No</span>
                            <span class="foot-value">{{ $employee->nrc_no ?? '—' }}</span>
                        </div>
                        <div class="foot-row">
                            <span class="foot-label">NHIS No</span>
                            <span class="foot-value">{{ $employee->nhis_no ?? '—' }}</span>
                        </div>
                        <div class="foot-row">
                            <span class="foot-label">Social S No</span>
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

                {{-- ════════════════════════════════════
                     CONFIDENTIALITY FOOTER
                ════════════════════════════════════ --}}
                <div class="confidential-footer">
                    <strong>Confidential.</strong> This payslip contains personal salary information intended solely for
                    {{ $employee->first_name }} {{ $employee->last_name }}. Do not forward or disclose without authorization.
                </div>

                <div class="strap-line">
                    Generated by Tradesmart Internal Payroll System
                </div>

            </div>

        </div>

    @endforeach

</body>
</html>