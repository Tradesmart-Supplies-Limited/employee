@extends('layouts.app')
@section('title', 'Announcements')

@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <div class="hr-kicker">Communication</div>
        <h1 class="h4 fw-bold mb-0">Announcements</h1>
        <div class="small hr-muted">News for everyone or for specific departments. Staff see them in the employee portal, and can be emailed.</div>
    </div>
    <a href="{{ route('announcements.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New announcement</a>
</div>

<div class="d-flex gap-1 flex-wrap mb-3">
    @foreach(['' => 'All', 'live' => 'Live', 'scheduled' => 'Scheduled', 'expired' => 'Expired'] as $k => $l)
        <a href="{{ route('announcements.index', array_filter(['state' => $k])) }}" class="btn btn-sm {{ (string) $state === $k ? 'btn-primary' : 'btn-light border' }}" style="border-radius:999px">{{ $l }}</a>
    @endforeach
</div>

<div class="hr-card">
    @forelse($list as $a)
        @php [$cl, $ci, $cc] = $a->category_meta; [$pl, $pc, $pbg] = $a->priority_meta; @endphp
        <a href="{{ route('announcements.show', $a) }}" class="an-item">
            <span class="an-ic" style="--c:{{ $cc }}"><i class="bi {{ $ci }}"></i></span>
            <div style="min-width:0" class="flex-grow-1">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="an-title">@if($a->pinned)<i class="bi bi-pin-angle-fill text-primary"></i> @endif{{ $a->title }}</span>
                    @if($a->priority !== 'normal')<span class="hr-chip" style="--bg:{{ $pbg }};--c:{{ $pc }}">{{ $pl }}</span>@endif
                    @if($a->state === 'scheduled')<span class="hr-chip" style="--bg:#e0f2fe;--c:#075985"><i class="bi bi-clock"></i> {{ $a->local('publish_at')->format('d M H:i') }}</span>
                    @elseif($a->state === 'expired')<span class="hr-chip">Expired</span>@endif
                </div>
                <div class="small hr-muted text-truncate">{{ $a->excerpt }}</div>
                <div class="small hr-muted mt-1"><i class="bi bi-people"></i> {{ $a->audience_label }} · {{ $cl }} · {{ ($a->local('publish_at') ?? $a->created_at)->format('d M Y') }} · {{ $a->author?->name ?? 'HR' }}{{ $a->emailed_at ? ' · emailed to ' . $a->emailed_count : '' }}</div>
            </div>
            <div class="text-end small text-nowrap"><div class="fw-bold">{{ $a->reads_count }}</div><div class="hr-muted">read</div></div>
        </a>
    @empty
        <div class="hr-empty"><i class="bi bi-megaphone"></i>No announcements {{ $state ? 'in this view' : 'yet' }}.</div>
    @endforelse
</div>
@endsection
