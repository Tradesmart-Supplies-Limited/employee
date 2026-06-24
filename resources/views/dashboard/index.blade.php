@extends('layouts.app')

@section('content')

{{-- =====================================================================
     DASHBOARD — TRADESMART HR
     Palette  : indigo #3B4FE8 · slate #1E293B · green #10B981 · amber #F59E0B
     Signature: payroll health strip (gross → deductions → net proportion)
====================================================================== --}}

<style>
/* ── Reset scope ── */
.hrdash * { box-sizing: border-box; }

/* ── Layout ── */
.hrdash {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    color: #1E293B;
    padding: 0 0 2rem;
}

/* ── Header ── */
.hrdash-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
    gap: .75rem;
}
.hrdash-header h1 {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -.02em;
    margin: 0;
    color: #0F172A;
}
.hrdash-header .subtitle {
    font-size: .8rem;
    color: #64748B;
    margin-top: .1rem;
}
.hrdash-timestamp {
    font-size: .75rem;
    color: #94A3B8;
    background: #F1F5F9;
    padding: .35rem .75rem;
    border-radius: 20px;
}

/* ── KPI grid ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 540px) { .kpi-grid { grid-template-columns: 1fr; } }

.kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.1rem 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.07), 0 1px 8px rgba(0,0,0,.04);
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    border-top: 3px solid transparent;
    transition: box-shadow .18s;
}
.kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.kpi-card.blue  { border-top-color: #3B4FE8; }
.kpi-card.green { border-top-color: #10B981; }
.kpi-card.amber { border-top-color: #F59E0B; }
.kpi-card.rose  { border-top-color: #F43F5E; }

.kpi-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.kpi-icon.blue  { background: #EEF2FF; color: #3B4FE8; }
.kpi-icon.green { background: #ECFDF5; color: #10B981; }
.kpi-icon.amber { background: #FFFBEB; color: #D97706; }
.kpi-icon.rose  { background: #FFF1F2; color: #F43F5E; }

.kpi-body { flex: 1; }
.kpi-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #64748B; }
.kpi-value { font-size: 1.8rem; font-weight: 800; letter-spacing: -.03em; color: #0F172A; line-height: 1.15; }
.kpi-sub { font-size: .74rem; margin-top: .2rem; }
.kpi-sub.good   { color: #10B981; }
.kpi-sub.warn   { color: #D97706; }
.kpi-sub.muted  { color: #94A3B8; }

/* ── Main 2-col grid ── */
.main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}
@media (max-width: 768px) { .main-grid { grid-template-columns: 1fr; } }

