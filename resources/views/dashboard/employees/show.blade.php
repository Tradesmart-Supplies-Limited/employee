@extends('layouts.app')

@section('content')

@php
    use App\Support\EmployeeFields as F;
    $fields = F::all();
    $val = function ($name) use ($employee) {
        $v = F::display($employee, $name);
        if ($v === null) return '<span class="ep-empty">—</span>';
        if (! empty(F::get($name)['mask'])) {
            return '<span class="ep-mask" data-full="' . e($v) . '">' . e(F::mask($v)) . '</span> <button type="button" class="ep-reveal" title="Show"><i class="bi bi-eye"></i></button>';
        }
        return e($v);
    };
    $section = fn ($step) => collect($fields)->filter(fn ($f) => $f['step'] === $step)->keys();
    $stateColor = ['expired' => '#dc2626', 'critical' => '#ea580c', 'soon' => '#d97706', 'ok' => '#16a34a'];
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $tenure = $employee->contract_start ? $employee->contract_start->diff(now()) : null;
@endphp

<style>
.ep-hero { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 16px; padding: 1.2rem 1.4rem; display: flex; gap: 1.2rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; }
.ep-photo { width: 92px; height: 92px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: linear-gradient(135deg, #185FA5, #4f8cff); color: #fff; font-size: 2rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.ep-photo img { width: 100%; height: 100%; object-fit: cover; }
.ep-hero h1 { font-size: 1.45rem; font-weight: 800; margin: 0; }
.ep-meta { font-size: .85rem; color: var(--admin-muted, #64748b); }
.ep-chip { font-size: .72rem; font-weight: 700; padding: .18rem .6rem; border-radius: 999px; display: inline-flex; gap: .3rem; align-items: center; margin: .15rem .2rem .15rem 0; }
.ep-tabs { display: flex; gap: .2rem; border-bottom: 1px solid var(--admin-border, #e2e8f0); margin-bottom: 1rem; overflow-x: auto; }
.ep-tab { border: 0; background: none; padding: .65rem .95rem; font-size: .88rem; font-weight: 600; color: var(--admin-muted, #64748b); border-bottom: 2px solid transparent; white-space: nowrap; }
.ep-tab.active { color: #185FA5; border-bottom-color: #185FA5; }
.ep-tab .count { font-size: .7rem; background: var(--admin-surface-soft, #f1f5f9); padding: .05rem .4rem; border-radius: 999px; margin-left: .2rem; }
.ep-pane { display: none; } .ep-pane.active { display: block; }
.ep-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1rem 1.2rem; margin-bottom: 1rem; }
.ep-card h2 { font-size: .9rem; font-weight: 800; margin: 0 0 .8rem; display: flex; align-items: center; gap: .45rem; }
.ep-card h2 a { margin-left: auto; font-size: .78rem; font-weight: 600; }
.ep-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .75rem 1.2rem; }
.ep-grid small { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-muted, #64748b); font-weight: 700; }
.ep-grid div > span, .ep-grid div { font-size: .88rem; }
.ep-empty { color: var(--admin-muted, #94a3b8); }
.ep-reveal { border: 0; background: none; color: var(--admin-muted, #94a3b8); padding: 0 .2rem; }
.ep-timeline { position: relative; padding-left: 1.4rem; }
.ep-timeline::before { content: ""; position: absolute; left: .4rem; top: .3rem; bottom: .3rem; width: 2px; background: var(--admin-border, #e2e8f0); }
.ep-tl { position: relative; padding: .3rem 0 .7rem; font-size: .86rem; }
.ep-tl::before { content: ""; position: absolute; left: -1.25rem; top: .55rem; width: 12px; height: 12px; border-radius: 50%; background: var(--c, #94a3b8); border: 2px solid var(--admin-surface, #fff); box-shadow: 0 0 0 1px var(--c, #94a3b8); }
.ep-tl small { color: var(--admin-muted, #64748b); display: block; }
.ep-doc { display: flex; align-items: center; gap: .8rem; padding: .6rem 0; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.ep-doc:last-child { border-bottom: 0; }
.ep-doc .ic { width: 38px; height: 38px; border-radius: 10px; background: var(--admin-surface-soft, #f1f5f9); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #185FA5; flex-shrink: 0; }
.ep-progress { height: 8px; border-radius: 4px; background: var(--admin-surface-soft, #f1f5f9); overflow: hidden; }
.ep-progress span { display: block; height: 100%; background: #16a34a; }
@media print { .ep-tabs, .ep-actions, .ep-reveal, form, .admin-sidebar { display: none !important; } .ep-pane { display: block !important; } }
</style>

{{-- ===== HERO ===== --}}
<div class="ep-hero">
    <div class="ep-photo">@if($employee->passport_photo)<img src="{{ asset('storage/' . $employee->passport_photo) }}" alt="">@else{{ $employee->initials }}@endif</div>
    <div class="flex-grow-1">
        <a href="{{ route('employees.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Employees</a>
        <h1>{{ $employee->full_name }}</h1>
        <div class="ep-meta">
            {{ $employee->position ?? 'No position' }}{{ $employee->department ? ' · ' . $employee->department : '' }}{{ $employee->branch ? ' · ' . $employee->branch : '' }}
            · <span class="fw-semibold">{{ $employee->employee_id }}</span>
        </div>
        <div class="mt-1">
            @php $sc = ['Active' => ['#dcfce7', '#166534'], 'Suspended' => ['#fef3c7', '#92400e'], 'Exited' => ['#f1f5f9', '#475569']][$employee->employment_status] ?? ['#f1f5f9', '#475569']; @endphp
            <span class="ep-chip" style="background:{{ $sc[0] }};color:{{ $sc[1] }}">{{ $employee->employment_status }}</span>
            <span class="ep-chip" style="background:#f1f5f9;color:#334155">{{ F::NATURES[$employee->employment_nature] ?? 'Permanent' }}</span>
            @foreach($endDates as $type => $d)
                <span class="ep-chip" style="background:{{ $stateColor[$d['state']] }}1a;color:{{ $stateColor[$d['state']] }}">
                    <i class="bi bi-calendar-event"></i> {{ ucfirst($type) }} {{ $d['days'] < 0 ? 'ended ' . abs($d['days']) . 'd ago' : 'ends ' . $d['end']->format('d M Y') . ' (' . $d['days'] . 'd)' }}
                </span>
            @endforeach
            @if($employee->supervisor_name)<span class="ep-chip" style="background:#eef2ff;color:#3730a3"><i class="bi bi-person-up"></i> Reports to {{ $employee->supervisor_name }}</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap ep-actions">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('attendance.employee', $employee) }}"><i class="bi bi-clock-history"></i> Attendance</a>
        <a class="btn btn-primary btn-sm" href="{{ route('employees.edit', $employee) }}"><i class="bi bi-pencil-square"></i> Edit</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('leave.manage.employee', $employee) }}"><i class="bi bi-calendar-check"></i> Leave</a>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i></button>
    </div>
</div>

@if($pendingChanges->isNotEmpty())
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-shield-exclamation fs-5"></i>
        <div class="flex-grow-1">{{ $employee->first_name }} has requested changes to {{ $pendingChanges->flatMap(fn ($r) => array_keys($r->changes))->map(fn ($f) => F::label($f))->unique()->implode(', ') }}.</div>
        <a href="{{ route('profile-changes.index') }}" class="btn btn-sm btn-warning">Review</a>
    </div>
@endif

@include('dashboard.employees.partials.portal-account')

{{-- ===== TABS ===== --}}
<div class="ep-tabs" role="tablist">
    @foreach(['overview' => 'Overview', 'personal' => 'Personal & contact', 'job' => 'Job & pay', 'documents' => 'Documents', 'leave' => 'Leave'] as $k => $label)
        <button class="ep-tab" data-tab="{{ $k }}" role="tab">{{ $label }}@if($k === 'documents')<span class="count">{{ $employee->documents->count() }}</span>@endif</button>
    @endforeach
</div>

{{-- ===== OVERVIEW ===== --}}
<div class="ep-pane" data-pane="overview">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="ep-card">
                <h2><i class="bi bi-person-badge"></i> Key facts</h2>
                <div class="ep-grid">
                    <div><small>Phone</small>{!! $val('primary_phone') !!}</div>
                    <div><small>Email</small>{!! $employee->company_email ? e($employee->company_email) : $val('personal_email') !!}</div>
                    <div><small>NRC</small>{!! $val('nrc_no') !!}</div>
                    <div><small>Date of birth</small>{!! $val('date_of_birth') !!}{{ $employee->age ? ' (' . $employee->age . ')' : '' }}</div>
                    <div><small>Started</small>{!! $val('contract_start') !!}@if($tenure) <span class="ep-empty">· {{ $tenure->y ? $tenure->y . 'y ' : '' }}{{ $tenure->m }}m</span>@endif</div>
                    <div><small>Leave balance</small><strong>{{ $n($employee->leave_balance) }}</strong> <span class="ep-empty">of {{ $n($employee->annual_leave_entitlement) }} days</span></div>
                    <div><small>Emergency contact</small>{{ $employee->emergency_name ?? '—' }}{!! $employee->emergency_phone ? '<br><span class="ep-empty">' . e($employee->emergency_phone) . '</span>' : '' !!}</div>
                    <div><small>Direct reports</small>{{ $employee->directReports->isNotEmpty() ? $employee->directReports->map->full_name->implode(', ') : '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="ep-card">
                <h2><i class="bi bi-clipboard-check"></i> Record completeness <a href="{{ route('employees.edit', $employee) }}">Complete</a></h2>
                <div class="d-flex align-items-center gap-2 mb-2"><div class="ep-progress flex-grow-1"><span style="width: {{ $completeness['percent'] }}%; {{ $completeness['percent'] < 60 ? 'background:#f59e0b' : '' }}"></span></div><strong>{{ $completeness['percent'] }}%</strong></div>
                @if($completeness['missing'])
                    <div class="small text-muted">Missing: {{ implode(', ', $completeness['missing']) }}</div>
                @else
                    <div class="small text-success"><i class="bi bi-check-circle"></i> All key details are filled in.</div>
                @endif
            </div>
            <div class="ep-card">
                <h2><i class="bi bi-signpost-split"></i> Employment timeline</h2>
                <div class="ep-timeline">
                    @php
                        $events = collect([
                            ['Started', $employee->contract_start, '#185FA5'],
                            ['Probation ends', $employee->probation_end, '#7c3aed'],
                            ['Contract ends', $employee->contract_end, '#dc2626'],
                        ])->filter(fn ($e) => $e[1])->sortBy(fn ($e) => $e[1]);
                    @endphp
                    @forelse($events as [$label, $date, $c])
                        <div class="ep-tl" style="--c: {{ $date->isPast() ? '#94a3b8' : $c }}">
                            <strong>{{ $label }}</strong><small>{{ $date->format('D d M Y') }} · {{ $date->isPast() ? $date->diffForHumans() : 'in ' . now()->startOfDay()->diffInDays($date) . ' days' }}</small>
                        </div>
                    @empty
                        <div class="small text-muted">No dates recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== PERSONAL & CONTACT ===== --}}
<div class="ep-pane" data-pane="personal">
    @foreach(['personal' => ['Personal', 'bi-person'], 'contact' => ['Contact & family', 'bi-telephone']] as $step => [$title, $icon])
        <div class="ep-card">
            <h2><i class="bi {{ $icon }}"></i> {{ $title }} <a href="{{ route('employees.edit', $employee) }}#{{ $step }}">Edit</a></h2>
            <div class="ep-grid">
                @foreach($section($step) as $f)<div><small>{{ F::label($f) }}</small>{!! $val($f) !!}</div>@endforeach
            </div>
        </div>
    @endforeach
</div>

{{-- ===== JOB & PAY ===== --}}
<div class="ep-pane" data-pane="job">
    <div class="ep-card">
        <h2><i class="bi bi-briefcase"></i> Job &amp; contract <a href="{{ route('employees.edit', $employee) }}#job">Edit</a></h2>
        <div class="ep-grid">
            <div><small>Employee number</small>{{ $employee->employee_id }}</div>
            <div><small>Reports to</small>{{ $employee->supervisor_name ?? '—' }}</div>
            @foreach($section('job') as $f)<div><small>{{ F::label($f) }}</small>{!! $val($f) !!}</div>@endforeach
        </div>
    </div>
    <div class="ep-card">
        <h2><i class="bi bi-bank"></i> Pay &amp; statutory <a href="{{ route('employees.edit', $employee) }}#pay">Edit</a></h2>
        <div class="ep-grid">
            @foreach($section('pay') as $f)<div><small>{{ F::label($f) }}</small>{!! $val($f) !!}</div>@endforeach
        </div>
    </div>
</div>

{{-- ===== DOCUMENTS ===== --}}
<div class="ep-pane" data-pane="documents" id="documents">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="ep-card">
                <h2><i class="bi bi-folder2-open"></i> Documents</h2>
                @forelse($employee->documents->sortByDesc(fn ($d) => $d->uploaded_at) as $doc)
                    <div class="ep-doc">
                        <span class="ic"><i class="bi {{ in_array($doc->ext, ['jpg', 'jpeg', 'png', 'webp']) ? 'bi-file-image' : ($doc->ext === 'pdf' ? 'bi-file-pdf' : 'bi-file-earmark-text') }}"></i></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate">{{ $doc->name }}</div>
                            <div class="small text-muted">
                                {{ $doc->category }} · {{ $doc->uploaded_by === 'employee' ? 'uploaded by employee' : 'uploaded by HR' }}
                                {{ $doc->uploaded_at ? '· ' . $doc->uploaded_at->format('d M Y') : '' }}
                                {{ $doc->size ? '· ' . number_format($doc->size / 1024) . ' KB' : '' }}
                            </div>
                        </div>
                        <a href="{{ asset('storage/' . $doc->path) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Open"><i class="bi bi-box-arrow-up-right"></i></a>
                        <a href="{{ route('employees.documents.download', [$employee, $doc->index]) }}" class="btn btn-sm btn-outline-secondary" title="Download"><i class="bi bi-download"></i></a>
                        <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $doc->index]) }}" onsubmit="return confirm('Delete {{ addslashes($doc->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="text-muted small py-3 text-center"><i class="bi bi-folder fs-3 d-block"></i> No documents yet.</div>
                @endforelse
            </div>
        </div>
        <div class="col-lg-4">
            <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="ep-card">
                @csrf
                <h2><i class="bi bi-cloud-upload"></i> Upload</h2>
                <input type="file" name="files[]" multiple class="form-control form-control-sm mb-2" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                <select name="category" class="form-select form-select-sm mb-2">@foreach(F::DOC_CATEGORIES as $c)<option>{{ $c }}</option>@endforeach</select>
                <input type="text" name="name" class="form-control form-control-sm mb-2" placeholder="Name (optional), e.g. Signed contract 2026">
                <button class="btn btn-primary btn-sm w-100">Upload</button>
                <div class="form-text">Up to 10 files, 10 MB each. The employee can see these in the portal.</div>
            </form>
        </div>
    </div>
</div>

{{-- ===== LEAVE ===== --}}
<div class="ep-pane" data-pane="leave">
    <div class="ep-card">
        <h2><i class="bi bi-calendar-check"></i> Leave <a href="{{ route('leave.manage.employee', $employee) }}">Full leave record &amp; balance history</a></h2>
        <div class="ep-grid mb-3">
            <div><small>Balance</small><strong class="fs-5">{{ $n($employee->leave_balance) }}</strong> days</div>
            <div><small>Entitlement</small>{{ $n($employee->annual_leave_entitlement) }} days / year</div>
            <div><small>Taken this year</small>{{ $n($employee->leaveTakenInYear()) }} days</div>
            <div><small>Pending</small>{{ $n($employee->leavePendingDays()) }} days</div>
        </div>
        @forelse($recentLeave as $l)
            <div class="d-flex justify-content-between align-items-center border-top py-2 small">
                <span>{{ $l->leave_type }} · {{ $l->date_from->format('d M') }} – {{ $l->date_to->format('d M Y') }} ({{ $l->total_days }}d)</span>
                <a href="{{ route('leave.show', $l) }}" class="text-decoration-none">@include('leave.partials.mini-tracker', ['leave' => $l])</a>
            </div>
        @empty
            <div class="small text-muted">No leave requests yet.</div>
        @endforelse
    </div>
</div>

<div class="mt-2 text-end">
    <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="d-inline"
          onsubmit="return confirm('Permanently delete {{ addslashes($employee->full_name) }}? To keep their history, set their status to Exited instead.')">
        @csrf @method('DELETE')
        <button class="btn btn-link text-danger btn-sm"><i class="bi bi-trash"></i> Delete employee</button>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const tabs = [...document.querySelectorAll('.ep-tab')], panes = [...document.querySelectorAll('.ep-pane')];
    function open(name) {
        if (!panes.some(p => p.dataset.pane === name)) name = 'overview';
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
        panes.forEach(p => p.classList.toggle('active', p.dataset.pane === name));
    }
    tabs.forEach(t => t.addEventListener('click', () => { open(t.dataset.tab); history.replaceState(null, '', '#' + t.dataset.tab); }));
    open(location.hash.replace('#', '') || 'overview');
    document.querySelectorAll('.ep-reveal').forEach(b => b.addEventListener('click', () => {
        const m = b.previousElementSibling; const full = m.dataset.full;
        if (m.textContent === full) { m.textContent = full.replace(/.(?=.{4})/g, '•'); } else { m.textContent = full; }
    }));
})();
</script>
@endpush

@endsection
