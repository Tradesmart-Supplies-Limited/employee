@extends($layout)
@section('title', 'Shift rota')

@section('content')
@if(! $portal)
    @include('dashboard.attendance.partials.nav', ['title' => 'Shift rota', 'subtitle' => 'Plan each week, then approve &amp; publish. Staff are emailed their shifts and see them in the portal.'])
@else
    <div class="mb-3">
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-week"></i> Team rota</h4>
        <small class="text-muted">Plan your team's shifts each week, then approve &amp; publish. Your team is emailed and sees it in the portal.</small>
    </div>
@endif

<style>
.rw-item { display: flex; align-items: center; gap: .9rem; padding: .85rem 1.1rem; border-bottom: 1px solid var(--admin-border, #eef2f7); flex-wrap: wrap; }
.rw-item:last-child { border-bottom: 0; }
.rw-item.now { background: color-mix(in srgb, #185FA5 6%, transparent); }
.rw-date { width: 64px; text-align: center; line-height: 1.1; flex-shrink: 0; }
.rw-date b { font-size: 1.3rem; display: block; }
.rw-date small { font-size: .68rem; text-transform: uppercase; font-weight: 700; color: var(--admin-muted, #64748b); }
</style>

@if(! $enabled)
    <div class="alert alert-warning"><i class="bi bi-toggle-off"></i> Weekly rotas are switched off. @unless($portal)Turn them on under <a href="{{ route('attendance.settings') }}">Attendance → Settings → Shift rota</a>.@endunless</div>
@elseif(! $departments)
    <div class="alert alert-info"><i class="bi bi-info-circle"></i> No department uses a rota yet. @unless($portal)Pick the departments under <a href="{{ route('attendance.settings') }}">Attendance → Settings → Shift rota</a> (e.g. Operations).@endunless</div>
@else
    @if(count($departments) > 1)
        <div class="d-flex gap-1 flex-wrap mb-3">
            @foreach($departments as $d)
                <a href="{{ $rr('index', ['department' => $d]) }}" class="btn btn-sm {{ $d === $department ? 'btn-primary' : 'btn-light border' }}" style="border-radius:999px"><i class="bi bi-diagram-3"></i> {{ $d }}</a>
            @endforeach
        </div>
    @endif

    <div class="hr-card">
        <div class="hr-card-h"><h6><i class="bi bi-calendar-week"></i> {{ $department }} · weekly rotas</h6><span class="small hr-muted">Mon → Sun</span></div>
        @foreach($weeks as $w)
            @php
                $r = $existing[$w->toDateString()] ?? null;
                $isNow = $w->equalTo($thisWeek);
                $past = $w->lt($thisWeek);
                $pend = in_array($w->toDateString(), $pending, true);
            @endphp
            <div class="rw-item {{ $isNow ? 'now' : '' }}">
                <div class="rw-date"><b>{{ $w->format('d') }}</b><small>{{ $w->format('M') }}</small></div>
                <div class="flex-grow-1" style="min-width:180px">
                    <div class="fw-semibold">{{ $w->format('D d M') }} – {{ $w->copy()->addDays(6)->format('D d M Y') }} @if($isNow)<span class="hr-chip" style="--bg:#dbeafe;--c:#1d4ed8">This week</span>@endif</div>
                    <div class="small hr-muted">
                        @if(! $r) Not started
                        @elseif($r->is_published) Published{{ $r->version > 1 ? ' (v' . $r->version . ')' : '' }} {{ $r->published_at?->diffForHumans() }} · {{ $r->shifts_count }} shift(s)
                        @else Draft · {{ $r->shifts_count }} shift(s) planned @endif
                    </div>
                </div>
                @if($r)
                    @if($r->is_published && ! $pend)<span class="hr-chip" style="--bg:#dcfce7;--c:#166534"><i class="bi bi-check2-circle"></i> Published</span>
                    @elseif($pend)<span class="hr-chip" style="--bg:#fef3c7;--c:#92400e"><i class="bi bi-pencil"></i> Changes not published</span>
                    @else<span class="hr-chip" style="--bg:#f1f5f9;--c:#475569"><i class="bi bi-file-earmark"></i> Draft</span>@endif
                    <a href="{{ $rr('edit', $r) }}" class="btn btn-sm {{ $past ? 'btn-light border' : 'btn-outline-primary' }}">{{ $past ? 'View' : 'Open' }}</a>
                @elseif(! $past)
                    <form method="POST" action="{{ $rr('store') }}" class="d-flex gap-1">
                        @csrf
                        <input type="hidden" name="department" value="{{ $department }}"><input type="hidden" name="week" value="{{ $w->toDateString() }}"><input type="hidden" name="copy" value="1">
                        <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Create rota</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
    <div class="small hr-muted mt-2"><i class="bi bi-info-circle"></i> A new week starts as a copy of the week before (or Day shift on working days if there's nothing to copy).</div>
@endif
@endsection
