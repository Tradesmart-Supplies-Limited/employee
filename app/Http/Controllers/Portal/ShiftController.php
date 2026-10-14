<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RotaController;
use App\Services\AttendanceService;
use App\Services\RotaService;
use Illuminate\Http\Request;

/** Portal: "My shifts" — this week and next, from the published rota (or the normal working pattern). */
class ShiftController extends Controller
{
    public function index(Request $request, AttendanceService $att, RotaService $rotas)
    {
        $e = $request->user()->employee;
        $today = AttendanceService::today();
        $monday = RotaService::monday($today);
        $usesRota = $att->settings()->usesRota($e->department);

        $weeks = [];
        foreach ([0, 1] as $i) {
            $m = $monday->copy()->addWeeks($i);
            $rota = $usesRota ? $rotas->publishedFor($e, $m) : null;
            $days = [];
            for ($d = $m->copy(); $d->lte($m->copy()->addDays(6)); $d->addDay()) {
                $days[] = ['date' => $d->copy(), 'plan' => $att->planFor($e, $d->copy()), 'leave' => $att->leaveOn($e->id, $d->copy())];
            }
            $weeks[] = [
                'monday' => $m,
                'rota'   => $rota,
                'days'   => $days,
                'sheet'  => $rota ? app(RotaController::class)->sheet($rota, true) + ['rota' => $rota] : null,
            ];
        }

        return view('portal.shifts.index', [
            'employee' => $e,
            'weeks'    => $weeks,
            'today'    => $today,
            'usesRota' => $usesRota,
            'canManage'=> (bool) $rotas->managedDepartments($request->user()),
        ]);
    }
}
