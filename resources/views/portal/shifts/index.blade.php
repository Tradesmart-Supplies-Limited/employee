@extends('layouts.portal')
@section('title', 'My Shifts')

@section('content')
<style>
.ms-day { display: flex; align-items: center; gap: .85rem; padding: .7rem 1rem; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.ms-day:last-child { border-bottom: 0; }
.ms-day.today { background: color-mix(in srgb, var(--brand, #185FA5) 8%, transparent); }
.ms-day .d { width: 48px; text-align: center; line-height: 1.1; flex-shrink: 0; }
.ms-day .d b { font-size: 1.2rem; display: block; }
.ms-day .d small { font-size: .66rem; text-transform: uppercase; font-weight: 700; color: var(--admin-muted, #64748b); }
.ms-pill { display: inline-flex; align-items: center; gap: .35rem; font-weight: 800; font-size: .8rem; padding: .25rem .7rem; border-radius: 999px; color: #fff; background: var(--c); }
.ms-pill.light { color: #3f2d00; }
.ms-hero { border-radius: 18px; padding: 1.1rem 1.2rem; color: #fff; background: linear-gradient(135deg, var(--c, var(--brand-text, #185FA5)), #0b3b6f); }
</style>

@php
    $todayRow = collect($weeks[0]['days'])->first(fn ($d) => $d['date']->isSameDay($today));
    $tomorrow = collect($weeks[0]['days'])->merge($weeks[1]['days'])->first(fn ($d) => $d['date']->isSameDay($today->copy()->addDay()));
    $pillColor = fn ($p) => $p->kind === 'shift' ? ($p->shift?->color ?? '#185FA5') : ($p->kind === 'backup' ? '#f1c232' : '#94a3b8');
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-week"></i> My Shifts</h4>
        <small class="text-muted">{{ $usesRota ? 'From the ' . $employee->department . ' rota approved by your supervisor / HR.' : 'Your normal working pattern.' }}</small>
    </div>
    @if($canManage)<a href="{{ route('portal.rota.index') }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i> Manage team rota</a>@endif
</div>

@if($todayRow)
    @php $p = $todayRow['plan']; @endphp
    <div class="ms-hero mb-3" style="--c:{{ $todayRow['leave'] ? '#7c3aed' : $pillColor($p) }}">
        <div class="small text-uppercase fw-bold" style="opacity:.85;letter-spacing:.06em">Today · {{ $today->format('l d M') }}</div>
        <div class="fs-4 fw-bold">
            @if($todayRow['leave']) You're on {{ $todayRow['leave'] }}
            @elseif($p->expected) Report for {{ $p->shift?->name ?? 'work' }} {{ $p->start ? '· ' . $p->start . '–' . $p->end : '' }}
            @elseif($p->kind === 'backup') You're backup (on call)
            @else {{ $p->label }} @endif
        </div>
        @if($p->expected && $p->overtime)<div class="small">{{ $p->day_type === 'holiday' ? 'Public holiday' : 'Sunday' }} shift: paid as overtime.</div>@endif
        @if($tomorrow)
            @php $t = $tomorrow['plan']; @endphp
            <div class="small mt-1" style="opacity:.9">Tomorrow: {{ $tomorrow['leave'] ? $tomorrow['leave'] : ($t->expected ? ($t->shift?->name ?? 'Work') . ($t->start ? ' ' . $t->start . '–' . $t->end : '') : $t->label) }}</div>
        @endif
    </div>
@endif

<div class="row g-3">
    @foreach($weeks as $i => $w)
        <div class="col-lg-6">
            <div class="hr-card h-100">
                <div class="hr-card-h">
                    <h6>{{ $i === 0 ? 'This week' : 'Next week' }} · {{ $w['monday']->format('d M') }} – {{ $w['monday']->copy()->addDays(6)->format('d M') }}</h6>
                    @if($usesRota)
                        @if($w['rota'])<span class="hr-chip" style="--bg:#dcfce7;--c:#166534"><i class="bi bi-check2-circle"></i> Rota approved</span>
                        @else<span class="hr-chip" style="--bg:#fef3c7;--c:#92400e">Rota not published yet</span>@endif
                    @endif
                </div>
                @foreach($w['days'] as $d)
                    @php $p = $d['plan']; @endphp
                    <div class="ms-day {{ $d['date']->isSameDay($today) ? 'today' : '' }}">
                        <div class="d"><b>{{ $d['date']->format('d') }}</b><small>{{ $d['date']->format('D') }}</small></div>
                        <div class="flex-grow-1" style="min-width:0">
                            @if($d['leave'])
                                <span class="ms-pill" style="--c:#7c3aed"><i class="bi bi-airplane"></i> {{ $d['leave'] }}</span>
                            @elseif($p->expected)
                                <span class="ms-pill" style="--c:{{ $pillColor($p) }}"><i class="bi bi-clock"></i> {{ $p->shift?->name ?? 'Work' }}</span>
                                <span class="fw-semibold ms-1">{{ $p->start }}–{{ $p->end }}</span>
                                @if($p->overtime)<div class="small" style="color:#b45309">{{ $p->day_type === 'holiday' ? 'Public holiday' : 'Sunday' }}: overtime</div>@endif
                            @elseif($p->kind === 'backup')
                                <span class="ms-pill light" style="--c:#f1c232"><i class="bi bi-telephone"></i> Backup (on call)</span>
                            @else
                                <span class="text-muted small">{{ $p->label }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
                @if($usesRota && ! $w['rota'])<div class="small text-muted px-3 pb-2">Until the rota is published, your normal hours are shown.</div>@endif
            </div>
        </div>
    @endforeach
</div>

@foreach($weeks as $i => $w)
    @if($w['sheet'])
        <details class="hr-card mt-3" @if($i === 0) open @endif>
            <summary class="hr-card-h" style="cursor:pointer"><h6 class="mb-0"><i class="bi bi-people"></i> Full {{ $employee->department }} rota · {{ $w['monday']->format('d M') }} – {{ $w['monday']->copy()->addDays(6)->format('d M') }}</h6></summary>
            <div class="p-2">@include('dashboard.rota.partials.sheet', $w['sheet'] + ['highlight' => $employee->id])</div>
            @if($w['rota']->notes)<div class="small px-3 pb-3"><i class="bi bi-chat-left-text"></i> {{ $w['rota']->notes }}</div>@endif
        </details>
    @endif
@endforeach
@endsection
