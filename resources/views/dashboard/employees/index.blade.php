@extends('layouts.app')

@section('content')

{{-- ===== TOP BAR ===== --}}
<div class="emp-top-bar">

    <h3 class="emp-title">Employees</h3>

    <div class="emp-top-right">

        <div class="emp-search-wrap">
            <i class="bi bi-search emp-search-icon"></i>
            <input type="text"
                   id="empSearch"
                   class="emp-search-input"
                   placeholder="Search name, ID, position…"
                   oninput="empFilter()">
        </div>

        {{-- IMPORT BUTTON --}}
        <button class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#importEmployeesModal">
            <i class="bi bi-upload"></i> Import
        </button>

        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Employee
        </a>

    </div>

</div>


{{-- ===== STATS BAR ===== --}}
<div class="emp-stats">

    <div class="emp-stat">
        <div class="emp-stat-label">Total Employees</div>
        <div class="emp-stat-value">{{ $employees->count() }}</div>
    </div>

    <div class="emp-stat">
        <div class="emp-stat-label">Active</div>
        <div class="emp-stat-value emp-stat-green">
            {{ $employees->where('employment_status', 'Active')->count() }}
        </div>
    </div>

    <div class="emp-stat">
        <div class="emp-stat-label">Departments</div>
        <div class="emp-stat-value">
            {{ $employees->pluck('department')->filter()->unique()->count() }}
        </div>
    </div>

    <div class="emp-stat">
        <div class="emp-stat-label">Contracts Expiring</div>
        <div class="emp-stat-value emp-stat-red">
            {{ $employees->filter(fn($e) => $e->contract_end && \Carbon\Carbon::parse($e->contract_end)->diffInDays(now()) <= 30 && \Carbon\Carbon::parse($e->contract_end)->isFuture())->count() }}
        </div>
    </div>

</div>


{{-- ===== FILTER BAR ===== --}}
<div class="panel emp-filter-bar">

    <span class="emp-filter-label">Filter:</span>

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

/* ---- Layout ---- */
.emp-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 10px;
}

.emp-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.emp-top-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ---- Search ---- */
.emp-search-wrap {
    position: relative;
}

.emp-search-icon {
    position: absolute;
    left: 9px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    font-size: 13px;
    pointer-events: none;
}

.emp-search-input {
    padding: 6px 10px 6px 30px;
    border: 1px solid #d0d0d0;
    border-radius: 7px;
    font-size: 13px;
    width: 220px;
    outline: none;
    transition: border-color .15s;
}

.emp-search-input:focus {
    border-color: #185FA5;
}

/* ---- Stats ---- */
.emp-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 12px;
}

.emp-stat {
    background: #2563EB;
    border-radius: 8px;
    padding: 12px 16px;
}

.emp-stat-label {
    font-size: 11px;
    color: #ffffff;
    margin-bottom: 4px;
}

.emp-stat-value {
    font-size: 22px;
    font-weight: 600;
    color: #ffffff;
}

.emp-stat-green { color: #ffffff; }
.emp-stat-red   { color: #ffffff; }

/* ---- Filter bar ---- */
.emp-filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 10px 16px !important;
    margin-bottom: 12px;
}

.emp-filter-label {
    font-size: 12px;
    color: #888;
}

.emp-select {
    font-size: 12px;
    padding: 5px 10px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    background: #fff;
    color: #333;
    outline: none;
    cursor: pointer;
}

.emp-select:focus {
    border-color: #185FA5;
}

/* ---- View toggle ---- */
.emp-view-toggle {
    display: flex;
    border: 1px solid #d0d0d0;
    border-radius: 7px;
    overflow: hidden;
}

.emp-view-btn {
    padding: 5px 12px;
    border: none;
    background: #fff;
    cursor: pointer;
    font-size: 12px;
    color: #666;
    border-right: 1px solid #d0d0d0;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background .12s;
}

.emp-view-btn:last-child {
    border-right: none;
}

.emp-view-btn.active {
    background: #185FA5;
    color: #fff;
}

/* ---- Table ---- */
.emp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.emp-table thead th {
    font-size: 11px;
    font-weight: 500;
    color: #888;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: 8px 10px;
    border-bottom: 1.5px solid #e8e8e8;
    text-align: left;
    white-space: nowrap;
}

.emp-row {
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background .12s;
}

.emp-row:hover {
    background: #f4f8fd;
}

.emp-row td {
    padding: 10px 10px;
    vertical-align: middle;
}

.emp-td-id {
    font-size: 11px;
    color: #aaa;
    white-space: nowrap;
}

.emp-td-muted {
    color: #888;
    font-size: 12px;
}

/* ---- Avatar ---- */
.emp-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 500;
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
    font-size: 14px;
}

