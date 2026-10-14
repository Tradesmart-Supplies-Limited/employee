<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Portal: my attendance QR code and my own record. */
class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $att)
    {
        $employee = $request->user()->employee;
        $today = AttendanceService::today();

        $month = $request->query('month');
        $from = $month && preg_match('/^\d{4}-\d{2}$/', $month) ? Carbon::createFromFormat('Y-m-d', $month . '-01', AttendanceService::tz())->startOfDay() : $today->copy()->startOfMonth();
        if ($from->gt($today)) {
            $from = $today->copy()->startOfMonth();
        }
        $to = $from->copy()->endOfMonth()->startOfDay();
        if ($to->gt($today)) {
            $to = $today->copy();
        }

        $row = $att->summary($from, $to, null, $employee->id)->first();
        $todayCell = $att->matrix($today, $today, null, $employee->id)['cells'][$employee->id][$today->toDateString()] ?? null;

        return view('portal.attendance.index', [
            'employee'  => $employee,
            'payload'   => AttendanceService::qrPayload($employee),
            'row'       => $row,
            'from'      => $from,
            'to'        => $to,
            'today'     => $today,
            'todayCell' => $todayCell,
            'settings'  => $att->settings(),
        ]);
    }
}
