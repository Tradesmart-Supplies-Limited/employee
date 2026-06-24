@extends('layouts.app')

@section('content')

<div class="container-fluid py-4 reports-page">

    {{-- ════════════════════════════════════════════════════════
         HEADER + RUN SELECTOR
    ════════════════════════════════════════════════════════ --}}
    <div class="rp-topbar">
        <div>
            <h4 class="rp-title">Payroll Reports</h4>
            <p class="rp-subtitle">Management reports, statutory submissions, and bank payment exports</p>
        </div>

        <form method="GET" class="rp-run-select">
            <label class="small text-muted mb-1 d-block">Payroll run</label>
            <select name="run_id" class="form-select" onchange="this.form.submit()">
                @foreach($runs as $r)
                    <option value="{{ $r->id }}" {{ $run && $run->id == $r->id ? 'selected' : '' }}>
                        {{ $r->period }} — {{ $r->status }} - {{ $r->alias }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if(!$run)
        <div class="alert alert-warning">No payroll runs found. Generate a run first.</div>
    @else

    {{-- ════════════════════════════════════════════════════════
         HEADLINE SUMMARY CARDS
    ════════════════════════════════════════════════════════ --}}
    <div class="rp-summary-grid">

        <div class="rp-card">
            <div class="rp-card-label">Employees</div>
            <div class="rp-card-value">{{ $summary['employee_count'] }}</div>
        </div>

        <div class="rp-card rp-card-earning">
            <div class="rp-card-label">Total earnings</div>
            <div class="rp-card-value">K {{ number_format($summary['total_earnings'], 2) }}</div>
        </div>

        <div class="rp-card rp-card-deduction">
            <div class="rp-card-label">Total deductions</div>
            <div class="rp-card-value">K {{ number_format($summary['total_deductions'], 2) }}</div>
        </div>

        <div class="rp-card rp-card-net">
            <div class="rp-card-label">Net payable</div>
            <div class="rp-card-value">K {{ number_format($summary['total_net'], 2) }}</div>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════
         SECTION 1 — MANAGEMENT REPORTS
    ════════════════════════════════════════════════════════ --}}
    <div class="rp-section">

        <div class="rp-section-head">
            <h5>Management reports</h5>
            <p class="text-muted small mb-0">All reports below export as PDF</p>
        </div>

        <div class="rp-report-grid">

            <a href="{{ route('payroll.reports.management.pdf', [$run->id, 'total_earnings']) }}" class="rp-report-tile">
                <div class="rp-tile-icon rp-icon-earning">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="rp-tile-title">Total earnings report</div>
                    <div class="rp-tile-desc">Every earning line, totaled and broken down by type</div>
                </div>
                <span class="rp-tile-pdf">PDF</span>
            </a>

            <a href="{{ route('payroll.reports.management.pdf', [$run->id, 'total_deductions']) }}" class="rp-report-tile">
                <div class="rp-tile-icon rp-icon-deduction">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="rp-tile-title">Total deductions report</div>
                    <div class="rp-tile-desc">PAYE, NAPSA, NHIMA and all other deductions itemized</div>
                </div>
                <span class="rp-tile-pdf">PDF</span>
            </a>

            <a href="{{ route('payroll.reports.management.pdf', [$run->id, 'net_payable']) }}" class="rp-report-tile">
                <div class="rp-tile-icon rp-icon-net">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M5 6l7-5 7 5M5 18l7 5 7-5"/></svg>
                </div>
                <div>
                    <div class="rp-tile-title">Net payable report</div>
                    <div class="rp-tile-desc">Final take-home amounts per employee, ready for sign-off</div>
                </div>
                <span class="rp-tile-pdf">PDF</span>
            </a>

            <a href="{{ route('payroll.reports.management.pdf', [$run->id, 'statutory_summary']) }}" class="rp-report-tile">
                <div class="rp-tile-icon rp-icon-statutory">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div>
                    <div class="rp-tile-title">Statutory summary</div>
                    <div class="rp-tile-desc">PAYE, NAPSA, NHIMA totals — for finance reconciliation</div>
                </div>
                <span class="rp-tile-pdf">PDF</span>
            </a>

            <a href="{{ route('payroll.reports.management.pdf', [$run->id, 'comprehensive']) }}" class="rp-report-tile rp-tile-featured">
                <div class="rp-tile-icon rp-icon-comprehensive">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
                </div>
                <div>
                    <div class="rp-tile-title">Comprehensive report</div>
                    <div class="rp-tile-desc">Full breakdown — every employee, every line, every total</div>
                </div>
                <span class="rp-tile-pdf">PDF</span>
            </a>

            <a href="{{ route('payroll.reports.management.pdf', [$run->id, 'department']) }}" class="rp-report-tile">
                <div class="rp-tile-icon rp-icon-dept">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <div>
                    <div class="rp-tile-title">By department report</div>
                    <div class="rp-tile-desc">Payroll cost rolled up by cost centre / department</div>
                </div>
                <span class="rp-tile-pdf">PDF</span>
            </a>

            <a href="{{ route('payroll.reports.management.pdf', [$run->id, 'branch']) }}" class="rp-report-tile">
                <div class="rp-tile-icon rp-icon-branch">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4 8 4v14M9 9v.01M9 12v.01M9 15v.01M15 9v.01M15 12v.01M15 15v.01"/></svg>
                </div>
                <div>
                    <div class="rp-tile-title">By branch report</div>
                    <div class="rp-tile-desc">Payroll cost rolled up by branch location</div>
                </div>
                <span class="rp-tile-pdf">PDF</span>
            </a>

        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════
         SECTION 2 — STATUTORY SUBMISSIONS
    ════════════════════════════════════════════════════════ --}}
    <div class="rp-section">

        <div class="rp-section-head">
            <h5>Statutory submissions</h5>
            <p class="text-muted small mb-0">Files formatted for direct upload to each authority's portal</p>
        </div>

        <div class="rp-export-list">

            <div class="rp-export-row">
                <div class="rp-export-info">
                    <span class="rp-export-badge rp-badge-csv">CSV</span>
                    <div>
                        <div class="rp-export-title">NAPSA — iCARE returns</div>
                        <div class="rp-export-desc">Employee &amp; employer pension contributions (5% each)</div>
                    </div>
                </div>
                <a href="{{ route('payroll.reports.napsa.csv', $run->id) }}" class="btn-rp-export">Download</a>
            </div>

            <div class="rp-export-row">
                <div class="rp-export-info">
                    <span class="rp-export-badge rp-badge-xlsx">XLSX</span>
                    <div>
                        <div class="rp-export-title">ZRA — PAYE returns</div>
                        <div class="rp-export-desc">Income tax deducted, per employee TPIN</div>
                    </div>
                </div>
                <a href="{{ route('payroll.reports.zra.excel', $run->id) }}" class="btn-rp-export">Download</a>
            </div>

            <div class="rp-export-row">
                <div class="rp-export-info">
                    <span class="rp-export-badge rp-badge-csv">CSV</span>
                    <div>
                        <div class="rp-export-title">NHIMA — health insurance returns</div>
                        <div class="rp-export-desc">Employee &amp; employer health scheme contributions (1% each)</div>
                    </div>
                </div>
                <a href="{{ route('payroll.reports.nhima.csv', $run->id) }}" class="btn-rp-export">Download</a>
            </div>

            <div class="rp-export-row">
                <div class="rp-export-info">
                    <span class="rp-export-badge rp-badge-csv">CSV</span>
                    <div>
                        <div class="rp-export-title">Workers' Compensation Fund (WCFCB)</div>
                        <div class="rp-export-desc">Insurable earnings return — confirm column order with WCFCB before filing</div>
                    </div>
                </div>
                <a href="{{ route('payroll.reports.wcfcb.csv', $run->id) }}" class="btn-rp-export">Download</a>
            </div>

        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════
         SECTION 3 — BANKING & PAYMENTS
    ════════════════════════════════════════════════════════ --}}
    <div class="rp-section">

        <div class="rp-section-head">
            <h5>Banking &amp; payments</h5>
            <p class="text-muted small mb-0">Bulk payment file for upload to your bank's payments portal</p>
        </div>

        <div class="rp-export-list">

            <div class="rp-export-row">
                <div class="rp-export-info">
                    <span class="rp-export-badge rp-badge-csv">CSV</span>
                    <div>
                        <div class="rp-export-title">Bank bulk payments</div>
                        <div class="rp-export-desc">Account number, amount, beneficiary, and sort code per employee</div>
                    </div>
                </div>
                <a href="{{ route('payroll.reports.bank_payments.csv', $run->id) }}" class="btn-rp-export">Download</a>
            </div>

        </div>

    </div>

    @endif

