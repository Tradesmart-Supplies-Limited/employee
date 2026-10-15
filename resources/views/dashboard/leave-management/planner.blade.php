@extends('layouts.app')

@section('content')

@php
    $prev = $month->copy()->subMonth()->format('Y-m');
    $next = $month->copy()->addMonth()->format('Y-m');
    $today = now()->toDateString();
    $keep = request()->only(['department', 'approved_only']);
@endphp

<style>
.pl-wrap { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; overflow: hidden; }
.pl-scroll { overflow-x: auto; }
.pl { border-collapse: separate; border-spacing: 0; font-size: .74rem; min-width: 100%; }
.pl th, .pl td { border-bottom: 1px solid var(--admin-border, #eef2f7); }
.pl thead th { position: sticky; top: 0; background: var(--admin-surface, #fff); z-index: 2; text-align: center; padding: .35rem 0; min-width: 30px; font-weight: 600; color: var(--admin-muted, #64748b); }
.pl thead th .dow { display: block; font-size: .62rem; font-weight: 500; }
.pl .name { position: sticky; left: 0; background: var(--admin-surface, #fff); z-index: 1; min-width: 190px; max-width: 220px; padding: .45rem .75rem; text-align: left; border-right: 1px solid var(--admin-border, #e2e8f0); }
.pl thead .name { z-index: 3; }
.pl .name small { display: block; color: var(--admin-muted, #64748b); font-size: .66rem; }
.pl td.day { height: 34px; padding: 3px 1px; text-align: center; }
.pl .off  { background: repeating-linear-gradient(45deg, rgba(148,163,184,.12), rgba(148,163,184,.12) 4px, transparent 4px, transparent 8px); }
.pl .hol  { background: rgba(245, 158, 11, .13); }
.pl .today { box-shadow: inset 2px 0 0 var(--brand, #185FA5), inset -2px 0 0 var(--brand, #185FA5); }
.pl thead th.today { color: var(--brand-text, #185FA5); }
.pl-bar { display: block; height: 22px; border-radius: 0; }
.pl-bar.ap { background: #16a34a; }
.pl-bar.pe { background: repeating-linear-gradient(45deg, #f59e0b, #f59e0b 5px, #fbbf24 5px, #fbbf24 10px); }
.pl-bar.skip { opacity: .35; }
.pl-bar.start { border-radius: 6px 0 0 6px; margin-left: 2px; }
.pl-bar.end { border-radius: 0 6px 6px 0; margin-right: 2px; }
.pl-bar.start.end { border-radius: 6px; }
.pl-bar a { display: block; height: 100%; }
.pl-legend { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .74rem; color: var(--admin-muted, #64748b); padding: .75rem 1rem; border-top: 1px solid var(--admin-border, #e2e8f0); }
.pl-legend i { display: inline-block; width: 14px; height: 10px; border-radius: 3px; vertical-align: -1px; margin-right: .3rem; }
.pl-count td { font-weight: 700; color: var(--admin-muted, #64748b); font-size: .7rem; text-align: center; background: var(--admin-surface-soft, #f8fafc); }
</style>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <a href="{{ route('leave.manage.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Leave reports</a>
        <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-calendar3-range"></i> Leave Planner · {{ $month->format('F Y') }}</h1>
        <div class="small text-muted">Who is away, day by day. Click a bar to open the request.</div>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
        <a href="{{ route('leave.manage.planner', array_merge($keep, ['month' => $prev])) }}" class="btn btn-sm btn-outline-secondary" title="Previous month"><i class="bi bi-chevron-left"></i></a>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control form-control-sm" style="width:150px" onchange="this.form.submit()">
        <a href="{{ route('leave.manage.planner', array_merge($keep, ['month' => $next])) }}" class="btn btn-sm btn-outline-secondary" title="Next month"><i class="bi bi-chevron-right"></i></a>
        <select name="department" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            @if(auth()->user()?->hasFullAccess())<option value="">All departments</option>@endif
            @foreach($options['departments'] as $d)<option @selected(request('department') === $d)>{{ $d }}</option>@endforeach
        </select>
        <div class="form-check small mb-0">
            <input class="form-check-input" type="checkbox" name="approved_only" value="1" id="apOnly" @checked(request()->boolean('approved_only')) onchange="this.form.submit()">
            <label for="apOnly" class="form-check-label">Approved only</label>
        </div>
        <a href="{{ route('leave.manage.planner') }}" class="btn btn-sm btn-link">This month</a>
    </form>
</div>

<div class="pl-wrap">
    <div class="pl-scroll">
        <table class="pl">
            <thead>
                <tr>
                    <th class="name">Employee</th>
                    @foreach($days as $d)
                        @php $key = $d->toDateString(); @endphp
                        <th class="{{ isset($holidays[$key]) ? 'hol' : ($d->isSunday() ? 'off' : '') }} {{ $key === $today ? 'today' : '' }}" title="{{ $holidays[$key] ?? $d->format('l') }}">
                            {{ $d->day }}<span class="dow">{{ substr($d->format('D'), 0, 2) }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <th class="name">
                            @if($row->employee && auth()->user()?->hasFullAccess())
                                <a href="{{ route('leave.manage.employee', $row->employee) }}" class="text-decoration-none fw-semibold">{{ $row->name }}</a>
                            @else
                                <span class="fw-semibold">{{ $row->name }}</span>
                            @endif
                            <small>{{ $row->department ?? '—' }}{{ $row->employee ? ' · bal ' . rtrim(rtrim(number_format((float) $row->employee->leave_balance, 1), '0'), '.') : '' }}</small>
                        </th>
                        @foreach($days as $d)
                            @php
                                $key = $d->toDateString();
                                $l = $row->leaves->first(fn ($x) => $x->date_from->toDateString() <= $key && $x->date_to->toDateString() >= $key);
                                $skipped = $l && collect($l->excluded_dates ?? [])->contains('date', $key);
                            @endphp
                            <td class="day {{ isset($holidays[$key]) ? 'hol' : ($d->isSunday() ? 'off' : '') }} {{ $key === $today ? 'today' : '' }}">
                                @if($l)
                                    <span class="pl-bar {{ $l->status === 'Approved' ? 'ap' : 'pe' }} {{ $skipped ? 'skip' : '' }} {{ $l->date_from->toDateString() === $key || $d->day === 1 ? 'start' : '' }} {{ $l->date_to->toDateString() === $key || $d->isLastOfMonth() ? 'end' : '' }}"
                                          title="{{ $row->name }} · {{ $l->leave_type }} · {{ $l->date_from->format('d M') }}–{{ $l->date_to->format('d M') }} · {{ $l->stage_label }}{{ $skipped ? ' · not counted (' . collect($l->excluded_dates)->firstWhere('date', $key)['reason'] . ')' : '' }}">
                                        <a href="{{ route('leave.show', $l) }}" aria-label="Open leave request"></a>
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ $days->count() + 1 }}" class="text-center text-muted py-5">
                        <i class="bi bi-calendar-check fs-3 d-block mb-1"></i> Nobody has leave booked in {{ $month->format('F Y') }}.
                    </td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
                <tfoot class="pl-count">
                    <tr>
                        <td class="name" style="text-align:left">People away</td>
                        @foreach($days as $d)
                            @php $key = $d->toDateString(); $c = $rows->filter(fn ($row) => $row->leaves->contains(fn ($x) => $x->status === 'Approved' && $x->date_from->toDateString() <= $key && $x->date_to->toDateString() >= $key))->count(); @endphp
                            <td style="{{ $c >= 3 ? 'color:#b91c1c' : '' }}">{{ $c ?: '' }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <div class="pl-legend">
        <span><i style="background:#16a34a"></i>Approved</span>
        <span><i style="background:repeating-linear-gradient(45deg,#f59e0b,#f59e0b 3px,#fbbf24 3px,#fbbf24 6px)"></i>Pending</span>
        <span><i style="background:#16a34a;opacity:.35"></i>Inside leave but not counted</span>
        <span><i style="background:rgba(245,158,11,.25)"></i>Public holiday</span>
        <span><i style="background:repeating-linear-gradient(45deg,rgba(148,163,184,.3),rgba(148,163,184,.3) 2px,transparent 2px,transparent 4px)"></i>Sunday</span>
        @php $monthHols = collect($holidays)->filter(fn ($n, $d) => str_starts_with($d, $month->format('Y-m'))); @endphp
        @if($monthHols->isNotEmpty())
            <span class="ms-auto"><i class="bi bi-flag" style="width:auto;height:auto"></i>
                {{ $monthHols->map(fn ($name, $d) => \Carbon\Carbon::parse($d)->format('d M') . ' ' . $name)->implode(' · ') }}</span>
        @endif
    </div>
</div>

@endsection
