<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalanceAdjustment;
use App\Models\User;
use App\Services\ContractMonitor;
use App\Services\LeaveBalance;
use App\Services\LeaveWorkflow;
use App\Services\PortalAccounts;
use App\Support\EmployeeFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST — server-side search, filters, sorting
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = $this->filtered($request);

        $sort = in_array($request->sort, ['name', 'employee_id', 'department', 'position', 'contract_end', 'created_at'], true) ? $request->sort : 'name';
        $dir = $request->dir === 'desc' ? 'desc' : 'asc';
        $sort === 'name' ? $query->orderBy('first_name', $dir)->orderBy('last_name', $dir) : $query->orderBy($sort, $dir);

        $employees = $query->with(['user', 'supervisorEmployee', 'supervisorUser'])->paginate((int) $request->input('per_page', 25))->withQueryString();

        $monitor = app(ContractMonitor::class);
        $endDates = $monitor->items(null, 30, 60);

        return view('dashboard.employees.index', [
            'employees' => $employees,
            'monitor'   => $monitor,
            'kpis'      => [
                'total'     => Employee::count(),
                'active'    => Employee::where('employment_status', 'Active')->count(),
                'suspended' => Employee::where('employment_status', 'Suspended')->count(),
                'exited'    => Employee::where('employment_status', 'Exited')->count(),
                'contracts' => $endDates->where('type', 'contract')->count(),
                'probation' => $endDates->where('type', 'probation')->count(),
                'no_portal' => Employee::where('employment_status', 'Active')->whereDoesntHave('user')->count(),
            ],
            'departments' => Employee::whereNotNull('department')->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department'),
            'branches'    => Employee::whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch')->pluck('branch'),
        ]);
    }

    /** CSV of the current list (same columns as the import template). */
    public function export(Request $request)
    {
        $cols = EmployeeImportController::COLUMNS;
        $rows = $this->filtered($request)->with(['supervisorEmployee', 'supervisorUser'])->orderBy('first_name')->get();

        return response()->streamDownload(function () use ($cols, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $cols);
            foreach ($rows as $e) {
                fputcsv($out, array_map(function ($c) use ($e) {
                    $v = $c === 'supervisor' ? $e->supervisor_name : $e->$c;

                    return $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v;
                }, $cols));
            }
            fclose($out);
        }, 'employees-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        $today = now()->startOfDay();

        return Employee::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%' . trim($request->q) . '%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $t)->orWhere('last_name', 'like', $t)
                    ->orWhere('employee_id', 'like', $t)->orWhere('position', 'like', $t)
                    ->orWhere('nrc_no', 'like', $t)->orWhere('company_email', 'like', $t)
                    ->orWhere('personal_email', 'like', $t)->orWhere('primary_phone', 'like', $t)
                    ->orWhereRaw((DB::getDriverName() === 'sqlite' ? "first_name || ' ' || last_name" : "CONCAT(first_name, ' ', last_name)") . ' LIKE ?', [$t]));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->status))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch', $request->branch))
            ->when($request->filled('nature'), fn ($q) => $q->where('employment_nature', $request->nature))
            ->when($request->attention === 'contracts', fn ($q) => $q->where('employment_status', 'Active')->whereNotNull('contract_end')
                ->whereDate('contract_end', '<=', $today->copy()->addDays(30)))
            ->when($request->attention === 'probation', fn ($q) => $q->where('employment_status', 'Active')->whereNotNull('probation_end')
                ->whereDate('probation_end', '>=', $today->copy()->subDays(30))->whereDate('probation_end', '<=', $today->copy()->addDays(30)))
            ->when($request->attention === 'no_portal', fn ($q) => $q->where('employment_status', 'Active')->whereDoesntHave('user'))
            ->when($request->attention === 'incomplete', fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('nrc_no')->orWhereNull('primary_phone')->orWhereNull('bank_account_no')
                ->orWhereNull('ssn')->orWhereNull('date_of_birth')));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE / STORE  (step-by-step wizard)
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('dashboard.employees.create', array_merge(
            ['employee' => new Employee(['employment_status' => 'Active', 'employment_nature' => 'PERMANENT',
                'annual_leave_entitlement' => config('leave.annual_entitlement', 24), 'leave_balance' => config('leave.annual_entitlement', 24)])],
            $this->formData()
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $employee = DB::transaction(function () use ($request, $data) {
            $employee = new Employee();
            $employee->fill($data);
            $employee->employee_id = $request->filled('employee_id') ? trim($request->employee_id) : $this->newEin();
            $employee->employment_status = $data['employment_status'] ?? 'Active';
            $employee->annual_leave_entitlement = $request->filled('annual_leave_entitlement') ? $request->annual_leave_entitlement : config('leave.annual_entitlement', 24);
            $employee->leave_balance = $request->filled('leave_balance') ? $request->leave_balance : $employee->annual_leave_entitlement;
            $this->applySupervisor($employee, $request->supervisor_ref);

            if ($request->hasFile('passport_photo')) {
                $employee->passport_photo = $request->file('passport_photo')->store('uploads/employees/passports', 'public');
            }
            $employee->save();

            foreach ((array) $request->file('documents', []) as $i => $file) {
                $employee->attachDocument($file, null, $request->input("document_categories.$i", 'Other'), 'hr');
            }

            LeaveBalanceAdjustment::create([
                'employee_id'    => $employee->id,
                'user_id'        => $request->user()?->id,
                'type'           => 'opening',
                'days'           => $employee->leave_balance,
                'balance_before' => 0,
                'balance_after'  => $employee->leave_balance,
                'year'           => now()->year,
                'reason'         => 'Opening balance when the employee was added',
            ]);

            return $employee;
        });

        $msg = "{$employee->full_name} added ({$employee->employee_id}).";

        if ($request->boolean('create_portal_account')) {
            try {
                $r = app(PortalAccounts::class)->create($employee, $request->user()?->name ?? 'HR');
                $msg .= $r['emailed'] ? ' Portal login emailed to ' . $r['user']->email . '.' : " Portal account created, but the email failed. Temporary password: {$r['password']}";
            } catch (\RuntimeException $e) {
                $msg .= ' Portal account not created: ' . $e->getMessage();
            }
        }

        return redirect()->route('employees.show', $employee)->with('success', $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */
    public function show(Employee $employee)
    {
        $employee->load(['supervisorEmployee', 'supervisorUser', 'directReports', 'user']);

        return view('dashboard.employees.show', [
            'employee'      => $employee,
            'recentLeave'   => $employee->leaveRequests()->latest()->take(5)->get(),
            'endDates'      => app(ContractMonitor::class)->statusFor($employee),
            'pendingChanges'=> $employee->changeRequests()->where('status', 'pending')->get(),
            'completeness'  => $this->completeness($employee),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT / UPDATE  (same wizard, any step can be opened)
    |--------------------------------------------------------------------------
    */
    public function edit(Employee $employee)
    {
        $employee->load(['supervisorEmployee', 'supervisorUser']);

        return view('dashboard.employees.edit', array_merge(['employee' => $employee], $this->formData($employee)));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validated($request, $employee);
        $supervisorBefore = $employee->supervisor_ref;

        $employee->fill($data);
        if ($request->filled('employee_id')) {
            $employee->employee_id = trim($request->employee_id);
        }
        $this->applySupervisor($employee, $request->supervisor_ref);

        if ($request->filled('annual_leave_entitlement')) {
            $employee->annual_leave_entitlement = $request->annual_leave_entitlement;
        }

        if ($request->boolean('remove_photo') && $employee->passport_photo) {
            Storage::disk('public')->delete($employee->passport_photo);
            $employee->passport_photo = null;
        }
        if ($request->hasFile('passport_photo')) {
            if ($employee->passport_photo) {
                Storage::disk('public')->delete($employee->passport_photo);
            }
            $employee->passport_photo = $request->file('passport_photo')->store('uploads/employees/passports', 'public');
        }

        $employee->save();

        foreach ((array) $request->file('documents', []) as $i => $file) {
            $employee->attachDocument($file, null, $request->input("document_categories.$i", 'Other'), 'hr');
        }

        // Balance edited on the form → goes through the ledger
        if ($request->filled('leave_balance') && round((float) $request->leave_balance, 1) !== round((float) $employee->leave_balance, 1)) {
            app(LeaveBalance::class)->set($employee, (float) $request->leave_balance, 'edit', 'Changed on the employee form', $request->user()?->id);
        }

        if ($supervisorBefore !== $employee->supervisor_ref) {
            app(LeaveWorkflow::class)->reassignPending($employee);
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Changes to ' . $employee->full_name . ' saved.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */
    public function destroy(Employee $employee)
    {
        if ($employee->passport_photo) {
            Storage::disk('public')->delete($employee->passport_photo);
        }
        foreach ($employee->documents as $doc) {
            if ($doc->path) {
                Storage::disk('public')->delete($doc->path);
            }
        }

        $name = $employee->full_name;
        $employee->user?->delete();
        $employee->delete();

        return redirect()->route('employees.index')->with('success', "{$name} was deleted.");
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTS
    |--------------------------------------------------------------------------
    */
    public function documentStore(Request $request, Employee $employee)
    {
        $request->validate([
            'files'    => 'required|array|max:10',
            'files.*'  => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:10240',
            'name'     => 'nullable|string|max:150',
            'category' => ['nullable', Rule::in(EmployeeFields::DOC_CATEGORIES)],
        ]);

        foreach ($request->file('files') as $i => $file) {
            $name = $request->filled('name') ? $request->name . (count($request->file('files')) > 1 ? ' (' . ($i + 1) . ')' : '') : null;
            $employee->attachDocument($file, $name, $request->input('category', 'Other'), 'hr');
        }

        return back()->with('success', count($request->file('files')) . ' document(s) uploaded.');
    }

    public function documentDownload(Employee $employee, int $index)
    {
        $doc = $employee->documents->firstWhere('index', $index);
        abort_unless($doc && $doc->path && Storage::disk('public')->exists($doc->path), 404);

        return Storage::disk('public')->download($doc->path, Str::slug(pathinfo($doc->name, PATHINFO_FILENAME)) . '.' . $doc->ext);
    }

    public function documentDestroy(Employee $employee, int $index)
    {
        $doc = $employee->detachDocument($index);

        return back()->with($doc ? 'success' : 'error', $doc ? "“{$doc['name']}” deleted." : 'Document not found.');
    }

    /** Old sample CSV link → the new import template. */
    public function downloadSampleCsv()
    {
        return redirect()->route('employees.import.template');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    private function validated(Request $request, ?Employee $employee = null): array
    {
        $rules = EmployeeFields::rules() + [
            'employee_id'              => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_id')->ignore($employee?->id)],
            'supervisor_ref'           => ['nullable', 'regex:/^(employee|user):\d+$/'],
            'annual_leave_entitlement' => 'nullable|numeric|min:0|max:365',
            'leave_balance'            => 'nullable|numeric|min:-365|max:365',
            'passport_photo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'documents'                => 'nullable|array|max:10',
            'documents.*'              => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:10240',
            'document_categories.*'    => ['nullable', Rule::in(EmployeeFields::DOC_CATEGORIES)],
        ];

        $messages = [
            'contract_end.after_or_equal'  => 'The contract end date must be on or after the start date.',
            'probation_end.after_or_equal' => 'The probation end date must be on or after the probation start date.',
            'employee_id.unique'           => 'That employee number (EIN) is already used by someone else.',
        ];

        $data = $request->validate($rules, $messages);

        // Only the catalogue fields are mass-assigned; empty strings become null.
        return collect($data)->only(array_keys(EmployeeFields::all()))
            ->map(fn ($v) => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v)
            ->all();
    }

    private function newEin(): string
    {
        do {
            $ein = 'EMP-' . date('Y') . '-' . strtoupper(Str::random(5));
        } while (Employee::where('employee_id', $ein)->exists());

        return $ein;
    }

    private function formData(?Employee $exclude = null): array
    {
        return [
            'supervisorEmployees' => Employee::with('user')
                ->where('employment_status', 'Active')
                ->when($exclude, fn ($q) => $q->whereKeyNot($exclude->id))
                ->orderBy('first_name')->orderBy('last_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'position', 'department']),
            'supervisorUsers' => User::whereNull('employee_id')->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'datalists' => [
                'department' => Employee::whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
                'branch'     => Employee::whereNotNull('branch')->distinct()->orderBy('branch')->pluck('branch'),
                'bank_name'  => collect(['Zanaco', 'Stanbic Bank', 'Absa Bank', 'FNB Zambia', 'Standard Chartered', 'Indo Zambia Bank', 'Access Bank', 'Atlas Mara', 'Investrust', 'First Capital Bank', 'Ecobank', 'United Bank for Africa', 'Citibank', 'Bank of China', 'ZICB', 'AB Bank', 'Natsave'])
                    ->merge(Employee::whereNotNull('bank_name')->distinct()->pluck('bank_name'))->unique()->sort()->values(),
            ],
        ];
    }

    /** % of key fields filled in, plus what's missing. */
    private function completeness(Employee $e): array
    {
        $keys = ['date_of_birth', 'gender', 'nrc_no', 'primary_phone', 'residential_address', 'emergency_name', 'emergency_phone',
            'next_of_kin_name', 'position', 'department', 'contract_start', 'salary', 'bank_name', 'bank_account_no', 'ssn', 'tpin', 'nhima_no'];
        $missing = array_values(array_filter($keys, fn ($k) => ! filled($e->$k)));

        return [
            'percent' => (int) round((count($keys) - count($missing)) / count($keys) * 100),
            'missing' => array_map(fn ($k) => EmployeeFields::label($k), $missing),
        ];
    }

    private function applySupervisor(Employee $employee, ?string $ref): void
    {
        $employee->supervisor_employee_id = null;
        $employee->supervisor_user_id     = null;
        $employee->supervisor             = null;

        if (! $ref || ! preg_match('/^(employee|user):(\d+)$/', $ref, $m)) {
            return;
        }

        if ($m[1] === 'employee' && (int) $m[2] !== (int) $employee->id) {
            if ($boss = Employee::find($m[2])) {
                $employee->supervisor_employee_id = $boss->id;
                $employee->supervisor             = $boss->full_name;
            }
        } elseif ($m[1] === 'user') {
            if ($user = User::whereNull('employee_id')->find($m[2])) {
                $employee->supervisor_user_id = $user->id;
                $employee->supervisor         = $user->name;
            }
        }
    }
}