</div>

<style>
.reports-page {
    --rp-border: #e8e9ec;
    --rp-text: #1a1d23;
    --rp-muted: #75798a;
    --rp-bg-subtle: #f7f8fa;
    --rp-accent: #2f6f4e;
    --rp-accent-soft: #e7f3ec;
    --rp-danger: #c4453a;
    --rp-danger-soft: #fbeae8;
    --rp-blue: #2c6ca3;
    --rp-blue-soft: #e3eef8;
    --rp-radius: 10px;
    font-size: 0.9rem;
    color: var(--rp-text);
    max-width: 1100px;
}

.rp-topbar { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
.rp-title { font-weight: 600; font-size: 1.35rem; margin: 0 0 0.15rem; letter-spacing: -0.01em; }
.rp-subtitle { color: var(--rp-muted); font-size: 0.85rem; margin: 0; }
.rp-run-select { min-width: 240px; }
.rp-run-select select { font-size: 0.85rem; }

/* ── Summary cards ───────────────────────────────────────────────── */
.rp-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.9rem;
    margin-bottom: 1.8rem;
}
.rp-card {
    background: #fff;
    border: 1px solid var(--rp-border);
    border-radius: var(--rp-radius);
    padding: 1rem 1.2rem;
}
.rp-card-label { font-size: 0.75rem; color: var(--rp-muted); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.3rem; }
.rp-card-value { font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.rp-card-earning .rp-card-value { color: var(--rp-accent); }
.rp-card-deduction .rp-card-value { color: var(--rp-danger); }
.rp-card-net .rp-card-value { color: var(--rp-text); }

/* ── Sections ────────────────────────────────────────────────────── */
.rp-section { margin-bottom: 2.2rem; }
.rp-section-head { margin-bottom: 1rem; }
.rp-section-head h5 { font-weight: 600; font-size: 1.05rem; margin-bottom: 0.15rem; }

/* ── Management report tiles ────────────────────────────────────── */
.rp-report-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.8rem;
}
.rp-report-tile {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    background: #fff;
    border: 1px solid var(--rp-border);
    border-radius: var(--rp-radius);
    padding: 0.9rem 1.1rem;
    text-decoration: none;
    color: var(--rp-text);
    transition: border-color .12s ease, box-shadow .12s ease;
}
.rp-report-tile:hover { border-color: #c7cad1; box-shadow: 0 2px 8px rgba(20,22,28,0.06); color: var(--rp-text); }
.rp-tile-featured { background: var(--rp-bg-subtle); }

.rp-tile-icon {
    width: 38px; height: 38px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.rp-icon-earning { background: var(--rp-accent-soft); color: var(--rp-accent); }
.rp-icon-deduction { background: var(--rp-danger-soft); color: var(--rp-danger); }
.rp-icon-net { background: var(--rp-blue-soft); color: var(--rp-blue); }
.rp-icon-statutory { background: #fbf2e3; color: #b8842a; }
.rp-icon-comprehensive { background: var(--rp-text); color: #fff; }
.rp-icon-dept, .rp-icon-branch { background: #eceef0; color: var(--rp-muted); }

.rp-tile-title { font-weight: 600; font-size: 0.88rem; margin-bottom: 0.1rem; }
.rp-tile-desc { color: var(--rp-muted); font-size: 0.76rem; line-height: 1.3; }

.rp-tile-pdf {
    margin-left: auto;
    flex-shrink: 0;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--rp-danger);
    border: 1px solid var(--rp-danger);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
}

/* ── Statutory / banking export rows ────────────────────────────── */
.rp-export-list {
    background: #fff;
    border: 1px solid var(--rp-border);
    border-radius: var(--rp-radius);
    overflow: hidden;
}
.rp-export-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.9rem 1.2rem;
    border-bottom: 1px solid var(--rp-border);
}
.rp-export-row:last-child { border-bottom: none; }

.rp-export-info { display: flex; align-items: center; gap: 0.9rem; }
.rp-export-badge {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.25rem 0.5rem;
    border-radius: 5px;
    flex-shrink: 0;
    width: 46px;
    text-align: center;
}
.rp-badge-csv { background: var(--rp-accent-soft); color: var(--rp-accent); }
.rp-badge-xlsx { background: var(--rp-blue-soft); color: var(--rp-blue); }

.rp-export-title { font-weight: 600; font-size: 0.88rem; }
.rp-export-desc { color: var(--rp-muted); font-size: 0.78rem; }

.btn-rp-export {
    border: 1px solid var(--rp-border);
    background: var(--rp-bg-subtle);
    color: var(--rp-text);
    padding: 0.45rem 1rem;
    border-radius: 7px;
    font-size: 0.82rem;
    font-weight: 500;
    text-decoration: none;
    flex-shrink: 0;
    transition: background .12s ease;
}
.btn-rp-export:hover { background: #eceef0; color: var(--rp-text); }

@media (max-width: 768px) {
    .rp-summary-grid { grid-template-columns: repeat(2, 1fr); }
    .rp-report-grid { grid-template-columns: 1fr; }
    .rp-export-row { flex-direction: column; align-items: flex-start; gap: 0.7rem; }
}
</style>

@endsection