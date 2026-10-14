@extends('layouts.portal')
@section('title', 'My Information')

@section('content')

@php
    use App\Support\EmployeeFields as F;
    $fields = F::all();
    $val = function ($name) use ($employee) {
        $v = F::display($employee, $name);
        if ($v === null) return '<span class="mp-empty">Not provided</span>';
        return ! empty(F::get($name)['mask']) ? e(F::mask($v)) : e($v);
    };
    $badge = fn ($f) => match ($fields[$f]['portal'] ?? 'readonly') {
        'direct'   => '',
        'approval' => '<i class="bi bi-shield-lock mp-lock" title="Changes are checked by HR"></i>',
        default    => '',
    };
    $sections = [
        'personal' => ['Personal', 'bi-person', 'You can update these. Changes to your name, date of birth, NRC or passport are checked by HR.'],
        'contact'  => ['Contact & family', 'bi-telephone', 'Keep these up to date so we can reach you and your family in an emergency.'],
        'job'      => ['Job & contract', 'bi-briefcase', 'Managed by HR. Speak to HR if something is wrong.'],
        'pay'      => ['Pay & statutory', 'bi-bank', 'Bank and NAPSA / TPIN / NHIMA changes are checked by HR before payroll uses them.'],
    ];
    $pendingFields = $pending->flatMap(fn ($r) => array_keys($r->changes))->unique()->all();
    $missing = collect(['date_of_birth', 'nrc_no', 'primary_phone', 'residential_address', 'emergency_name', 'emergency_phone', 'next_of_kin_name', 'bank_account_no', 'ssn', 'tpin'])
        ->filter(fn ($f) => ! filled($employee->$f))->map(fn ($f) => F::label($f));
    $pct = (int) round((10 - $missing->count()) / 10 * 100);
@endphp

