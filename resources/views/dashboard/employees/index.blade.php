@extends('layouts.app')

@section('content')

{{-- ===== TOP BAR ===== --}}
<div class="emp-top-bar">

    <div>
        <div class="emp-eyebrow">Workforce</div>
        <h3 class="emp-title">Employees</h3>
    </div>

    <div class="emp-top-right">

        <div class="emp-search-wrap">
            <i class="bi bi-search emp-search-icon"></i>
            <input type="text"
                   id="empSearch"
                   class="emp-search-input"
                   placeholder="Search name, ID, position…"
                   oninput="empFilter()">
        </div>

        {{-- CONTRACT REMINDERS BUTTON --}}
        <a href="{{ route('settings.contract-reminders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bell"></i> Contract Reminders
        </a>

        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Employee
        </a>

    </div>

</div>


{{-- ===== KPI RIBBON ===== --}}
<div class="emp-kpi-ribbon">

    <div class="emp-kpi-item blue">
        <div class="emp-kpi-icon"><i class="bi bi-people-fill"></i></div>
        <div class="emp-kpi-body">
            <div class="emp-kpi-label">Total Employees</div>
            <div class="emp-kpi-value">{{ $employees->count() }}</div>
        </div>
    </div>

    <div class="emp-kpi-item green">
        <div class="emp-kpi-icon"><i class="bi bi-person-check-fill"></i></div>
        <div class="emp-kpi-body">
            <div class="emp-kpi-label">Active</div>
            <div class="emp-kpi-value">{{ $employees->where('employment_status', 'Active')->count() }}</div>
        </div>
    </div>

    <div class="emp-kpi-item amber">
        <div class="emp-kpi-icon"><i class="bi bi-diagram-3-fill"></i></div>
        <div class="emp-kpi-body">
            <div class="emp-kpi-label">Departments</div>
            <div class="emp-kpi-value">{{ $employees->pluck('department')->filter()->unique()->count() }}</div>
        </div>
    </div>

    <div class="emp-kpi-item rose">
        <div class="emp-kpi-icon"><i class="bi bi-file-earmark-x-fill"></i></div>
        <div class="emp-kpi-body">
            <div class="emp-kpi-label">Contracts Expiring</div>
            <div class="emp-kpi-value">
                {{ $employees->filter(fn($e) => $e->contract_end && \Carbon\Carbon::parse($e->contract_end)->diffInDays(now()) <= 30 && \Carbon\Carbon::parse($e->contract_end)->isFuture())->count() }}
            </div>
        </div>
    </div>

</div>


