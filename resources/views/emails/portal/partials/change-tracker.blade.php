{{-- 3-step tracker. $stage: submitted | review | approved | rejected | withdrawn --}}
@php
    $final = in_array($stage, ['approved', 'rejected', 'withdrawn'], true);
    $steps = [
        ['Submitted', true, '#16a34a'],
        ['HR review', $stage !== 'submitted', $stage === 'review' ? '#d97706' : '#16a34a'],
        [$final ? ucfirst($stage) : 'Decision', $final, ['approved' => '#16a34a', 'rejected' => '#dc2626', 'withdrawn' => '#64748b'][$stage] ?? '#cbd5e1'],
    ];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:4px 0 18px;">
    <tr>
        @foreach($steps as $i => [$label, $on, $color])
            <td align="center" style="width:33%; vertical-align:top;">
                <div style="width:30px; height:30px; line-height:30px; border-radius:50%; margin:0 auto; font-size:14px; font-weight:800; color:#fff; background:{{ $on ? $color : '#e2e8f0' }};">{{ $on ? ($i === 2 && $stage === 'rejected' ? '✕' : '✓') : $i + 1 }}</div>
                <div style="font-size:11px; margin-top:5px; font-weight:700; color:{{ $on ? '#0f172a' : '#94a3b8' }};">{{ $label }}</div>
            </td>
        @endforeach
    </tr>
</table>