<style>
.mp-hero { display: flex; gap: 1.2rem; align-items: center; flex-wrap: wrap; background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 16px; padding: 1.2rem 1.4rem; margin-bottom: 1rem; }
.mp-photo { width: 96px; height: 96px; border-radius: 50%; overflow: hidden; background: linear-gradient(135deg, #185FA5, #4f8cff); color: #fff; font-size: 2rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mp-photo img { width: 100%; height: 100%; object-fit: cover; }
.mp-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1rem 1.2rem; margin-bottom: 1rem; }
.mp-card h2 { font-size: .95rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: .45rem; }
.mp-card .sub { font-size: .78rem; color: var(--admin-muted, #64748b); margin: .2rem 0 .9rem; }
.mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .8rem 1.2rem; }
.mp-grid small { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-muted, #64748b); font-weight: 700; }
.mp-grid div { font-size: .9rem; }
.mp-empty { color: var(--admin-muted, #94a3b8); font-style: italic; font-size: .84rem; }
.mp-lock { color: #7c3aed; font-size: .75rem; }
.mp-pending { font-size: .68rem; font-weight: 700; background: #fef3c7; color: #92400e; padding: .05rem .4rem; border-radius: 999px; margin-left: .25rem; }
.mp-progress { height: 8px; border-radius: 4px; background: var(--admin-surface-soft, #f1f5f9); overflow: hidden; }
.mp-progress span { display: block; height: 100%; background: #16a34a; }
</style>

<div class="mp-hero">
    <div class="mp-photo">@if($employee->passport_photo)<img src="{{ asset('storage/' . $employee->passport_photo) }}" alt="">@else{{ $employee->initials }}@endif</div>
    <div class="flex-grow-1">
        <h4 class="mb-0 fw-bold">{{ $employee->full_name }}</h4>
        <div class="text-muted small">{{ $employee->position ?? '—' }}{{ $employee->department ? ' · ' . $employee->department : '' }} · EIN {{ $employee->employee_id }}</div>
        <div class="small mt-1">
            <span class="badge bg-success">{{ $employee->employment_status }}</span>
            @if($employee->supervisor_name)<span class="text-muted ms-1"><i class="bi bi-person-up"></i> Reports to {{ $employee->supervisor_name }}</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('portal.profile.edit') }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Update my details</a>
        <a href="{{ route('portal.documents.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-folder2-open"></i> My documents ({{ $employee->documents->count() }})</a>
    </div>
</div>

@foreach($pending as $req)
    <div class="alert alert-warning d-flex align-items-center gap-2 flex-wrap">
        <i class="bi bi-hourglass-split fs-5"></i>
        <div class="flex-grow-1">
            <strong>Waiting for HR approval:</strong> {{ collect(array_keys($req->changes))->map(fn ($f) => F::label($f))->implode(', ') }}
            <span class="text-muted small">· sent {{ $req->created_at->diffForHumans() }}</span>
        </div>
        <form method="POST" action="{{ route('portal.profile.requests.cancel', $req) }}" onsubmit="return confirm('Withdraw this change request?')">
            @csrf <button class="btn btn-sm btn-outline-dark">Withdraw</button>
        </form>
    </div>
@endforeach

<div class="row g-3">
    <div class="col-lg-8">
        @foreach($sections as $step => [$title, $icon, $sub])
            <div class="mp-card">
                <h2><i class="bi {{ $icon }}"></i> {{ $title }}
                    @if(in_array($step, ['personal', 'contact', 'pay'], true))<a href="{{ route('portal.profile.edit') }}#{{ $step }}" class="ms-auto small fw-semibold text-decoration-none">Edit</a>@endif
                </h2>
                <div class="sub">{{ $sub }}</div>
                <div class="mp-grid">
                    @if($step === 'job')
                        <div><small>Employee number</small>{{ $employee->employee_id }}</div>
                        <div><small>Reports to</small>{{ $employee->supervisor_name ?? '—' }}</div>
                    @endif
                    @foreach(collect($fields)->filter(fn ($f) => $f['step'] === $step && $f['portal'] !== 'hidden') as $name => $def)
                        <div>
                            <small>{{ $def['label'] }} {!! $badge($name) !!}@if(in_array($name, $pendingFields, true))<span class="mp-pending">pending</span>@endif</small>
                            {!! $val($name) !!}
                        </div>
                    @endforeach
                    @if($step === 'job')
                        <div><small>Leave balance</small>{{ rtrim(rtrim(number_format((float) $employee->leave_balance, 1), '0'), '.') }} of {{ rtrim(rtrim(number_format((float) $employee->annual_leave_entitlement, 1), '0'), '.') }} days</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="col-lg-4">
        <div class="mp-card">
            <h2><i class="bi bi-clipboard-check"></i> Profile completeness</h2>
            <div class="d-flex align-items-center gap-2 mt-2 mb-2"><div class="mp-progress flex-grow-1"><span style="width: {{ $pct }}%; {{ $pct < 60 ? 'background:#f59e0b' : '' }}"></span></div><strong>{{ $pct }}%</strong></div>
            @if($missing->isNotEmpty())
                <div class="small text-muted mb-2">Please add: {{ $missing->implode(', ') }}</div>
                <a href="{{ route('portal.profile.edit') }}" class="btn btn-sm btn-outline-primary">Complete my profile</a>
            @else
                <div class="small text-success"><i class="bi bi-check-circle"></i> Your profile is complete. Thank you!</div>
            @endif
        </div>

        <div class="mp-card">
            <h2><i class="bi bi-info-circle"></i> What can I change?</h2>
            <ul class="small mb-0 ps-3 mt-2">
                <li class="mb-1"><strong>Straight away:</strong> phone, emails, address, emergency contact, next of kin, gender, marital status, photo.</li>
                <li class="mb-1"><i class="bi bi-shield-lock mp-lock"></i> <strong>Checked by HR:</strong> legal name, date of birth, NRC, passport, bank account and NAPSA / TPIN / NHIMA numbers. You'll get an email when HR approves.</li>
                <li><strong>HR only:</strong> job title, department, salary and contract dates.</li>
            </ul>
        </div>

        @if($requests->where('status', '!=', 'pending')->isNotEmpty())
            <div class="mp-card">
                <h2><i class="bi bi-clock-history"></i> Recent change requests</h2>
                @foreach($requests->where('status', '!=', 'pending') as $r)
                    <div class="border-top pt-2 mt-2 small">
                        <span class="badge {{ ['approved' => 'bg-success', 'rejected' => 'bg-danger', 'cancelled' => 'bg-secondary'][$r->status] ?? 'bg-secondary' }}">{{ ucfirst($r->status) }}</span>
                        {{ collect(array_keys($r->changes))->map(fn ($f) => F::label($f))->implode(', ') }}
                        <div class="text-muted">{{ optional($r->reviewed_at ?? $r->updated_at)->format('d M Y') }}{{ $r->review_note ? ' · “' . $r->review_note . '”' : '' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection
