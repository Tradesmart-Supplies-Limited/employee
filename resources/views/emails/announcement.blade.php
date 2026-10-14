@extends('emails.layouts.brand')

@php [$cat, , $c] = $a->category_meta; [$pl, $pc, $pbg] = $a->priority_meta; @endphp

@section('title', $a->title)
@section('tag', 'Announcement')
@section('preheader', $a->excerpt)
@section('heading', $a->title)

@section('body')
    <p style="margin:0 0 14px;">
        <span style="display:inline-block; font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; background:#eef2ff; color:{{ $c }};">{{ $cat }}</span>
        @if($a->priority !== 'normal')<span style="display:inline-block; font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; background:{{ $pbg }}; color:{{ $pc }};">{{ $pl }}</span>@endif
        <span style="font-size:12px; color:#64748b;">&nbsp;For: {{ $a->audience_label }}</span>
    </p>
    <p style="margin:0 0 6px;">Dear {{ $employee->first_name }},</p>
    <div style="font-size:14px; line-height:1.7; color:#1f2937;">{!! $a->body_html !!}</div>
    @if($a->attachment_name)
        <p style="margin:14px 0 0; font-size:13px;">📎 Attachment: <strong>{{ $a->attachment_name }}</strong> (open it in the portal)</p>
    @endif
    @include('emails.layouts.partials.button', ['url' => $url, 'label' => 'Read in the employee portal'])
    <p style="margin:0; font-size:13px; color:#64748b;">From {{ $a->author?->name ?? 'HR' }}{{ $a->expires_at ? ' · relevant until ' . $a->expires_at->format('d M Y') : '' }}</p>
@endsection

@section('footnote')
    You get announcement emails as a member of staff. All announcements are also on your employee portal under Announcements.
@endsection
