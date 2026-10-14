<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Portal\LeaveController as PortalLeave;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Services\LeaveBalance;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveReport;
use App\Services\LeaveWorkflow;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * HR: Leave Management — overview & reports, planner, per-employee leave
 * record and balance ledger, new-year allocation, recording leave on behalf
 * of employees, CSV exports and the public holiday calendar.
 */
class LeaveManagementController extends Controller
{
    public function __construct(
        private LeaveDayCalculator $days,
        private LeaveBalance $balance,
        private LeaveWorkflow $workflow,
    ) {}

    /* ===================== OVERVIEW & REPORTS ===================== */

    public function index(Request $request)
    {
        $report = new LeaveReport($request->only(['year', 'department', 'branch', 'q', 'include_inactive', 'flag', 'sort', 'dir']));

        return view('dashboard.leave-management.index', [
            'r'       => $report,
            'f'       => $report->filters,
            'options' => LeaveReport::filterOptions(),
            'carryMax'=> config('leave.carry_forward_max'),
            'startedYears' => \App\Models\LeaveBalanceAdjustment::where('type', 'year_start')->distinct()->pluck('year')->all(),
        ]);
    }

    public function exportBalances(Request $request)
    {
        $report = new LeaveReport($request->only(['year', 'department', 'branch', 'q', 'include_inactive', 'flag', 'sort', 'dir']));
        $year = $report->filters['year'];

        return $this->csv("leave-balances-{$year}.csv",
            ['EIN', 'Employee', 'Department', 'Branch', 'Position', 'Supervisor', 'Entitlement', 'Taken (deductible)', 'Other leave taken', 'Pending days', 'Balance', 'Utilisation %', 'Approved', 'Pending', 'Rejected', 'On leave today', 'Next leave'],
            $report->employees->map(fn ($r) => [
                $r->employee->employee_id, $r->name, $r->employee->department, $r->employee->branch, $r->employee->position,
                $r->employee->supervisor_name, $r->entitlement, $r->taken, $r->other_taken, $r->pending_days, $r->balance,
                $r->utilization, $r->approved, $r->pending, $r->rejected,
                $r->on_leave ? 'Yes (back ' . optional($r->on_leave->return_date)->format('Y-m-d') . ')' : 'No',
                $r->next_leave ? $r->next_leave->date_from->format('Y-m-d') : '',
            ])
        );
    }

