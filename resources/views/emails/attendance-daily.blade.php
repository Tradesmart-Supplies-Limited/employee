@extends('emails.layouts.brand')

@php
    $t = $r['totals'];
    $tiles = [
        ['On time', $t['present'] + $t['half_day'], '#16a34a', '#f0fdf4'],
        ['Late', $t['late'], '#d97706', '#fffbeb'],
        ['Absent', $t['absent'], '#dc2626', '#fef2f2'],
        ['On leave', $t['on_leave'], '#7c3aed', '#f5f3ff'],
    ];
    $lists = [
        ['Absent', $r['absent'], '#dc2626', 'No scan and no approved leave.'],
        ['Late arrivals', $r['late'], '#d97706', null],
        ['Did not clock out', $r['missing_out'], '#0891b2', 'Clocked in but never scanned out. Hours could not be counted.'],
        ['Left before closing time', $r['early'], '#64748b', null],
        ['On leave', $r['on_leave'], '#7c3aed', null],
    ];
@endphp

@section('title', 'Attendance report')
@section('tag', 'Daily attendance')
@section('preheader', $t['attended'] . ' of ' . $t['expected'] . ' attended · ' . $t['late'] . ' late · ' . $t['absent'] . ' absent')
@section('heading', 'Attendance · ' . $r['date_label'])

@section('body')
    @if($test)
        <p style="margin:0 0 12px; padding:8px 12px; background:#fef9c3; border-radius:8px; font-size:12px;">This is a test of the daily report, showing data for {{ $r['date_label'] }}.</p>
    @endif
    @if($r['holiday'])
        <p style="margin:0 0 12px; color:#0284c7;"><strong>Public holiday:</strong> {{ $r['holiday'] }}</p>
    @elseif(! $r['working'])
        <p style="margin:0 0 12px; color:#64748b;">This was not a normal working day.</p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
        <tr>
            <td style="padding:14px 16px; background:#0f172a; border-radius:10px; color:#fff;">
                <div style="font-size:12px; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em;">Attendance rate</div>
                <div style="font-size:30px; font-weight:800;">{{ $t['rate'] !== null ? $t['rate'] . '%' : '—' }}</div>
                <div style="font-size:12px; color:#cbd5e1;">{{ $t['attended'] }} of {{ $t['expected'] }} expected staff came in{{ $t['punctual'] !== null ? ' · ' . $t['punctual'] . '% on time' : '' }}</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="6" style="margin:0 -6px 14px;">
        <tr>
            @foreach($tiles as [$label, $v, $c, $bg])
                <td width="25%" style="background:{{ $bg }}; border-radius:10px; padding:10px; text-align:center;">
                    <div style="font-size:22px; font-weight:800; color:{{ $c }};">{{ $v }}</div>
                    <div style="font-size:11px; color:#475569; font-weight:600;">{{ $label }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    @if(count($r['departments']) > 1)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden; margin-bottom:14px; font-size:12px;">
            <tr style="background:#f8fafc; color:#64748b; text-transform:uppercase; font-size:10px; letter-spacing:.05em;">
                <td style="padding:8px 10px; font-weight:700;">Department</td><td align="center" style="font-weight:700;">In</td><td align="center" style="font-weight:700;">Late</td><td align="center" style="font-weight:700;">Absent</td><td align="center" style="font-weight:700;">Leave</td><td align="right" style="padding-right:10px; font-weight:700;">Rate</td>
            </tr>
            @foreach($r['departments'] as $d)
                <tr>
                    <td style="padding:7px 10px; border-top:1px solid #f1f5f9; font-weight:600;">{{ $d['name'] }}</td>
                    <td align="center" style="border-top:1px solid #f1f5f9;">{{ $d['attended'] }}/{{ $d['expected'] }}</td>
                    <td align="center" style="border-top:1px solid #f1f5f9; color:#d97706;">{{ $d['late'] ?: '' }}</td>
                    <td align="center" style="border-top:1px solid #f1f5f9; color:#dc2626;">{{ $d['absent'] ?: '' }}</td>
                    <td align="center" style="border-top:1px solid #f1f5f9; color:#7c3aed;">{{ $d['on_leave'] ?: '' }}</td>
                    <td align="right" style="padding-right:10px; border-top:1px solid #f1f5f9; font-weight:700;">{{ $d['rate'] !== null ? round($d['rate']) . '%' : '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @foreach($lists as [$title, $items, $c, $hint])
        @if(count($items))
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden; margin-bottom:12px; font-size:12px;">
                <tr><td colspan="3" style="border-left:4px solid {{ $c }}; padding:8px 12px; background:#f8fafc; font-weight:700; color:{{ $c }}; text-transform:uppercase; font-size:11px; letter-spacing:.05em;">{{ $title }} ({{ count($items) }}){!! $hint ? '<div style="font-weight:400; text-transform:none; letter-spacing:0; color:#64748b;">' . e($hint) . '</div>' : '' !!}</td></tr>
                @foreach($items as $i)
                    <tr>
                        <td style="padding:7px 12px; border-top:1px solid #f1f5f9;"><strong>{{ $i['name'] }}</strong> <span style="color:#94a3b8;">{{ $i['ein'] }}{{ $i['dept'] ? ' · ' . $i['dept'] : '' }}</span></td>
                        <td style="padding:7px 6px; border-top:1px solid #f1f5f9; white-space:nowrap; color:#475569;">
                            @if($i['in']) in {{ $i['in'] }} @endif @if($i['out']) · out {{ $i['out'] }} @endif
                            @if($title === 'Late arrivals' && $i['late']) <strong style="color:#d97706;">+{{ $i['late'] }} min</strong> @endif
                        </td>
                        <td align="right" style="padding:7px 12px; border-top:1px solid #f1f5f9; color:#64748b;">{{ $i['note'] }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endforeach

    @include('emails.layouts.partials.button', ['url' => $r['url'], 'label' => 'Open attendance dashboard', 'margin' => '18px auto 6px'])
@endsection

@section('footnote')
    The full register for the day is attached as a spreadsheet (CSV). You get this email because you are on the attendance report list. HR can change the list under Attendance → Settings.
@endsection
