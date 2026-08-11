@extends('layouts.app')

@section('content')

{{-- =====================================================================
     DASHBOARD — TRADESMART HR
     Structure: KPI ribbon → Workforce Overview (main) + Alerts feed (main)
                | Quick Actions + Recently Added (rail)
     Uses shared --admin-* tokens, follows light/dark theme automatically.
====================================================================== --}}

<style>
.hrdash * { box-sizing: border-box; }

.hrdash {
    font-family: "Segoe UI", Arial, sans-serif;
    color: var(--admin-text);
    padding: 0 0 2rem;
}

/* ── Topbar ── */
.hrdash-topbar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .9rem;
    margin-bottom: 1.5rem;
}
.hrdash-eyebrow {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--admin-primary);
    margin-bottom: .3rem;
}
.hrdash-topbar h1 {
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: -.02em;
    margin: 0;
    color: var(--admin-text);
}
.hrdash-topbar .subtitle {
    font-size: .82rem;
    color: var(--admin-muted);
    margin-top: .2rem;
}
.hrdash-actions {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
}
.hrdash-actions .btn {
    font-size: .84rem;
    padding: .5rem .9rem;
}

/* ── KPI ribbon: single bordered strip, divided sections ── */
.kpi-ribbon {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    box-shadow: var(--admin-shadow-sm);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.kpi-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: 1rem 1.15rem;
    border-right: 1px solid var(--admin-border);
    min-width: 0;
}
.kpi-item:last-child { border-right: none; }
.kpi-item .kpi-icon {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    font-size: 1rem;
    background: var(--kpi-bg, #eaf2ff);
    color: var(--kpi-fg, var(--admin-primary));
}
.kpi-item.blue  { --kpi-bg: #eaf2ff; --kpi-fg: var(--admin-primary); }
.kpi-item.green { --kpi-bg: #e7f6f3; --kpi-fg: var(--admin-success); }
.kpi-item.amber { --kpi-bg: #fff4df; --kpi-fg: var(--admin-warning); }
.kpi-item.rose  { --kpi-bg: #ffecec; --kpi-fg: var(--admin-danger); }
html[data-theme="dark"] .kpi-item.blue  { --kpi-bg: rgba(96,165,250,.14); }
html[data-theme="dark"] .kpi-item.green { --kpi-bg: rgba(45,212,191,.14); }
html[data-theme="dark"] .kpi-item.amber { --kpi-bg: rgba(251,191,36,.14); }
html[data-theme="dark"] .kpi-item.rose  { --kpi-bg: rgba(248,113,113,.14); }

.kpi-item-body { min-width: 0; }
.kpi-item-label {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--admin-muted);
}
.kpi-item-value {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--admin-text);
    line-height: 1.25;
}
.kpi-item-sub {
    font-size: .72rem;
    font-weight: 600;
    margin-top: .1rem;
}
.kpi-item-sub.good  { color: var(--admin-success); }
.kpi-item-sub.warn  { color: var(--admin-warning); }
.kpi-item-sub.muted { color: var(--admin-muted); }

@media (max-width: 900px) { .kpi-ribbon { grid-template-columns: repeat(2, 1fr); } .kpi-item:nth-child(2) { border-right: none; } .kpi-item:nth-child(odd) { border-right: 1px solid var(--admin-border); } .kpi-item { border-bottom: 1px solid var(--admin-border); } .kpi-item:nth-last-child(-n+2) { border-bottom: none; } }
@media (max-width: 540px) { .kpi-ribbon { grid-template-columns: 1fr; } .kpi-item { border-right: none !important; border-bottom: 1px solid var(--admin-border); } .kpi-item:last-child { border-bottom: none; } }

/* ── Main structural grid: dominant column + narrow rail ── */
.dash-grid {
    display: grid;
    grid-template-columns: 2.1fr 1fr;
    gap: 1.1rem;
    align-items: start;
}
@media (max-width: 992px) { .dash-grid { grid-template-columns: 1fr; } }

.dash-main, .dash-rail {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

/* ── Panels ── */
.panel {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 1.25rem;
    box-shadow: var(--admin-shadow-sm);
}
.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: 1.1rem;
}
.panel-title {
    font-size: .8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--admin-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.panel-title i { color: var(--admin-primary); font-size: .9rem; }
.panel-link {
    font-size: .74rem;
    color: var(--admin-primary);
    font-weight: 700;
    white-space: nowrap;
}
.panel-link:hover { text-decoration: underline; }

/* ── Workforce overview: dept rows as a real data table feel ── */
.wf-total {
    font-size: .78rem;
    color: var(--admin-muted);
    margin-bottom: 1rem;
}
.wf-total strong { color: var(--admin-text); }

.dept-row {
    display: grid;
    grid-template-columns: 140px 1fr 60px;
    align-items: center;
    gap: .9rem;
    padding: .55rem 0;
    border-bottom: 1px solid var(--admin-border);
}
.dept-row:last-child { border-bottom: none; }
.dept-name {
    font-size: .84rem;
    font-weight: 600;
    color: var(--admin-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dept-bar-track {
    height: 8px;
    background: var(--admin-surface-soft);
    border: 1px solid var(--admin-border);
    border-radius: 99px;
    overflow: hidden;
}
.dept-bar-fill {
    height: 100%;
    background: var(--admin-primary);
    border-radius: 99px;
    transition: width .6s ease;
}
.dept-figures {
    text-align: right;
    font-size: .82rem;
    font-weight: 700;
    color: var(--admin-text);
    white-space: nowrap;
}
.dept-figures small {
    display: block;
    font-weight: 600;
    color: var(--admin-muted);
    font-size: .68rem;
}

/* ── Alert feed: severity stripe rows ── */
.feed-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    padding: .7rem .8rem .7rem .9rem;
    margin-bottom: .5rem;
    border-radius: 6px;
    background: var(--admin-surface-soft);
    border: 1px solid var(--admin-border);
    border-left: 3px solid var(--feed-color, var(--admin-primary));
}
.feed-item:last-child { margin-bottom: 0; }
.feed-item.severity-danger  { --feed-color: var(--admin-danger); }
.feed-item.severity-warning { --feed-color: var(--admin-warning); }
.feed-item.severity-primary { --feed-color: var(--admin-primary); }
.feed-item.severity-success { --feed-color: var(--admin-success); }

.feed-message {
    font-size: .84rem;
    color: var(--admin-text);
    line-height: 1.4;
}
.badge-pill {
    font-size: .66rem;
    font-weight: 700;
    padding: .22rem .6rem;
    border-radius: 6px;
    white-space: nowrap;
    flex-shrink: 0;
}
.badge-danger  { background: #ffecec; color: var(--admin-danger); }
.badge-warning { background: #fff4df; color: var(--admin-warning); }
.badge-primary { background: #eaf2ff; color: var(--admin-primary); }
.badge-success { background: #e7f6f3; color: var(--admin-success); }
html[data-theme="dark"] .badge-danger  { background: rgba(248,113,113,.16); }
html[data-theme="dark"] .badge-warning { background: rgba(251,191,36,.16); }
html[data-theme="dark"] .badge-primary { background: rgba(96,165,250,.16); }
html[data-theme="dark"] .badge-success { background: rgba(45,212,191,.16); }

/* ── No-data state ── */
.no-data {
    text-align: center;
    padding: 1.75rem 0;
    color: var(--admin-muted);
    font-size: .85rem;
}
.no-data .icon { font-size: 1.6rem; display: block; margin-bottom: .4rem; }

/* ── Quick actions rail ── */
.qa-list {
    display: flex;
    flex-direction: column;
    gap: .5rem;
}
.qa-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .65rem .75rem;
    border: 1px solid var(--admin-border);
    border-radius: 7px;
    color: var(--admin-text);
    font-size: .85rem;
    font-weight: 600;
    transition: border-color .15s ease, background .15s ease;
}
.qa-item:hover {
    border-color: var(--admin-primary);
    background: var(--admin-surface-soft);
    color: var(--admin-text);
}
.qa-item i {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: #eaf2ff;
    color: var(--admin-primary);
    font-size: .92rem;
}
html[data-theme="dark"] .qa-item i { background: rgba(96,165,250,.14); }
.qa-item span { flex: 1; }
.qa-item small {
    display: block;
    font-weight: 500;
    color: var(--admin-muted);
    font-size: .72rem;
    margin-top: .1rem;
}
.qa-item .bi-chevron-right {
    background: none;
    width: auto;
    height: auto;
    color: var(--admin-muted);
    font-size: .8rem;
}

/* ── Recently added employees (rail) ── */
.emp-list { list-style: none; margin: 0; padding: 0; }
.emp-list li {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .6rem 0;
    border-bottom: 1px solid var(--admin-border);
}
.emp-list li:last-child { border-bottom: none; }
.emp-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #4f8cff);
    color: #fff;
    font-weight: 700;
    font-size: .74rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.emp-info { flex: 1; min-width: 0; }
.emp-name { font-size: .83rem; font-weight: 600; color: var(--admin-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.emp-meta { font-size: .7rem; color: var(--admin-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.emp-dept {
    font-size: .68rem;
    padding: .15rem .55rem;
    border-radius: 6px;
    background: var(--admin-surface-soft);
    border: 1px solid var(--admin-border);
    color: var(--admin-muted);
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
}
</style>

<div class="hrdash">

    {{-- ===== TOPBAR ===== --}}
    <div class="hrdash-topbar">
        <div>
            <div class="hrdash-eyebrow">Overview</div>
            <h1>HR Dashboard</h1>
            <div class="subtitle">Workforce summary · {{ now()->format('l, d F Y') }}</div>
        </div>

        <div class="hrdash-actions">
            <a href="{{ route('employees.index') }}" class="btn btn-light">
                <i class="bi bi-people"></i> Employees
            </a>
            <!-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importEmployeesModal">
                <i class="bi bi-cloud-upload"></i> Import Employees
            </button> -->
        </div>
    </div>

    {{-- ===== KPI RIBBON ===== --}}
    <div class="kpi-ribbon">

        <div class="kpi-item blue">
            <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
            <div class="kpi-item-body">
                <div class="kpi-item-label">Employees</div>
                <div class="kpi-item-value">{{ $stats['employees'] }}</div>
                <div class="kpi-item-sub {{ $stats['new_this_month'] > 0 ? 'good' : 'muted' }}">
                    @if($stats['new_this_month'] > 0)
                        +{{ $stats['new_this_month'] }} this month
                    @else
                        {{ $stats['active_employees'] }} active
                    @endif
                </div>
            </div>
        </div>

        <div class="kpi-item green">
            <div class="kpi-icon"><i class="bi bi-diagram-3-fill"></i></div>
            <div class="kpi-item-body">
                <div class="kpi-item-label">Departments</div>
                <div class="kpi-item-value">{{ $stats['departments'] }}</div>
                <div class="kpi-item-sub muted">Across the org</div>
            </div>
        </div>

        <div class="kpi-item blue">
            <div class="kpi-icon"><i class="bi bi-building"></i></div>
            <div class="kpi-item-body">
                <div class="kpi-item-label">Branches</div>
                <div class="kpi-item-value">{{ $stats['branches'] }}</div>
                <div class="kpi-item-sub muted">Operating locations</div>
            </div>
        </div>

        <div class="kpi-item rose">
            <div class="kpi-icon"><i class="bi bi-file-earmark-x-fill"></i></div>
            <div class="kpi-item-body">
                <div class="kpi-item-label">Expiring Contracts</div>
                <div class="kpi-item-value">{{ $stats['contracts_expiring'] }}</div>
                <div class="kpi-item-sub {{ $stats['contracts_expiring'] > 0 ? 'warn' : 'good' }}">
                    {{ $stats['contracts_expiring'] > 0 ? 'Within 30 days' : 'None due soon' }}
                </div>
            </div>
        </div>

    </div>

    {{-- ===== STRUCTURAL GRID ===== --}}
    <div class="dash-grid">

        {{-- ---- MAIN COLUMN ---- --}}
        <div class="dash-main">

            {{-- Workforce Overview --}}
            <div class="panel">
                <div class="panel-head">
                    <h2 class="panel-title"><i class="bi bi-bar-chart-fill"></i> Workforce Overview</h2>
                </div>

                @if($departmentBreakdown->isEmpty())
                    <div class="no-data">
                        <span class="icon">📊</span>
                        No department data available yet
                    </div>
                @else
                    @php
                        $totalHeadcount = $departmentBreakdown->sum('count');
                        $maxCount = $departmentBreakdown->max('count');
                    @endphp

                    <div class="wf-total">
                        <strong>{{ $totalHeadcount }}</strong> employees across
                        <strong>{{ $departmentBreakdown->count() }}</strong> department(s)
                    </div>

                    @foreach($departmentBreakdown as $dept)
                        @php
                            $pct = $totalHeadcount > 0 ? round(($dept->count / $totalHeadcount) * 100) : 0;
                            $barWidth = $maxCount > 0 ? round(($dept->count / $maxCount) * 100) : 0;
                        @endphp
                        <div class="dept-row">
                            <div class="dept-name">{{ $dept->department }}</div>
                            <div class="dept-bar-track">
                                <div class="dept-bar-fill" style="width: {{ $barWidth }}%"></div>
                            </div>
                            <div class="dept-figures">
                                {{ $dept->count }}
                                <small>{{ $pct }}%</small>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- HR Alerts & Notices --}}
            <div class="panel">
                <div class="panel-head">
                    <h2 class="panel-title"><i class="bi bi-bell-fill"></i> HR Alerts &amp; Notices</h2>
                </div>

                @if($alerts->isEmpty())
                    <div class="no-data">
                        <span class="icon">✅</span>
                        No active alerts — all clear
                    </div>
                @else
                    @foreach($alerts as $alert)
                        <div class="feed-item severity-{{ $alert['severity'] }}">
                            <div class="feed-message">{{ $alert['message'] }}</div>
                            <span class="badge-pill badge-{{ $alert['severity'] }}">{{ $alert['label'] }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Payroll Health Strip (disabled — re-enable when payroll routes are active) --}}
            {{--
            <div class="panel payroll-panel">
                ... payroll health markup unchanged from before, omitted here while disabled ...
            </div>
            --}}

            {{-- Recent Leave Requests (disabled — re-enable when leave routes are active) --}}
            {{--
            <div class="panel">
                ... leave table markup unchanged from before, omitted here while disabled ...
            </div>
            --}}

        </div>

        {{-- ---- RAIL COLUMN ---- --}}
        <div class="dash-rail">

            {{-- Quick Actions --}}
            <div class="panel">
                <div class="panel-head">
                    <h2 class="panel-title"><i class="bi bi-lightning-fill"></i> Quick Actions</h2>
                </div>

                <div class="qa-list">
                    <a href="{{ route('employees.index') }}" class="qa-item">
                        <i class="bi bi-people"></i>
                        <span>
                            Manage Employees
                            <small>View, edit, and add staff records</small>
                        </span>
                        <i class="bi bi-chevron-right"></i>
                    </a>

                    <!-- <button type="button" class="qa-item" style="background:none;text-align:left;width:100%;cursor:pointer;" data-bs-toggle="modal" data-bs-target="#importEmployeesModal">
                        <i class="bi bi-cloud-upload"></i>
                        <span>
                            Import Employees
                            <small>Bulk create from a CSV file</small>
                        </span>
                        <i class="bi bi-chevron-right"></i>
                    </button>

                    <a href="{{ route('settings.index') }}" class="qa-item">
                        <i class="bi bi-gear"></i>
                        <span>
                            Settings
                            <small>Configure your HR platform</small>
                        </span>
                        <i class="bi bi-chevron-right"></i>
                    </a> -->
                </div>
            </div>

            {{-- Recently Added Employees --}}
            <div class="panel">
                <div class="panel-head">
                    <h2 class="panel-title"><i class="bi bi-person-plus-fill"></i> Recently Added</h2>
                    <a href="{{ route('employees.index') }}" class="panel-link">View all</a>
                </div>

                @if($recentEmployees->isEmpty())
                    <div class="no-data">No employees yet</div>
                @else
                    <ul class="emp-list">
                        @foreach($recentEmployees as $emp)
                            <li>
                                <div class="emp-avatar">
                                    {{ strtoupper(substr($emp->first_name, 0, 1)) }}{{ strtoupper(substr($emp->last_name, 0, 1)) }}
                                </div>
                                <div class="emp-info">
                                    <div class="emp-name">{{ $emp->first_name }} {{ $emp->last_name }}</div>
                                    <div class="emp-meta">{{ $emp->position ?? '—' }} · {{ $emp->created_at->diffForHumans() }}</div>
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