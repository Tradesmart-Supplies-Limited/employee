@extends('layouts.app')
@section('title', 'Departments')

@section('content')
@php
    use App\Services\DepartmentStats as DS;
    $t = $totals;
    $max = max(1, $departments->max('active'));
    $palette = ['#185FA5', '#16a34a', '#d97706', '#7c3aed', '#0891b2', '#db2777', '#dc2626', '#65a30d', '#0f766e', '#9333ea'];
@endphp

<style>
.dp-card { display: flex; flex-direction: column; height: 100%; color: inherit; text-decoration: none; transition: box-shadow .15s, transform .15s; }
.dp-card:hover { box-shadow: var(--admin-shadow, 0 12px 30px rgba(15,23,42,.1)); transform: translateY(-2px); }
.dp-top { display: flex; gap: .8rem; align-items: center; padding: 1rem 1.1rem .6rem; }
.dp-ic { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; background: var(--c); flex-shrink: 0; }
.dp-name { font-weight: 800; font-size: 1.02rem; line-height: 1.2; }
.dp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; padding: .3rem 1.1rem .8rem; }
.dp-grid div { background: var(--admin-surface-soft, #f8fafc); border-radius: 10px; padding: .45rem .5rem; text-align: center; }
.dp-grid b { display: block; font-size: 1.1rem; line-height: 1.2; }
.dp-grid small { font-size: .66rem; color: var(--admin-muted, #64748b); text-transform: uppercase; font-weight: 700; letter-spacing: .03em; }
.dp-foot { margin-top: auto; padding: .7rem 1.1rem; border-top: 1px solid var(--admin-border, #eef2f7); display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; font-size: .78rem; }
.dp-faces { display: flex; }
.dp-faces .hr-av { width: 28px; height: 28px; font-size: .62rem; border: 2px solid var(--admin-surface, #fff); margin-left: -8px; }
.dp-faces .hr-av:first-child { margin-left: 0; }
.dp-hbar { display: flex; align-items: center; gap: .6rem; padding: .35rem 0; font-size: .84rem; }
.dp-hbar .n { width: 34%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
.dp-hbar .b { flex: 1; height: 10px; border-radius: 5px; background: var(--admin-surface-soft, #f1f5f9); overflow: hidden; }
.dp-hbar .b span { display: block; height: 100%; border-radius: 5px; }
</style>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <div class="hr-kicker">Organisation</div>
        <h1 class="h4 fw-bold mb-0">Departments</h1>
        <div class="small hr-muted">Live from employee records. Change an employee's department on their profile and it shows here.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-megaphone"></i> All announcements</a>
        <a href="{{ route('announcements.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New announcement</a>
    </div>
</div>

<div class="row g-2 g-md-3 mb-3">
    @foreach([
        ['Departments', $t['departments'], '#185FA5', $t['unassigned'] ? $t['unassigned'] . ' staff not assigned' : 'everyone assigned'],
        ['Active staff', $t['active'], '#16a34a', 'avg ' . $t['avg_team'] . ' per department'],
        ['Largest', $t['largest']?->name ?? '—', '#7c3aed', $t['largest'] ? $t['largest']->active . ' people' : ''],
        ['In today', $t['attended'] . '/' . $t['expected'], '#0891b2', $t['on_leave'] . ' on leave'],
    ] as [$l, $v, $c, $s])
        <div class="col-6 col-xl-3"><div class="hr-card hr-stat" style="--c:{{ $c }}"><div class="l">{{ $l }}</div><div class="v text-truncate">{{ $v }}</div><div class="s text-truncate">{{ $s }}</div></div></div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="row g-3">
            @forelse($departments as $i => $d)
                @php $c = $d->name === DS::NONE ? '#94a3b8' : $palette[$i % count($palette)]; $att = $d->attendance; @endphp
                <div class="col-md-6">
                    <a href="{{ route('departments.show', $d->slug) }}" class="hr-card dp-card" style="--c:{{ $c }}">
                        <div class="dp-top">
                            <span class="dp-ic"><i class="bi {{ $d->name === DS::NONE ? 'bi-question-lg' : 'bi-diagram-3' }}"></i></span>
                            <div style="min-width:0" class="flex-grow-1">
                                <div class="dp-name text-truncate">{{ $d->name }}</div>
                                <div class="small hr-muted text-truncate">{!! $d->manager ? '<i class="bi bi-person-badge"></i> ' . e($d->manager) : 'No supervisor set' !!}</div>
                            </div>
                            <i class="bi bi-chevron-right hr-muted"></i>
                        </div>
                        <div class="dp-grid">
                            <div><b>{{ $d->active }}</b><small>Staff</small></div>
                            <div><b style="color:{{ $att['rate'] === null ? 'inherit' : ($att['rate'] >= 90 ? '#16a34a' : ($att['rate'] >= 75 ? '#d97706' : '#dc2626')) }}">{{ $att['expected'] ? $att['attended'] . '/' . $att['expected'] : '—' }}</b><small>In today</small></div>
                            <div><b>{{ $d->on_leave }}</b><small>On leave</small></div>
                        </div>
                        <div class="px-3 pb-2">
                            <div class="hr-bar" title="{{ $d->male }} male · {{ $d->female }} female">
                                @if($d->active)<span style="width:{{ $d->male / $d->active * 100 }}%;background:#3b82f6"></span><span style="width:{{ $d->female / $d->active * 100 }}%;background:#ec4899"></span>@endif
                            </div>
                            <div class="d-flex justify-content-between small hr-muted mt-1"><span>{{ $d->male }} M · {{ $d->female }} F</span><span>{{ $d->positions->count() }} role(s){{ $d->avg_tenure !== null ? ' · ' . $d->avg_tenure . ' yrs avg service' : '' }}</span></div>
                        </div>
                        <div class="dp-foot">
                            <div class="dp-faces">
                                @foreach($d->active_list->take(5) as $e)
                                    <span class="hr-av" title="{{ $e->full_name }}">@if($e->passport_photo)<img src="{{ asset('storage/' . $e->passport_photo) }}" alt="">@else{{ $e->initials }}@endif</span>
                                @endforeach
                                @if($d->active > 5)<span class="hr-av" style="background:#64748b">+{{ $d->active - 5 }}</span>@endif
                            </div>
                            <div class="ms-auto d-flex gap-1 flex-wrap justify-content-end">
                                @if($d->ending_soon)<span class="hr-chip" style="--bg:#fee2e2;--c:#b91c1c"><i class="bi bi-hourglass-split"></i> {{ $d->ending_soon }} contract(s) ending</span>@endif
                                @if($d->new_30)<span class="hr-chip" style="--bg:#dcfce7;--c:#166534"><i class="bi bi-stars"></i> {{ $d->new_30 }} new</span>@endif
                                @if($d->inactive)<span class="hr-chip">{{ $d->inactive }} former</span>@endif
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12"><div class="hr-card hr-empty"><i class="bi bi-diagram-3"></i>No employees yet. Departments appear as soon as employees are added with a department.</div></div>
            @endforelse
        </div>
    </div>

    <div class="col-xl-4">
        <div class="hr-card mb-3">
            <div class="hr-card-h"><h6><i class="bi bi-bar-chart"></i> Headcount</h6><span class="small hr-muted">{{ $t['active'] }} active</span></div>
            <div class="hr-card-b">
                @foreach($departments as $i => $d)
                    <div class="dp-hbar">
                        <div class="n" title="{{ $d->name }}">{{ $d->name }}</div>
                        <div class="b"><span style="width:{{ $d->active / $max * 100 }}%;background:{{ $d->name === DS::NONE ? '#94a3b8' : $palette[$i % count($palette)] }}"></span></div>
                        <div class="fw-bold" style="width:28px;text-align:right">{{ $d->active }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="hr-card">
            <div class="hr-card-h"><h6><i class="bi bi-megaphone"></i> Announcements</h6><a href="{{ route('announcements.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New</a></div>
            @forelse($announcements as $a)
                @php [$cl, $ci, $cc] = $a->category_meta; @endphp
                <a href="{{ route('announcements.show', $a) }}" class="an-item">
                    <span class="an-ic" style="--c:{{ $cc }}"><i class="bi {{ $ci }}"></i></span>
                    <div style="min-width:0" class="flex-grow-1">
                        <div class="an-title text-truncate">@if($a->pinned)<i class="bi bi-pin-angle-fill text-primary"></i> @endif{{ $a->title }}</div>
                        <div class="small hr-muted text-truncate">{{ $a->audience_label }} · {{ $a->local('publish_at')?->format('d M') ?? $a->created_at->format('d M') }}{{ $a->state !== 'live' ? ' · ' . $a->state : '' }}</div>
                    </div>
                    <span class="small hr-muted text-nowrap" title="Employees who opened it"><i class="bi bi-eye"></i> {{ $a->reads_count }}</span>
                </a>
            @empty
                <div class="hr-empty"><i class="bi bi-megaphone"></i>No announcements yet. Post one to everyone or to a single department. Staff see it in their portal.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
