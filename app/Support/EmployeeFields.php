<?php

namespace App\Support;

/**
 * One definition of every employee field: label, input type, options, which
 * wizard step it lives on, validation, and who may change it in the portal.
 *
 * Used by: the admin add/edit wizard, the employee profile, bulk import,
 * CSV export and the self-service portal.
 *
 * portal: 'direct'    employee can change it immediately
 *         'approval'  employee can request a change; HR approves (see config/portal.php)
 *         'readonly'  employee can see it, only HR changes it
 *         'hidden'    not shown in the portal
 */
class EmployeeFields
{
    public const STEPS = [
        'personal'  => ['Personal',            'bi-person'],
        'contact'   => ['Contact & family',    'bi-telephone'],
        'job'       => ['Job & contract',      'bi-briefcase'],
        'pay'       => ['Pay & statutory',     'bi-bank'],
        'extras'    => ['Leave & documents',   'bi-folder2-open'],
    ];

    public const GENDERS = ['Male', 'Female'];
    public const MARITAL = ['Single', 'Married', 'Divorced', 'Widowed', 'Separated'];
    public const STATUSES = ['Active', 'Suspended', 'Exited'];
    public const NATURES = ['PERMANENT' => 'Permanent', 'CONTRACT' => 'Fixed-term contract', 'CASUAL' => 'Casual'];
    public const DOC_CATEGORIES = ['Contract', 'ID / NRC', 'Passport', 'Qualification', 'CV', 'Medical', 'Bank details', 'Letter', 'Other'];

    /** @return array<string, array> */
    public static function all(): array
    {
        return [
            // ---------------- PERSONAL ----------------
            'first_name'      => ['label' => 'First name', 'step' => 'personal', 'rules' => 'required|string|max:100', 'portal' => 'approval', 'col' => 4, 'required' => true],
            'middle_name'     => ['label' => 'Middle name', 'step' => 'personal', 'rules' => 'nullable|string|max:100', 'portal' => 'approval', 'col' => 4],
            'last_name'       => ['label' => 'Last name', 'step' => 'personal', 'rules' => 'required|string|max:100', 'portal' => 'approval', 'col' => 4, 'required' => true],
            'date_of_birth'   => ['label' => 'Date of birth', 'step' => 'personal', 'type' => 'date', 'rules' => 'nullable|date|before:today', 'portal' => 'approval', 'col' => 4],
            'gender'          => ['label' => 'Gender', 'step' => 'personal', 'type' => 'select', 'options' => self::GENDERS, 'rules' => 'nullable|in:Male,Female', 'portal' => 'direct', 'col' => 4],
            'marital_status'  => ['label' => 'Marital status', 'step' => 'personal', 'type' => 'select', 'options' => self::MARITAL, 'rules' => 'nullable|string|max:30', 'portal' => 'direct', 'col' => 4],
            'nationality'     => ['label' => 'Nationality', 'step' => 'personal', 'rules' => 'nullable|string|max:100', 'portal' => 'direct', 'col' => 4, 'placeholder' => 'Zambian'],
            'nrc_no'          => ['label' => 'NRC number', 'step' => 'personal', 'rules' => 'nullable|string|max:30', 'portal' => 'approval', 'col' => 4, 'placeholder' => '123456/78/1', 'hint' => 'Format 123456/78/1'],
            'passport_number' => ['label' => 'Passport number', 'step' => 'personal', 'rules' => 'nullable|string|max:30', 'portal' => 'approval', 'col' => 4],

            // ---------------- CONTACT & FAMILY ----------------
            'company_email'          => ['label' => 'Company email', 'step' => 'contact', 'type' => 'email', 'rules' => 'nullable|email|max:255', 'portal' => 'readonly', 'col' => 6, 'hint' => 'Used as the portal login if set'],
            'personal_email'         => ['label' => 'Personal email', 'step' => 'contact', 'type' => 'email', 'rules' => 'nullable|email|max:255', 'portal' => 'direct', 'col' => 6],
            'primary_phone'          => ['label' => 'Phone', 'step' => 'contact', 'type' => 'tel', 'rules' => 'nullable|string|max:30', 'portal' => 'direct', 'col' => 6, 'placeholder' => '097 1234567'],
            'secondary_phone'        => ['label' => 'Alternative phone', 'step' => 'contact', 'type' => 'tel', 'rules' => 'nullable|string|max:30', 'portal' => 'direct', 'col' => 6],
            'residential_address'    => ['label' => 'Home address', 'step' => 'contact', 'type' => 'textarea', 'rules' => 'nullable|string|max:500', 'portal' => 'direct', 'col' => 12],
            'emergency_name'         => ['label' => 'Emergency contact name', 'step' => 'contact', 'group' => 'Emergency contact', 'rules' => 'nullable|string|max:255', 'portal' => 'direct', 'col' => 4],
            'emergency_relationship' => ['label' => 'Relationship', 'step' => 'contact', 'group' => 'Emergency contact', 'rules' => 'nullable|string|max:100', 'portal' => 'direct', 'col' => 4],
            'emergency_phone'        => ['label' => 'Emergency phone', 'step' => 'contact', 'group' => 'Emergency contact', 'type' => 'tel', 'rules' => 'nullable|string|max:30', 'portal' => 'direct', 'col' => 4],
            'next_of_kin_name'       => ['label' => 'Next of kin name', 'step' => 'contact', 'group' => 'Next of kin', 'rules' => 'nullable|string|max:255', 'portal' => 'direct', 'col' => 4],
            'next_of_kin_phone'      => ['label' => 'Next of kin phone', 'step' => 'contact', 'group' => 'Next of kin', 'type' => 'tel', 'rules' => 'nullable|string|max:30', 'portal' => 'direct', 'col' => 4],
            'next_of_kin_address'    => ['label' => 'Next of kin address', 'step' => 'contact', 'group' => 'Next of kin', 'rules' => 'nullable|string|max:500', 'portal' => 'direct', 'col' => 4],

            // ---------------- JOB & CONTRACT ----------------
            'position'          => ['label' => 'Position / job title', 'step' => 'job', 'rules' => 'required|string|max:150', 'portal' => 'readonly', 'col' => 6, 'required' => true],
            'department'        => ['label' => 'Department', 'step' => 'job', 'rules' => 'nullable|string|max:150', 'portal' => 'readonly', 'col' => 6, 'datalist' => 'department'],
            'branch'            => ['label' => 'Branch', 'step' => 'job', 'rules' => 'nullable|string|max:150', 'portal' => 'readonly', 'col' => 6, 'datalist' => 'branch'],
            'employment_status' => ['label' => 'Status', 'step' => 'job', 'type' => 'select', 'options' => self::STATUSES, 'rules' => 'nullable|in:Active,Suspended,Exited', 'portal' => 'readonly', 'col' => 3, 'default' => 'Active'],
            'employment_nature' => ['label' => 'Employment type', 'step' => 'job', 'type' => 'select', 'options' => self::NATURES, 'rules' => 'nullable|in:PERMANENT,CONTRACT,CASUAL', 'portal' => 'readonly', 'col' => 3, 'default' => 'PERMANENT'],
            'contract_start'    => ['label' => 'Start date', 'step' => 'job', 'group' => 'Dates', 'type' => 'date', 'rules' => 'nullable|date', 'portal' => 'readonly', 'col' => 3],
            'contract_end'      => ['label' => 'Contract end', 'step' => 'job', 'group' => 'Dates', 'type' => 'date', 'rules' => 'nullable|date|after_or_equal:contract_start', 'portal' => 'readonly', 'col' => 3, 'hint' => 'Blank for permanent staff'],
            'probation_start'   => ['label' => 'Probation start', 'step' => 'job', 'group' => 'Dates', 'type' => 'date', 'rules' => 'nullable|date', 'portal' => 'readonly', 'col' => 3],
            'probation_end'     => ['label' => 'Probation end', 'step' => 'job', 'group' => 'Dates', 'type' => 'date', 'rules' => 'nullable|date|after_or_equal:probation_start', 'portal' => 'readonly', 'col' => 3],

            // ---------------- PAY & STATUTORY ----------------
            'salary'            => ['label' => 'Basic salary (ZMW)', 'step' => 'pay', 'type' => 'number', 'rules' => 'nullable|numeric|min:0', 'portal' => 'readonly', 'col' => 4, 'mask' => false],
            'bank_name'         => ['label' => 'Bank', 'step' => 'pay', 'group' => 'Bank', 'rules' => 'nullable|string|max:150', 'portal' => 'approval', 'col' => 4, 'datalist' => 'bank_name'],
            'bank_branch'       => ['label' => 'Bank branch', 'step' => 'pay', 'group' => 'Bank', 'rules' => 'nullable|string|max:150', 'portal' => 'approval', 'col' => 4],
            'bank_account_no'   => ['label' => 'Account number', 'step' => 'pay', 'group' => 'Bank', 'rules' => 'nullable|string|max:40', 'portal' => 'approval', 'col' => 4, 'mask' => true],
            'bank_sort_code'    => ['label' => 'Sort code', 'step' => 'pay', 'group' => 'Bank', 'rules' => 'nullable|string|max:20', 'portal' => 'approval', 'col' => 4],
            'ssn'               => ['label' => 'NAPSA number', 'step' => 'pay', 'group' => 'Statutory', 'rules' => 'nullable|string|max:40', 'portal' => 'approval', 'col' => 4, 'mask' => true],
            'tpin'              => ['label' => 'ZRA TPIN', 'step' => 'pay', 'group' => 'Statutory', 'rules' => 'nullable|string|max:20', 'portal' => 'approval', 'col' => 4, 'mask' => true],
            'nhima_no'          => ['label' => 'NHIMA number', 'step' => 'pay', 'group' => 'Statutory', 'rules' => 'nullable|string|max:40', 'portal' => 'approval', 'col' => 4, 'mask' => true],
        ];
    }

