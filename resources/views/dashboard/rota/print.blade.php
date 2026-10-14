<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shift schedule · {{ $rota->title }}</title>
<style>
@page { size: A4 landscape; margin: 10mm; }
body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; margin: 0; color: #0f172a; background: #fff; }
.bar { background: #0f172a; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; }
.bar button { background: #3b82f6; color: #fff; border: 0; border-radius: 8px; padding: 8px 16px; font-weight: 700; cursor: pointer; }
.wrap { padding: 14px; }
h1 { font-size: 18px; text-align: center; margin: 4px 0 2px; letter-spacing: .02em; }
.sub { text-align: center; color: #475569; font-size: 12px; margin-bottom: 10px; }
.notes { margin-top: 10px; font-size: 12px; border-left: 4px solid #185FA5; padding: 6px 10px; background: #f8fafc; }
.foot { display: flex; justify-content: space-between; margin-top: 12px; font-size: 11px; color: #64748b; }
@media print { .bar { display: none; } .wrap { padding: 0; } .rs { min-width: 0 !important; } }
</style>
</head>
<body>
<div class="bar"><span>{{ $rota->title }} · {{ $rota->is_published ? 'Published' : 'DRAFT' }}</span><button onclick="window.print()">Print</button></div>
<div class="wrap">
    <h1>{{ strtoupper(config('company.name')) }} · EMPLOYEE SHIFT SCHEDULE</h1>
    <div class="sub">{{ strtoupper($rota->department) }} · WEEK OF {{ strtoupper($rota->week_start->format('d M')) }} – {{ strtoupper($rota->week_end->format('d M Y')) }}{{ $rota->is_published ? '' : ' · DRAFT (not yet approved)' }}</div>
    @include('dashboard.rota.partials.sheet')
    @if($rota->notes)<div class="notes"><strong>Comment:</strong> {!! nl2br(e($rota->notes)) !!}</div>@endif
    <div class="foot">
        <span>{{ $rota->is_published ? 'Approved by ' . ($rota->publisher?->name ?? '—') . ' on ' . $rota->published_at?->format('d M Y H:i') : 'Draft' }}</span>
        <span>Sunday & public-holiday shifts are paid as overtime · Backup = on call</span>
    </div>
</div>
</body>
</html>
