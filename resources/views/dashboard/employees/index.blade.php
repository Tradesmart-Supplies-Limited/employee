@extends('layouts.app')

@section('content')

@php
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->only(['q', 'status', 'department', 'branch', 'nature', 'attention', 'sort', 'dir', 'per_page']), $extra), fn ($v) => $v !== null && $v !== '');
    $sortLink = function ($col, $label) {
        $active = request('sort', 'name') === $col;
        $dir = $active && request('dir', 'asc') === 'asc' ? 'desc' : 'asc';
        $icon = $active ? (request('dir', 'asc') === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : 'bi-chevron-expand';
        return '<a class="el-sort" href="' . e(route('employees.index', array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => $col, 'dir' => $dir]))) . '">' . e($label) . ' <i class="bi ' . $icon . '"></i></a>';
    };
    $badge = ['expired' => ['#fee2e2', '#991b1b'], 'critical' => ['#ffedd5', '#9a3412'], 'soon' => ['#fef3c7', '#92400e'], 'ok' => ['#f1f5f9', '#475569']];
@endphp

<style>
.el-head { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: .8rem; margin-bottom: 1rem; }
.el-head h1 { font-size: 1.4rem; font-weight: 800; margin: 0; }
.el-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .6rem; margin-bottom: 1rem; }
.el-kpi { display: block; text-decoration: none; color: inherit; background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 12px; padding: .7rem .9rem; position: relative; overflow: hidden; }
.el-kpi::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--c); }
.el-kpi .v { font-size: 1.45rem; font-weight: 800; line-height: 1.15; }
.el-kpi .l { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--admin-muted, #64748b); }
.el-kpi.active, a.el-kpi:hover { border-color: var(--c); }
.el-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; }
.el-filters { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; padding: .8rem 1rem; border-bottom: 1px solid var(--admin-border, #e2e8f0); }
.el-filters .form-select, .el-filters .form-control { width: auto; font-size: .82rem; }
.el-search { position: relative; flex: 1 1 240px; max-width: 340px; }
.el-search i { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: var(--admin-muted, #94a3b8); }
.el-search input { padding-left: 2rem; width: 100% !important; }
.el-table { font-size: .84rem; margin: 0; }
.el-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; color: var(--admin-muted, #64748b); }
.el-sort { color: inherit; text-decoration: none; } .el-sort i { font-size: .62rem; opacity: .6; }
.el-person { display: flex; align-items: center; gap: .7rem; min-width: 210px; }
.el-av { width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; overflow: hidden; background: linear-gradient(135deg, var(--brand, #185FA5), #4f8cff); color: #fff; font-weight: 700; font-size: .8rem; display: flex; align-items: center; justify-content: center; }
.el-av img { width: 100%; height: 100%; object-fit: cover; }
.el-name { font-weight: 700; color: var(--admin-text, #0f172a); text-decoration: none; }
.el-sub { font-size: .72rem; color: var(--admin-muted, #64748b); }
.el-chip { font-size: .68rem; font-weight: 700; padding: .12rem .5rem; border-radius: 999px; white-space: nowrap; display: inline-block; margin: .05rem 0; }
.el-status-Active { background: #dcfce7; color: #166534; } .el-status-Suspended { background: #fef3c7; color: #92400e; } .el-status-Exited { background: #f1f5f9; color: #475569; }
.el-row { cursor: pointer; }
.el-empty { padding: 3rem 1rem; text-align: center; color: var(--admin-muted, #64748b); }
</style>

<div class="el-head">
    <div>
        <div class="text-uppercase fw-bold small" style="color:var(--brand-text, #185FA5); letter-spacing:.08em">Workforce</div>
        <h1>Employees</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('settings.contract-reminders.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bell"></i> Contract reminders</a>
        <a href="{{ route('employees.export', $qs()) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> Export</a>
        <a href="{{ route('employees.import') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-cloud-upload"></i> Bulk import</a>
        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Add employee</a>
    </div>
</div>

<div class="el-kpis">
    <a class="el-kpi {{ ! request()->hasAny(['status', 'attention']) ? 'active' : '' }}" style="--c:var(--brand-text, #185FA5)" href="{{ route('employees.index') }}"><div class="l">All employees</div><div class="v">{{ $kpis['total'] }}</div></a>
    <a class="el-kpi {{ request('status') === 'Active' ? 'active' : '' }}" style="--c:#16a34a" href="{{ route('employees.index', ['status' => 'Active']) }}"><div class="l">Active</div><div class="v">{{ $kpis['active'] }}</div></a>
    <a class="el-kpi {{ request('attention') === 'contracts' ? 'active' : '' }}" style="--c:#dc2626" href="{{ route('employees.index', ['attention' => 'contracts']) }}"><div class="l">Contracts ≤ 30 days</div><div class="v">{{ $kpis['contracts'] }}</div></a>
    <a class="el-kpi {{ request('attention') === 'probation' ? 'active' : '' }}" style="--c:#7c3aed" href="{{ route('employees.index', ['attention' => 'probation']) }}"><div class="l">Probation ending</div><div class="v">{{ $kpis['probation'] }}</div></a>
    <a class="el-kpi {{ request('attention') === 'no_portal' ? 'active' : '' }}" style="--c:#0ea5e9" href="{{ route('employees.index', ['attention' => 'no_portal']) }}"><div class="l">No portal login</div><div class="v">{{ $kpis['no_portal'] }}</div></a>
    <a class="el-kpi {{ request('status') === 'Exited' ? 'active' : '' }}" style="--c:#64748b" href="{{ route('employees.index', ['status' => 'Exited']) }}"><div class="l">Exited / suspended</div><div class="v">{{ $kpis['exited'] }} <span class="fs-6 text-muted">/ {{ $kpis['suspended'] }}</span></div></a>
</div>

<div class="el-card">
    <form method="GET" class="el-filters">
        <div class="el-search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, EIN, NRC, phone, email…"></div>
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Any status</option>
            @foreach(\App\Support\EmployeeFields::STATUSES as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
        </select>
        <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All departments</option>
            @foreach($departments as $d)<option @selected(request('department') === $d)>{{ $d }}</option>@endforeach
        </select>
        @if($branches->isNotEmpty())
            <select name="branch" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All branches</option>
                @foreach($branches as $b)<option @selected(request('branch') === $b)>{{ $b }}</option>@endforeach
            </select>
        @endif
        <select name="attention" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Show everyone</option>
            <option value="contracts" @selected(request('attention') === 'contracts')>Contract ending ≤ 30d / expired</option>
            <option value="probation" @selected(request('attention') === 'probation')>Probation ending</option>
            <option value="no_portal" @selected(request('attention') === 'no_portal')>No portal login</option>
            <option value="incomplete" @selected(request('attention') === 'incomplete')>Missing NRC / bank / NAPSA / DOB</option>
        </select>
        <button class="btn btn-sm btn-primary">Search</button>
        @if(request()->hasAny(['q', 'status', 'department', 'branch', 'attention']))
            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-link">Clear</a>
        @endif
        <span class="ms-auto small text-muted">{{ $employees->total() }} found</span>
    </form>

    @if($employees->isEmpty())
        <div class="el-empty">
            <i class="bi bi-people fs-2 d-block mb-2"></i>
            @if(request()->hasAny(['q', 'status', 'department', 'branch', 'attention']))
                No employees match these filters. <a href="{{ route('employees.index') }}">Clear filters</a>
            @else
                No employees yet. <a href="{{ route('employees.create') }}">Add the first one</a> or <a href="{{ route('employees.import') }}">import a spreadsheet</a>.
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle el-table">
                <thead class="table-light">
                    <tr>
                        <th>{!! $sortLink('name', 'Employee') !!}</th>
                        <th>{!! $sortLink('department', 'Department') !!}</th>
                        <th>Contact</th>
                        <th>Reports to</th>
                        <th>{!! $sortLink('contract_end', 'Contract / probation') !!}</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $e)
                        @php $dates = $monitor->statusFor($e); @endphp
                        <tr class="el-row" onclick="if(!event.target.closest('a,button,form')) window.location='{{ route('employees.show', $e) }}'">
                            <td>
                                <div class="el-person">
                                    <span class="el-av">@if($e->passport_photo)<img src="{{ asset('storage/' . $e->passport_photo) }}" alt="">@else{{ $e->initials }}@endif</span>
                                    <div>
                                        <a href="{{ route('employees.show', $e) }}" class="el-name">{{ $e->full_name }}</a>
                                        <div class="el-sub">{{ $e->employee_id }} · {{ $e->position ?? 'No position' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $e->department ?? '—' }}<div class="el-sub">{{ $e->branch }}</div></td>
                            <td class="text-nowrap">
                                @if($e->primary_phone)<div><i class="bi bi-telephone text-muted"></i> {{ $e->primary_phone }}</div>@endif
                                @if($e->company_email ?: $e->personal_email)<div class="el-sub text-truncate" style="max-width:190px">{{ $e->company_email ?: $e->personal_email }}</div>@endif
                                @if(! $e->primary_phone && ! ($e->company_email ?: $e->personal_email))<span class="text-muted">—</span>@endif
                            </td>
                            <td class="small">{{ $e->supervisor_name ?? '—' }}</td>
                            <td>
                                @forelse($dates as $type => $d)
                                    @php [$bg, $fg] = $badge[$d['state']]; @endphp
                                    <span class="el-chip" style="background:{{ $bg }};color:{{ $fg }}" title="{{ ucfirst($type) }} ends {{ $d['end']->format('d M Y') }}">
                                        {{ ucfirst($type) }}: {{ $d['days'] < 0 ? 'ended ' . abs($d['days']) . 'd ago' : ($d['state'] === 'ok' ? $d['end']->format('d M Y') : $d['days'] . 'd left') }}
                                    </span><br>
                                @empty
                                    <span class="el-sub">{{ \App\Support\EmployeeFields::NATURES[$e->employment_nature] ?? 'Permanent' }}</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="el-chip el-status-{{ $e->employment_status }}">{{ $e->employment_status }}</span>
                                <div class="el-sub mt-1" title="{{ $e->user ? 'Has a self-service login' : 'No self-service login' }}">
                                    <i class="bi {{ $e->user ? 'bi-person-check text-success' : 'bi-person-dash' }}"></i> {{ $e->user ? 'Portal' : 'No portal' }}
                                </div>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('employees.show', $e) }}" class="btn btn-sm btn-outline-secondary" title="View profile"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('employees.edit', $e) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <div class="dropdown d-inline">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" title="More"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end small">
                                        <li><a class="dropdown-item" href="{{ route('leave.manage.employee', $e) }}"><i class="bi bi-calendar-check"></i> Leave record</a></li>
                                        <li><a class="dropdown-item" href="{{ route('employees.show', $e) }}#documents"><i class="bi bi-folder2-open"></i> Documents</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('employees.destroy', $e) }}" onsubmit="return confirm('Permanently delete {{ addslashes($e->full_name) }}, their documents and portal login? To keep their history, set their status to Exited instead.')">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-2">
            <form method="GET" class="small text-muted d-flex align-items-center gap-1">
                @foreach($qs(['per_page' => null, 'page' => null]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                Show <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">@foreach([25, 50, 100] as $pp)<option @selected((int) request('per_page', 25) === $pp)>{{ $pp }}</option>@endforeach</select> per page
            </form>
            {{ $employees->links() }}
        </div>
    @endif
</div>

@endsection
