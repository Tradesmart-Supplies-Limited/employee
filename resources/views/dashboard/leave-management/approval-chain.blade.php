@extends('layouts.app')

@section('content')

@php
    $steps = [
        'supervisor' => ['Supervisor', 'bi-person-check', 'The person the employee reports to. If none is assigned, the Manager of their department. HR, Management or the Admin can act on their behalf.'],
        'hr'         => ['HR', 'bi-building-check', 'Any HR or Admin login. Checks the dates and the leave balance.'],
        'director'   => ['Management', 'bi-briefcase', 'Management (or the Admin on their behalf) gives the last approval.'],
    ];
    $on = fn ($s) => (bool) old($s . '_step', $settings->{$s . '_step'});
    $dis = $canEdit ? '' : 'disabled';
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <a href="{{ route('leave.manage.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Leave reports</a>
        <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-diagram-3"></i> Leave Approval Chain</h1>
        <div class="small text-muted">Who approves a leave request, and in what order. The last step that is switched on gives the final approval and updates the leave balance. A revert at any step ends the request, unless a higher approver overrides it.</div>
    </div>
</div>

@unless($canEdit)
    <div class="alert alert-light border small"><i class="bi bi-lock me-1"></i> Only the Admin can change the approval chain. You can see how it is set up.</div>
@endunless

@if($errors->any())
    <div class="alert alert-danger small">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('leave.manage.chain.update') }}" id="chainForm">
    @csrf
    @method('PUT')

    <div class="ac-flow" id="acFlow">
        <div class="ac-node is-fixed">
            <div class="ac-ico"><i class="bi bi-person"></i></div>
            <div class="ac-name">Employee</div>
            <div class="ac-desc">Applies on the portal, or HR records it for them.</div>
            <span class="ac-tag">Always</span>
        </div>

        @foreach($steps as $key => [$name, $icon, $desc])
            <div class="ac-arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></div>
            <label class="ac-node {{ $on($key) ? 'is-on' : '' }}" data-step="{{ $key }}">
                <input type="hidden" name="{{ $key }}_step" value="0">
                <div class="form-check form-switch ac-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}_step" value="1" @checked($on($key)) {{ $dis }} aria-label="{{ $name }} step">
                </div>
                <div class="ac-ico"><i class="bi {{ $icon }}"></i></div>
                <div class="ac-name">{{ $name }}</div>
                <div class="ac-desc">{{ $desc }}</div>
                <span class="ac-tag ac-final">Final approval</span>
                <span class="ac-tag ac-off">Switched off</span>
                @if(($waiting[$key] ?? 0) > 0)
                    <a href="{{ route('leave.index', ['stage' => 'awaiting_' . $key]) }}" class="ac-wait">{{ $waiting[$key] }} waiting</a>
                @endif
            </label>
        @endforeach
    </div>

    <div class="ac-summary" id="acSummary" aria-live="polite"></div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100" id="directorOptions">
                <div class="card-header bg-transparent"><strong><i class="bi bi-briefcase me-1"></i> Management step</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="director_min_days">Only send to Management when the request is at least</label>
                        <div class="input-group" style="max-width:240px">
                            <input type="number" step="0.5" min="0" max="365" class="form-control" id="director_min_days" name="director_min_days"
                                   value="{{ old('director_min_days', rtrim(rtrim(number_format((float) $settings->director_min_days, 1), '0'), '.')) }}" {{ $dis }}>
                            <span class="input-group-text">day(s)</span>
                        </div>
                        <div class="form-text">0 = every request goes to Management. Shorter requests end at the step before.</div>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold" for="director_user_id">Management login that receives the emails</label>
                        <select class="form-select" id="director_user_id" name="director_user_id" style="max-width:360px" {{ $dis }}>
                            <option value="">Every Management login ({{ $directors->count() }})</option>
                            @foreach($directors as $d)
                                <option value="{{ $d->id }}" @selected((int) old('director_user_id', $settings->director_user_id) === $d->id)>{{ $d->name }} · {{ $d->email }}</option>
                            @endforeach
                        </select>
                        @if($directors->isEmpty())
                            <div class="form-text text-danger"><i class="bi bi-exclamation-triangle"></i> There is no Management login yet, so the Management step will be skipped. Create one under <a href="{{ route('users.index') }}">Users &amp; Roles</a>.</div>
                        @else
                            <div class="form-text">Anyone in Management can approve; this only decides who is emailed.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-transparent"><strong><i class="bi bi-info-circle me-1"></i> How it works</strong></div>
                <div class="card-body small">
                    <ul class="ps-3 mb-0 ac-rules">
                        <li>Each approver is emailed when it is their turn; the employee and HR are emailed at every step.</li>
                        <li>Approving passes the request to the next step. The <strong>last</strong> approval is final and deducts the balance.</li>
                        <li>Reverting at <strong>any</strong> step ends the request (a short reason is kindly required). An approver higher up the chain can override a revert, and everyone in the chain can comment.</li>
                        <li>Nobody can approve their own leave.</li>
                        <li>If a step has nobody to act (no supervisor or no Management login), it is skipped automatically.</li>
                        <li>Changes apply to <strong>new</strong> requests. Requests already on their way keep their chain.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
        @if($canEdit)
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save approval chain</button>
        @endif
        @if($settings->editor)
            <small class="text-muted">Last changed by {{ $settings->editor->name }} · {{ $settings->updated_at->format('d M Y, H:i') }}</small>
        @endif
    </div>
