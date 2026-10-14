<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalanceAdjustment;
use App\Models\User;
use App\Support\EmployeeFields;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Bulk employee import from CSV / Excel.
 *
 *   read()     file → raw rows
 *   map()      header row → field names (forgiving: "First Name", "Surname", "NRC No." …)
 *   analyse()  every row → create / update / skip + errors & warnings  (nothing saved)
 *   commit()   apply the analysed rows; blank cells never wipe existing data
 */
class EmployeeImporter
{
    public const MAX_ROWS = 2000;

    public const DATE_FIELDS = ['date_of_birth', 'contract_start', 'contract_end', 'probation_start', 'probation_end'];

    /** normalised header → field */
    private const ALIASES = [
        'ein' => 'employee_id', 'employeeid' => 'employee_id', 'employeeno' => 'employee_id', 'employeenumber' => 'employee_id', 'empno' => 'employee_id', 'staffno' => 'employee_id', 'staffid' => 'employee_id', 'manno' => 'employee_id', 'id' => 'employee_id',
        'firstname' => 'first_name', 'first' => 'first_name', 'givenname' => 'first_name', 'forename' => 'first_name',
        'middlename' => 'middle_name', 'othernames' => 'middle_name',
        'lastname' => 'last_name', 'surname' => 'last_name', 'familyname' => 'last_name',
        'dob' => 'date_of_birth', 'dateofbirth' => 'date_of_birth', 'birthdate' => 'date_of_birth', 'birthday' => 'date_of_birth',
        'sex' => 'gender', 'gender' => 'gender',
        'maritalstatus' => 'marital_status', 'marital' => 'marital_status',
        'nationality' => 'nationality', 'country' => 'nationality',
        'nrc' => 'nrc_no', 'nrcno' => 'nrc_no', 'nrcnumber' => 'nrc_no', 'nationalid' => 'nrc_no', 'nationalidnumber' => 'nrc_no',
        'passport' => 'passport_number', 'passportno' => 'passport_number', 'passportnumber' => 'passport_number',
        'companyemail' => 'company_email', 'workemail' => 'company_email', 'officeemail' => 'company_email',
        'email' => 'personal_email', 'personalemail' => 'personal_email', 'emailaddress' => 'personal_email',
        'phone' => 'primary_phone', 'mobile' => 'primary_phone', 'cell' => 'primary_phone', 'phonenumber' => 'primary_phone', 'primaryphone' => 'primary_phone', 'contactnumber' => 'primary_phone', 'telephone' => 'primary_phone',
        'secondaryphone' => 'secondary_phone', 'altphone' => 'secondary_phone', 'alternativephone' => 'secondary_phone', 'phone2' => 'secondary_phone',
        'address' => 'residential_address', 'homeaddress' => 'residential_address', 'residentialaddress' => 'residential_address', 'physicaladdress' => 'residential_address',
        'emergencyname' => 'emergency_name', 'emergencycontact' => 'emergency_name', 'emergencycontactname' => 'emergency_name',
        'emergencyrelationship' => 'emergency_relationship', 'relationship' => 'emergency_relationship',
        'emergencyphone' => 'emergency_phone', 'emergencycontactphone' => 'emergency_phone',
        'nextofkin' => 'next_of_kin_name', 'nextofkinname' => 'next_of_kin_name', 'nok' => 'next_of_kin_name',
        'nextofkinphone' => 'next_of_kin_phone', 'nokphone' => 'next_of_kin_phone',
        'nextofkinaddress' => 'next_of_kin_address', 'nokaddress' => 'next_of_kin_address',
        'position' => 'position', 'jobtitle' => 'position', 'title' => 'position', 'designation' => 'position', 'role' => 'position',
        'department' => 'department', 'dept' => 'department', 'section' => 'department',
        'branch' => 'branch', 'location' => 'branch', 'station' => 'branch', 'site' => 'branch',
        'supervisor' => 'supervisor', 'reportsto' => 'supervisor', 'manager' => 'supervisor', 'linemanager' => 'supervisor',
        'status' => 'employment_status', 'employmentstatus' => 'employment_status',
        'employmenttype' => 'employment_nature', 'employmentnature' => 'employment_nature', 'contracttype' => 'employment_nature', 'nature' => 'employment_nature',
        'startdate' => 'contract_start', 'contractstart' => 'contract_start', 'datejoined' => 'contract_start', 'joiningdate' => 'contract_start', 'dateofengagement' => 'contract_start', 'hiredate' => 'contract_start',
        'enddate' => 'contract_end', 'contractend' => 'contract_end', 'contractenddate' => 'contract_end', 'contractexpiry' => 'contract_end',
        'probationstart' => 'probation_start', 'probationend' => 'probation_end', 'probationenddate' => 'probation_end',
        'salary' => 'salary', 'basicsalary' => 'salary', 'basicpay' => 'salary', 'grosssalary' => 'salary', 'basic' => 'salary',
        'bank' => 'bank_name', 'bankname' => 'bank_name',
        'bankbranch' => 'bank_branch',
        'accountno' => 'bank_account_no', 'accountnumber' => 'bank_account_no', 'bankaccount' => 'bank_account_no', 'bankaccountno' => 'bank_account_no', 'bankaccountnumber' => 'bank_account_no',
        'sortcode' => 'bank_sort_code', 'banksortcode' => 'bank_sort_code', 'branchcode' => 'bank_sort_code',
        'napsa' => 'ssn', 'napsano' => 'ssn', 'napsanumber' => 'ssn', 'ssn' => 'ssn', 'socialsecurityno' => 'ssn', 'socialsecuritynumber' => 'ssn',
        'tpin' => 'tpin', 'tpinno' => 'tpin', 'zratpin' => 'tpin', 'taxno' => 'tpin',
        'nhima' => 'nhima_no', 'nhimano' => 'nhima_no', 'nhimanumber' => 'nhima_no', 'nhis' => 'nhima_no',
        'leaveentitlement' => 'annual_leave_entitlement', 'annualleave' => 'annual_leave_entitlement', 'annualleaveentitlement' => 'annual_leave_entitlement', 'leavedays' => 'annual_leave_entitlement',
        'leavebalance' => 'leave_balance', 'leavedaysbalance' => 'leave_balance', 'balance' => 'leave_balance',
    ];