    public static function get(string $field): ?array
    {
        return self::all()[$field] ?? null;
    }

    public static function label(string $field): string
    {
        return self::all()[$field]['label'] ?? ucwords(str_replace('_', ' ', $field));
    }

    /** Fields of one wizard step, grouped by sub-heading. */
    public static function forStep(string $step): array
    {
        return collect(self::all())->filter(fn ($f) => $f['step'] === $step)->all();
    }

    /** Validation rules for the admin form. */
    public static function rules(): array
    {
        return collect(self::all())->map(fn ($f) => $f['rules'])->all();
    }

    public static function portal(string $mode): array
    {
        return array_keys(array_filter(self::all(), fn ($f) => $f['portal'] === $mode));
    }

    /** "•••• 1234" */
    public static function mask(?string $v): ?string
    {
        if (! filled($v)) {
            return null;
        }

        return str_repeat('•', max(strlen($v) - 4, 2)) . substr($v, -4);
    }

    /** Display value for a field (formats dates, select labels). */
    public static function display($employee, string $field): ?string
    {
        $v = $employee->$field;
        if (! filled($v)) {
            return null;
        }
        $def = self::get($field) ?? [];

        if (($def['type'] ?? null) === 'date') {
            return \Carbon\Carbon::parse($v)->format('d M Y');
        }
        if ($field === 'salary') {
            return 'K ' . number_format((float) $v, 2);
        }
        if ($field === 'employment_nature') {
            return self::NATURES[$v] ?? $v;
        }

        return (string) $v;
    }
}
