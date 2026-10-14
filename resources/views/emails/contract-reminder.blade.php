@extends('emails.layouts.brand')

@php
    $sections = [
        'expired'  => ['Ended, employee still Active: action needed', '#dc2626', '#fef2f2'],
        'critical' => ['Ending within 7 days', '#ea580c', '#fff7ed'],
        'soon'     => ['Ending in 8–30 days', '#d97706', '#fffbeb'],
        'upcoming' => ['Ending later', '#0284c7', '#f0f9ff'],
    ];
    $total = $groups->flatten(1)->count();
@endphp

@section('title', 'Contract & probation reminder')
@section('tag', 'Contract reminders')
@section('preheader', $label)

@section('heading', $label)

@section('body')
    <p style="margin:0 0 16px;">
        @if($kind === 'supervisor')
            These people in your team have a contract or probation period ending soon. HR has also been notified.
        @else
            {{ $total }} contract / probation date(s) need attention. Please renew, extend, confirm or close them off in time.
        @endif
    </p>

    @foreach($sections as $state => [$title, $color, $bg])
        @if(isset($groups[$state]) && count($groups[$state]))
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden; margin-bottom:14px;">
                <tr><td colspan="4" style="background:{{ $bg }}; border-left:4px solid {{ $color }}; padding:9px 12px; font-size:12px; font-weight:700; color:{{ $color }}; text-transform:uppercase; letter-spacing:.05em;">{{ $title }} ({{ count($groups[$state]) }})</td></tr>
                @foreach($groups[$state] as $r)
                    <tr>
                        <td style="padding:9px 12px; border-top:1px solid #f1f5f9; font-size:13px;">
                            <a href="{{ $r['url'] }}" style="color:#0f172a; font-weight:700; text-decoration:none;">{{ $r['name'] }}</a>
                            <div style="color:#94a3b8; font-size:11px;">{{ $r['ein'] }}{{ $r['position'] ? ' · ' . $r['position'] : '' }}{{ $r['department'] ? ' · ' . $r['department'] : '' }}</div>
                        </td>
                        <td style="padding:9px 6px; border-top:1px solid #f1f5f9; font-size:11px;">
                            <span style="display:inline-block; padding:2px 7px; border-radius:6px; font-weight:700; text-transform:uppercase; {{ $r['type'] === 'probation' ? 'background:#ede9fe;color:#5b21b6' : 'background:#e0f2fe;color:#075985' }}">{{ $r['type'] }}</span>
                        </td>
                        <td style="padding:9px 6px; border-top:1px solid #f1f5f9; font-size:12px; color:#374151; white-space:nowrap;">{{ \Carbon\Carbon::parse($r['end'])->format('D d M Y') }}</td>
                        <td align="right" style="padding:9px 12px; border-top:1px solid #f1f5f9; font-size:13px; font-weight:800; color:{{ $color }}; white-space:nowrap;">
                            {{ $r['days_left'] < 0 ? abs($r['days_left']) . 'd ago' : ($r['days_left'] === 0 ? 'today' : $r['days_left'] . 'd') }}
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endforeach

    @if($kind !== 'supervisor')
        @include('emails.layouts.partials.button', ['url' => $settings, 'label' => 'Open the reminders board'])
    @endif
@endsection

@section('footnote')
    @if($kind === 'test')
        This is a test preview. No reminders were marked as sent.
    @else
        You'll only be reminded once per date and threshold. To stop reminders for someone, update their end date (renewal) or set their status to Exited.
    @endif
@endsection
