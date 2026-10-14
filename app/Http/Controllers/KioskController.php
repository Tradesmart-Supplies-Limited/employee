<?php

namespace App\Http\Controllers;

use App\Models\AttendanceKiosk;
use App\Models\AttendanceRecord;
use App\Models\AttendanceScan;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The entrance tablet. No login: the long random token in the link is the
 * device's key (HR creates / disables links under Attendance → QR & kiosks).
 */
class KioskController extends Controller
{
    public function __construct(private AttendanceService $att) {}

    public function show(string $token)
    {
        $kiosk = AttendanceKiosk::where('token', $token)->first();

        if (! $kiosk || ! $kiosk->is_active) {
            return response()->view('attendance.kiosk-disabled', [], $kiosk ? 403 : 404);
        }

        $this->touch($kiosk, request());

        return view('attendance.kiosk', [
            'kiosk'    => $kiosk,
            'token'    => $token,
            'settings' => $this->att->settings(),
            'now'      => AttendanceService::now(),
        ]);
    }

    public function scan(Request $request, string $token): JsonResponse
    {
        $kiosk = $this->kiosk($token);
        if (! $kiosk) {
            return response()->json(['ok' => false, 'kind' => 'error', 'title' => 'Kiosk disabled', 'message' => 'This kiosk link is no longer active. Ask HR for a new one.'], 403);
        }

        $data = $request->validate([
            'code'  => ['required', 'string', 'max:200'],
            'photo' => ['nullable', 'string', 'max:600000'],
        ]);

        $this->touch($kiosk, $request);
        $result = $this->att->scan($data['code'], $kiosk, $kiosk->capture_photo ? ($data['photo'] ?? null) : null, 'qr', $request->ip());

        return response()->json($result + ['recent' => $this->recent($kiosk)]);
    }

    /** Heartbeat every minute: keeps the clock right, shows online status, and runs the midnight close if it was missed. */
    public function ping(Request $request, string $token): JsonResponse
    {
        $kiosk = $this->kiosk($token);
        if (! $kiosk) {
            return response()->json(['ok' => false, 'disabled' => true], 403);
        }
        $this->touch($kiosk, $request);

        try {
            $this->att->runIfDue();
        } catch (\Throwable $e) {
            report($e);
        }

        $today = AttendanceService::today()->toDateString();
        $recs = AttendanceRecord::whereDate('date', $today)->whereNotNull('clock_in')->get(['status', 'clock_out']);

        return response()->json([
            'ok'     => true,
            'now'    => AttendanceService::now()->format('Y-m-d\TH:i:s'),
            'counts' => [
                'in'   => $recs->count(),
                'late' => $recs->where('status', 'late')->count(),
                'out'  => $recs->whereNotNull('clock_out')->count(),
            ],
            'recent' => $this->recent($kiosk),
            'photo'  => $kiosk->capture_photo,
        ]);
    }

    private function kiosk(string $token): ?AttendanceKiosk
    {
        return AttendanceKiosk::where('token', $token)->where('is_active', true)->first();
    }

    private function touch(AttendanceKiosk $k, Request $r): void
    {
        if (! $k->last_seen_at || $k->last_seen_at->lt(now()->subSeconds(30))) {
            $k->forceFill(['last_seen_at' => now(), 'last_ip' => $r->ip()])->save();
        }
    }

    private function recent(AttendanceKiosk $k): array
    {
        return AttendanceScan::with('employee:id,first_name,last_name,passport_photo')
            ->where('attendance_kiosk_id', $k->id)
            ->whereDate('date', AttendanceService::today()->toDateString())
            ->whereIn('action', ['in', 'out'])
            ->latest('scanned_at')->latest('id')->limit(8)->get()
            ->map(fn ($s) => [
                'name'   => $s->employee?->full_name ?? 'Unknown',
                'action' => $s->action,
                'time'   => $s->scanned_at->format('H:i'),
                'late'   => str_contains((string) $s->message, 'late'),
            ])->all();
    }
}
