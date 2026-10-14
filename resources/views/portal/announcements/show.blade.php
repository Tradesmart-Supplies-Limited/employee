@extends('layouts.portal')
@section('title', $a->title)

@section('content')
@php [$cl, $ci, $cc] = $a->category_meta; [$pl, $pc, $pbg] = $a->priority_meta; @endphp

<a href="{{ route('portal.announcements.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All announcements</a>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <article class="hr-card hr-card-b">
            <div class="d-flex gap-2 flex-wrap mb-2">
                <span class="hr-chip" style="--bg:#eef2ff;--c:{{ $cc }}"><i class="bi {{ $ci }}"></i> {{ $cl }}</span>
                @if($a->priority !== 'normal')<span class="hr-chip" style="--bg:{{ $pbg }};--c:{{ $pc }}">{{ $pl }}</span>@endif
            </div>
            <h1 class="h4 fw-bold">{{ $a->title }}</h1>
            <div class="small text-muted mb-3">From {{ $a->author?->name ?? 'HR' }} · {{ ($a->local('publish_at') ?? $a->local('created_at'))->format('l d F Y, H:i') }}{{ $a->departments ? ' · for ' . $a->audience_label : '' }}</div>
            <div class="an-body">{!! $a->body_html !!}</div>
            @if($a->attachment_name)
                <a href="{{ route('portal.announcements.attachment', $a) }}" class="btn btn-outline-primary btn-sm mt-3"><i class="bi bi-paperclip"></i> {{ $a->attachment_name }}</a>
            @endif
        </article>
    </div>
    <div class="col-lg-4">
        <div class="hr-card">
            <div class="hr-card-h"><h6>More announcements</h6></div>
            @forelse($more as $m)
                @php [, $mi, $mc] = $m->category_meta; @endphp
                <a href="{{ route('portal.announcements.show', $m) }}" class="an-item {{ isset($read[$m->id]) ? '' : 'unread' }}">
                    <span class="an-ic" style="--c:{{ $mc }}"><i class="bi {{ $mi }}"></i></span>
                    <div style="min-width:0"><div class="an-title text-truncate">{{ $m->title }}</div><div class="small text-muted">{{ ($m->local('publish_at') ?? $m->local('created_at'))->format('d M Y') }}</div></div>
                </a>
            @empty
                <div class="hr-empty">That's everything.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
