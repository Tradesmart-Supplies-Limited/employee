<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OvertimeController extends Controller
{
    /** No single request may exceed this many hours. */
    private const MAX_HOURS = 16;

    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        $requests = $employee->overtimeRequests()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('work_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'approved_month' => $employee->overtimeRequests()
                ->where('status', 'Approved')
                ->whereYear('work_date', now()->year)
                ->whereMonth('work_date', now()->month)
                ->sum('hours'),
            'pending' => $employee->overtimeRequests()->where('status', 'Pending')->count(),
            'approved_year' => $employee->overtimeRequests()
                ->where('status', 'Approved')
                ->whereYear('work_date', now()->year)
                ->sum('hours'),
        ];

        return view('portal.overtime.index', compact('requests', 'summary'));
    }

    public function create()
    {
        return view('portal.overtime.create', ['dayTypes' => OvertimeRequest::DAY_TYPES]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;

        $data = $request->validate([
            // Overtime is claimed after (or on the day of) the work: no future dates.
            'work_date'  => 'required|date|before_or_equal:today|after_or_equal:' . now()->subDays(60)->toDateString(),
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|different:start_time',
            'day_type'   => ['required', Rule::in(array_keys(OvertimeRequest::DAY_TYPES))],
            'reason'     => 'required|string|min:5|max:2000',
        ], [
            'work_date.after_or_equal'  => 'Overtime must be claimed within 60 days of the work date.',
            'work_date.before_or_equal' => 'Overtime cannot be claimed for a future date.',
            'end_time.different'        => 'End time must differ from start time.',
        ]);

        $start = Carbon::parse($data['work_date'] . ' ' . $data['start_time']);
        $end   = Carbon::parse($data['work_date'] . ' ' . $data['end_time']);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay(); // shift crossed midnight
        }

        $hours = round($start->diffInMinutes($end) / 60, 2);

        if ($hours > self::MAX_HOURS) {
            return back()->withInput()->withErrors([
                'end_time' => 'A single overtime request cannot exceed ' . self::MAX_HOURS . ' hours.',
            ]);
        }

        // Reject windows that overlap another pending / approved request.
        $clash = $employee->overtimeRequests()
            ->whereIn('status', ['Pending', 'Approved'])
            ->whereBetween('work_date', [
                Carbon::parse($data['work_date'])->subDay()->toDateString(),
                $data['work_date'],
            ])
            ->get()
            ->contains(fn (OvertimeRequest $o) => $o->starts_at->lt($end) && $o->ends_at->gt($start));

        if ($clash) {
            return back()->withInput()->withErrors([
                'start_time' => 'This time window overlaps another overtime request you already submitted.',
            ]);
        }

        $employee->overtimeRequests()->create([
            'work_date'  => $data['work_date'],
            'start_time' => $data['start_time'],
            'end_time'   => $data['end_time'],
            'hours'      => $hours,
            'day_type'   => $data['day_type'],
            'reason'     => $data['reason'],
            'status'     => 'Pending',
        ]);

        return redirect()
            ->route('portal.overtime.index')
            ->with('success', "Overtime request for {$hours} hour(s) submitted for approval.");
    }

    public function destroy(Request $request, OvertimeRequest $overtime)
    {
        abort_unless($overtime->employee_id === $request->user()->employee_id, 404);

        if ($overtime->status !== 'Pending') {
            return back()->with('error', 'Only pending requests can be withdrawn.');
        }

        $overtime->delete();

        return redirect()
            ->route('portal.overtime.index')
            ->with('success', 'Overtime request withdrawn.');
    }
}