/* Department color variants — add more as needed */
.emp-avatar-it,
.emp-dept-it             { background: #E6F1FB; color: #0C447C; }

.emp-avatar-finance,
.emp-dept-finance        { background: #E1F5EE; color: #085041; }

.emp-avatar-hr,
.emp-dept-hr             { background: #FBEAF0; color: #72243E; }

.emp-avatar-operations,
.emp-dept-operations     { background: #FAEEDA; color: #633806; }

.emp-avatar-default,
.emp-dept-default        { background: #f1f1f1; color: #555; }

/* ---- Name cell ---- */
.emp-name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.emp-name {
    font-weight: 500;
    font-size: 13px;
    color: #1a1a1a;
}

.emp-pos {
    font-size: 11px;
    color: #aaa;
}

/* ---- Badges ---- */
.emp-dept-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    padding: 2px 9px;
    border-radius: 10px;
}

.emp-status-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    padding: 2px 9px;
    border-radius: 10px;
}

.emp-status-active   { background: #EAF3DE; color: #3B6D11; }
.emp-status-inactive { background: #f1f1f1; color: #666; }

/* ---- Contract ---- */
.emp-contract {
    font-size: 11px;
    color: #888;
    white-space: nowrap;
}

.emp-contract-warn {
    color: #A32D2D;
    font-weight: 500;
}

.emp-soon-label {
    display: inline-block;
    margin-left: 4px;
    background: #FCEBEB;
    color: #A32D2D;
    font-size: 10px;
    padding: 1px 6px;
    border-radius: 8px;
}

/* ---- Row action buttons ---- */
.emp-actions-cell {
    text-align: right;
}

.emp-row-actions {
    display: flex;
    gap: 4px;
    justify-content: flex-end;
    opacity: 0;
    transition: opacity .15s;
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
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    color: #555;
    text-decoration: none;
    background: #fff;
    font-size: 13px;
    transition: background .12s;
}

.emp-action-btn:hover {
    background: #f0f0f0;
    color: #185FA5;
}

/* ---- Empty state ---- */
.emp-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #bbb;
    font-size: 13px;
}

.emp-empty-state i {
    font-size: 28px;
    display: block;
    margin-bottom: 8px;
}

/* ---- Card view ---- */
.emp-dept-group {
    margin-bottom: 24px;
}

.emp-dept-group-label {
    font-size: 12px;
    font-weight: 600;
    color: #185FA5;
    background: #E6F1FB;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 6px;
    margin-bottom: 10px;
}

.emp-dept-count {
    background: #185FA5;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 8px;
}

.emp-card {
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    padding: 14px;
    background: #fff;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    height: 100%;
}

.emp-card:hover {
    border-color: #185FA5;
    box-shadow: 0 0 0 3px rgba(24,95,165,.06);
}

.emp-card-top {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
}

.emp-card-name {
    font-weight: 600;
    font-size: 13px;
    color: #1a1a1a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.emp-card-pos {
    font-size: 11px;
    color: #aaa;
    margin-top: 1px;
}

.emp-card-id {
    font-size: 10.5px;
    color: #bbb;
    margin-top: 2px;
}

.emp-card-detail {
    font-size: 12px;
    color: #888;
    margin-bottom: 5px;
}

.emp-card-warn {
    font-size: 11px;
    color: #A32D2D;
    font-weight: 500;
    margin-bottom: 5px;
}

.emp-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
}

.emp-card-actions {
    display: flex;
    gap: 5px;
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
@media (max-width: 768px) {
    .emp-stats {
        grid-template-columns: repeat(2, 1fr);
    }

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