    /* ===================== READ ===================== */

    /** @return array<int, array<int, string|null>> */
    public function read(string $path, string $ext): array
    {
        $ext = strtolower($ext);

        if (in_array($ext, ['xlsx', 'xls', 'ods'], true)) {
            if (! class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
                throw new RuntimeException('Excel files need the maatwebsite/excel package. Save the sheet as CSV and upload that instead.');
            }
            $sheets = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\RawSheetImport, $path);
            $rows = $sheets[0] ?? [];
        } else {
            $rows = $this->readCsv($path);
        }

        // Drop fully empty rows
        return array_values(array_filter($rows, fn ($r) => collect($r)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty()));
    }

    private function readCsv(string $path): array
    {
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);       // UTF-8 BOM
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252'); // Excel "CSV" on Windows
        }

        $firstLine = strtok($content, "\n");
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($firstLine, $d))->first();

        $h = fopen('php://temp', 'r+');
        fwrite($h, $content);
        rewind($h);
        $rows = [];
        while (($r = fgetcsv($h, 0, $delimiter)) !== false) {
            $rows[] = $r;
        }
        fclose($h);

        return $rows;
    }

    /* ===================== MAP HEADERS ===================== */

    /** @return array{map: array<int,string>, unknown: string[]} */
    public function map(array $header): array
    {
        $known = array_keys(EmployeeFields::all()) + [];
        $extra = ['employee_id', 'supervisor', 'annual_leave_entitlement', 'leave_balance'];
        $map = [];
        $unknown = [];

        foreach ($header as $i => $h) {
            $raw = trim((string) $h);
            if ($raw === '') {
                continue;
            }
            $snake = Str::snake(str_replace(['-', '.', '/'], ' ', strtolower($raw)));
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($raw));

            $field = in_array($snake, array_merge($known, $extra), true) ? $snake : (self::ALIASES[$norm] ?? null);

            if ($field && ! in_array($field, $map, true)) {
                $map[$i] = $field;
            } else {
                $unknown[] = $raw;
            }
        }

        return ['map' => $map, 'unknown' => $unknown];
    }

    /** Raw rows → [['line' => n, 'data' => [field => value]]] */
    public function extract(array $rows): array
    {
        if (count($rows) < 2) {
            throw new RuntimeException('The file has no data rows. Row 1 must be the column headings, with employees from row 2.');
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            throw new RuntimeException('Please import at most ' . self::MAX_ROWS . ' employees per file.');
        }

        ['map' => $map, 'unknown' => $unknown] = $this->map($rows[0]);

        if (! in_array('first_name', $map, true) || ! in_array('last_name', $map, true)) {
            throw new RuntimeException('Couldn\'t find "First name" and "Last name" columns. Please use the template (row 1 = headings).');
        }

        $out = [];
        foreach (array_slice($rows, 1) as $k => $r) {
            $data = [];
            foreach ($map as $i => $field) {
                $v = $r[$i] ?? null;
                $data[$field] = is_string($v) ? trim($v) : $v;
            }
            $out[] = ['line' => $k + 2, 'data' => $data];
        }

        return ['rows' => $out, 'columns' => array_values($map), 'unknown' => $unknown];
    }

    /* ===================== ANALYSE ===================== */

    /**
     * @return array{rows: array, summary: array}
     */
    public function analyse(array $rows, string $mode = 'create_update'): array
    {
        $seenEin = [];
        $seenNrc = [];
        $emails = [];
        $result = [];

        // Lookups for matching existing employees
        $existing = Employee::get(['id', 'employee_id', 'first_name', 'middle_name', 'last_name', 'nrc_no', 'company_email', 'personal_email']);
        $byEin = $existing->keyBy(fn ($e) => strtolower((string) $e->employee_id));
        $byNrc = $existing->filter(fn ($e) => $e->nrc_no)->keyBy(fn ($e) => $this->nrcKey($e->nrc_no));
        $byEmail = collect();
        foreach ($existing as $e) {
            foreach (['company_email', 'personal_email'] as $c) {
                if ($e->$c) {
                    $byEmail[strtolower($e->$c)] = $e;
                }
            }
        }

        foreach ($rows as $row) {
            $errors = [];
            $warnings = [];
            [$data, $normErrors] = $this->normalise($row['data']);
            $errors = array_merge($errors, $normErrors);

            // ---- match existing ----
            $match = null;
            $matchedBy = null;
            if (! empty($data['employee_id']) && $byEin->has(strtolower($data['employee_id']))) {
                $match = $byEin[strtolower($data['employee_id'])];
                $matchedBy = 'EIN';
            } elseif (! empty($data['nrc_no']) && $byNrc->has($this->nrcKey($data['nrc_no']))) {
                $match = $byNrc[$this->nrcKey($data['nrc_no'])];
                $matchedBy = 'NRC';
            } else {
                foreach (['company_email', 'personal_email'] as $c) {
                    if (! empty($data[$c]) && $byEmail->has($data[$c])) {
                        $match = $byEmail[$data[$c]];
                        $matchedBy = 'email';
                        break;
                    }
                }
            }

            // ---- required for new people ----
            if (! $match) {
                foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $f => $label) {
                    if (empty($data[$f])) {
                        $errors[] = "{$label} is required for a new employee";
                    }
                }
                if (empty($data['position'])) {
                    $warnings[] = 'No position';
                }
            }

            // ---- field validation ----
            $v = Validator::make($data, collect(EmployeeFields::rules())
                ->map(fn ($r) => str_replace(['required|', '|after_or_equal:contract_start', '|after_or_equal:probation_start', '|before:today'], ['nullable|', '', '', ''], $r))
                ->all() + ['annual_leave_entitlement' => 'nullable|numeric|min:0|max:365', 'leave_balance' => 'nullable|numeric|min:-365|max:365']);
            foreach ($v->errors()->messages() as $field => $msgs) {
                $errors[] = EmployeeFields::label($field) . ': ' . str_replace('The ' . str_replace('_', ' ', $field) . ' field', 'value', $msgs[0]);
            }

            // ---- date sanity ----
            if (! empty($data['contract_start']) && ! empty($data['contract_end']) && $data['contract_end'] < $data['contract_start']) {
                $errors[] = 'Contract end is before the start date';
            }
            if (! empty($data['probation_start']) && ! empty($data['probation_end']) && $data['probation_end'] < $data['probation_start']) {
                $errors[] = 'Probation end is before probation start';
            }
            if (! empty($data['date_of_birth']) && $data['date_of_birth'] >= now()->toDateString()) {
                $errors[] = 'Date of birth is in the future';
            }
            if (($data['employment_nature'] ?? null) === 'CONTRACT' && empty($data['contract_end']) && ! $match) {
                $warnings[] = 'Contract staff without an end date (no expiry reminders)';
            }
            if (! empty($data['contract_end']) && $data['contract_end'] < now()->toDateString() && ($data['employment_status'] ?? 'Active') === 'Active') {
                $warnings[] = 'Contract already ended but status is Active';
            }

            // ---- duplicates inside the file ----
            if (! empty($data['employee_id'])) {
                $k = strtolower($data['employee_id']);
                if (isset($seenEin[$k])) {
                    $errors[] = "EIN {$data['employee_id']} also on row {$seenEin[$k]}";
                }
                $seenEin[$k] = $row['line'];
            }
            if (! empty($data['nrc_no'])) {
                $k = $this->nrcKey($data['nrc_no']);
                if (isset($seenNrc[$k])) {
                    $errors[] = "NRC {$data['nrc_no']} also on row {$seenNrc[$k]}";
                }
                $seenNrc[$k] = $row['line'];
            }
            foreach (['company_email', 'personal_email'] as $c) {
                if (! empty($data[$c])) {
                    if (isset($emails[$data[$c]]) && $emails[$data[$c]] !== $row['line']) {
                        $warnings[] = "{$data[$c]} also on row {$emails[$data[$c]]}";
                    }
                    $emails[$data[$c]] ??= $row['line'];
                    if ($byEmail->has($data[$c]) && $match && $byEmail[$data[$c]]->id !== $match->id) {
                        $warnings[] = "{$data[$c]} belongs to " . $byEmail[$data[$c]]->full_name;
                    }
                }
            }

            $action = match (true) {
                (bool) $errors                       => 'error',
                $match && $mode === 'create_only'    => 'skip',
                (bool) $match                        => 'update',
                default                              => 'create',
            };

            if ($action === 'update') {
                $full = Employee::find($match->id);
                $changes = collect($data)->except(['supervisor'])->filter(fn ($val) => $val !== null && $val !== '')
                    ->filter(function ($val, $f) use ($full) {
                        $cur = $full->$f;
                        $cur = $cur instanceof \DateTimeInterface ? $cur->format('Y-m-d') : (string) $cur;

                        return (string) $val !== $cur && ! (is_numeric($val) && is_numeric($cur) && (float) $val == (float) $cur);
                    })->keys()->all();
                if (! $changes && empty($data['supervisor'])) {
                    $action = 'unchanged';
                }
            }

            $result[] = [
                'line'       => $row['line'],
                'data'       => $data,
                'action'     => $action,
                'match_id'   => $match?->id,
                'match_name' => $match?->full_name,
                'matched_by' => $matchedBy,
                'changes'    => $changes ?? [],
                'errors'     => $errors,
                'warnings'   => $warnings,
            ];
            unset($changes);
        }

        // Supervisor names must exist (in the database or in this file)
        $names = collect($result)->map(fn ($r) => strtolower(trim(($r['data']['first_name'] ?? '') . ' ' . ($r['data']['last_name'] ?? ''))))->filter()->all();
        foreach ($result as &$r) {
            $sup = $r['data']['supervisor'] ?? null;
            if ($sup && ! $this->findSupervisor($sup) && ! in_array(strtolower($sup), $names, true)) {
                $r['warnings'][] = "Supervisor \"{$sup}\" not found, so it will be left blank";
            }
        }
        unset($r);

        $c = collect($result)->countBy('action');

        return [
            'rows'    => $result,
            'summary' => [
                'total'     => count($result),
                'create'    => $c['create'] ?? 0,
                'update'    => $c['update'] ?? 0,
                'unchanged' => $c['unchanged'] ?? 0,
                'skip'      => $c['skip'] ?? 0,
                'error'     => $c['error'] ?? 0,
                'warnings'  => collect($result)->filter(fn ($r) => $r['warnings'])->count(),
            ],
        ];
    }

    /* ===================== COMMIT ===================== */

    /**
     * @return array{created: Employee[], updated: int, skipped: int, errors: array}
     */
    public function commit(array $analysis, array $options, ?User $actor): array
    {
        $created = [];
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $touched = [];   // line => employee
        $ledger = app(LeaveBalance::class);
        $defaultEnt = (float) ($options['default_entitlement'] ?? config('leave.annual_entitlement', 24));

        foreach ($analysis['rows'] as $row) {
            if (! in_array($row['action'], ['create', 'update'], true)) {
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($row, $actor, $ledger, $defaultEnt, &$created, &$updated, &$touched) {
                    $data = $row['data'];
                    $fields = collect($data)->only(array_keys(EmployeeFields::all()))->filter(fn ($v) => $v !== null && $v !== '')->all();

                    if ($row['action'] === 'create') {
                        $e = new Employee($fields);
                        $e->employee_id = $data['employee_id'] ?: $this->newEin();
                        $e->employment_status = $fields['employment_status'] ?? 'Active';
                        $e->employment_nature = $fields['employment_nature'] ?? (! empty($fields['contract_end']) ? 'CONTRACT' : 'PERMANENT');
                        $e->annual_leave_entitlement = $data['annual_leave_entitlement'] ?? $defaultEnt;
                        $e->leave_balance = $data['leave_balance'] ?? $e->annual_leave_entitlement;
                        $e->save();

                        LeaveBalanceAdjustment::create([
                            'employee_id' => $e->id, 'user_id' => $actor?->id, 'type' => 'opening',
                            'days' => $e->leave_balance, 'balance_before' => 0, 'balance_after' => $e->leave_balance,
                            'year' => now()->year, 'reason' => 'Opening balance (bulk import)',
                        ]);
                        $created[] = $e;
                    } else {
                        $e = Employee::lockForUpdate()->findOrFail($row['match_id']);
                        $e->fill($fields);
                        if (! empty($data['employee_id'])) {
                            $e->employee_id = $data['employee_id'];
                        }
                        if (isset($data['annual_leave_entitlement'])) {
                            $e->annual_leave_entitlement = $data['annual_leave_entitlement'];
                        }
                        $e->save();
                        if (isset($data['leave_balance']) && round((float) $data['leave_balance'], 1) !== round((float) $e->leave_balance, 1)) {
                            $ledger->set($e, (float) $data['leave_balance'], 'edit', 'Bulk import', $actor?->id);
                        }
                        $updated++;
                    }

                    $touched[$row['line']] = $e;
                });
            } catch (\Throwable $ex) {
                $errors[] = "Row {$row['line']}: " . $ex->getMessage();
            }
        }

        // Second pass: supervisors (may refer to people created above)
        foreach ($analysis['rows'] as $row) {
            $sup = $row['data']['supervisor'] ?? null;
            $e = $touched[$row['line']] ?? null;
            if (! $sup || ! $e) {
                continue;
            }
            $found = $this->findSupervisor($sup);
            if ($found instanceof Employee && $found->id !== $e->id) {
                $e->forceFill(['supervisor_employee_id' => $found->id, 'supervisor_user_id' => null, 'supervisor' => $found->full_name])->save();
            } elseif ($found instanceof User) {
                $e->forceFill(['supervisor_user_id' => $found->id, 'supervisor_employee_id' => null, 'supervisor' => $found->name])->save();
            } else {
                $e->forceFill(['supervisor' => $sup])->save();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }

    /* ===================== HELPERS ===================== */

    /** @return array{0: array, 1: string[]} */
    public function normalise(array $data): array
    {
        $errors = [];

        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $v = trim(preg_replace('/\s+/', ' ', $v));
            }
            $data[$k] = ($v === '' || $v === '-' || $v === '—' || strtolower((string) $v) === 'n/a') ? null : $v;
        }

        foreach (self::DATE_FIELDS as $f) {
            if (! empty($data[$f])) {
                $parsed = $this->parseDate($data[$f]);
                if ($parsed === null) {
                    $errors[] = EmployeeFields::label($f) . ": \"{$data[$f]}\" isn't a date I recognise (use YYYY-MM-DD or DD/MM/YYYY)";
                }
                $data[$f] = $parsed;
            }
        }

        foreach (['company_email', 'personal_email'] as $f) {
            if (! empty($data[$f])) {
                $data[$f] = strtolower($data[$f]);
            }
        }

        foreach (['primary_phone', 'secondary_phone', 'emergency_phone', 'next_of_kin_phone'] as $f) {
            if (! empty($data[$f])) {
                $p = preg_replace('/[^\d+]/', '', (string) $data[$f]);
                // Excel drops the leading 0: 977123456 → 0977123456
                if (preg_match('/^[79]\d{8}$/', $p)) {
                    $p = '0' . $p;
                }
                $data[$f] = $p ?: $data[$f];
            }
        }

        if (! empty($data['gender'])) {
            $g = strtolower($data['gender']);
            $data['gender'] = match (true) { in_array($g, ['m', 'male', 'man'], true) => 'Male', in_array($g, ['f', 'female', 'woman'], true) => 'Female', default => $data['gender'] };
        }

        if (! empty($data['marital_status'])) {
            $data['marital_status'] = ucfirst(strtolower($data['marital_status']));
        }

        if (! empty($data['employment_status'])) {
            $s = strtolower($data['employment_status']);
            $data['employment_status'] = match (true) {
                in_array($s, ['active', 'current', 'employed', 'yes', '1'], true) => 'Active',
                in_array($s, ['suspended', 'on hold'], true) => 'Suspended',
                in_array($s, ['exited', 'terminated', 'resigned', 'left', 'dismissed', 'retired', 'inactive', 'no', '0'], true) => 'Exited',
                default => $data['employment_status'],
            };
        }

        if (! empty($data['employment_nature'])) {
            $n = strtolower($data['employment_nature']);
            $data['employment_nature'] = match (true) {
                str_starts_with($n, 'perm') => 'PERMANENT',
                str_starts_with($n, 'contract') || str_starts_with($n, 'fixed') => 'CONTRACT',
                str_starts_with($n, 'casual') || str_starts_with($n, 'temp') || str_starts_with($n, 'part') => 'CASUAL',
                default => strtoupper($data['employment_nature']),
            };
        }

        foreach (['salary', 'annual_leave_entitlement', 'leave_balance'] as $f) {
            if (isset($data[$f]) && $data[$f] !== null) {
                $clean = preg_replace('/[^\d.\-]/', '', str_ireplace(['zmw', 'k '], '', (string) $data[$f]));
                $data[$f] = is_numeric($clean) ? (float) $clean : $data[$f];
            }
        }

        foreach (['employee_id', 'nrc_no', 'ssn', 'tpin', 'nhima_no', 'bank_account_no', 'bank_sort_code'] as $f) {
            if (isset($data[$f]) && is_float($data[$f])) {
                $data[$f] = number_format($data[$f], 0, '', '');   // Excel made it a number
            }
            if (isset($data[$f]) && $data[$f] !== null) {
                $data[$f] = (string) $data[$f];
            }
        }

        return [$data, $errors];
    }

    public function parseDate($v): ?string
    {
        if ($v instanceof \DateTimeInterface) {
            return Carbon::instance($v)->toDateString();
        }
        $v = trim((string) $v);

        // Excel serial number (days since 1899-12-30)
        if (preg_match('/^\d{4,5}(\.\d+)?$/', $v) && (float) $v > 1000 && (float) $v < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $v)->toDateString();
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'd/m/y', 'd M Y', 'd F Y', 'j M Y', 'j F Y', 'M d, Y', 'F d, Y', 'Y-m-d H:i:s', 'd-M-Y', 'd-M-y'] as $fmt) {
            $d = \DateTime::createFromFormat('!' . $fmt, $v);
            $errs = \DateTime::getLastErrors();
            if ($d && (! $errs || ($errs['warning_count'] === 0 && $errs['error_count'] === 0))) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }

    /** Find a supervisor by EIN, email or full name (employee first, then admin user). */
    public function findSupervisor(string $ref): Employee|User|null
    {
        $ref = trim($ref);
        $lower = strtolower($ref);

        $e = Employee::where('employee_id', $ref)->first()
            ?? Employee::whereRaw('LOWER(company_email) = ? OR LOWER(personal_email) = ?', [$lower, $lower])->first()
            ?? Employee::get(['id', 'first_name', 'middle_name', 'last_name'])->first(fn ($x) => in_array($lower, [
                strtolower($x->full_name), strtolower(trim($x->first_name . ' ' . $x->last_name)),
            ], true));

        if ($e) {
            return Employee::find($e->id);
        }

        return User::whereNull('employee_id')->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [$lower])->orWhereRaw('LOWER(email) = ?', [$lower]))->first();
    }

    private function nrcKey(?string $nrc): string
    {
        return preg_replace('/[^0-9]/', '', (string) $nrc);
    }

    private function newEin(): string
    {
        do {
            $ein = 'EMP-' . date('Y') . '-' . strtoupper(Str::random(5));
        } while (Employee::where('employee_id', $ein)->exists());

        return $ein;
    }
}