{{-- ===== FILTER BAR ===== --}}
<div class="panel emp-filter-bar">

    <span class="emp-filter-label">Filter</span>

    <select id="empDeptFilter" class="emp-select" onchange="empFilter()">
        <option value="">All Departments</option>
        @foreach($employees->pluck('department')->filter()->unique()->sort() as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
        @endforeach
    </select>

    <select id="empStatusFilter" class="emp-select" onchange="empFilter()">
        <option value="">All Statuses</option>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
    </select>

    <div class="emp-view-toggle ms-auto">
        <button id="btnTable" class="emp-view-btn active" onclick="empShowView('table')">
            <i class="bi bi-list-ul"></i> Table
        </button>
        <button id="btnCard" class="emp-view-btn" onclick="empShowView('card')">
            <i class="bi bi-grid"></i> Cards
        </button>
    </div>

</div>


{{-- ===== TABLE VIEW ===== --}}
<div class="panel" id="empTableView">

    <table class="emp-table" id="empTable">

        <thead>
            <tr>
                <th>EIN</th>
                <th>Name</th>
                <th>Department</th>
                <th>Phone</th>
                <th>Contract</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
            @foreach($employees as $employee)

            @php
                $contractEndingSoon = $employee->contract_end
                    && \Carbon\Carbon::parse($employee->contract_end)->isFuture()
                    && \Carbon\Carbon::parse($employee->contract_end)->diffInDays(now()) <= 30;
            @endphp

            <tr class="emp-row"
                data-dept="{{ $employee->department }}"
                data-status="{{ $employee->employment_status }}"
                data-search="{{ strtolower($employee->first_name . ' ' . $employee->last_name . ' ' . $employee->employee_id . ' ' . $employee->position . ' ' . $employee->department . ' ' . $employee->branch) }}"
                onclick="window.location='{{ route('employees.show', $employee->id) }}'">

                {{-- EIN --}}
                <td class="emp-td-id">
                    <i class="bi bi-hash"></i>{{ $employee->employee_id }}
                </td>

                {{-- Name --}}
                <td>
                    <div class="emp-name-cell">
                        <div class="emp-avatar emp-avatar-{{ Str::slug($employee->department ?? 'default') }}">
                            <img src="{{ $employee->passport_photo
                    ? asset('storage/' . $employee->passport_photo)
                    : asset('assets/images/avatar/avatar.jpg') }}" class="rounded-circle border" width="45" height="45"
                                style="object-fit: cover;">
                        </div>
                        <div>
                            <div class="emp-name">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                            <div class="emp-pos">
                                <i class="bi bi-briefcase"></i> {{ $employee->position }}
                            </div>
                        </div>
                    </div>
                </td>

                {{-- Department --}}
                <td>
                    <span class="emp-dept-badge emp-dept-{{ Str::slug($employee->department ?? 'default') }}">
                        {{ $employee->department ?? '—' }}
                    </span>
                </td>

                {{-- Branch --}}
                <td>
                    <span class="emp-branch">{{ $employee->branch ?? '—' }}</span>
                </td>

                {{-- Phone --}}
                <td class="emp-td-muted">
                    <i class="bi bi-telephone"></i> {{ $employee->primary_phone ?? '—' }}
                </td>

                {{-- Contract --}}
                <td>
                    @if($employee->contract_start && $employee->contract_end)
                        <div class="emp-contract {{ $contractEndingSoon ? 'emp-contract-warn' : '' }}">
                            @if($contractEndingSoon)
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            @else
                                <i class="bi bi-calendar-event"></i>
                            @endif
                            {{ $employee->contract_start->format('M d, Y') }}
                            &rarr;
                            {{ $employee->contract_end->format('M d, Y') }}
                            @if($contractEndingSoon)
                                <span class="emp-soon-label">ending soon</span>
                            @endif
                        </div>
                    @else
                        <span class="emp-td-muted">—</span>
                    @endif
                </td>

                {{-- Status --}}
                <td>
                    <span class="emp-status-badge emp-status-{{ strtolower($employee->employment_status) }}">
                        {{ $employee->employment_status }}
                    </span>
                </td>

                {{-- Actions --}}
                <td onclick="event.stopPropagation()" class="emp-actions-cell">
                    <div class="emp-row-actions">
                        <a href="{{ route('employees.show', $employee->id) }}"
                           class="emp-action-btn" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('employees.edit', $employee->id) }}"
                           class="emp-action-btn" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </td>

            </tr>

            @endforeach
        </tbody>


    </table>

    <div id="empTableEmpty" class="emp-empty-state" style="display:none;">
        <i class="bi bi-search"></i>
        <div>No employees match your search or filters.</div>
    </div>

</div>


