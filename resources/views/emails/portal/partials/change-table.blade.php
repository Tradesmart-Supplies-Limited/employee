{{-- $rows: [field => [old, new]], $title, $color --}}
@php use App\Support\EmployeeFields as F; @endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden; margin-bottom:14px;">
    <tr><td colspan="3" style="border-left:4px solid {{ $color }}; background:#f8fafc; padding:8px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:{{ $color }};">{{ $title }}</td></tr>
    @foreach($rows as $field => $c)
        @php $mask = ! empty(F::get($field)['mask']); @endphp
        <tr>
            <td style="padding:8px 12px; border-top:1px solid #f1f5f9; font-size:13px; color:#64748b; width:34%;">{{ F::label($field) ?: ucfirst(str_replace('_', ' ', $field)) }}</td>
            <td style="padding:8px 12px; border-top:1px solid #f1f5f9; font-size:13px; color:#94a3b8; text-decoration:line-through;">{{ $mask ? F::mask($c['old']) : ($c['old'] ?? '—') }}</td>
            <td style="padding:8px 12px; border-top:1px solid #f1f5f9; font-size:13px; color:#0f172a; font-weight:700;">{{ $mask ? F::mask($c['new']) : ($c['new'] ?? '—') }}</td>
        </tr>
    @endforeach
</table>
