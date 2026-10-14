@extends('emails.layouts.brand')

@php
    $changed = fn ($i) => $old && (($old[$i]['code'] ?? '') !== ($week[$i]['code'] ?? ''));
    $working = collect($week)->whereIn('kind', ['shift'])->count();
@endphp

@section('title', 'Your shifts')
@section('tag', 'Shift rota · ' . $rota->department)
@section('preheader', $working . ' shift(s) for the week of ' . $rota->week_start->format('d M'))
@section('heading', $first ? 'Your shifts for the week' : 'Your rota has changed')

@section('body')
    <p style="margin:0 0 12px;">Hi {{ $employee->first_name }},</p>
    <p style="margin:0 0 14px;">
        @if($first)
            The {{ $rota->department }} rota for <strong>{{ $rota->week_start->format('l d M') }} – {{ $rota->week_end->format('l d M Y') }}</strong> has been approved. Please report for work on the days below.
        @else
            The {{ $rota->department }} rota for {{ $rota->week_start->format('d M') }} – {{ $rota->week_end->format('d M Y') }} was updated. Days that changed are highlighted.
        @endif
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden; font-size:13px;">
        @foreach($week as $i => $d)
            <tr style="{{ $changed($i) ? 'background:#fef9c3;' : '' }}">
                <td style="padding:9px 12px; border-top:{{ $i ? '1px solid #f1f5f9' : '0' }}; width:34%;">
                    <strong>{{ $d['date']->format('l') }}</strong><br><span style="color:#64748b;">{{ $d['date']->format('d M') }}{{ $d['holiday'] ? ' · ' . $d['holiday'] : '' }}</span>
                </td>
                <td style="padding:9px 12px; border-top:{{ $i ? '1px solid #f1f5f9' : '0' }};">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{ $d['color'] }}; margin-right:6px;"></span>
                    <strong style="color:{{ $d['kind'] === 'none' ? '#94a3b8' : '#0f172a' }};">{{ $d['kind'] === 'none' ? 'Not on the rota' : $d['label'] }}</strong>
                    @if($d['time']) <span style="color:#475569;">{{ $d['time'] }}</span>@endif
                    @if($d['overtime'] && $d['kind'] === 'shift')<br><span style="color:#b45309; font-size:12px;">{{ $d['holiday'] ? 'Public holiday' : 'Sunday' }}: paid as overtime</span>@endif
                    @if($changed($i))<br><span style="color:#92400e; font-size:12px;">Was: {{ ($old[$i]['kind'] ?? 'none') === 'none' ? 'not on the rota' : $old[$i]['label'] . ($old[$i]['time'] ? ' ' . $old[$i]['time'] : '') }}</span>@endif
                </td>
            </tr>
        @endforeach
    </table>

    @if($rota->notes)
        <div style="margin-top:14px; border-left:4px solid #185FA5; background:#f8fafc; padding:10px 14px; border-radius:6px; font-size:13px;"><strong>Note from your supervisor:</strong> {!! nl2br(e($rota->notes)) !!}</div>
    @endif

    @include('emails.layouts.partials.button', ['url' => $url, 'label' => 'See the full rota'])
@endsection

@section('footnote')
    Backup means you're on call: be reachable and ready to come in if asked. Can't make a shift? Tell your supervisor as early as possible. Scan your QR code at the entrance when you arrive and leave.
@endsection