{{-- ===== CARD VIEW ===== --}}
<div id="empCardView" style="display:none;">

    @foreach($employees->groupBy('department') as $department => $group)

    <div class="emp-dept-group" data-dept-group="{{ $department }}">

        <div class="emp-dept-group-label">
            {{ $department ?? 'Unassigned' }}
            <span class="emp-dept-count">{{ $group->count() }}</span>
        </div>

        <div class="row g-3" id="cardGroup-{{ Str::slug($department ?? 'unassigned') }}">

            @foreach($group as $employee)

            @php
                $contractEndingSoon = $employee->contract_end
                    && \Carbon\Carbon::parse($employee->contract_end)->isFuture()
                    && \Carbon\Carbon::parse($employee->contract_end)->diffInDays(now()) <= 30;
            @endphp

            <div class="col-md-4 emp-card-col"
                 data-dept="{{ $employee->department }}"
                 data-status="{{ $employee->employment_status }}"
                 data-search="{{ strtolower($employee->first_name . ' ' . $employee->last_name . ' ' . $employee->employee_id . ' ' . $employee->position . ' ' . $employee->department) }}">

                <div class="emp-card" onclick="window.location='{{ route('employees.show', $employee->id) }}'">

                    <div class="emp-card-top">

                        <div class="emp-avatar emp-avatar-lg emp-avatar-{{ Str::slug($employee->department ?? 'default') }}">
                            @if($employee->passport_photo)
                                <img src="{{ asset('storage/' . $employee->passport_photo) }}"
                                     alt="{{ $employee->first_name }}">
                            @else
                                {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                            @endif
                        </div>

                        <div class="flex-grow-1 min-width-0">
                            <div class="emp-card-name">
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </div>
                            <div class="emp-card-pos">{{ $employee->position }}</div>
                            <div class="emp-card-id">
                                <i class="bi bi-hash"></i>{{ $employee->employee_id }}
                            </div>
                        </div>

                        <span class="emp-status-badge emp-status-{{ strtolower($employee->employment_status) }}">
                            {{ $employee->employment_status }}
                        </span>

                    </div>

                    @if($employee->primary_phone)
                    <div class="emp-card-detail">
                        <i class="bi bi-telephone"></i> {{ $employee->primary_phone }}
                    </div>
                    @endif

                    @if($contractEndingSoon)
                    <div class="emp-card-warn">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Contract ending soon ({{ $employee->contract_end->format('M d, Y') }})
                    </div>
                    @endif

                    <div class="emp-card-footer">
                        <span class="emp-dept-badge emp-dept-{{ Str::slug($employee->department ?? 'default') }}">
                            {{ $employee->department ?? 'Unassigned' }}
                        </span>
                        <div class="emp-card-actions" onclick="event.stopPropagation()">
                            <a href="{{ route('employees.show', $employee->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('employees.edit', $employee->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </div>

                </div>

            </div>

            @endforeach

        </div>

    </div>

    @endforeach

    <div id="empCardEmpty" class="emp-empty-state" style="display:none;">
        <i class="bi bi-search"></i>
        <div>No employees match your search or filters.</div>
    </div>

</div>


{{-- ===== STYLES ===== --}}
<style>

.emp-top-bar,
.emp-kpi-ribbon,
.emp-filter-bar,
#empTableView,
#empCardView { font-family: "Segoe UI", Arial, sans-serif; }

/* ---- Layout ---- */
.emp-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.1rem;
    flex-wrap: wrap;
    gap: .75rem;
}

.emp-eyebrow {
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--admin-primary);
    margin-bottom: .2rem;
}

.emp-title {
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
    color: var(--admin-text);
}

.emp-top-right {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
}

/* ---- Search ---- */
.emp-search-wrap {
    position: relative;
}

.emp-search-icon {
    position: absolute;
    left: .7rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--admin-muted);
    font-size: .82rem;
    pointer-events: none;
}

.emp-search-input {
    padding: .5rem .7rem .5rem 2.1rem;
    border: none;
    border-radius: 7px;
    background: var(--admin-surface-soft);
    box-shadow: inset 0 0 0 1px var(--admin-border);
    font-size: .82rem;
    width: 230px;
    color: var(--admin-text);
    outline: none;
    transition: box-shadow .15s ease, background .15s ease;
}

.emp-search-input::placeholder { color: var(--admin-muted); }

.emp-search-input:focus {
    background: var(--admin-surface);
    box-shadow: inset 0 0 0 1.5px var(--admin-primary), var(--admin-ring);
}

/* ---- KPI ribbon ---- */
.emp-kpi-ribbon {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    box-shadow: var(--admin-shadow-sm);
    margin-bottom: 1rem;
    overflow: hidden;
}

.emp-kpi-item {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .9rem 1.1rem;
    border-right: 1px solid var(--admin-border);
    min-width: 0;
}
.emp-kpi-item:last-child { border-right: none; }

