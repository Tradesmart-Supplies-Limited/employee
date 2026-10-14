{{-- Email-safe 4-step tracker (tables + inline styles only) --}}
@php
    $palette = [
        'done'      => ['bg' => '#16a34a', 'fg' => '#ffffff', 'mark' => '&#10003;', 'text' => '#15803d'],
        'current'   => ['bg' => '#f59e0b', 'fg' => '#ffffff', 'mark' => '&#8226;&#8226;&#8226;', 'text' => '#b45309'],
        'declined'  => ['bg' => '#dc2626', 'fg' => '#ffffff', 'mark' => '&#10005;', 'text' => '#b91c1c'],
        'skipped'   => ['bg' => '#cbd5e1', 'fg' => '#475569', 'mark' => '&#8211;', 'text' => '#64748b'],
        'cancelled' => ['bg' => '#e2e8f0', 'fg' => '#64748b', 'mark' => '&#8211;', 'text' => '#94a3b8'],
        'upcoming'  => ['bg' => '#e2e8f0', 'fg' => '#64748b', 'mark' => null,       'text' => '#94a3b8'],
    ];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px;">
    <tr>
        @foreach($steps as $i => $step)
            @php $p = $palette[$step['state']]; @endphp
            <td width="25%" align="center" valign="top" style="padding:16px 4px 14px;">
                <table role="presentation" cellpadding="0" cellspacing="0" align="center">
                    <tr>
                        <td width="34" height="34" align="center" valign="middle"
                            style="width:34px; height:34px; border-radius:17px; background:{{ $p['bg'] }}; color:{{ $p['fg'] }}; font-size:{{ $step['state'] === 'current' ? '9px' : '15px' }}; font-weight:700; line-height:34px;">
                            {!! $p['mark'] ?? ($i + 1) !!}
                        </td>
                    </tr>
                </table>
                <div style="font-size:12px; font-weight:700; color:#0f172a; margin-top:8px;">{{ $step['title'] }}</div>
                <div style="font-size:11px; color:{{ $p['text'] }}; margin-top:2px; font-weight:600;">{{ $step['note'] }}</div>
                @if($step['who'])
                    <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ $step['who'] }}</div>
                @endif
                @if($step['when'])
                    <div style="font-size:10px; color:#94a3b8; margin-top:1px;">{{ $step['when']->format('d M, H:i') }}</div>
                @endif
            </td>
        @endforeach
    </tr>
</table>
