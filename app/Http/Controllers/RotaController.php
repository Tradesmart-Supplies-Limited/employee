<?php

namespace App\Http\Controllers;

use App\Models\AttendanceShift;
use App\Models\Rota;
use App\Services\AttendanceService;
use App\Services\RotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Weekly shift rotas. Same screens for HR (admin area) and for supervisors
 * (employee portal, their own department only).
 */
class RotaController extends Controller
{
    public function __construct(private RotaService $rotas, private AttendanceService $att) {}

    private function portal(): bool
    {
        return (bool) request()->route()?->named('portal.*');
    }

    private function r(string $name, $params = []): string
    {
        return route(($this->portal() ? 'portal.rota.' : 'attendance.rota.') . $name, $params);
    }

    private function authorizeRota(Request $request, Rota $rota): void
    {
        abort_unless($this->rotas->canManage($request->user(), $rota->department), 403, 'You can only manage the rota for your own department.');
    }

    private function viewData(): array
    {
        return ['portal' => $this->portal(), 'layout' => $this->portal() ? 'layouts.portal' : 'layouts.app', 'rr' => fn ($n, $p = []) => $this->r($n, $p)];
    }

    public function index(Request $request)
    {
        $depts = $this->rotas->managedDepartments($request->user());
        abort_if($this->portal() && ! $depts, 403, 'Only supervisors can manage rotas.');
        $dept = in_array($request->query('department'), $depts, true) ? $request->query('department') : ($depts[0] ?? null);

        $thisWeek = RotaService::monday(AttendanceService::today());
        $weeks = collect(range(-3, 4))->map(fn ($i) => $thisWeek->copy()->addWeeks($i));
        $existing = $dept ? Rota::where('department', $dept)
            ->whereDate('week_start', '>=', $weeks->first()->toDateString())->whereDate('week_start', '<=', $weeks->last()->toDateString())
            ->withCount(['assignments as shifts_count' => fn ($q) => $q->where('kind', 'shift')])
            ->get()->keyBy(fn ($r) => $r->week_start->toDateString()) : collect();

        return view('dashboard.rota.index', $this->viewData() + [
            'enabled'     => $this->rotas->enabled(),
            'departments' => $depts,
            'department'  => $dept,
            'weeks'       => $weeks,
            'thisWeek'    => $thisWeek,
            'existing'    => $existing,
            'pending'     => $existing->filter(fn ($r) => $this->rotas->hasPendingChanges($r))->keys()->all(),
        ]);
    }

    /** Open (or create) the rota for a department + week. */
    public function store(Request $request)
    {
        $data = $request->validate(['department' => ['required', 'string'], 'week' => ['required', 'date'], 'copy' => ['nullable', 'boolean']]);
        abort_unless($this->rotas->canManage($request->user(), $data['department']), 403);
        $rota = $this->rotas->openOrCreate($data['department'], RotaService::monday($data['week']), $request->user(), $request->boolean('copy', true));

        return redirect($this->r('edit', $rota));
    }

    public function edit(Request $request, Rota $rota)
    {
        $this->authorizeRota($request, $rota);
        $rota->load('assignments.shift', 'publisher');
        $from = $rota->days[0];
        $to = $rota->days[6];

        return view('dashboard.rota.edit', $this->viewData() + [
            'rota'     => $rota,
            'people'   => $this->rotas->people($rota),
            'cells'    => $this->rotas->cells($rota),
            'shifts'   => AttendanceShift::where('is_active', true)->orderBy('sort')->get(),
            'allShifts'=> AttendanceShift::all()->keyBy('id'),
            'holidays' => $this->att->holidays($from, $to),
            'leaves'   => $this->att->leaveMap($from, $to),
            'warnings' => $this->rotas->warnings($rota),
            'pending'  => $this->rotas->hasPendingChanges($rota),
            'prev'     => RotaService::monday($from->copy()->subWeek()),
            'next'     => RotaService::monday($from->copy()->addWeek()),
            'hasPrev'  => (bool) $this->rotas->find($rota->department, $from->copy()->subWeek()),
        ]);
    }

    public function update(Request $request, Rota $rota)
    {
        $this->authorizeRota($request, $rota);
        $data = $request->validate(['cells' => ['nullable', 'array'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $this->rotas->save($rota, $data['cells'] ?? [], $data['notes'] ?? null, $request->user());

        if ($request->input('action') === 'publish') {
            return $this->publish($request, $rota->fresh());
        }

        $msg = 'Rota saved as a draft.';
        if ($rota->is_published) {
            $msg = 'Changes saved. They take effect and staff are told once you click "Publish changes".';
        }

        return redirect($this->r('edit', $rota))->with('success', $msg);
    }

    public function publish(Request $request, Rota $rota)
    {
        $this->authorizeRota($request, $rota);
        try {
            $res = $this->rotas->publish($rota, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        $warn = collect($this->rotas->warnings($rota->fresh()))->where(0, 'danger')->count();

        return redirect($this->r('edit', $rota))->with('success', ($res['first'] ? 'Rota approved and published. ' : 'Changes published. ')
            . ($res['notified'] ? $res['notified'] . ' employee(s) emailed their shifts.' : 'Nobody needed an email.')
            . ($warn ? " Note: {$warn} leave clash(es) still on the rota." : ''));
    }

    public function unpublish(Request $request, Rota $rota)
    {
        $this->authorizeRota($request, $rota);
        $this->rotas->unpublish($rota, $request->user());

        return redirect($this->r('edit', $rota))->with('success', 'Rota moved back to draft. Attendance no longer uses it until it is published again.');
    }

    public function copyPrevious(Request $request, Rota $rota)
    {
        $this->authorizeRota($request, $rota);
        $prev = $this->rotas->find($rota->department, Carbon::parse($rota->week_start->toDateString())->subWeek());
        if (! $prev) {
            return back()->with('error', 'There is no rota for the week before to copy.');
        }
        $n = $this->rotas->copyInto($rota, $prev);

        return redirect($this->r('edit', $rota))->with('success', "Copied last week's rota ({$n} entries). Review it, then save or publish.");
    }

    public function print(Request $request, Rota $rota)
    {
        $this->authorizeRota($request, $rota);

        return view('dashboard.rota.print', $this->sheet($rota) + ['rota' => $rota]);
    }

    public function destroy(Request $request, Rota $rota)
    {
        $this->authorizeRota($request, $rota);
        if ($rota->is_published) {
            return back()->with('error', 'Published rotas can\'t be deleted. Move it back to draft first.');
        }
        $dept = $rota->department;
        $rota->delete();

        return redirect($this->r('index', ['department' => $dept]))->with('success', 'Draft rota deleted.');
    }

    /** Data for the "TSL shift schedule" sheet layout (also used in the portal). */
    public function sheet(Rota $rota, bool $published = false): array
    {
        $cells = $published ? ($rota->published_snapshot ?? []) : $this->rotas->cells($rota->loadMissing('assignments'));
        $holidays = $this->att->holidays($rota->days[0], $rota->days[6]);
        $people = \App\Models\Employee::whereIn('id', array_keys($cells))->get()->keyBy('id');

        return ['cells' => $cells, 'holidays' => $holidays, 'people' => $people, 'shifts' => AttendanceShift::all()->keyBy('id')];
    }
}