</form>

<style>
.ac-flow { display: grid; grid-template-columns: minmax(0,1fr) auto minmax(0,1fr) auto minmax(0,1fr) auto minmax(0,1fr); align-items: stretch; gap: .5rem; }
.ac-arrow { display: flex; align-items: center; color: var(--admin-muted, #94a3b8); font-size: 1.2rem; }
.ac-node { position: relative; display: flex; flex-direction: column; gap: .3rem; padding: 1rem; border-radius: 14px; cursor: pointer;
    background: var(--admin-surface, #fff); border: 2px dashed var(--admin-border, #e2e8f0); transition: border-color .15s, box-shadow .15s, opacity .15s; opacity: .7; margin: 0; }
.ac-node.is-on, .ac-node.is-fixed { opacity: 1; border-style: solid; border-color: rgba(var(--brand-rgb, 24,95,165), .35); box-shadow: 0 6px 18px rgba(15,23,42,.06); }
.ac-node.is-fixed { cursor: default; }
.ac-node.is-final { border-color: var(--brand, #185FA5); box-shadow: 0 0 0 3px rgba(var(--brand-rgb, 24,95,165), .15); }
.ac-ico { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; font-size: 1.25rem;
    background: rgba(var(--brand-rgb, 24,95,165), .12); color: var(--brand-text, #185FA5); }
.ac-node:not(.is-on):not(.is-fixed) .ac-ico { background: var(--admin-surface-soft, #f1f5f9); color: var(--admin-muted, #94a3b8); }
.ac-name { font-weight: 700; font-size: 1rem; }
.ac-desc { font-size: .78rem; color: var(--admin-muted, #64748b); line-height: 1.4; flex: 1; }
.ac-switch { position: absolute; top: .8rem; right: .4rem; margin: 0; }
.ac-switch .form-check-input { cursor: pointer; width: 2.4em; height: 1.3em; }
.ac-tag { align-self: flex-start; font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: .2rem .5rem; border-radius: 999px;
    background: var(--admin-surface-soft, #f1f5f9); color: var(--admin-muted, #64748b); }
.ac-final { display: none; background: var(--brand, #185FA5); color: #fff; }
.ac-off { display: none; }
.ac-node.is-final .ac-final { display: inline-block; }
.ac-node:not(.is-on):not(.is-fixed) .ac-off { display: inline-block; }
.ac-wait { font-size: .74rem; font-weight: 600; color: #b45309; text-decoration: none; }
.ac-summary { margin-top: .9rem; padding: .7rem 1rem; border-radius: 12px; font-size: .9rem;
    background: rgba(var(--brand-rgb, 24,95,165), .08); color: var(--admin-text, #0f172a); border: 1px solid rgba(var(--brand-rgb, 24,95,165), .2); }
.ac-summary.is-bad { background: rgba(220,38,38,.08); border-color: rgba(220,38,38,.3); color: #b91c1c; }
.ac-rules li { margin-bottom: .35rem; }
#directorOptions.is-off { opacity: .55; }
@media (max-width: 991.98px) {
    .ac-flow { grid-template-columns: 1fr; }
    .ac-arrow { justify-content: center; transform: rotate(90deg); height: 1.2rem; }
}
</style>

<script>
(function () {
    var nodes = [].slice.call(document.querySelectorAll('.ac-node[data-step]'));
    var summary = document.getElementById('acSummary');
    var dirOpts = document.getElementById('directorOptions');
    var names = { supervisor: 'the supervisor', hr: 'HR', director: 'Management' };

    function paint() {
        var on = nodes.filter(function (n) { return n.querySelector('input[type=checkbox]').checked; });
        nodes.forEach(function (n) {
            n.classList.toggle('is-on', on.indexOf(n) !== -1);
            n.classList.remove('is-final');
        });
        if (on.length) on[on.length - 1].classList.add('is-final');
        var dirOn = on.some(function (n) { return n.dataset.step === 'director'; });
        dirOpts.classList.toggle('is-off', !dirOn);
        var finalOk = on.some(function (n) { return n.dataset.step === 'hr' || n.dataset.step === 'director'; });
        summary.classList.toggle('is-bad', !finalOk);
        summary.innerHTML = finalOk
            ? '<i class="bi bi-signpost-split me-1"></i> A request goes from the employee to ' +
              on.map(function (n) { return '<strong>' + names[n.dataset.step] + '</strong>'; }).join(', then ') +
              '. ' + names[on[on.length - 1].dataset.step].replace(/^./, function (c) { return c.toUpperCase(); }) + ' gives the final approval.'
            : '<i class="bi bi-exclamation-triangle me-1"></i> Switch on HR or Management: one of them must give the final approval.';
    }
    nodes.forEach(function (n) { n.querySelector('input[type=checkbox]').addEventListener('change', paint); });
    paint();
})();
</script>

@endsection