    public function exportRequests(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $rows = LeaveRequest::with(['employee', 'supervisorEmployee', 'supervisorUser'])
            ->whereYear('date_from', $year)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('department'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department', $request->department)))
            ->orderBy('date_from')
            ->get();

        return $this->csv("leave-requests-{$year}.csv",
            ['Ref', 'Employee', 'EIN', 'Department', 'Type', 'From', 'To', 'Leave days', 'Calendar days', 'Return date', 'Stage', 'Status', 'Supervisor', 'Supervisor decision', 'Supervisor date', 'HR decision by', 'HR date', 'HR comments', 'Balance deducted', 'Balance after', 'Submitted'],
            $rows->map(fn ($l) => [
                'LV-' . str_pad($l->id, 5, '0', STR_PAD_LEFT), $l->employee_name, $l->employee_number, $l->employee?->department,
                $l->leave_type . ($l->other_leave_type ? " ({$l->other_leave_type})" : ''),
                $l->date_from->format('Y-m-d'), $l->date_to->format('Y-m-d'), $l->total_days, $l->calendar_days,
                optional($l->return_date)->format('Y-m-d'), $l->stage_label, $l->status,
                $l->assigned_supervisor_name, $l->supervisor_decision, optional($l->supervisor_acted_at)->format('Y-m-d H:i'),
                $l->hr_name, optional($l->hr_acted_at)->format('Y-m-d H:i'), $l->hr_comments,
                $l->balance_deducted, $l->days_balance, $l->created_at->format('Y-m-d H:i'),
            ])
        );
    }

    /* ===================== PLANNER (month grid) ===================== */

    public function planner(Request $request)
    {
        $month = Carbon::createFromFormat('Y-m', $request->input('month', now()->format('Y-m')))->startOfMonth();
        $start = $month->copy();
        $end = $month->copy()->endOfMonth();

        $leaves = LeaveRequest::with('employee')
            ->whereIn('status', $request->boolean('approved_only') ? ['Approved'] : ['Approved', 'Pending'])
            ->whereDate('date_from', '<=', $end)
            ->whereDate('date_to', '>=', $start)
            ->when($request->filled('department'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department', $request->department)))
            ->orderBy('date_from')
            ->get();

        $rows = $leaves->groupBy(fn ($l) => $l->employee_id ?: 'x' . $l->id)->map(function ($group) {
            $first = $group->first();

            return (object) [
                'name'       => $first->employee?->full_name ?? $first->employee_name,
                'department' => $first->employee?->department,
                'employee'   => $first->employee,
                'leaves'     => $group,
            ];
        })->sortBy('name')->values();

        $holidays = $this->days->holidays($start->year, $end->year);

        return view('dashboard.leave-management.planner', [
            'month'    => $month,
            'days'     => collect(CarbonPeriod::create($start, $end)),
            'rows'     => $rows,
            'holidays' => $holidays,
            'options'  => LeaveReport::filterOptions(),
        ]);
    }

    /* ===================== EMPLOYEE LEAVE RECORD ===================== */

    public function employee(Request $request, Employee $employee)
    {
        $employee->load(['supervisorEmployee', 'supervisorUser', 'user']);
        $year = (int) $request->input('year', now()->year);

        $requests = $employee->leaveRequests()->with(['supervisorEmployee', 'supervisorUser'])->latest('date_from')->get();
        $deductible = config('leave.deductible_types', ['Annual']);

        $years = $requests->groupBy(fn ($l) => $l->date_from->year)->map(fn ($g, $y) => [
            'year'     => $y,
            'taken'    => (float) $g->where('status', 'Approved')->whereIn('leave_type', $deductible)->sum('total_days'),
            'other'    => (float) $g->where('status', 'Approved')->whereNotIn('leave_type', $deductible)->sum('total_days'),
            'approved' => $g->where('status', 'Approved')->count(),
            'rejected' => $g->where('status', 'Rejected')->count(),
            'pending'  => $g->where('status', 'Pending')->count(),
        ])->sortKeysDesc();

        return view('dashboard.leave-management.employee', [
            'employee' => $employee,
            'requests' => $requests,
            'years'    => $years,
            'year'     => $year,
            'ledger'   => $employee->balanceAdjustments()->with('user')->limit(100)->get(),
            'byType'   => $requests->where('status', 'Approved')->filter(fn ($l) => $l->date_from->year === $year)
                ->groupBy('leave_type')->map(fn ($g) => (float) $g->sum('total_days'))->sortDesc(),
        ]);
    }

    public function adjust(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'mode'   => 'required|in:add,deduct,set',
            'days'   => 'required|numeric|min:0|max:365',
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($data, $employee, $request) {
            $e = Employee::whereKey($employee->id)->lockForUpdate()->first();
            match ($data['mode']) {
                'add'    => $this->balance->change($e, (float) $data['days'], 'manual', $data['reason'], $request->user()->id),
                'deduct' => $this->balance->change($e, -(float) $data['days'], 'manual', $data['reason'], $request->user()->id),
                'set'    => $this->balance->set($e, (float) $data['days'], 'manual', $data['reason'], $request->user()->id),
            };
        });

        return back()->with('success', 'Leave balance updated: now ' . rtrim(rtrim(number_format((float) $employee->fresh()->leave_balance, 1), '0'), '.') . ' day(s).');
    }

    public function startYear(Request $request)
    {
        $data = $request->validate([
            'year'      => 'required|integer|min:2020|max:2100',
            'carry_max' => 'nullable|numeric|min:0|max:365',
            'confirm'   => 'accepted',
        ], ['confirm.accepted' => 'Tick the box to confirm.']);

        $result = $this->balance->startYear(
            (int) $data['year'],
            $request->filled('carry_max') ? (float) $data['carry_max'] : null,
            $request->user()->id
        );

        return back()->with('success', "New leave year {$data['year']}: balances set for {$result['done']} employee(s)"
            . ($result['skipped'] ? ", {$result['skipped']} already done earlier were skipped" : '') . '.');
    }

    /* ===================== RECORD LEAVE FOR AN EMPLOYEE ===================== */

    public function recordForm(Request $request)
    {
        return view('dashboard.leave-management.record', [
            'employees'       => Employee::where('employment_status', 'Active')->orderBy('first_name')->get(),
            'selected'        => $request->integer('employee') ?: null,
            'types'           => PortalLeave::TYPES,
            'holidays'        => $this->days->holidays(now()->year - 1, now()->year + 1),
            'dayRules'        => LeaveDayCalculator::defaultOptions(),
            'canChangeRules'  => true,
            'deductibleTypes' => config('leave.deductible_types', ['Annual']),
            'allowNegative'   => true,
        ]);
    }

    public function recordStore(Request $request)
    {
        $data = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'leave_type'       => ['required', Rule::in(PortalLeave::TYPES)],
            'other_leave_type' => 'nullable|required_if:leave_type,Other|string|max:255',
            'date_from'        => 'required|date',
            'date_to'          => 'required|date|after_or_equal:date_from',
            'reason'           => 'nullable|string|max:5000',
            'outcome'          => 'required|in:approve,workflow',
            'hr_comments'      => 'nullable|string|max:5000',
            'override_balance' => 'nullable|boolean',
        ]);

        $employee = Employee::with('user')->findOrFail($data['employee_id']);
        $from = Carbon::parse($data['date_from']);
        $to = Carbon::parse($data['date_to']);

        $calc = $this->days->calculate($from, $to, [
            'exclude_sundays'   => $request->boolean('exclude_sundays'),
            'exclude_saturdays' => $request->boolean('exclude_saturdays'),
            'exclude_holidays'  => $request->boolean('exclude_holidays'),
        ]);

        if ($calc['working_days'] < 1) {
            return back()->withInput()->withErrors(['date_to' => 'Every selected day is a non-working day.']);
        }

        $overlap = $employee->leaveRequests()->whereIn('status', ['Pending', 'Approved'])
            ->whereDate('date_from', '<=', $to)->whereDate('date_to', '>=', $from)->exists();
        if ($overlap) {
            return back()->withInput()->withErrors(['date_from' => "{$employee->full_name} already has pending or approved leave overlapping these dates."]);
        }

        $leave = LeaveRequest::create([
            'employee_id'       => $employee->id,
            'employee_name'     => $employee->full_name,
            'employee_number'   => $employee->employee_id,
            'position'          => $employee->position,
            'email'             => $employee->contact_email ?? '',
            'leave_type'        => $data['leave_type'],
            'other_leave_type'  => $data['other_leave_type'] ?? null,
            'date_from'         => $from,
            'date_to'           => $to,
            'return_date'       => $calc['return_date'],
            'total_days'        => $calc['working_days'],
            'calendar_days'     => $calc['calendar_days'],
            'exclude_sundays'   => $calc['options']['exclude_sundays'],
            'exclude_saturdays' => $calc['options']['exclude_saturdays'],
            'exclude_holidays'  => $calc['options']['exclude_holidays'],
            'excluded_dates'    => $calc['excluded'],
            'reason'            => trim(($data['reason'] ?? '') . "\n\n[Recorded by HR: {$request->user()->name}]"),
            'applicant_signature_date' => now(),
            'status'            => 'Pending',
        ]);

        if ($data['outcome'] === 'approve') {
            $this->workflow->submit($leave, notify: false);

            try {
                $this->workflow->approve($leave->fresh(), $request->user(), $data['hr_comments'] ?? 'Recorded and approved by HR', $request->boolean('override_balance'));
            } catch (RuntimeException $e) {
                return redirect()->route('leave.show', $leave)->with('error', 'Leave recorded but NOT approved: ' . $e->getMessage());
            }

            return redirect()->route('leave.show', $leave)->with('success', "Leave recorded and approved for {$employee->full_name}. The balance is updated and they've been emailed.");
        }

        $this->workflow->submit($leave);

        return redirect()->route('leave.show', $leave)->with('success', "Leave recorded for {$employee->full_name} and sent through the normal approval steps.");
    }

