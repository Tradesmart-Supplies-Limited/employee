@extends($layout)
@section('title', 'Rota · ' . $rota->department)

@section('content')
@php
    use App\Services\ShiftSchedule;
    $codeColor = $allShifts->mapWithKeys(fn ($s) => ['s:' . $s->id => $s->color])->all() + ['backup' => '#f1c232', 'off' => '#94a3b8'];
    $locked = $rota->week_end->lt(\App\Services\AttendanceService::today()->subDays(7)); // well in the past: read-only
    $status = $rota->is_published ? ($pending ? 'Published · changes not published yet' : 'Published' . ($rota->version > 1 ? ' (v' . $rota->version . ')' : '')) : 'Draft';
@endphp

<style>
.ro-head { display: flex; justify-content: space-between; align-items: flex-end; gap: .8rem; flex-wrap: wrap; margin-bottom: .9rem; }
.ro-grid { border-collapse: separate; border-spacing: 0; width: 100%; min-width: 980px; font-size: .82rem; }
.ro-grid th, .ro-grid td { border-bottom: 1px solid var(--admin-border, #e2e8f0); padding: .35rem .3rem; background: var(--admin-surface, #fff); }
.ro-grid thead th { position: sticky; top: 0; z-index: 3; text-align: center; font-size: .72rem; line-height: 1.2; background: #c9daf8; color: #0f172a; border-bottom: 2px solid #94a3b8; }
.ro-grid thead th.hol { background: #ff9900; color: #fff; }
.ro-grid thead th.sun { background: #e2e8f0; }
.ro-grid .who { position: sticky; left: 0; z-index: 2; min-width: 170px; max-width: 200px; box-shadow: 2px 0 0 var(--admin-border, #e2e8f0); }
.ro-grid thead .who { z-index: 4; text-align: left; background: #f8fafc; }
.ro-cell { position: relative; }
.ro-cell select { width: 100%; min-width: 96px; font-size: .78rem; font-weight: 700; border-radius: 8px; border: 1px solid var(--admin-border, #cbd5e1); padding: .3rem .4rem; background: var(--bg, #fff); color: var(--fg, #0f172a); }
.ro-cell.leave select { outline: 2px solid #dc2626; outline-offset: -2px; }
.ro-cell .lv { position: absolute; top: -2px; right: 2px; font-size: .58rem; font-weight: 800; color: #b91c1c; background: #fee2e2; border-radius: 4px; padding: 0 .25rem; }
.ro-grid tfoot td { font-size: .7rem; background: #f8fafc; vertical-align: top; }
.ro-cnt span { display: inline-block; margin: 1px; padding: 0 .35rem; border-radius: 999px; color: #fff; font-weight: 700; }
.ro-fill { font-size: .72rem; padding: .15rem .3rem; border-radius: 6px; max-width: 100%; }
.ro-legend span { display: inline-flex; align-items: center; gap: .3rem; margin-right: .8rem; font-size: .8rem; }
.ro-legend i { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
.ro-bar { position: sticky; bottom: 0; z-index: 6; background: var(--admin-surface, #fff); border-top: 1px solid var(--admin-border, #e2e8f0); padding: .8rem 1.1rem; display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; box-shadow: 0 -6px 16px rgba(15,23,42,.06); border-radius: 0 0 16px 16px; }
@media (max-width: 767.98px) { .ro-grid .who { min-width: 120px; max-width: 130px; } .ro-bar { position: static; } }
</style>

<div class="ro-head">
    <div style="min-width:0">
        <a href="{{ $rr('index', ['department' => $rota->department]) }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All weeks</a>
        <h1 class="h4 fw-bold mb-0 mt-1">{{ $rota->department }} rota</h1>
        <div class="small hr-muted">{{ $rota->week_start->format('l d M') }} – {{ $rota->week_end->format('l d M Y') }} ·
            <span class="hr-chip" style="{{ $rota->is_published && ! $pending ? '--bg:#dcfce7;--c:#166534' : ($pending ? '--bg:#fef3c7;--c:#92400e' : '') }}">{{ $status }}</span>
            @if($rota->publisher) <span>approved by {{ $rota->publisher->name }} {{ $rota->published_at?->diffForHumans() }}</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="{{ $rr('store') }}" class="d-inline">@csrf<input type="hidden" name="department" value="{{ $rota->department }}"><input type="hidden" name="week" value="{{ $prev->toDateString() }}"><button class="btn btn-sm btn-light border" title="Previous week"><i class="bi bi-chevron-left"></i></button></form>
        <form method="POST" action="{{ $rr('store') }}" class="d-inline">@csrf<input type="hidden" name="department" value="{{ $rota->department }}"><input type="hidden" name="week" value="{{ $next->toDateString() }}"><button class="btn btn-sm btn-light border" title="Next week"><i class="bi bi-chevron-right"></i></button></form>
        <a href="{{ $rr('print', $rota) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</a>
        @if($hasPrev && ! $locked)
            <form method="POST" action="{{ $rr('copy', $rota) }}" onsubmit="return confirm('Replace this week with a copy of last week?')">@csrf<button class="btn btn-sm btn-outline-secondary"><i class="bi bi-files"></i> Copy last week</button></form>
        @endif
    </div>
</div>

@if($warnings)
    <div class="hr-card mb-3">
        @foreach($warnings as [$lvl, $msg])
            <div class="d-flex gap-2 align-items-start px-3 py-2 small border-bottom" style="color:{{ ['danger' => '#b91c1c', 'warning' => '#b45309', 'info' => '#075985'][$lvl] }}">
                <i class="bi {{ ['danger' => 'bi-exclamation-octagon', 'warning' => 'bi-exclamation-triangle', 'info' => 'bi-info-circle'][$lvl] }}"></i><span>{{ $msg }}</span>
            </div>
        @endforeach
    </div>
@endif

<div class="ro-legend mb-2">
    @foreach($shifts as $sh)<span><i style="background:{{ $sh->color }}"></i>{{ $sh->name }} <span class="hr-muted">{{ collect([$sh->weekday_start ? $sh->weekday_start . '–' . $sh->weekday_end : null, $sh->saturday_start ? 'Sat ' . $sh->saturday_start . '–' . $sh->saturday_end : null])->filter()->implode(' · ') }}</span></span>@endforeach
    <span><i style="background:#f1c232"></i>Backup (on call)</span>
    <span><i style="background:#94a3b8"></i>Day off</span>
</div>

<form method="POST" action="{{ $rr('update', $rota) }}" id="roForm" class="hr-card">
    @csrf @method('PUT')
    <div class="table-responsive" style="max-height:70vh">
        <table class="ro-grid">
            <thead>
                <tr>
                    <th class="who">Employee <span class="hr-muted fw-normal">({{ $people->count() }})</span></th>
                    @foreach($rota->days as $d)
                        @php $hol = $holidays[$d->toDateString()] ?? null; $dt = ShiftSchedule::dayType($d, $holidays); @endphp
                        <th class="{{ $hol ? 'hol' : ($d->isSunday() ? 'sun' : '') }}" style="min-width:110px">
                            {{ $d->format('D') }} {{ $d->format('d M') }}
                            <div class="fw-normal" style="font-size:.64rem">{{ $hol ? $hol . ' · overtime' : ($d->isSunday() ? 'Day off · overtime' : ($d->isSaturday() ? 'Saturday hours' : '')) }}</div>
                        </th>
                    @endforeach
                    <th style="min-width:100px">Fill Mon–Fri</th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $p)
                    <tr data-emp="{{ $p->id }}">
                        <td class="who">
                            <div class="fw-semibold text-truncate" title="{{ $p->full_name }}">{{ $p->full_name }}</div>
                            <div class="small hr-muted text-truncate">{{ $p->position ?? '—' }}{{ $p->department !== $rota->department ? ' · now ' . ($p->department ?: 'no dept') : '' }}</div>
                        </td>
                        @foreach($rota->days as $d)
                            @php
                                $ds = $d->toDateString();
                                $code = $cells[$p->id][$ds] ?? '';
                                $onLeave = collect($leaves[$p->id] ?? [])->first(fn ($l) => $ds >= $l[0] && $ds <= $l[1]);
                            @endphp
                            <td class="ro-cell {{ $onLeave ? 'leave' : '' }}" @if($onLeave) title="On {{ $onLeave[2] }}" @endif>
                                @if($onLeave)<span class="lv">LEAVE</span>@endif
                                <select name="cells[{{ $p->id }}][{{ $ds }}]" data-day="{{ $ds }}" data-dow="{{ $d->dayOfWeekIso }}" @disabled($locked) aria-label="{{ $p->full_name }} {{ $d->format('D d M') }}">
                                    <option value="">—</option>
                                    @foreach($shifts as $sh)<option value="s:{{ $sh->id }}" @selected($code === 's:' . $sh->id)>{{ $sh->name }}</option>@endforeach
                                    @if(str_starts_with($code, 's:') && ! $shifts->firstWhere('id', (int) substr($code, 2)))<option value="{{ $code }}" selected>{{ $allShifts[(int) substr($code, 2)]->name ?? 'Old shift' }}</option>@endif
                                    <option value="backup" @selected($code === 'backup')>Backup</option>
                                    <option value="off" @selected($code === 'off')>Day off</option>
                                </select>
                            </td>
                        @endforeach
                        <td>
                            <select class="form-select form-select-sm ro-fill" data-fill @disabled($locked) aria-label="Fill Monday to Friday for {{ $p->full_name }}">
                                <option value="">Set…</option>
                                @foreach($shifts as $sh)<option value="s:{{ $sh->id }}">{{ $sh->name }}</option>@endforeach
                                <option value="backup">Backup</option><option value="off">Day off</option><option value="__clear">Clear week</option>
                            </select>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center hr-muted py-5">No active employees in {{ $rota->department }}.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td class="who fw-semibold">On shift</td>
                    @foreach($rota->days as $d)<td class="ro-cnt" data-count="{{ $d->toDateString() }}"></td>@endforeach
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="p-3 border-top">
        <label class="form-label small fw-semibold mb-1">Note to the team <span class="fw-normal hr-muted">(included in the email)</span></label>
        <textarea name="notes" rows="2" maxlength="2000" class="form-control form-control-sm" placeholder="e.g. Stock-take on Saturday: everyone on the day shift please report by 08:30." @disabled($locked)>{{ old('notes', $rota->notes) }}</textarea>
    </div>

    @unless($locked)
        <div class="ro-bar">
            <button name="action" value="save" class="btn btn-outline-primary"><i class="bi bi-save"></i> Save {{ $rota->is_published ? 'changes' : 'draft' }}</button>
            <button name="action" value="publish" class="btn btn-success" onclick="return confirm('{{ $rota->is_published ? 'Publish these changes? Only people whose shifts changed will be emailed.' : 'Approve and publish this rota? Everyone on it will be emailed their shifts.' }}')">
                <i class="bi bi-send-check"></i> {{ $rota->is_published ? 'Publish changes' : 'Approve & publish' }}
            </button>
            <span class="small hr-muted" id="dirty">{{ $rota->is_published ? 'Staff see the published version until you publish changes.' : 'Staff don\'t see a draft until it\'s published.' }}</span>
        </div>
    @endunless
</form>

<div class="d-flex gap-2 mt-3 flex-wrap">
    @if($rota->is_published && ! $locked)
        <form method="POST" action="{{ $rr('unpublish', $rota) }}" onsubmit="return confirm('Take this rota back to draft? Attendance stops using it until it is published again.')">@csrf<button class="btn btn-sm btn-link text-danger p-0">Move back to draft</button></form>
    @elseif(! $rota->is_published)
        <form method="POST" action="{{ $rr('destroy', $rota) }}" onsubmit="return confirm('Delete this draft rota?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0">Delete draft</button></form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const colors = @json($codeColor);
    const names = @json($allShifts->mapWithKeys(fn ($s) => ['s:' . $s->id => $s->name])->all() + ['backup' => 'Backup']);
    const form = document.getElementById('roForm');
    const paint = sel => {
        const c = colors[sel.value];
        sel.style.setProperty('--bg', c ? c : '');
        sel.style.setProperty('--fg', c ? (sel.value === 'backup' ? '#3f2d00' : '#fff') : '');
    };
    const count = () => {
        document.querySelectorAll('[data-count]').forEach(td => {
            const day = td.dataset.count, n = {};
            form.querySelectorAll('select[data-day="' + day + '"]').forEach(s => { if (s.value && s.value !== 'off') n[s.value] = (n[s.value] || 0) + 1; });
            td.innerHTML = Object.keys(n).map(k => '<span style="background:' + colors[k] + (k === 'backup' ? ';color:#3f2d00' : '') + '">' + n[k] + ' ' + (names[k] || k).replace(' shift', '') + '</span>').join('') || '<span style="background:#e2e8f0;color:#64748b">none</span>';
        });
    };
    let dirty = false;
    form.querySelectorAll('select[data-day]').forEach(s => { paint(s); s.addEventListener('change', () => { paint(s); count(); dirty = true; document.getElementById('dirty').textContent = 'Unsaved changes'; }); });
    form.querySelectorAll('[data-fill]').forEach(f => f.addEventListener('change', () => {
        const row = f.closest('tr');
        row.querySelectorAll('select[data-day]').forEach(s => { if (+s.dataset.dow <= 5) { s.value = f.value === '__clear' ? '' : f.value; paint(s); } });
        if (f.value === '__clear') row.querySelectorAll('select[data-day]').forEach(s => { s.value = ''; paint(s); });
        f.value = ''; count(); dirty = true;
    }));
    count();
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endpush
