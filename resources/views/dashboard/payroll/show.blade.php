@extends('layouts.app')

@section('content')

<div class="container py-4 payslip-page">

    {{-- ════════════════════════════════════════════════════════
         TOP BAR
    ════════════════════════════════════════════════════════ --}}
    <div class="ps-topbar">

        <div>
            <a href="{{ route('payroll.index') }}" class="ps-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Back to payroll
            </a>
            <h4 class="ps-title">Payslip</h4>
            <p class="ps-subtitle">{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }} · {{ $payroll->pay_period }}</p>
        </div>

        <div class="ps-actions">
            <span class="ps-status-pill ps-status-{{ strtolower($payroll->status ?? 'draft') }}">
                {{ $payroll->status ?? 'Draft' }}
            </span>

            <!-- <a href="{{ route('payroll.print', $payroll) }}" target="_blank" class="btn-ps-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <path d="M6 14h12v8H6z"/>
                </svg>
                Print
            </a> -->
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════
         PAYSLIP CARD
    ════════════════════════════════════════════════════════ --}}
    <div class="ps-card">

        {{-- HEADER STRIP --}}
        <div class="ps-header">

            <div class="ps-company">
                <!-- <div class="ps-company-mark">{{ strtoupper(substr($payroll->company ?? 'TS', 0, 2)) }}</div> -->
                <div>
                    <div class="ps-company-name">{{ $payroll->company ?? 'TRADESMART SUPPLIES LIMITED' }}</div>
                    <div class="ps-company-tag">Official employee payslip</div>
                </div>
            </div>

            <div class="ps-net-callout">
                <div class="ps-net-label">Net pay</div>
                <div class="ps-net-value">K {{ number_format($payroll->net_pay, 2) }}</div>
            </div>

        </div>

        {{-- EMPLOYEE DETAILS --}}
        <div class="ps-details">

            <div class="ps-detail-group">
                <div class="ps-detail">
                    <span class="ps-detail-label">Employee</span>
                    <span class="ps-detail-value">{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</span>
                </div>
                <div class="ps-detail">
                    <span class="ps-detail-label">Employee ID</span>
                    <span class="ps-detail-value mono">{{ $payroll->employee->employee_id }}</span>
                </div>
                <div class="ps-detail">
                    <span class="ps-detail-label">Position</span>
                    <span class="ps-detail-value">{{ $payroll->employee->position }}</span>
                </div>
            </div>

            <div class="ps-detail-group ps-detail-group-end">
                <div class="ps-detail">
                    <span class="ps-detail-label">Pay period</span>
                    <span class="ps-detail-value">{{ $payroll->pay_period }}</span>
                </div>
                <div class="ps-detail">
                    <span class="ps-detail-label">Branch</span>
                    <span class="ps-detail-value">{{ $payroll->branch }}</span>
                </div>
                <div class="ps-detail">
                    <span class="ps-detail-label">Department</span>
                    <span class="ps-detail-value">{{ $payroll->cost_centre }}</span>
                </div>
            </div>

        </div>

        {{-- EARNINGS / DEDUCTIONS --}}
        <div class="ps-breakdown">

            <div class="ps-column">
                <div class="ps-column-head ps-head-earning">
                    <span>Earnings</span>
                </div>
                <table class="ps-line-table">
                    <tbody>
                        @forelse($payroll->items->where('type', 'earning') as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="ps-amount">K {{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="ps-line-empty">No earnings recorded</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ps-column">
                <div class="ps-column-head ps-head-deduction">
                    <span>Deductions</span>
                </div>
                <table class="ps-line-table">
                    <tbody>
                        @forelse($payroll->items->where('type', 'deduction') as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="ps-amount">K {{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="ps-line-empty">No deductions recorded</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        {{-- TOTALS --}}
        <table class="ps-totals">
            <tbody>
                <tr>
                    <td class="ps-total-label">Total earnings</td>
                    <td class="ps-total-value ps-total-earning">K {{ number_format($payroll->total_income, 2) }}</td>
                </tr>
                <tr>
                    <td class="ps-total-label">Total deductions</td>
                    <td class="ps-total-value ps-total-deduction">K {{ number_format($payroll->total_deductions, 2) }}</td>
                </tr>
                <tr class="ps-total-block-net">
                    <td class="ps-total-label">Net pay</td>
                    <td class="ps-total-value ps-total-net">K {{ number_format($payroll->net_pay, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- FOOTER --}}
        <div class="ps-footer">
            <p>This is a computer-generated payslip and does not require a signature.</p>
            <p>Generated on {{ now()->format('d M Y, H:i') }}</p>
        </div>

    </div>

</div>

<style>
.payslip-page {
    --ps-border: #e8e9ec;
    --ps-text: #1a1d23;
    --ps-muted: #75798a;
    --ps-bg-subtle: #f7f8fa;
    --ps-accent: #2f6f4e;
    --ps-accent-soft: #e7f3ec;
    --ps-danger: #c4453a;
    --ps-danger-soft: #fbeae8;
    --ps-radius: 10px;
    max-width: 840px;
    font-size: 0.9rem;
    color: var(--ps-text);
}

/* ── Top bar ─────────────────────────────────────────────────────── */
.ps-topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.4rem;
    flex-wrap: wrap;
    gap: 0.8rem;
}

.ps-back {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    color: var(--ps-muted);
    text-decoration: none;
    margin-bottom: 0.6rem;
}
.ps-back:hover { color: var(--ps-text); }

.ps-title { font-weight: 600; font-size: 1.3rem; margin: 0 0 0.15rem; letter-spacing: -0.01em; }
.ps-subtitle { color: var(--ps-muted); font-size: 0.85rem; margin: 0; }

.ps-actions { display: flex; align-items: center; gap: 0.6rem; }

.ps-status-pill {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 0.32rem 0.7rem;
    border-radius: 999px;
}
.ps-status-draft     { background: #eceef0; color: var(--ps-muted); }
.ps-status-processed { background: #e3eef8; color: #2c6ca3; }
.ps-status-approved  { background: var(--ps-accent-soft); color: var(--ps-accent); }

.btn-ps-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: 1px solid var(--ps-border);
    background: #fff;
    color: var(--ps-text);
    padding: 0.45rem 0.85rem;
    border-radius: 7px;
    font-size: 0.82rem;
    font-weight: 500;
    text-decoration: none;
    transition: background .12s ease;
}
.btn-ps-secondary:hover { background: var(--ps-bg-subtle); color: var(--ps-text); }

/* ── Card shell ──────────────────────────────────────────────────── */
.ps-card {
    background: #fff;
    border: 1px solid var(--ps-border);
    border-radius: var(--ps-radius);
    overflow: hidden;
}

/* ── Header strip ────────────────────────────────────────────────── */
.ps-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.3rem 1.6rem;
    border-bottom: 1px solid var(--ps-border);
    background: var(--ps-bg-subtle);
}

.ps-company { display: flex; align-items: center; gap: 0.8rem; }
.ps-company-mark {
    width: 42px; height: 42px;
    border-radius: 9px;
    background: var(--ps-text);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.ps-company-name { font-weight: 600; font-size: 0.98rem; letter-spacing: -0.01em; }
.ps-company-tag { color: var(--ps-muted); font-size: 0.78rem; }

.ps-net-callout { text-align: right; }
.ps-net-label { font-size: 0.72rem; color: var(--ps-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.1rem; }
.ps-net-value { font-size: 1.45rem; font-weight: 700; color: var(--ps-accent); font-variant-numeric: tabular-nums; }

/* ── Employee details ───────────────────────────────────────────── */
.ps-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    padding: 1.3rem 1.6rem;
    border-bottom: 1px solid var(--ps-border);
    gap: 1rem;
}
.ps-detail-group-end { text-align: right; }
.ps-detail { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.45rem; }
.ps-detail-group-end .ps-detail { justify-content: flex-end; }
.ps-detail:last-child { margin-bottom: 0; }

.ps-detail-label { color: var(--ps-muted); font-size: 0.82rem; }
.ps-detail-value { font-weight: 500; font-size: 0.85rem; }
.ps-detail-value.mono { font-family: 'SF Mono', Consolas, monospace; font-size: 0.8rem; }

/* ── Earnings / deductions ──────────────────────────────────────── */
.ps-breakdown {
    display: grid;
    grid-template-columns: 1fr 1fr;
}
.ps-column + .ps-column { border-left: 1px solid var(--ps-border); }

.ps-column-head {
    padding: 0.75rem 1.6rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.ps-head-earning { color: var(--ps-accent); }
.ps-head-deduction { color: var(--ps-danger); }

.ps-line-table { width: 100%; border-collapse: collapse; }
.ps-line-table td {
    padding: 0.4rem 1.6rem;
    font-size: 0.85rem;
    border-top: 1px solid #f3f4f6;
}
.ps-line-table tr:first-child td { border-top: none; }
.ps-amount { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.ps-line-empty { color: var(--ps-muted); font-style: italic; padding: 0.8rem 1.6rem; }

/* ── Totals ──────────────────────────────────────────────────────── */
.ps-totals {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1.1rem;
    padding: 1.2rem 1.6rem;
    border-top: 1px solid var(--ps-border);
    background: var(--ps-bg-subtle);
    flex-wrap: wrap;
}

.ps-total-block { display: flex; flex-direction: column; align-items: flex-end; gap: 0.15rem; }
.ps-total-label { font-size: 0.72rem; color: var(--ps-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.ps-total-value { font-size: 1rem; font-weight: 600; font-variant-numeric: tabular-nums; }
.ps-total-earning { color: var(--ps-accent); }
.ps-total-deduction { color: var(--ps-danger); }

.ps-total-divider { color: var(--ps-muted); font-size: 1.1rem; font-weight: 300; }

.ps-total-block-net .ps-total-value { font-size: 1.3rem; color: var(--ps-text); }

/* ── Footer ──────────────────────────────────────────────────────── */
.ps-footer {
    text-align: center;
    color: var(--ps-muted);
    font-size: 0.76rem;
    padding: 1rem 1.6rem 1.3rem;
    border-top: 1px solid var(--ps-border);
}
.ps-footer p { margin: 0.15rem 0; }

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .ps-details, .ps-breakdown { grid-template-columns: 1fr; }
    .ps-detail-group-end { text-align: left; }
    .ps-detail-group-end .ps-detail { justify-content: space-between; }
    .ps-column + .ps-column { border-left: none; border-top: 1px solid var(--ps-border); }
    .ps-header { flex-direction: column; align-items: flex-start; gap: 0.9rem; }
    .ps-net-callout { text-align: left; }
    .ps-totals { justify-content: space-between; }
}
</style>

@endsection