.emp-kpi-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    font-size: .95rem;
    background: var(--kpi-bg, #eaf2ff);
    color: var(--kpi-fg, var(--admin-primary));
}
.emp-kpi-item.blue  { --kpi-bg: #eaf2ff; --kpi-fg: var(--admin-primary); }
.emp-kpi-item.green { --kpi-bg: #e7f6f3; --kpi-fg: var(--admin-success); }
.emp-kpi-item.amber { --kpi-bg: #fff4df; --kpi-fg: var(--admin-warning); }
.emp-kpi-item.rose  { --kpi-bg: #ffecec; --kpi-fg: var(--admin-danger); }
html[data-theme="dark"] .emp-kpi-item.blue  { --kpi-bg: rgba(96,165,250,.14); }
html[data-theme="dark"] .emp-kpi-item.green { --kpi-bg: rgba(45,212,191,.14); }
html[data-theme="dark"] .emp-kpi-item.amber { --kpi-bg: rgba(251,191,36,.14); }
html[data-theme="dark"] .emp-kpi-item.rose  { --kpi-bg: rgba(248,113,113,.14); }

.emp-kpi-body { min-width: 0; }
.emp-kpi-label {
    font-size: .67rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--admin-muted);
}
.emp-kpi-value {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--admin-text);
    line-height: 1.25;
}

/* ---- Filter bar ---- */
.emp-filter-bar {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
    padding: .7rem 1rem !important;
    margin-bottom: 1rem;
}

.emp-filter-label {
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--admin-muted);
}

.emp-select {
    font-size: .78rem;
    padding: .4rem .65rem;
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    background: var(--admin-surface);
    color: var(--admin-text);
    outline: none;
    cursor: pointer;
}

.emp-select:focus {
    border-color: var(--admin-primary);
    box-shadow: var(--admin-ring);
}

/* ---- View toggle ---- */
.emp-view-toggle {
    display: flex;
    border: 1px solid var(--admin-border);
    border-radius: 7px;
    overflow: hidden;
}

.emp-view-btn {
    padding: .4rem .8rem;
    border: none;
    background: var(--admin-surface);
    cursor: pointer;
    font-size: .78rem;
    font-weight: 600;
    color: var(--admin-muted);
    border-right: 1px solid var(--admin-border);
    display: flex;
    align-items: center;
    gap: .35rem;
    transition: background .12s ease, color .12s ease;
}

.emp-view-btn:last-child {
    border-right: none;
}

.emp-view-btn:hover {
    background: var(--admin-surface-soft);
    color: var(--admin-text);
}

.emp-view-btn.active {
    background: var(--admin-primary);
    color: #ffffff;
}

/* ---- Table ---- */
.emp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
}

.emp-table thead th {
    font-size: .68rem;
    font-weight: 700;
    color: var(--admin-muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: .6rem .65rem;
    border-bottom: 1px solid var(--admin-border);
    text-align: left;
    white-space: nowrap;
}

.emp-row {
    border-bottom: 1px solid var(--admin-border);
    cursor: pointer;
    transition: background .12s ease;
}

.emp-row:hover {
    background: var(--admin-surface-soft);
}

.emp-row td {
    padding: .65rem .65rem;
    vertical-align: middle;
}

.emp-td-id {
    font-size: .74rem;
    color: var(--admin-muted);
    white-space: nowrap;
}

.emp-td-muted {
    color: var(--admin-muted);
    font-size: .8rem;
}

/* ---- Avatar ---- */
.emp-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .78rem;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
}

.emp-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.emp-avatar-lg {
    width: 46px;
    height: 46px;
    font-size: .88rem;
}