    /* ===================== PUBLIC HOLIDAYS ===================== */

    public function holidays(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $this->days->ensureYear($year);

        return view('dashboard.leave-management.holidays', [
            'year'     => $year,
            'holidays' => PublicHoliday::whereYear('date', $year)->orderBy('date')->get(),
        ]);
    }

    public function holidayStore(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:150',
        ]);

        $h = PublicHoliday::whereDate('date', $data['date'])->first() ?? new PublicHoliday(['date' => $data['date']]);
        $h->fill(['name' => $data['name'], 'source' => $h->exists ? $h->source : 'manual', 'is_active' => true])->save();

        return redirect()->route('leave.manage.holidays', ['year' => $h->date->year])
            ->with('success', "{$h->name} ({$h->date->format('D d M Y')}) added. It won't be counted in new leave requests.");
    }

    public function holidayGenerate(Request $request)
    {
        $year = (int) $request->validate(['year' => 'required|integer|min:2000|max:2100'])['year'];
        $added = $this->days->ensureYear($year, force: true);

        return back()->with('success', $added ? "{$added} Zambian public holiday(s) added for {$year}." : "The {$year} Zambian calendar was already loaded. Nothing new to add.");
    }

    public function holidayToggle(PublicHoliday $holiday)
    {
        $holiday->update(['is_active' => ! $holiday->is_active]);

        return back()->with('success', "{$holiday->name} " . ($holiday->is_active ? 'switched on' : 'switched off') . '.');
    }

    public function holidayDestroy(PublicHoliday $holiday)
    {
        if ($holiday->source === 'generated') {
            $holiday->update(['is_active' => false]);

            return back()->with('success', "{$holiday->name} switched off. Calendar days are kept so they aren't re-added.");
        }

        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }

    /* ===================== helpers ===================== */

    private function csv(string $filename, array $header, $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_bool($v) ? ($v ? 'Yes' : 'No') : $v, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
