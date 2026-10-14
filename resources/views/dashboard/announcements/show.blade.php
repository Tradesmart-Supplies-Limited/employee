@extends('layouts.app')
@section('title', $a->title)

@section('content')
@php
    [$cl, $ci, $cc] = $a->category_meta; [$pl, $pc, $pbg] = $a->priority_meta;
    $total = $audience->count(); $read = $audience->filter(fn ($e) => isset($reads[$e->id]))->count();
@endphp

<div class="mb-3">
    <a href="{{ route('announcements.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All announcements</a>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="hr-card">
            <div class="hr-card-b">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <span class="hr-chip" style="--bg:#eef2ff;--c:{{ $cc }}"><i class="bi {{ $ci }}"></i> {{ $cl }}</span>
                    @if($a->priority !== 'normal')<span class="hr-chip" style="--bg:{{ $pbg }};--c:{{ $pc }}">{{ $pl }}</span>@endif
                    @if($a->pinned)<span class="hr-chip" style="--bg:#dbeafe;--c:#1d4ed8"><i class="bi bi-pin-angle-fill"></i> Pinned</span>@endif
                    <span class="hr-chip" style="{{ $a->state === 'live' ? '--bg:#dcfce7;--c:#166534' : ($a->state === 'scheduled' ? '--bg:#e0f2fe;--c:#075985' : '') }}">{{ ucfirst($a->state) }}</span>
                </div>
                <h1 class="h4 fw-bold">{{ $a->title }}</h1>
                <div class="small hr-muted mb-3">
                    {{ $a->author?->name ?? 'HR' }} · {{ $a->state === 'scheduled' ? 'goes live' : 'posted' }} {{ ($a->local('publish_at') ?? $a->local('created_at'))->format('D d M Y, H:i') }}
                    @if($a->expires_at) · shown until {{ $a->local('expires_at')->format('d M Y H:i') }}@endif
                </div>
                <div class="an-body">{!! $a->body_html !!}</div>
                @if($a->attachment_name)
                    <a href="{{ route('announcements.attachment', $a) }}" class="btn btn-sm btn-outline-secondary mt-3"><i class="bi bi-paperclip"></i> {{ $a->attachment_name }}</a>
                @endif
            </div>
            <div class="hr-card-h border-top border-bottom-0">
                <span class="small hr-muted"><i class="bi bi-people"></i> {{ $a->audience_label }}{{ $a->emailed_at ? ' · emailed to ' . $a->emailed_count . ' on ' . $a->local('emailed_at')->format('d M H:i') : ($a->send_email ? ' · email when it goes live' : '') }}</span>
                <div class="d-flex gap-2">
                    <a href="{{ route('announcements.edit', $a) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <form method="POST" action="{{ route('announcements.destroy', $a) }}" onsubmit="return confirm('Delete this announcement? Employees will no longer see it.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="hr-card">
            <div class="hr-card-h"><h6><i class="bi bi-eye"></i> Read by {{ $read }} of {{ $total }}</h6><span class="small fw-bold">{{ $total ? round($read / $total * 100) : 0 }}%</span></div>
            <div class="px-3 pt-3"><div class="hr-bar"><span style="width:{{ $total ? $read / $total * 100 : 0 }}%;background:#16a34a"></span></div></div>
            <div style="max-height:520px; overflow:auto" class="mt-2">
                @forelse($audience as $e)
                    @php $r = $reads[$e->id] ?? null; @endphp
                    <div class="hr-list-item">
                        <span class="hr-av">@if($e->passport_photo)<img src="{{ asset('storage/' . $e->passport_photo) }}" alt="">@else{{ $e->initials }}@endif</span>
                        <div class="flex-grow-1" style="min-width:0"><div class="fw-semibold text-truncate">{{ $e->full_name }}</div><div class="small hr-muted">{{ $e->department ?? '—' }}</div></div>
                        @if($r)<span class="small text-success text-nowrap"><i class="bi bi-check2-all"></i> {{ $r->read_at->copy()->setTimezone(\App\Services\AttendanceService::tz())->format('d M H:i') }}</span>
                        @else<span class="small hr-muted">not yet</span>@endif
                    </div>
                @empty
                    <div class="hr-empty">No active employees in this audience.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
