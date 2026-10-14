@extends('layouts.portal')
@section('title', 'Announcements')

@section('content')
@php use App\Models\Announcement as A; $unread = $all->reject(fn ($a) => isset($read[$a->id]))->count(); @endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-megaphone"></i> Announcements</h4>
        <small class="text-muted">News from HR for {{ auth()->user()->employee?->department ? 'everyone and ' . auth()->user()->employee->department : 'all staff' }}.{{ $unread ? ' ' . $unread . ' unread.' : '' }}</small>
    </div>
    @if($unread)
        <form method="POST" action="{{ route('portal.announcements.read-all') }}">@csrf<button class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2-all"></i> Mark all as read</button></form>
    @endif
</div>

<div class="d-flex gap-1 flex-wrap mb-3">
    <a href="{{ route('portal.announcements.index') }}" class="btn btn-sm {{ ! $cat ? 'btn-primary' : 'btn-light border' }}" style="border-radius:999px">All ({{ $all->count() }})</a>
    @foreach($all->groupBy('category') as $k => $g)
        <a href="{{ route('portal.announcements.index', ['category' => $k]) }}" class="btn btn-sm {{ $cat === $k ? 'btn-primary' : 'btn-light border' }}" style="border-radius:999px">{{ A::CATEGORIES[$k][0] ?? $k }} ({{ $g->count() }})</a>
    @endforeach
</div>

<div class="hr-card">
    @forelse($list as $a)
        @php [$cl, $ci, $cc] = $a->category_meta; [$pl, $pc, $pbg] = $a->priority_meta; $new = ! isset($read[$a->id]); @endphp
        <a href="{{ route('portal.announcements.show', $a) }}" class="an-item {{ $new ? 'unread' : '' }}">
            <span class="an-ic" style="--c:{{ $cc }}"><i class="bi {{ $ci }}"></i></span>
            <div style="min-width:0" class="flex-grow-1">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="an-title {{ $new ? '' : 'fw-semibold' }}">@if($a->pinned)<i class="bi bi-pin-angle-fill text-primary"></i> @endif{{ $a->title }}</span>
                    @if($a->priority !== 'normal')<span class="hr-chip" style="--bg:{{ $pbg }};--c:{{ $pc }}">{{ $pl }}</span>@endif
                    @if($new)<span class="hr-chip" style="--bg:#dbeafe;--c:#1d4ed8">New</span>@endif
                </div>
                <div class="small text-muted mt-1" style="overflow-wrap:anywhere">{{ $a->excerpt }}</div>
                <div class="small text-muted mt-1">{{ $cl }} · {{ ($a->local('publish_at') ?? $a->local('created_at'))->format('d M Y') }}{{ $a->attachment_name ? ' · 📎' : '' }}{{ $a->departments ? ' · ' . $a->audience_label : '' }}</div>
            </div>
        </a>
    @empty
        <div class="hr-empty"><i class="bi bi-megaphone"></i>No announcements right now.</div>
    @endforelse
</div>
@endsection