/* Department color variants */
.emp-avatar-it,
.emp-dept-it             { background: #eaf2ff; color: var(--admin-primary); }

.emp-avatar-finance,
.emp-dept-finance        { background: #e7f6f3; color: var(--admin-success); }

.emp-avatar-hr,
.emp-dept-hr             { background: #fbeaf0; color: #b8285f; }

.emp-avatar-operations,
.emp-dept-operations     { background: #fff4df; color: var(--admin-warning); }

.emp-avatar-sales,
.emp-dept-sales          { background: #f2ecff; color: #6d3fd6; }

.emp-avatar-marketing,
.emp-dept-marketing      { background: #e6f8fb; color: #0891b2; }

.emp-avatar-default,
.emp-dept-default        { background: var(--admin-surface-soft); color: var(--admin-muted); }

html[data-theme="dark"] .emp-avatar-it,        html[data-theme="dark"] .emp-dept-it        { background: rgba(96,165,250,.16); }
html[data-theme="dark"] .emp-avatar-finance,   html[data-theme="dark"] .emp-dept-finance   { background: rgba(45,212,191,.16); }
html[data-theme="dark"] .emp-avatar-hr,        html[data-theme="dark"] .emp-dept-hr        { background: rgba(244,63,148,.16); color: #f9a8d4; }
html[data-theme="dark"] .emp-avatar-operations,html[data-theme="dark"] .emp-dept-operations{ background: rgba(251,191,36,.16); }
html[data-theme="dark"] .emp-avatar-sales,     html[data-theme="dark"] .emp-dept-sales      { background: rgba(167,139,250,.18); color: #c4b5fd; }
html[data-theme="dark"] .emp-avatar-marketing, html[data-theme="dark"] .emp-dept-marketing  { background: rgba(34,211,238,.16); color: #67e8f9; }
html[data-theme="dark"] .emp-avatar-default,   html[data-theme="dark"] .emp-dept-default    { background: var(--admin-surface-soft); }

/* ---- Name cell ---- */
.emp-name-cell {
    display: flex;
    align-items: center;
    gap: .65rem;
}

.emp-name {
    font-weight: 600;
    font-size: .85rem;
    color: var(--admin-text);
}

.emp-pos {
    font-size: .72rem;
    color: var(--admin-muted);
}

/* ---- Badges ---- */
.emp-dept-badge {
    display: inline-block;
    font-size: .7rem;
    font-weight: 600;
    padding: .18rem .6rem;
    border-radius: 6px;
}

.emp-status-badge {
    display: inline-block;
    font-size: .7rem;
    font-weight: 600;
    padding: .18rem .6rem;
    border-radius: 6px;
}

.emp-status-active   { background: #e7f6f3; color: var(--admin-success); }
.emp-status-inactive { background: var(--admin-surface-soft); color: var(--admin-muted); border: 1px solid var(--admin-border); }

html[data-theme="dark"] .emp-status-active { background: rgba(45,212,191,.16); }

.emp-branch {
    font-size: .8rem;
    color: var(--admin-muted);
}

/* ---- Contract ---- */
.emp-contract {
    font-size: .74rem;
    color: var(--admin-muted);
    white-space: nowrap;
}

.emp-contract-warn {
    color: var(--admin-danger);
    font-weight: 600;
}

.emp-soon-label {
    display: inline-block;
    margin-left: .3rem;
    background: #ffecec;
    color: var(--admin-danger);
    font-size: .65rem;
    font-weight: 700;
    padding: .1rem .5rem;
    border-radius: 6px;
}
html[data-theme="dark"] .emp-soon-label { background: rgba(248,113,113,.16); }

/* ---- Row action buttons ---- */
.emp-actions-cell {
    text-align: right;
}

.emp-row-actions {
    display: flex;
    gap: .3rem;
    justify-content: flex-end;
    opacity: 0;
    transition: opacity .15s ease;
}

.emp-row:hover .emp-row-actions {
    opacity: 1;
}

.emp-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    color: var(--admin-muted);
    text-decoration: none;
    background: var(--admin-surface);
    font-size: .8rem;
    transition: background .12s ease, color .12s ease, border-color .12s ease;
}

.emp-action-btn:hover {
    background: var(--admin-surface-soft);
    border-color: var(--admin-primary);
    color: var(--admin-primary);
}

/* ---- Empty state ---- */
.emp-empty-state {
    text-align: center;
    padding: 2.75rem 1.25rem;
    color: var(--admin-muted);
    font-size: .85rem;
}

.emp-empty-state i {
    font-size: 1.8rem;
    display: block;
    margin-bottom: .5rem;
}

/* ---- Card view ---- */
.emp-dept-group {
    margin-bottom: 1.5rem;
}

.emp-dept-group-label {
    font-size: .74rem;
    font-weight: 700;
    color: var(--admin-primary);
    background: #eaf2ff;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .3rem .75rem;
    border-radius: 6px;
    margin-bottom: .65rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}
html[data-theme="dark"] .emp-dept-group-label { background: rgba(96,165,250,.16); }

.emp-dept-count {
    background: var(--admin-primary);
    color: #ffffff;
    font-size: .64rem;
    font-weight: 700;
    padding: .05rem .4rem;
    border-radius: 6px;
}

.emp-card {
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: .9rem;
    background: var(--admin-surface);
    cursor: pointer;
    box-shadow: var(--admin-shadow-sm);
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    height: 100%;
}

.emp-card:hover {
    border-color: var(--admin-primary);
    box-shadow: var(--admin-shadow);
    transform: translateY(-2px);
}

.emp-card-top {
    display: flex;
    align-items: flex-start;
    gap: .65rem;
    margin-bottom: .65rem;
}

.emp-card-name {
    font-weight: 700;
    font-size: .85rem;
    color: var(--admin-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.emp-card-pos {
    font-size: .72rem;
    color: var(--admin-muted);
    margin-top: .05rem;
}

.emp-card-id {
    font-size: .68rem;
    color: var(--admin-muted);
    margin-top: .1rem;
}

.emp-card-detail {
    font-size: .78rem;
    color: var(--admin-muted);
    margin-bottom: .3rem;
}

.emp-card-warn {
    font-size: .72rem;
    color: var(--admin-danger);
    font-weight: 600;
    margin-bottom: .3rem;
}

.emp-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: .65rem;
    padding-top: .65rem;
    border-top: 1px solid var(--admin-border);
}

.emp-card-actions {
    display: flex;
    gap: .3rem;
}

.min-width-0 { min-width: 0; }

/* ---- Print ---- */
@media print {
    .emp-top-right,
    .emp-filter-bar,
    .emp-row-actions,
    .emp-card-actions { display: none !important; }
}

/* ---- Responsive ---- */
@media (max-width: 900px) {
    .emp-kpi-ribbon { grid-template-columns: repeat(2, 1fr); }
    .emp-kpi-item:nth-child(2) { border-right: none; }
    .emp-kpi-item:nth-child(odd) { border-right: 1px solid var(--admin-border); }
    .emp-kpi-item { border-bottom: 1px solid var(--admin-border); }
    .emp-kpi-item:nth-last-child(-n+2) { border-bottom: none; }
}

@media (max-width: 768px) {
    .emp-search-input {
        width: 160px;
    }

    .emp-table thead th:nth-child(4),
    .emp-table tbody td:nth-child(4),
    .emp-table thead th:nth-child(5),
    .emp-table tbody td:nth-child(5) {
        display: none;
    }
}

@media (max-width: 540px) {
    .emp-kpi-ribbon { grid-template-columns: 1fr; }
    .emp-kpi-item { border-right: none !important; border-bottom: 1px solid var(--admin-border); }
    .emp-kpi-item:last-child { border-bottom: none; }
}

</style>


{{-- ===== SCRIPTS ===== --}}
<script>

function empFilter() {
    const search = document.getElementById('empSearch').value.toLowerCase().trim();
    const dept   = document.getElementById('empDeptFilter').value;
    const status = document.getElementById('empStatusFilter').value;

    // --- Table rows ---
    const rows = document.querySelectorAll('#empTable tbody .emp-row');
    let tableVisible = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchDept   = !dept   || row.dataset.dept   === dept;
        const matchStatus = !status || row.dataset.status === status;
        const show = matchSearch && matchDept && matchStatus;
        row.style.display = show ? '' : 'none';
        if (show) tableVisible++;
    });

    document.getElementById('empTableEmpty').style.display = tableVisible === 0 ? 'block' : 'none';

    // --- Card columns ---
    const cols = document.querySelectorAll('.emp-card-col');
    let cardVisible = 0;

    cols.forEach(col => {
        const matchSearch = !search || col.dataset.search.includes(search);
        const matchDept   = !dept   || col.dataset.dept   === dept;
        const matchStatus = !status || col.dataset.status === status;
        const show = matchSearch && matchDept && matchStatus;
        col.style.display = show ? '' : 'none';
        if (show) cardVisible++;
    });

    // Hide dept group headers when all their cards are filtered out
    document.querySelectorAll('.emp-dept-group').forEach(group => {
        const visibleInGroup = group.querySelectorAll('.emp-card-col:not([style*="display: none"])').length;
        group.style.display = visibleInGroup === 0 ? 'none' : '';
    });

    document.getElementById('empCardEmpty').style.display = cardVisible === 0 ? 'block' : 'none';
}

function empShowView(view) {
    const tableView = document.getElementById('empTableView');
    const cardView  = document.getElementById('empCardView');
    const btnTable  = document.getElementById('btnTable');
    const btnCard   = document.getElementById('btnCard');

    if (view === 'table') {
        tableView.style.display = 'block';
        cardView.style.display  = 'none';
        btnTable.classList.add('active');
        btnCard.classList.remove('active');
    } else {
        tableView.style.display = 'none';
        cardView.style.display  = 'block';
        btnTable.classList.remove('active');
        btnCard.classList.add('active');
    }
}

// Persist view preference in sessionStorage
(function () {
    const saved = sessionStorage.getItem('empView');
    if (saved === 'card') empShowView('card');
})();

document.getElementById('btnTable').addEventListener('click', () => sessionStorage.setItem('empView', 'table'));
document.getElementById('btnCard').addEventListener('click',  () => sessionStorage.setItem('empView', 'card'));

</script>

@endsection