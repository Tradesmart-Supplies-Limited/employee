@extends('layouts.app')

@section('content')

@php
    $stateMeta = [
        'expired'  => ['Expired, still active', '#dc2626', 'bi-exclamation-octagon-fill'],
        'critical' => ['Within 7 days', '#ea580c', 'bi-alarm-fill'],
        'soon'     => ['8–30 days', '#d97706', 'bi-hourglass-split'],
        'upcoming' => ['31+ days', '#0284c7', 'bi-calendar-event'],
    ];
@endphp

<style>
.cr-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1.1rem 1.2rem; }
.cr-card h2 { font-size: .95rem; font-weight: 700; margin: 0 0 .9rem; display: flex; gap: .45rem; align-items: center; }
.cr-tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: .7rem; margin-bottom: 1rem; }
.cr-tile { border: 1px solid var(--admin-border, #e2e8f0); border-radius: 12px; padding: .8rem 1rem; background: var(--admin-surface, #fff); position: relative; overflow: hidden; }
.cr-tile::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--c); }
.cr-tile .n { font-size: 1.6rem; font-weight: 800; line-height: 1.1; }
.cr-tile .l { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--admin-muted, #64748b); }
.cr-pill { font-size: .7rem; font-weight: 700; padding: .15rem .55rem; border-radius: 999px; white-space: nowrap; }
.cr-days { font-weight: 800; font-size: .95rem; }
.cr-type { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: .1rem .45rem; border-radius: 6px; }
.cr-type.contract { background: #e0f2fe; color: #075985; } .cr-type.probation { background: #ede9fe; color: #5b21b6; }
.cr-help { font-size: .76rem; color: var(--admin-muted, #64748b); }
.cr-run { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; font-size: .8rem; color: var(--admin-muted, #64748b); }
@media (max-width: 768px) { .cr-tiles { grid-template-columns: 1fr 1fr; } }
</style>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <div class="text-uppercase fw-bold small" style="color:var(--brand-text, #185FA5); letter-spacing:.08em">Employees</div>
        <h1 class="h4 fw-bold mb-0">Contract &amp; Probation Reminders</h1>
        <div class="small text-muted">Active employees whose contract or probation is ending, and when HR will be reminded.</div>
    </div>
    <div class="cr-run">
        <span><i class="bi bi-clock-history"></i> Last check: {{ $setting->last_run_at ? $setting->last_run_at->diffForHumans() : 'never' }}</span>
        <form method="POST" action="{{ route('settings.contract-reminders.run') }}">@csrf
            <button class="btn btn-primary btn-sm"><i class="bi bi-play-circle"></i> Check &amp; send now</button>
        </form>
        <form method="POST" action="{{ route('settings.contract-reminders.test', $setting->id) }}">@csrf
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-envelope-paper"></i> Send test email</button>
        </form>
    </div>
</div>

{{-- ===== STATUS TILES ===== --}}
<div class="cr-tiles">
    @foreach($stateMeta as $key => [$label, $color, $icon])
        <div class="cr-tile" style="--c: {{ $color }}">
            <div class="l"><i class="bi {{ $icon }}" style="color: {{ $color }}"></i> {{ $label }}</div>
            <div class="n">{{ $counts[$key] }}</div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    {{-- ===== STATUS BOARD ===== --}}
    <div class="col-xl-7">
        <div class="cr-card">
            <h2>
                <i class="bi bi-list-check"></i> Coming up
                <form method="GET" class="ms-auto">
                    <select name="ahead" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach([30, 60, 90, 180, 365] as $d)<option value="{{ $d }}" @selected($ahead == $d)>Next {{ $d }} days</option>@endforeach
                    </select>
                </form>
            </h2>

            @if($items->isEmpty())
                <div class="text-center text-muted py-4"><i class="bi bi-check2-circle fs-3 d-block text-success"></i>No contracts or probations ending in this window.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.84rem">
                        <thead class="table-light"><tr><th>Employee</th><th>Type</th><th>Ends</th><th class="text-end">Days</th><th>Next reminder</th></tr></thead>
                        <tbody>
                            @foreach($items as $it)
                                @php
                                    [$label, $color] = $stateMeta[$it->state];
                                    $thresholds = collect($setting->days_before ?: [30, 14, 7, 1])->sort()->values();
                                    $next = $it->days_left < 0 ? null : $thresholds->filter(fn ($t) => $t < $it->days_left)->last();
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('employees.show', $it->employee) }}" class="fw-semibold text-decoration-none">{{ $it->employee->full_name }}</a>
                                        <div class="text-muted" style="font-size:.72rem">{{ $it->employee->position ?? '—' }}{{ $it->employee->department ? ' · ' . $it->employee->department : '' }}</div>
                                    </td>
                                    <td><span class="cr-type {{ $it->type }}">{{ $it->type }}</span></td>
                                    <td class="text-nowrap">{{ $it->end->format('D d M Y') }}</td>
                                    <td class="text-end"><span class="cr-days" style="color: {{ $color }}">{{ $it->days_left < 0 ? abs($it->days_left) . ' ago' : ($it->days_left === 0 ? 'today' : $it->days_left) }}</span></td>
                                    <td class="text-muted small">
                                        @if($it->days_left < 0)
                                            <span class="cr-pill" style="background:#fee2e2;color:#991b1b">Renew, extend or mark as exited</span>
                                        @elseif($next !== null)
                                            at {{ $next }} days ({{ $it->end->copy()->subDays($next)->format('d M') }})
                                        @else
                                            final reminder sent / due
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="cr-help mt-2"><i class="bi bi-info-circle"></i> To clear an item, update the end date on the employee (renewal) or change their status to Exited.</div>
            @endif
        </div>

        <div class="cr-card mt-3">
            <h2><i class="bi bi-envelope-check"></i> Recently sent</h2>
            @forelse($logs as $log)
                <div class="d-flex justify-content-between border-bottom py-2 small">
                    <span>
                        @if($log->event === 'digest')
                            <strong>Monthly digest</strong>
                        @else
                            <strong>{{ $log->employee?->full_name ?? 'Employee removed' }}</strong>
                            · {{ $log->event ?? 'contract' }} {{ $log->threshold === 0 ? 'expired' : ($log->threshold ? $log->threshold . '-day reminder' : $log->trigger_type) }}
                            @if($log->end_date)<span class="text-muted">(ends {{ $log->end_date->format('d M Y') }})</span>@endif
                        @endif
                    </span>
                    <span class="text-muted">{{ optional($log->sent_at)->format('d M Y H:i') }}</span>
                </div>
            @empty
                <div class="text-muted small">Nothing sent yet.</div>
            @endforelse
        </div>
    </div>

    {{-- ===== SETTINGS ===== --}}
    <div class="col-xl-5">
        <form method="POST" action="{{ route('settings.contract-reminders.update', $setting->id) }}" class="cr-card">
            @csrf
            @method('PUT')
            <h2><i class="bi bi-gear"></i> Settings</h2>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked($setting->is_active)>
                <label class="form-check-label fw-semibold" for="is_active">Reminders switched on</label>
            </div>

            <label class="form-label fw-semibold small">Send reminders to</label>
            <textarea name="recipients" rows="2" class="form-control form-control-sm mb-1" placeholder="hr@company.com, md@company.com">{{ old('recipients', $setting->recipients_text) }}</textarea>
            <div class="form-check small mb-3">
                <input class="form-check-input" type="checkbox" id="notify_supervisor" name="notify_supervisor" value="1" @checked($setting->notify_supervisor)>
                <label class="form-check-label" for="notify_supervisor">Also email each employee's supervisor about their own team</label>
            </div>

            <label class="form-label fw-semibold small">What to track</label>
            <div class="mb-3">
                <div class="form-check small"><input class="form-check-input" type="checkbox" id="track_contracts" name="track_contracts" value="1" @checked($setting->track_contracts !== false)><label class="form-check-label" for="track_contracts">Contract end dates</label></div>
                <div class="form-check small"><input class="form-check-input" type="checkbox" id="track_probation" name="track_probation" value="1" @checked($setting->track_probation !== false)><label class="form-check-label" for="track_probation">Probation end dates (confirm or extend)</label></div>
                <div class="form-check small"><input class="form-check-input" type="checkbox" id="include_expired" name="include_expired" value="1" @checked($setting->include_expired !== false)><label class="form-check-label" for="include_expired">Alert once when a date passes and the employee is still Active</label></div>
            </div>

            <div class="form-check form-switch mb-1">
                <input class="form-check-input" type="checkbox" role="switch" id="use_days_before" name="use_days_before" value="1" @checked($setting->use_days_before)>
                <label class="form-check-label fw-semibold small" for="use_days_before">Remind this many days before</label>
            </div>
            <input type="text" name="days_before" class="form-control form-control-sm" placeholder="30, 14, 7, 1" value="{{ old('days_before', $setting->days_before_text) }}">
            <div class="cr-help mb-3">Each reminder is sent once per end date. If the system was off on the day, it's sent on the next check, so nothing is missed.</div>

            <div class="form-check form-switch mb-1">
                <input class="form-check-input" type="checkbox" role="switch" id="use_monthly_fixed" name="use_monthly_fixed" value="1" @checked($setting->use_monthly_fixed)>
                <label class="form-check-label fw-semibold small" for="use_monthly_fixed">Monthly summary email</label>
            </div>
            <div class="row g-2 mb-1">
                <div class="col-6"><input type="text" name="monthly_fixed_days" class="form-control form-control-sm" placeholder="Day(s) e.g. 1, 15" value="{{ old('monthly_fixed_days', $setting->monthly_fixed_days_text) }}"></div>
                <div class="col-6"><div class="input-group input-group-sm"><span class="input-group-text">next</span><input type="number" name="monthly_lookahead_days" class="form-control" min="1" max="365" value="{{ old('monthly_lookahead_days', $setting->monthly_lookahead_days) }}"><span class="input-group-text">days</span></div></div>
            </div>
            <div class="cr-help mb-3">A full list on those days of the month (1–28).</div>

            <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> Save settings</button>

            <div class="cr-help mt-3 p-2 rounded" style="background:var(--admin-surface-soft,#f8fafc)">
                <i class="bi bi-lightning-charge"></i> <strong>Runs by itself:</strong> the check happens once a day the first time an admin opens the dashboard, and also via <code>php artisan schedule:run</code> if Windows Task Scheduler is set up.
            </div>
        </form>
    </div>
</div>

@endsection
