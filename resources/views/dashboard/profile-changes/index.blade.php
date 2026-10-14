@extends('layouts.app')

@section('content')

@php use App\Support\EmployeeFields as F; @endphp

<style>
.pc-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; margin-bottom: 1rem; overflow: hidden; }
.pc-head { display: flex; gap: .8rem; align-items: center; padding: .9rem 1.1rem; border-bottom: 1px solid var(--admin-border, #e2e8f0); flex-wrap: wrap; }
.pc-av { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #185FA5, #4f8cff); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.pc-av img { width: 100%; height: 100%; object-fit: cover; }
.pc-table { margin: 0; font-size: .86rem; }
.pc-old { color: #94a3b8; text-decoration: line-through; }
.pc-new { font-weight: 700; color: #0f172a; }
.pc-foot { padding: .8rem 1.1rem; background: var(--admin-surface-soft, #f8fafc); display: flex; gap: .6rem; flex-wrap: wrap; align-items: flex-start; }
</style>

<div class="mb-3">
    <div class="text-uppercase fw-bold small" style="color:#185FA5; letter-spacing:.08em">Self-service</div>
    <h1 class="h4 fw-bold mb-0">Employee detail changes</h1>
    <div class="small text-muted">Sensitive details employees changed in the portal. Nothing changes on their record until you approve.</div>
</div>

@forelse($pending as $req)
    @php $e = $req->employee; @endphp
    <div class="pc-card" id="req-{{ $req->id }}">
        <div class="pc-head">
            <span class="pc-av">@if($e->passport_photo)<img src="{{ asset('storage/' . $e->passport_photo) }}" alt="">@else{{ $e->initials }}@endif</span>
            <div class="flex-grow-1">
                <a href="{{ route('employees.show', $e) }}" class="fw-bold text-decoration-none">{{ $e->full_name }}</a>
                <div class="small text-muted">{{ $e->employee_id }} · {{ $e->position ?? '—' }} · requested {{ $req->created_at->diffForHumans() }}</div>
            </div>
            <a href="{{ route('employees.show', $e) }}#documents" class="btn btn-sm btn-outline-secondary"><i class="bi bi-folder2-open"></i> Their documents ({{ $e->documents->count() }})</a>
        </div>

        <form method="POST" action="{{ route('profile-changes.approve', $req) }}" id="approve-{{ $req->id }}">
            @csrf
            <table class="table pc-table align-middle">
                <thead class="table-light"><tr><th style="width:40px"></th><th>Field</th><th>Current</th><th>Requested</th></tr></thead>
                <tbody>
                    @foreach($req->changes as $field => $c)
                        <tr>
                            <td><input type="checkbox" class="form-check-input" name="fields[]" value="{{ $field }}" checked aria-label="Approve {{ F::label($field) }}"></td>
                            <td class="text-muted">{{ F::label($field) }}</td>
                            <td class="pc-old">{{ $c['old'] ?? '—' }}</td>
                            <td class="pc-new">{{ $c['new'] ?? '(cleared)' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($req->note)
                <div class="px-3 pb-2 small"><i class="bi bi-chat-left-quote"></i> <strong>Employee's note:</strong> {{ $req->note }}</div>
            @endif
        </form>

        <div class="pc-foot">
            <input type="text" name="review_note" form="approve-{{ $req->id }}" class="form-control form-control-sm" style="max-width:320px" placeholder="Note to employee (optional)">
            <button class="btn btn-success btn-sm" form="approve-{{ $req->id }}"><i class="bi bi-check2"></i> Approve ticked changes</button>
            <form method="POST" action="{{ route('profile-changes.reject', $req) }}" class="d-flex gap-2 ms-auto flex-wrap">
                @csrf
                <input type="text" name="review_note" class="form-control form-control-sm" style="max-width:280px" placeholder="Reason for rejecting (required)" required>
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg"></i> Reject</button>
            </form>
        </div>
    </div>
@empty
    <div class="pc-card p-4 text-center text-muted"><i class="bi bi-check2-circle fs-2 text-success d-block"></i>No changes waiting for approval.</div>
@endforelse

@if($recent->isNotEmpty())
    <div class="pc-card">
        <div class="pc-head"><strong><i class="bi bi-clock-history"></i> Recently handled</strong></div>
        <div class="table-responsive">
            <table class="table pc-table">
                <thead class="table-light"><tr><th>Employee</th><th>Changes</th><th>Result</th><th>By</th><th>When</th></tr></thead>
                <tbody>
                    @foreach($recent as $r)
                        <tr>
                            <td>{{ $r->employee?->full_name }}</td>
                            <td class="small">{{ collect(array_keys($r->changes))->map(fn ($f) => F::label($f))->implode(', ') }}</td>
                            <td><span class="badge {{ ['approved' => 'bg-success', 'rejected' => 'bg-danger'][$r->status] ?? 'bg-secondary' }}">{{ ucfirst($r->status) }}</span>{!! $r->review_note ? '<div class="small text-muted">' . e($r->review_note) . '</div>' : '' !!}</td>
                            <td class="small">{{ $r->reviewer?->name ?? ($r->status === 'cancelled' ? 'Withdrawn by employee' : '—') }}</td>
                            <td class="small">{{ optional($r->reviewed_at ?? $r->updated_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
