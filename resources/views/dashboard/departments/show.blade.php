@extends('layouts.app')
@section('title', $d->name . ' · Department')

@section('content')
@php
    use App\Services\AttendanceService as Att;
    use App\Services\DepartmentStats as DS;
    use App\Support\EmployeeFields as F;
    $att = $d->attendance;
    $real = $d->name !== DS::NONE;
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div style="min-width:0">
        <a href="{{ route('departments.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All departments</a>
        <h1 class="h4 fw-bold mb-0 mt-1">{{ $d->name }}</h1>
        <div class="small hr-muted">{!! $d->manager ? '<i class="bi bi-person-badge"></i> Most of the team reports to <strong>' . e($d->manager) . '</strong> · ' : '' !!}{{ $d->branches->implode(', ') ?: 'No branch set' }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <select class="form-select form-select-sm" style="width:auto" onchange="location.href=this.value" aria-label="Switch department">
            @foreach($others as $slug => $name)<option value="{{ route('departments.show', $slug) }}" @selected($slug === $d->slug)>{{ $name }}</option>@endforeach
        </select>
        @if($real)
            <a href="{{ route('attendance.index', ['department' => $d->name]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clock-history"></i> Attendance</a>
            <a href="{{ route('announcements.create', ['department' => $d->name]) }}" class="btn btn-sm btn-primary"><i class="bi bi-megaphone"></i> Announce to {{ \Illuminate\Support\Str::limit($d->name, 18) }}</a>
        @endif
    </div>
</div>

<div class="row g-2 g-md-3 mb-3">
    @foreach([
        ['Active staff', $d->active, 'var(--brand-text, #185FA5)', $d->inactive ? $d->inactive . ' former employee(s)' : ($d->new_30 ? $d->new_30 . ' joined in 30 days' : 'all current')],
        ['In today', $att['expected'] ? $att['attended'] . '/' . $att['expected'] : '—', '#16a34a', $att['late'] ? $att['late'] . ' late' : ($att['rate'] !== null ? $att['rate'] . '% attendance' : 'no one expected')],
        ['On leave today', $d->on_leave, '#7c3aed', $d->on_leave_list->pluck('first_name')->take(3)->implode(', ') ?: 'nobody'],
        ['Average age', $d->avg_age ?? '—', '#0891b2', 'years'],
        ['Average service', $d->avg_tenure !== null ? $d->avg_tenure : '—', '#d97706', 'years with the company'],
        ['Contracts ending', $d->ending_soon, $d->ending_soon ? '#dc2626' : '#64748b', 'in the next 30 days'],
    ] as [$l, $v, $c, $s])
        <div class="col-6 col-md-4 col-xl-2"><div class="hr-card hr-stat" style="--c:{{ $c }}"><div class="l">{{ $l }}</div><div class="v">{{ $v }}</div><div class="s text-truncate" title="{{ $s }}">{{ $s }}</div></div></div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="hr-card">
            <div class="hr-card-h">
                <h6><i class="bi bi-people"></i> Team ({{ $d->employees->count() }})</h6>
                <input type="search" class="form-control form-control-sm" style="max-width:220px" placeholder="Find someone…" data-table-search="teamTbl">
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="teamTbl" style="font-size:.86rem">
                    <thead class="table-light"><tr><th>Employee</th><th>Role</th><th>Today</th><th>Contact</th><th>Contract</th></tr></thead>
                    <tbody>
                        @foreach($d->employees as $e)
                            @php
                                $active = strtolower((string) $e->employment_status) === 'active';
                                $row = $d->att_rows[$e->id] ?? null;
                                $meta = $row ? Att::meta($row->status) : null;
                            @endphp
                            <tr style="{{ $active ? '' : 'opacity:.55' }}">
                                <td>
                                    <a href="{{ route('employees.show', $e) }}" class="d-flex align-items-center gap-2 text-decoration-none" style="color:inherit">
                                        <span class="hr-av">@if($e->passport_photo)<img src="{{ asset('storage/' . $e->passport_photo) }}" alt="">@else{{ $e->initials }}@endif</span>
                                        <span style="min-width:0"><span class="fw-semibold d-block text-truncate">{{ $e->full_name }}</span><small class="hr-muted">{{ $e->employee_id }}</small></span>
                                    </a>
                                </td>
                                <td>{{ $e->position ?? '—' }}<div class="small hr-muted">{{ $e->supervisor_name ? 'Reports to ' . $e->supervisor_name : '' }}</div></td>
                                <td>
                                    @if(! $active)<span class="hr-chip">{{ $e->employment_status }}</span>
                                    @elseif($meta)<span class="hr-chip" style="--bg:{{ $meta['bg'] }};--c:{{ $meta['color'] }}"><i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span>@if($row->record?->in_time)<div class="small hr-muted">in {{ $row->record->in_time }}</div>@endif
                                    @else<span class="hr-muted small">—</span>@endif
                                </td>
                                <td class="small">
                                    @if($e->primary_phone)<a href="tel:{{ $e->primary_phone }}" class="d-block text-nowrap"><i class="bi bi-telephone"></i> {{ $e->primary_phone }}</a>@endif
                                    @if($e->contact_email)<a href="mailto:{{ $e->contact_email }}" class="d-block text-truncate" style="max-width:180px"><i class="bi bi-envelope"></i> {{ $e->contact_email }}</a>@endif
                                </td>
                                <td class="small text-nowrap">
                                    {{ F::NATURES[strtoupper((string) $e->employment_nature)] ?? '—' }}
                                    @if($e->contract_end)<div class="hr-muted">ends {{ \Carbon\Carbon::parse($e->contract_end)->format('d M Y') }}</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="hr-card mb-3">
            <div class="hr-card-h"><h6><i class="bi bi-pie-chart"></i> Make-up</h6></div>
            <div class="hr-card-b">
                <div class="small fw-semibold mb-1">Gender</div>
                <div class="hr-bar mb-1">@if($d->active)<span style="width:{{ $d->male / $d->active * 100 }}%;background:#3b82f6"></span><span style="width:{{ $d->female / $d->active * 100 }}%;background:#ec4899"></span>@endif</div>
                <div class="small hr-muted mb-3">{{ $d->male }} male · {{ $d->female }} female{{ $d->active - $d->male - $d->female > 0 ? ' · ' . ($d->active - $d->male - $d->female) . ' not set' : '' }}</div>

                <div class="small fw-semibold mb-1">Employment type</div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach($d->natures as $n => $count)<span class="hr-chip">{{ F::NATURES[$n] ?? 'Not set' }}: {{ $count }}</span>@endforeach
                </div>

                <div class="small fw-semibold mb-1">Roles</div>
                @forelse($d->positions as $pos => $count)
                    <div class="d-flex justify-content-between small py-1 border-bottom"><span>{{ $pos }}</span><strong>{{ $count }}</strong></div>
                @empty
                    <div class="small hr-muted">No positions recorded.</div>
                @endforelse
            </div>
        </div>

        @if($d->birthdays->isNotEmpty())
            <div class="hr-card mb-3">
                <div class="hr-card-h"><h6><i class="bi bi-cake2"></i> Birthdays in {{ now()->format('F') }}</h6></div>
                @foreach($d->birthdays->sortBy(fn ($e) => \Carbon\Carbon::parse($e->date_of_birth ?: $e->dob)->day) as $e)
                    <div class="hr-list-item"><span class="hr-av">{{ $e->initials }}</span><span class="flex-grow-1">{{ $e->full_name }}</span><span class="small hr-muted">{{ \Carbon\Carbon::parse($e->date_of_birth ?: $e->dob)->format('d M') }}</span></div>
                @endforeach
            </div>
        @endif

        <div class="hr-card">
            <div class="hr-card-h"><h6><i class="bi bi-megaphone"></i> Announcements seen here</h6>@if($real)<a href="{{ route('announcements.create', ['department' => $d->name]) }}" class="small">New</a>@endif</div>
            @forelse($announcements as $a)
                @php [, $ci, $cc] = $a->category_meta; @endphp
                <a href="{{ route('announcements.show', $a) }}" class="an-item">
                    <span class="an-ic" style="--c:{{ $cc }}"><i class="bi {{ $ci }}"></i></span>
                    <div style="min-width:0" class="flex-grow-1"><div class="an-title text-truncate">{{ $a->title }}</div><div class="small hr-muted">{{ $a->audience_label === 'Everyone' ? 'Everyone' : 'This department' }} · {{ ($a->local('publish_at') ?? $a->created_at)->format('d M Y') }}</div></div>
                </a>
            @empty
                <div class="hr-empty"><i class="bi bi-megaphone"></i>Nothing posted for this department yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
