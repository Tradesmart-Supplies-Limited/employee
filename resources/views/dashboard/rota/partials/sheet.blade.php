{{-- TSL shift-schedule layout: one column per day, people grouped by shift / backup / off.
     Needs: $rota, $cells [emp][date] => code, $people (keyed), $shifts (keyed), $holidays; optional $highlight (employee id). --}}
@php
    use App\Services\ShiftSchedule;
    $highlight = $highlight ?? null;
    $group = function ($d) use ($cells, $people) {
        $g = [];
        foreach ($cells as $emp => $days) {
            $code = $days[$d] ?? '';
            if ($code !== '' && isset($people[$emp])) {
                $g[$code][] = $people[$emp];
            }
        }
        return $g;
    };
@endphp
<style>
.rs-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.rs { width: 100%; min-width: 860px; border-collapse: collapse; table-layout: fixed; font-size: .8rem; }
.rs th { background: #c9daf8; color: #0f172a; padding: .45rem .4rem; text-align: center; border: 1px solid #94a3b8; font-weight: 800; }
.rs th small { display: block; font-weight: 600; color: #334155; }
.rs th.hol { background: #ff9900; color: #fff; } .rs th.hol small { color: #fff; }
.rs th.sun { background: #e2e8f0; }
.rs td { vertical-align: top; border: 1px solid #cbd5e1; padding: 0; background: #fff; }
.rs .blk-h { padding: .25rem .4rem; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #fff; background: var(--c); }
.rs .blk-h.bk { background: #f1c232; color: #3f2d00; }
.rs .blk-h.off { background: #e2e8f0; color: #475569; }
.rs .blk-h.hol { background: #ff9900; }
.rs .nm { padding: .2rem .45rem; border-bottom: 1px dashed #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #0f172a; }
.rs .nm.me { background: #dbeafe; font-weight: 800; }
.rs .none { padding: .5rem; color: #94a3b8; font-style: italic; }
html[data-theme="dark"] .rs td { background: #0f172a; } html[data-theme="dark"] .rs .nm { color: #e2e8f0; }
</style>
<div class="rs-wrap">
<table class="rs">
    <thead><tr>
        @foreach($rota->days as $d)
            @php $hol = $holidays[$d->toDateString()] ?? null; @endphp
            <th class="{{ $hol ? 'hol' : ($d->isSunday() ? 'sun' : '') }}">{{ strtoupper($d->format('l')) }}<small>{{ $d->format('d M Y') }}{{ $hol ? ' · ' . $hol : '' }}</small></th>
        @endforeach
    </tr></thead>
    <tbody><tr>
        @foreach($rota->days as $d)
            @php
                $ds = $d->toDateString();
                $g = $group($ds);
                $dt = ShiftSchedule::dayType($d, $holidays);
            @endphp
            <td>
                @foreach($shifts as $sid => $sh)
                    @if(! empty($g['s:' . $sid]))
                        @php [$st, $en] = $sh->timesOrWeekday($dt); @endphp
                        <div class="blk-h {{ $dt === 'holiday' ? 'hol' : '' }}" style="--c:{{ $sh->color }}">
                            {{ $dt === 'holiday' ? 'Public holiday · ' : ($dt === 'sunday' ? 'Sunday · ' : ($dt === 'saturday' ? 'Saturday · ' : '')) }}{{ $sh->name }}{{ $st ? " {$st}–{$en}" : '' }}
                        </div>
                        @foreach($g['s:' . $sid] as $p)<div class="nm {{ $highlight === $p->id ? 'me' : '' }}" title="{{ $p->full_name }}">{{ $p->full_name }}</div>@endforeach
                    @endif
                @endforeach
                @if(! empty($g['backup']))
                    <div class="blk-h bk">Backup</div>
                    @foreach($g['backup'] as $p)<div class="nm {{ $highlight === $p->id ? 'me' : '' }}">{{ $p->full_name }}</div>@endforeach
                @endif
                @if(! empty($g['off']))
                    <div class="blk-h off">Day off</div>
                    @foreach($g['off'] as $p)<div class="nm {{ $highlight === $p->id ? 'me' : '' }}">{{ $p->full_name }}</div>@endforeach
                @endif
                @if(! $g)<div class="none">{{ $d->isSunday() ? 'Off' : 'Nobody rostered' }}</div>@endif
            </td>
        @endforeach
    </tr></tbody>
</table>
</div>