/* ── Cards ── */
.panel {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.07), 0 1px 8px rgba(0,0,0,.04);
}
.panel-title {
    font-size: .8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #64748B;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}

/* ── Alerts ── */
.alert-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .6rem 0;
    border-bottom: 1px solid #F1F5F9;
    font-size: .85rem;
    color: #334155;
    gap: .5rem;
}
.alert-item:last-child { border-bottom: none; }
.badge-pill {
    font-size: .68rem;
    font-weight: 700;
    padding: .2rem .6rem;
    border-radius: 20px;
    white-space: nowrap;
}
.badge-danger  { background: #FFF1F2; color: #F43F5E; }
.badge-warning { background: #FFFBEB; color: #D97706; }
.badge-primary { background: #EEF2FF; color: #3B4FE8; }
.badge-success { background: #ECFDF5; color: #059669; }

/* ── No-alerts state ── */
.no-alerts {
    text-align: center;
    padding: 1.5rem 0;
    color: #94A3B8;
    font-size: .85rem;
}
.no-alerts .icon { font-size: 1.75rem; }

/* ── Department bars ── */
.dept-row {
    margin-bottom: .85rem;
}
.dept-row:last-child { margin-bottom: 0; }
.dept-meta {
    display: flex;
    justify-content: space-between;
    font-size: .8rem;
    color: #334155;
    margin-bottom: .3rem;
}
.dept-count { font-weight: 700; color: #0F172A; }
.dept-bar-track {
    height: 6px;
    background: #F1F5F9;
    border-radius: 99px;
    overflow: hidden;
}
.dept-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3B4FE8, #6366F1);
    border-radius: 99px;
    transition: width .6s ease;
}

/* ── SIGNATURE: Payroll health strip ── */
.payroll-panel {}
.payroll-period {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}
.payroll-period-label { font-size: .95rem; font-weight: 700; color: #0F172A; }
.status-chip {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: .2rem .7rem;
    border-radius: 20px;
}
.status-chip.approved { background: #ECFDF5; color: #059669; }
.status-chip.processed { background: #EEF2FF; color: #3B4FE8; }
.status-chip.draft { background: #FFF7ED; color: #D97706; }

/* The health strip — signature element */
.health-strip-label {
    font-size: .7rem;
    color: #94A3B8;
    margin-bottom: .5rem;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.health-strip {
    display: flex;
    height: 18px;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: .65rem;
}
.hs-gross      { background: #3B4FE8; }
.hs-deductions { background: #F43F5E; }
.hs-net        { background: #10B981; }

.health-legend {
    display: flex;
    gap: 1.25rem;
    font-size: .75rem;
    color: #64748B;
    flex-wrap: wrap;
}
.hl-dot {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    margin-right: .3rem;
    vertical-align: middle;
}
.hl-dot.blue  { background: #3B4FE8; }
.hl-dot.red   { background: #F43F5E; }
.hl-dot.green { background: #10B981; }
.health-legend span { font-weight: 700; color: #0F172A; }

.payroll-figures {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .75rem;
    margin-top: 1rem;
}
.pf-item { text-align: center; }
.pf-label { font-size: .68rem; color: #94A3B8; text-transform: uppercase; letter-spacing: .05em; }
.pf-value { font-size: 1rem; font-weight: 800; color: #0F172A; letter-spacing: -.02em; }
.pf-value.earnings   { color: #3B4FE8; }
.pf-value.deductions { color: #F43F5E; }
.pf-value.net        { color: #10B981; }

.payroll-empty {
    text-align: center;
    color: #94A3B8;
    font-size: .85rem;
    padding: 1.5rem 0;
}

/* ── Recent leave table ── */
.leave-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.leave-table th {
    text-align: left;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #94A3B8;
    padding: 0 .5rem .6rem;
    border-bottom: 1px solid #F1F5F9;
}
.leave-table td {
    padding: .55rem .5rem;
    border-bottom: 1px solid #F8FAFC;
    color: #334155;
    vertical-align: middle;
}
.leave-table tr:last-child td { border-bottom: none; }
.leave-name { font-weight: 600; color: #0F172A; }
.leave-type { color: #64748B; }
.leave-days { font-weight: 700; color: #3B4FE8; }

/* ── Recent employees ── */
.emp-list { list-style: none; margin: 0; padding: 0; }
.emp-list li {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .55rem 0;
    border-bottom: 1px solid #F8FAFC;
}
.emp-list li:last-child { border-bottom: none; }
.emp-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3B4FE8 0%, #6366F1 100%);
    color: #fff;
    font-weight: 700;
    font-size: .8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.emp-info { flex: 1; }
.emp-name { font-size: .85rem; font-weight: 600; color: #0F172A; }
.emp-meta { font-size: .72rem; color: #94A3B8; }
.emp-dept {
    font-size: .7rem;
    padding: .15rem .55rem;
    border-radius: 20px;
    background: #F1F5F9;
    color: #64748B;
    font-weight: 600;
    white-space: nowrap;
}

/* ── Full-width bottom row ── */
.bottom-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 1rem;
    margin-top: 0;
}
@media (max-width: 768px) { .bottom-row { grid-template-columns: 1fr; } }
</style>

<div class="hrdash">

    {{-- ===== HEADER ===== --}}
    <div class="hrdash-header">
        <div>
            <h1>HR Dashboard</h1>
            <div class="subtitle">Workforce overview · {{ now()->format('l, d F Y') }}</div>
        </div>
        <div class="hrdash-timestamp">
            <i class="bi bi-arrow-clockwise me-1"></i>Updated {{ now()->format('H:i') }}
        </div>
    </div>

    {{-- ===== KPI CARDS ===== --}}
    <div class="kpi-grid">

        <div class="kpi-card blue">
            <div class="kpi-icon blue"><i class="bi bi-people-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Total Employees</div>
                <div class="kpi-value">{{ $stats['employees'] }}</div>
                <div class="kpi-sub {{ $stats['new_this_month'] > 0 ? 'good' : 'muted' }}">
                    @if($stats['new_this_month'] > 0)
                        +{{ $stats['new_this_month'] }} this month
                    @else
                        {{ $stats['active_employees'] }} active
                    @endif
                </div>
            </div>
        </div>

        <div class="kpi-card amber">
            <div class="kpi-icon amber"><i class="bi bi-calendar-x-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Pending Leave</div>
                <div class="kpi-value">{{ $stats['pending_leave'] }}</div>
                <div class="kpi-sub {{ $stats['pending_leave'] > 0 ? 'warn' : 'muted' }}">
                    {{ $stats['on_leave_today'] }} on leave today
                </div>
            </div>
        </div>

        <div class="kpi-card green">
            <div class="kpi-icon green"><i class="bi bi-diagram-3-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Departments</div>
                <div class="kpi-value">{{ $stats['departments'] }}</div>
                <div class="kpi-sub muted">{{ $stats['branches'] }} branch(es)</div>
            </div>
        </div>

        <div class="kpi-card rose">
            <div class="kpi-icon rose"><i class="bi bi-file-earmark-x-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Expiring Contracts</div>
                <div class="kpi-value">{{ $stats['contracts_expiring'] }}</div>
                <div class="kpi-sub {{ $stats['contracts_expiring'] > 0 ? 'warn' : 'good' }}">
                    {{ $stats['contracts_expiring'] > 0 ? 'Within 30 days' : 'None due soon' }}
                </div>
            </div>
        </div>

    </div>

    {{-- ===== MAIN ROW: Alerts + Payroll Health ===== --}}
    <div class="main-grid mb-3">

        {{-- HR Alerts --}}
        <div class="panel">
            <div class="panel-title">
                <i class="bi bi-bell-fill text-warning"></i> HR Alerts
            </div>

            @if($alerts->isEmpty())
                <div class="no-alerts">
                    <div class="icon">✅</div>
                    <div class="mt-1">No active alerts — all clear</div>
                </div>
            @else
                @foreach($alerts as $alert)
                    <div class="alert-item">
                        <span>{{ $alert['message'] }}</span>
                        <span class="badge-pill badge-{{ $alert['severity'] }}">{{ $alert['label'] }}</span>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Payroll Health Strip (SIGNATURE ELEMENT) --}}
        <div class="panel payroll-panel">
            <div class="panel-title">
                <i class="bi bi-currency-dollar text-success"></i> Payroll Health
            </div>

            @if($payrollSummary)
                <div class="payroll-period">
                    <span class="payroll-period-label">{{ $payrollSummary['period'] }}</span>
                    <span class="status-chip {{ strtolower($payrollSummary['status']) }}">
                        {{ $payrollSummary['status'] }}
                    </span>
                </div>

                @php
                    $gross = $payrollSummary['total_earnings'];
                    $deductions = $payrollSummary['total_deductions'];
                    $net = $payrollSummary['net_pay'];

                    // proportions relative to gross
                    $grossPct = $gross > 0 ? 100 : 0;
                    $dedPct   = $gross > 0 ? round(($deductions / $gross) * 100, 1) : 0;
                    $netPct   = $gross > 0 ? round(($net / $gross) * 100, 1) : 0;
                    // normalise widths so they sum to 100%
                    $dedW = $dedPct;
                    $netW = $netPct;
                    $grossW = max(0, 100 - $dedW - $netW);
                @endphp

                <div class="health-strip-label">Gross → Deductions → Net</div>
                <div class="health-strip">
                    <div class="hs-gross"      style="width: {{ $grossW }}%"></div>
                    <div class="hs-deductions" style="width: {{ $dedW }}%"></div>
                    <div class="hs-net"        style="width: {{ $netW }}%"></div>
                </div>
                <div class="health-legend">
                    <div><span class="hl-dot blue"></span>Gross <span>{{ $dedW + $netW + $grossW > 0 ? ($grossW + $dedW).'%' : '' }}</span></div>
                    <div><span class="hl-dot red"></span>Deductions <span>{{ $dedPct }}%</span></div>
                    <div><span class="hl-dot green"></span>Net <span>{{ $netPct }}%</span></div>
                </div>

                <div class="payroll-figures">
                    <div class="pf-item">
                        <div class="pf-label">Gross</div>
                        <div class="pf-value earnings">K{{ number_format($gross, 0) }}</div>
                    </div>
                    <div class="pf-item">
                        <div class="pf-label">Deductions</div>
                        <div class="pf-value deductions">K{{ number_format($deductions, 0) }}</div>
                    </div>
                    <div class="pf-item">
                        <div class="pf-label">Net Pay</div>
                        <div class="pf-value net">K{{ number_format($net, 0) }}</div>
                    </div>
                </div>

                <div class="mt-3" style="font-size:.74rem; color:#94A3B8; text-align:right;">
                    {{ $payrollSummary['employee_count'] }} employees · 
                    @if($payrollSummary['finalized_at'])
                        Finalized {{ \Carbon\Carbon::parse($payrollSummary['finalized_at'])->format('d M Y') }}
                    @else
                        Not yet finalized
                    @endif
                </div>
            @else
                <div class="payroll-empty">
                    <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                    No payroll run found yet.
                    <a href="{{ route('payroll.runs.create') }}" class="d-block mt-2 text-primary" style="font-weight:600;">Start a payroll run →</a>
                </div>
            @endif
        </div>

    </div>

    {{-- ===== BOTTOM ROW: Recent Leave + Depts + New Employees ===== --}}
    <div class="bottom-row">

        {{-- Recent Leave Requests --}}
        <div class="panel">
            <div class="panel-title">
                <i class="bi bi-clock-history"></i> Recent Leave Requests
                <a href="{{ route('leave.index') }}" style="margin-left:auto; font-size:.72rem; color:#3B4FE8; font-weight:600; text-transform:none; letter-spacing:0;">View all</a>
            </div>

            @if($recentLeave->isEmpty())
                <div class="no-alerts">
                    <div class="icon">📭</div>
                    <div class="mt-1">No leave requests yet</div>
                </div>
            @else
                <table class="leave-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Days</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLeave as $leave)
                            <tr>
                                <td class="leave-name">{{ $leave->employee_name }}</td>
                                <td class="leave-type">{{ $leave->leave_type }}</td>
                                <td class="leave-days">{{ $leave->total_days }}</td>
                                <td>
                                    @php
                                        $sc = match($leave->status) {
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            default    => 'warning',
                                        };
                                    @endphp
                                    <span class="badge-pill badge-{{ $sc }}">{{ $leave->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Right column: dept breakdown + new employees --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">

            {{-- Department Breakdown --}}
            <div class="panel">
                <div class="panel-title">
                    <i class="bi bi-pie-chart-fill"></i> Headcount by Department
                </div>

                @if($departmentBreakdown->isEmpty())
                    <div class="no-alerts"><div>No department data</div></div>
                @else
                    @php $maxCount = $departmentBreakdown->max('count'); @endphp
                    @foreach($departmentBreakdown as $dept)
                        <div class="dept-row">
                            <div class="dept-meta">
                                <span>{{ $dept->department }}</span>
                                <span class="dept-count">{{ $dept->count }}</span>
                            </div>
                            <div class="dept-bar-track">
                                <div class="dept-bar-fill" style="width: {{ $maxCount > 0 ? round(($dept->count / $maxCount) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- New Employees --}}
            <div class="panel">
                <div class="panel-title">
                    <i class="bi bi-person-plus-fill"></i> Recently Added
                    <a href="{{ route('employees.index') }}" style="margin-left:auto; font-size:.72rem; color:#3B4FE8; font-weight:600; text-transform:none; letter-spacing:0;">View all</a>
                </div>

                @if($recentEmployees->isEmpty())
                    <div class="no-alerts"><div>No employees yet</div></div>
                @else
                    <ul class="emp-list">
                        @foreach($recentEmployees as $emp)
                            <li>
                                <div class="emp-avatar">
                                    {{ strtoupper(substr($emp->first_name, 0, 1)) }}{{ strtoupper(substr($emp->last_name, 0, 1)) }}
                                </div>
                                <div class="emp-info">
                                    <div class="emp-name">{{ $emp->first_name }} {{ $emp->last_name }}</div>
                                    <div class="emp-meta">{{ $emp->position ?? '—' }} · Added {{ $emp->created_at->diffForHumans() }}</div>
                                </div>
                                @if($emp->department)
                                    <span class="emp-dept">{{ $emp->department }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection