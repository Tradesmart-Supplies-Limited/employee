@extends('layouts.management')
@section('title', 'Management Portal')

@section('content')
@php $first = explode(' ', trim($user->name))[0]; $hour = $now->hour; @endphp

<div class="mp-hero mb-4">
    @include('management.partials.avatar', ['user' => $user, 'size' => 64])
    <div class="flex-grow-1" style="min-width:0">
        <div class="small opacity-75">Management Self Service</div>
        <h1 class="h4 fw-bold mb-0">Good {{ $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') }}, {{ $first }}</h1>
        <div class="small opacity-75">{{ $user->job_title ?: 'Management' }}{{ $user->office_location ? ' · ' . $user->office_location : '' }}</div>
    </div>
    <a href="{{ route('mgmt.profile') }}" class="btn btn-light btn-sm"><i class="bi bi-pencil-square me-1"></i> Edit profile</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        @include('management.partials.clock-card', ['todayRec' => $todayRec])

        <div class="hr-card mt-4">
            <div class="hr-card-h d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-calendar-week me-1"></i> This week</strong>
                <a href="{{ route('mgmt.attendance') }}" class="small">Full history <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="hr-card-b">
                <div class="mp-week">
                    @foreach($weekDays as $d)
                        @php $r = $week[$d->format('Y-m-d')] ?? null; $isToday = $d->isSameDay($today); $future = $d->gt($today); @endphp
                        <div class="mp-day {{ $isToday ? 'is-today' : '' }} {{ $r?->clock_in ? 'is-in' : '' }} {{ $future ? 'is-future' : '' }}">
                            <small>{{ $d->format('D') }}</small>
                            <b>{{ $d->format('d') }}</b>
                            <span>{{ $r?->clock_in ? ($r->clock_out ? \App\Models\ManagementAttendance::hm($r->minutes) : 'In ' . $r->in_time) : ($future ? '' : '—') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="small text-muted mt-2">{{ \App\Models\ManagementAttendance::hm($weekMins) }} this week</div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="row g-3">
            <div class="col-6"><div class="hr-card hr-card-b mp-stat"><small>Days this month</small><b>{{ $monthDays }}</b></div></div>
            <div class="col-6"><div class="hr-card hr-card-b mp-stat"><small>Hours this month</small><b>{{ \App\Models\ManagementAttendance::hm($monthMins) }}</b></div></div>
        </div>

        <div class="hr-card hr-card-b mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong><i class="bi bi-person-check me-1"></i> Your profile</strong>
                <span class="small fw-semibold">{{ $completion['percent'] }}%</span>
            </div>
            <div class="progress" style="height:8px" role="progressbar" aria-valuenow="{{ $completion['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Profile complete">
                <div class="progress-bar bg-success" style="width: {{ $completion['percent'] }}%"></div>
            </div>
            @if($completion['missing'])
                <div class="small text-muted mt-2">When you have a moment, you could add: {{ implode(', ', array_map('strtolower', $completion['missing'])) }}.</div>
                <a href="{{ route('mgmt.profile') }}" class="btn btn-sm btn-outline-primary mt-2">Complete my profile</a>
            @else
                <div class="small text-muted mt-2">Your profile is complete. Thank you!</div>
            @endif
        </div>

        <div class="hr-card hr-card-b mt-3">
            <strong class="d-block mb-2"><i class="bi bi-lightning-charge me-1"></i> Quick links</strong>
            <div class="d-grid gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-light border text-start"><i class="bi bi-grid-1x2 me-2"></i> Management admin dashboard</a>
                <a href="{{ route('leave.index', ['stage' => 'awaiting_director']) }}" class="btn btn-light border text-start"><i class="bi bi-calendar-check me-2"></i> Leave waiting for Management</a>
                <a href="{{ route('announcements.index') }}" class="btn btn-light border text-start"><i class="bi bi-megaphone me-2"></i> Announcements</a>
            </div>
        </div>
    </div>
</div>

<style>
.mp-hero { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; padding: 18px 20px; border-radius: 18px; color: #fff;
    background: radial-gradient(120% 140% at 0% 0%, rgba(255,255,255,.18), transparent 50%), linear-gradient(120deg, var(--hero-from, #0f2a4a), var(--hero-to, var(--brand, #185FA5))); }
.mp-hero img, .mp-hero > span:first-child { box-shadow: 0 0 0 3px rgba(255,255,255,.55); }
.mp-week { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
.mp-day { text-align: center; padding: 8px 2px; border-radius: 12px; background: var(--admin-surface-soft, #f8fafc); border: 1px solid var(--admin-border, #e2e8f0); min-width: 0; }
.mp-day small { display: block; font-size: .68rem; text-transform: uppercase; color: var(--admin-muted, #64748b); }
.mp-day b { display: block; font-size: 1.05rem; }
.mp-day span { display: block; font-size: .66rem; color: var(--admin-muted, #64748b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-height: 1em; }
.mp-day.is-in { background: rgba(22,163,74,.1); border-color: rgba(22,163,74,.35); }
.mp-day.is-in span { color: #15803d; font-weight: 600; }
.mp-day.is-today { box-shadow: 0 0 0 2px var(--brand, #185FA5); }
.mp-day.is-future { opacity: .5; }
.mp-stat small { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-muted, #64748b); }
.mp-stat b { font-size: 1.5rem; }
html[data-theme=dark] .mp-day.is-in span { color: #4ade80; }
</style>
@endsection
