<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Attendance QR badges{{ $department ? ' · ' . $department : '' }}</title>
<style>
@page { size: A4; margin: 10mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a; background: #e2e8f0; }
.bar { position: sticky; top: 0; background: #0f172a; color: #fff; padding: 10px 16px; display: flex; gap: 12px; align-items: center; justify-content: space-between; z-index: 2; font-size: 14px; }
.bar button { background: #3b82f6; color: #fff; border: 0; border-radius: 8px; padding: 8px 16px; font-weight: 700; cursor: pointer; font-size: 14px; }
.sheet { width: 190mm; margin: 12px auto; display: grid; grid-template-columns: repeat(2, 85.6mm); gap: 6mm 8mm; justify-content: center; }
.card { width: 85.6mm; height: 54mm; background: #fff; border-radius: 3mm; border: .3mm dashed #94a3b8; overflow: hidden; display: flex; flex-direction: column; break-inside: avoid; page-break-inside: avoid; }
.top { background: var(--brand, #185FA5); color: #fff; display: flex; align-items: center; gap: 2mm; padding: 1.8mm 3mm; font-size: 2.6mm; font-weight: 700; letter-spacing: .02em; }
.top img { height: 5.5mm; background: #fff; border-radius: 1mm; padding: .4mm; }
.top span:last-child { margin-left: auto; font-weight: 600; opacity: .85; font-size: 2.3mm; }
.body { flex: 1; display: flex; gap: 3mm; padding: 2.6mm 3mm 2.4mm; align-items: stretch; }
.info { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.ph { width: 15mm; height: 15mm; border-radius: 50%; background: linear-gradient(135deg, var(--brand, #185FA5), #4f8cff) center/cover; color: #fff; font-weight: 800; font-size: 5mm; display: flex; align-items: center; justify-content: center; margin-bottom: 1.6mm; border: .6mm solid #e2e8f0; }
.nm { font-weight: 800; font-size: 3.8mm; line-height: 1.15; }
.ps { font-size: 2.6mm; color: #475569; margin-top: .6mm; line-height: 1.2; }
.ein { margin-top: auto; font-family: ui-monospace, Consolas, monospace; font-size: 3mm; font-weight: 700; background: #f1f5f9; border-radius: 1mm; padding: .6mm 1.4mm; align-self: flex-start; }
.qr { width: 36mm; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.qr svg { width: 33mm; height: 33mm; display: block; }
.qr small { font-size: 2.1mm; color: #64748b; margin-top: .6mm; text-align: center; }
.empty { text-align: center; padding: 60px; font-size: 16px; color: #475569; }
@media print { body { background: #fff; } .bar { display: none; } .sheet { margin: 0 auto; } }
</style>
</head>
<body>
<div class="bar">
    <div><strong>{{ $employees->count() }}</strong> badge(s){{ $department ? ' · ' . $department : '' }} · print on card or plain paper, cut along the dotted lines, and laminate.</div>
    <button onclick="window.print()">Print</button>
</div>

@if($employees->isEmpty())
    <div class="empty">No employees to print.</div>
@else
<div class="sheet">
    @foreach($employees as $e)
        <div class="card">
            <div class="top">
                @if(config('company.logo_url'))<img src="{{ config('company.logo_url') }}" alt="" onerror="this.remove()">@endif
                <span>{{ \Illuminate\Support\Str::limit(config('company.name'), 34) }}</span>
                <span>STAFF</span>
            </div>
            <div class="body">
                <div class="info">
                    <div class="ph" style="{{ $e->passport_photo ? "background-image:url('" . asset('storage/' . $e->passport_photo) . "')" : '' }}">{{ $e->passport_photo ? '' : $e->initials }}</div>
                    <div class="nm">{{ $e->full_name }}</div>
                    <div class="ps">{{ $e->position }}{{ $e->department ? ' · ' . $e->department : '' }}</div>
                    <div class="ein">{{ $e->employee_id }}</div>
                </div>
                <div class="qr">
                    <div data-qr="{{ $e->attendance_qr }}"></div>
                    <small>Scan at the entrance<br>in &amp; out</small>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<script src="{{ asset('vendor/attendance/qrcode.js') }}"></script>
<script>
document.querySelectorAll('[data-qr]').forEach(el => {
    const q = qrcode(0, 'M'); q.addData(el.dataset.qr); q.make();
    el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
});
</script>
</body>
</html>
