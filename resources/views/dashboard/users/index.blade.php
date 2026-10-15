@extends('layouts.app')
@section('title', 'Users & Roles')

@section('content')
<style>
.ur-role { display: block; height: 100%; padding: .85rem 1rem; border-radius: 14px; border: 1px solid var(--admin-border, #e2e8f0); background: var(--admin-surface, #fff); text-decoration: none; color: inherit; border-top: 4px solid var(--c); transition: transform .12s, box-shadow .12s; }
.ur-role:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,23,42,.08); color: inherit; }
.ur-role.on { box-shadow: 0 0 0 2px var(--c) inset; }
.ur-role .n { font-size: 1.5rem; font-weight: 800; color: var(--c); line-height: 1; }
.ur-role .t { font-weight: 700; }
.ur-role .d { font-size: .74rem; color: var(--admin-muted, #64748b); line-height: 1.35; margin-top: .25rem; }
.ur-badge { display: inline-flex; align-items: center; gap: .3rem; font-size: .74rem; font-weight: 700; padding: .2rem .65rem; border-radius: 999px; color: var(--c); background: color-mix(in srgb, var(--c) 12%, transparent); white-space: nowrap; }
.ur-row { border-bottom: 1px solid var(--admin-border, #eef2f7); }
.ur-row:last-child { border-bottom: 0; }
.ur-row > summary { list-style: none; display: flex; align-items: center; gap: .8rem; padding: .8rem 1.1rem; cursor: pointer; flex-wrap: wrap; }
.ur-row > summary::-webkit-details-marker { display: none; }
.ur-row[open] > summary { background: color-mix(in srgb, var(--brand, #185FA5) 5%, transparent); }
.ur-av { width: 38px; height: 38px; border-radius: 50%; color: #fff; font-weight: 700; font-size: .8rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--c); }
.ur-edit { padding: 1rem 1.1rem 1.2rem; background: color-mix(in srgb, var(--brand, #185FA5) 3%, transparent); border-top: 1px dashed var(--admin-border, #e2e8f0); }
.ur-access { font-size: .74rem; color: var(--admin-muted, #64748b); }
.ur-access i { margin-right: .15rem; }
.ur-roles-pick { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .45rem; }
.ur-roles-pick label { display: flex; gap: .5rem; align-items: flex-start; border: 1px solid var(--admin-border, #e2e8f0); border-radius: 10px; padding: .5rem .6rem; cursor: pointer; font-size: .8rem; background: var(--admin-surface, #fff); }
.ur-roles-pick label:has(input:checked) { border-color: var(--c); box-shadow: 0 0 0 1px var(--c) inset; background: color-mix(in srgb, var(--c) 6%, var(--admin-surface, #fff)); }
.ur-roles-pick label.disabled { opacity: .45; cursor: not-allowed; }
.ur-roles-pick b { display: block; color: var(--c); }
</style>

@php
    $isAdmin = $me->roleKey() === 'admin';
    $empLabel = fn ($e) => $e->first_name . ' ' . $e->last_name . ' · ' . ($e->employee_id ?: 'no EIN') . ($e->department ? ' · ' . $e->department : '') . (strtolower((string) $e->employment_status) !== 'active' ? ' (' . ($e->employment_status ?: 'inactive') . ')' : '');
    $access = function ($u) {
        $bits = [];
        if ($u->hasFullAccess()) $bits[] = ['bi-shield-check', 'Full admin area'];
        elseif ($u->isManager()) $bits[] = ['bi-diagram-3', 'Admin area: ' . (implode(', ', $u->managedDepartments() ?? []) ?: 'no department yet')];
        if ($u->canUsePortal()) $bits[] = ['bi-person-badge', 'Self-service portal'];
        elseif (in_array($u->roleKey(), ['hr', 'manager', 'admin'], true)) $bits[] = ['bi-link-45deg', 'No portal (not linked to an employee)'];
        if ($u->isDirector()) $bits[] = ['bi-slash-circle', 'Not counted in attendance / leave reports'];
        if (! $u->roleKey()) $bits[] = ['bi-exclamation-triangle', 'No access'];
        return $bits;
    };
    $rolePicker = function (string $name, string $current, string $idPrefix) use ($roles, $isAdmin) {
        $out = '<div class="ur-roles-pick">';
        foreach ($roles as $key => $r) {
            $dis = ! $isAdmin && $key === 'admin';
            $out .= '<label class="' . ($dis ? 'disabled' : '') . '" style="--c:' . $r['color'] . '"><input type="radio" class="form-check-input mt-1" name="' . $name . '" value="' . $key . '" id="' . $idPrefix . $key . '"' . ($current === $key ? ' checked' : '') . ($dis ? ' disabled' : '') . ' required>'
                . '<span><b><i class="bi ' . $r['icon'] . '"></i> ' . e($r['label']) . '</b>' . e($r['desc']) . '</span></label>';
        }
        return $out . '</div>';
    };
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <div class="small fw-bold text-uppercase" style="color:var(--brand-text, #185FA5);letter-spacing:.08em">System</div>
        <h1 class="h4 fw-bold mb-0">Users &amp; Roles</h1>
        <div class="small text-muted">Who can sign in, and what each person can see and do.</div>
    </div>
    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#newUser" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}"><i class="bi bi-person-plus"></i> Add a login</button>
</div>

@if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}</div>@endif

{{-- role legend / filter --}}
<div class="row g-2 mb-3">
    @foreach($roles as $key => $r)
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('users.index', $role === $key ? [] : ['role' => $key]) }}" class="ur-role {{ $role === $key ? 'on' : '' }}" style="--c:{{ $r['color'] }}">
                <div class="d-flex justify-content-between align-items-start"><span class="t"><i class="bi {{ $r['icon'] }}"></i> {{ $r['label'] }}</span><span class="n">{{ $counts[$key] ?? 0 }}</span></div>
                <div class="d">{{ $r['desc'] }}</div>
            </a>
        </div>
    @endforeach
</div>

{{-- add a login --}}
<div class="collapse {{ $errors->any() ? 'show' : '' }}" id="newUser">
    <form method="POST" action="{{ route('users.store') }}" class="hr-card mb-3">
        @csrf
        <div class="hr-card-h"><h6><i class="bi bi-person-plus"></i> Add a login</h6><span class="small hr-muted">For a staff member's self-service login only, use <em>Create account</em> on their employee profile.</span></div>
        <div class="p-3">
            @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Employee record <span class="text-muted fw-normal">(optional for Admin / Director)</span></label>
                    <select name="employee_id" class="form-select form-select-sm" id="nuEmp">
                        <option value="">— Not an employee / no record —</option>
                        @foreach($employees as $e)
                            @unless(in_array($e->id, $linked))
                                <option value="{{ $e->id }}" data-name="{{ $e->first_name }} {{ $e->last_name }}" data-email="{{ $e->company_email ?: $e->personal_email }}" @selected(old('employee_id') == $e->id)>{{ $empLabel($e) }}</option>
                            @endunless
                        @endforeach
                    </select>
                    <div class="form-text">Linking gives HR and Managers their own portal, and sets a Manager's department.</div>
                </div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Name</label><input name="name" id="nuName" class="form-control form-control-sm" value="{{ old('name') }}" required></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Login email</label><input name="email" id="nuEmail" type="email" class="form-control form-control-sm" value="{{ old('email') }}" required></div>
                <div class="col-12"><label class="form-label small fw-semibold">Role</label>{!! $rolePicker('role', old('role', 'manager'), 'nr') !!}</div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Password <span class="text-muted fw-normal">(optional)</span></label>
                    <input name="password" type="text" minlength="8" class="form-control form-control-sm" autocomplete="new-password" placeholder="Leave blank to generate one">
                    <div class="form-text">Leave blank and a temporary password is emailed to them; they choose their own when they first sign in.</div>
                </div>
                <div class="col-md-7 d-flex align-items-end justify-content-end"><button class="btn btn-primary btn-sm"><i class="bi bi-check2"></i> Create login</button></div>
            </div>
        </div>
    </form>
</div>

<div class="hr-card">
    <div class="hr-card-h">
        <h6><i class="bi bi-people"></i> {{ $role ? $roles[$role]['label'] . ' logins' : 'All logins' }} <span class="hr-muted fw-normal">({{ $users->total() }})</span></h6>
        <form method="GET" class="d-flex gap-1">
            @if($role)<input type="hidden" name="role" value="{{ $role }}">@endif
            <input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Name, email, department…" style="max-width:220px">
            <button class="btn btn-sm btn-light border"><i class="bi bi-search"></i></button>
        </form>
    </div>

    @forelse($users as $u)
        @php
            $rk = $u->roleKey();
            $r = $roles[$rk] ?? ['label' => 'No role', 'color' => '#dc2626', 'icon' => 'bi-question-circle'];
            $locked = (! $isAdmin && $rk === 'admin');
            $self = $u->id === $me->id;
        @endphp
        <details class="ur-row" @if($errors->has('u' . $u->id)) open @endif>
            <summary>
                <span class="ur-av" style="--c:{{ $r['color'] }}">{{ strtoupper(collect(explode(' ', $u->name))->map(fn ($p) => substr($p, 0, 1))->take(2)->implode('')) }}</span>
                <div class="flex-grow-1" style="min-width:180px">
                    <div class="fw-semibold">{{ $u->name }} @if($self)<span class="small hr-muted">(you)</span>@endif</div>
                    <div class="small hr-muted text-break">{{ $u->email }}{{ $u->employee ? ' · ' . $u->employee->employee_id . ($u->employee->department ? ' · ' . $u->employee->department : '') : '' }}</div>
                    <div class="ur-access">@foreach($access($u) as [$ic, $tx])<span class="me-2"><i class="bi {{ $ic }}"></i>{{ $tx }}</span>@endforeach</div>
                </div>
                <span class="ur-badge" style="--c:{{ $r['color'] }}"><i class="bi {{ $r['icon'] }}"></i> {{ $r['label'] }}</span>
                <span class="btn btn-sm btn-light border">{{ $locked ? 'View' : 'Edit' }}</span>
            </summary>
            <div class="ur-edit">
                @if($locked)
                    <div class="small text-muted"><i class="bi bi-lock"></i> Only an Admin can change an Admin login.</div>
                @else
                    <form method="POST" action="{{ route('users.update', $u) }}">
                        @csrf @method('PUT')
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label small fw-semibold">Name</label><input name="name" class="form-control form-control-sm" value="{{ $u->name }}" required></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Login email</label><input name="email" type="email" class="form-control form-control-sm" value="{{ $u->email }}" required></div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Employee record</label>
                                <select name="employee_id" class="form-select form-select-sm">
                                    <option value="">— Not linked —</option>
                                    @foreach($employees as $e)
                                        @if($e->id === $u->employee_id || ! in_array($e->id, $linked))
                                            <option value="{{ $e->id }}" @selected($e->id === $u->employee_id)>{{ $empLabel($e) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Role @if($self)<span class="text-muted fw-normal">(you can't change your own role)</span>@endif</label>
                                @if($self)
                                    <input type="hidden" name="role" value="{{ $rk }}">
                                    <div><span class="ur-badge" style="--c:{{ $r['color'] }}"><i class="bi {{ $r['icon'] }}"></i> {{ $r['label'] }}</span></div>
                                @else
                                    {!! $rolePicker('role', $rk, 'r' . $u->id) !!}
                                @endif
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 mt-3 flex-wrap">
                            <button class="btn btn-primary btn-sm"><i class="bi bi-check2"></i> Save</button>
                        </div>
                    </form>
                    @unless($self)
                        <form method="POST" action="{{ route('users.reset', $u) }}" class="mt-3 pt-3 border-top d-flex align-items-center gap-2 flex-wrap" onsubmit="return confirm('Email {{ addslashes($u->name) }} a new temporary password? Their current password will stop working.')">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-envelope"></i> Email new login details</button>
                            <span class="small text-muted">{{ $u->must_change_password ? 'Hasn\'t chosen their own password yet.' : 'Sends a new temporary password to ' . $u->email . '.' }}</span>
                        </form>
                        <form method="POST" action="{{ route('users.destroy', $u) }}" class="mt-2 text-end" onsubmit="return confirm('Remove {{ addslashes($u->name) }}\'s login? They will no longer be able to sign in. Their employee record is kept.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-person-x"></i> Remove login</button>
                        </form>
                    @endunless
                @endif
            </div>
        </details>
    @empty
        <div class="hr-empty"><i class="bi bi-people"></i>No logins match.</div>
    @endforelse
</div>
<div class="mt-3">{{ $users->links() }}</div>

<script>
// Picking an employee fills in their name and email.
document.getElementById('nuEmp')?.addEventListener('change', function () {
    const o = this.selectedOptions[0];
    if (!o || !o.value) return;
    const n = document.getElementById('nuName'), m = document.getElementById('nuEmail');
    if (!n.value) n.value = o.dataset.name || '';
    if (!m.value) m.value = o.dataset.email || '';
});
</script>
@endsection
