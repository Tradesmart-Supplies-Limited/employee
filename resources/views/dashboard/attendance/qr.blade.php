@extends('layouts.app')
@section('title', 'QR & kiosks')

@section('content')
@php
    $newKiosk = session('new_kiosk');
    $actions = '<a href="' . route('attendance.badges') . '" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-printer"></i> Print all badges</a>';
@endphp

@include('dashboard.attendance.partials.nav', ['title' => 'QR codes & entrance kiosks', 'subtitle' => 'Set up the tablet at the door, and give every employee their QR code.', 'actions' => $actions])

<style>
.kq-step { display: flex; gap: .8rem; align-items: flex-start; font-size: .86rem; }
.kq-step .n { width: 26px; height: 26px; border-radius: 50%; background: var(--brand, #185FA5); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .8rem; }
.kq-kiosk { padding: 1rem 1.1rem; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.kq-kiosk.hl { background: #eff6ff; }
.kq-link { font-family: ui-monospace, Consolas, monospace; font-size: .76rem; background: var(--admin-surface-soft, #f1f5f9); border-radius: 8px; padding: .4rem .6rem; word-break: break-all; }
.kq-qr svg, .kq-mini svg { display: block; width: 100%; height: auto; }
.kq-qr { width: 120px; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; flex-shrink: 0; }
.kq-mini { width: 46px; background: #fff; padding: 2px; border-radius: 6px; border: 1px solid #e2e8f0; }
</style>

@unless($secure)
    <div class="alert alert-warning small d-flex gap-2">
        <i class="bi bi-shield-exclamation fs-5"></i>
        <div><strong>Heads-up for the tablet camera.</strong> Browsers only allow the camera on <strong>https://</strong> sites (or on <code>localhost</code>). This system is opened at <code>{{ request()->getSchemeAndHttpHost() }}</code>, so on the tablet either
            (1) open the kiosk link over HTTPS (switch on SSL in XAMPP Apache, or use your domain with a certificate), or
            (2) in Chrome on the tablet open <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code>, add <code>{{ request()->getSchemeAndHttpHost() }}</code>, choose <em>Enabled</em> and restart Chrome.
            A USB or Bluetooth QR scanner plugged into the tablet works either way.</div>
    </div>
@endunless

<div class="row g-3">
    <div class="col-xl-6">
        <div class="at-card mb-3">
            <div class="at-card-h"><h6><i class="bi bi-tablet-landscape"></i> Entrance kiosks</h6><span class="small at-muted">{{ $kiosks->count() }} device(s)</span></div>

            @foreach($kiosks as $k)
                <div class="kq-kiosk {{ $newKiosk == $k->id ? 'hl' : '' }}">
                    <div class="d-flex gap-3">
                        <div class="kq-qr" data-qr="{{ $k->url }}" title="Scan with the tablet's camera to open the kiosk"></div>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <strong>{{ $k->name }}</strong>
                                @if(! $k->is_active)<span class="badge bg-secondary">Switched off</span>
                                @elseif($k->is_online)<span class="badge" style="background:#dcfce7;color:#166534"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> Online</span>
                                @else<span class="badge" style="background:#fee2e2;color:#991b1b">Offline</span>@endif
                                @if($k->capture_photo)<span class="badge bg-light text-secondary border" title="Takes a photo at each scan"><i class="bi bi-camera"></i> Photo</span>@endif
                            </div>
                            <div class="small at-muted">{{ $k->location ?: 'No location' }} · {{ $k->last_seen_at ? 'last seen ' . $k->last_seen_at->diffForHumans() : 'never opened' }}</div>
                            <div class="kq-link mt-2" id="kl{{ $k->id }}">{{ $k->url }}</div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy="kl{{ $k->id }}"><i class="bi bi-clipboard"></i> Copy link</button>
                                <a href="{{ $k->url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i> Open</a>
                                <form method="POST" action="{{ route('attendance.kiosks.update', $k) }}">@csrf @method('PUT')<input type="hidden" name="toggle_photo" value="1"><button class="btn btn-sm btn-outline-secondary" title="Photo at each scan"><i class="bi bi-camera{{ $k->capture_photo ? '' : '-video-off' }}"></i></button></form>
                                <form method="POST" action="{{ route('attendance.kiosks.update', $k) }}">@csrf @method('PUT')<input type="hidden" name="toggle" value="1"><button class="btn btn-sm btn-outline-{{ $k->is_active ? 'warning' : 'success' }}">{{ $k->is_active ? 'Switch off' : 'Switch on' }}</button></form>
                                <form method="POST" action="{{ route('attendance.kiosks.regenerate', $k) }}" onsubmit="return confirm('Create a new link? The current link stops working and the tablet must open the new one.')">@csrf<button class="btn btn-sm btn-outline-secondary" title="If the link leaked"><i class="bi bi-key"></i> New link</button></form>
                                <form method="POST" action="{{ route('attendance.kiosks.destroy', $k) }}" onsubmit="return confirm('Delete kiosk {{ addslashes($k->name) }}? Its scans stay in the records.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <form method="POST" action="{{ route('attendance.kiosks.store') }}" class="p-3 {{ $kiosks->isEmpty() ? '' : 'border-top' }}">
                @csrf
                <div class="small fw-bold mb-2"><i class="bi bi-plus-circle"></i> Add a kiosk</div>
                <div class="row g-2">
                    <div class="col-sm-5"><input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="Name, e.g. Main entrance" required maxlength="80" value="{{ old('name') }}"></div>
                    <div class="col-sm-4"><input type="text" name="location" class="form-control form-control-sm" placeholder="Location (optional)" maxlength="120" value="{{ old('location') }}"></div>
                    <div class="col-sm-3"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i> Create</button></div>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="capture_photo" value="0">
                    <input class="form-check-input" type="checkbox" name="capture_photo" value="1" id="cp" checked>
                    <label class="form-check-label small" for="cp">Take a photo at each scan (stops people scanning a colleague's badge)</label>
                </div>
            </form>
        </div>

        <div class="at-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-signpost-split"></i> Setting up the entrance tablet</h6>
            <div class="d-grid gap-3">
                <div class="kq-step"><span class="n">1</span><div>Create a kiosk above, then open its link on the tablet: scan the kiosk's square code with the tablet camera, or copy the link into Chrome.</div></div>
                <div class="kq-step"><span class="n">2</span><div>Allow the camera when asked, tap the screen once (turns on sound and full screen), and add the page to the home screen.</div></div>
                <div class="kq-step"><span class="n">3</span><div>Mount the tablet at chest height facing the door, on charge, with screen timeout off. The front camera is used; the arrow button swaps cameras.</div></div>
                <div class="kq-step"><span class="n">4</span><div>Staff scan once when they arrive (clock in) and once when they leave (clock out). They get a green / amber / blue screen with their name and a beep.</div></div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="at-card">
            <div class="at-card-h">
                <h6><i class="bi bi-person-badge"></i> Employee QR codes</h6>
                <form method="GET" action="{{ route('attendance.badges') }}" target="_blank" class="d-flex gap-1">
                    <select name="department" class="form-select form-select-sm" style="width:auto">
                        <option value="">All departments</option>
                        @foreach($departments as $d)<option>{{ $d }}</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Print</button>
                </form>
            </div>
            <div class="px-3 pt-3">
                <p class="small at-muted mb-2">Each QR code holds the employee ID with a security check, so it can't be faked by typing an ID into a QR app. Employees can also show it on their phone from the portal (<em>My Attendance</em>).</p>
                <form method="GET" class="mb-2"><input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Find an employee…"></form>
            </div>
            <div class="table-responsive">
                <table class="table at-table align-middle mb-0">
                    <tbody>
                        @forelse($employees as $e)
                            <tr>
                                <td style="width:60px"><div class="kq-mini" data-qr="{{ $e->attendance_qr }}"></div></td>
                                <td style="width:100%"><div class="fw-semibold">{{ $e->full_name }}</div><div class="small at-muted">{{ $e->employee_id }}{{ $e->department ? ' · ' . $e->department : '' }}{{ ($e->attendance_qr_version ?? 1) > 1 ? ' · badge v' . $e->attendance_qr_version : '' }}</div></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('attendance.badges', ['ids' => [$e->id]]) }}" target="_blank" class="btn btn-sm btn-light border" title="Print badge"><i class="bi bi-printer"></i></a>
                                    <form method="POST" action="{{ route('attendance.qr.reissue', $e) }}" class="d-inline" onsubmit="return confirm('Cancel {{ addslashes($e->full_name) }}\'s current QR code and issue a new one? Their old badge will stop working.')">@csrf
                                        <button class="btn btn-sm btn-light border" title="Lost badge: issue a new code"><i class="bi bi-arrow-repeat"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center at-muted py-4">No active employees found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($employees->hasPages())<div class="p-3">{{ $employees->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/attendance/qrcode.js') }}"></script>
<script>
document.querySelectorAll('[data-qr]').forEach(el => {
    try { const q = qrcode(0, 'M'); q.addData(el.dataset.qr); q.make(); el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true }); } catch (e) {}
});
document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', () => {
    const t = document.getElementById(b.dataset.copy).textContent.trim();
    (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(() => { b.innerHTML = '<i class="bi bi-check2"></i> Copied'; }).catch(() => {
        const r = document.createRange(); r.selectNodeContents(document.getElementById(b.dataset.copy)); getSelection().removeAllRanges(); getSelection().addRange(r);
    });
}));
</script>
@endpush
