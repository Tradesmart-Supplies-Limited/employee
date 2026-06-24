<?php

namespace App\Http\Controllers;

use App\Models\PayrollRule;
use Illuminate\Http\Request;

class PayrollRuleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Boolean flag fields — single source of truth for store() and update()
    |--------------------------------------------------------------------------
    */
    private const BOOLEAN_FIELDS = [
        'is_statutory',
        'is_recurring',
        'requires_assignment',
        'affects_gross',
        'affects_net',
        'show_on_payslip',
        'is_pensionable',
    ];

    /*
    |--------------------------------------------------------------------------
    | LIST / SEARCH — returns JSON, used by the modal's live search box
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = PayrollRule::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $rules = $query->orderBy('type')->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'rules'   => $rules,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE NEW RULE — returns JSON (AJAX form, no page reload)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'code'         => 'nullable|string|max:50|unique:payroll_rules,code',
            'type'         => 'required|in:earning,deduction,system',
            'category'     => 'nullable|string|max:100',
            'formula_type' => 'required|in:fixed,percentage',
            'value'        => 'required|numeric|min:0',
            'applies_to'   => 'nullable|in:BASICPAY,GROSSPAY',
            'tax_profile'  => 'required|in:taxable,napsa_only,non_taxable',
            'description'  => 'nullable|string|max:500',
            'sort_order'   => 'nullable|integer',
        ]);

        $validated['active'] = true;

        foreach (self::BOOLEAN_FIELDS as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $rule = PayrollRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rule created.',
            'rule'    => $rule,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE SINGLE RULE — used by inline editing, one field or many at once
    |--------------------------------------------------------------------------
    |
    | Inline editing sends only the field(s) that changed, e.g.:
    |   PATCH /payroll/rules/14   { "value": 250.00 }
    |   PATCH /payroll/rules/14   { "is_statutory": true }
    |
    | All fields use "sometimes" validation so a single-field PATCH never
    | fails because other fields are "missing" — only what's present in the
    | request gets validated and written.
    |
    | Booleans are read with $request->boolean() ONLY when the key is present
    | (guarded by has()), so omitted boolean fields are left untouched rather
    | than being coerced to false. This is what makes "click cell, toggle one
    | checkbox" safe without a full-row form submit.
    |
    */
    public function update(Request $request, PayrollRule $rule)
    {
        $validated = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'code'         => 'sometimes|nullable|string|max:50|unique:payroll_rules,code,' . $rule->id,
            'type'         => 'sometimes|required|in:earning,deduction',
            'category'     => 'sometimes|nullable|string|max:100',
            'formula_type' => 'sometimes|required|in:fixed,percentage',
            'value'        => 'sometimes|required|numeric|min:0',
            'applies_to'   => 'sometimes|nullable|in:BASICPAY,GROSSPAY',
            'tax_profile'  => 'sometimes|required|in:taxable,napsa_only,non_taxable',
            'description'  => 'sometimes|nullable|string|max:500',
            'sort_order'   => 'sometimes|nullable|integer',
            'active'       => 'sometimes|boolean',

            'is_statutory'        => 'sometimes|boolean',
            'is_recurring'        => 'sometimes|boolean',
            'requires_assignment' => 'sometimes|boolean',
            'affects_gross'       => 'sometimes|boolean',
            'affects_net'         => 'sometimes|boolean',
            'show_on_payslip'     => 'sometimes|boolean',
            'is_pensionable'      => 'sometimes|boolean',
        ]);

        // Re-cast booleans explicitly — Laravel's validated array already
        // converts "true"/"1"/true to real booleans via the 'boolean' rule,
        // but we only touch fields that were actually present in the payload.
        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Saved.',
            'rule'    => $rule->fresh(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE RULE
    |--------------------------------------------------------------------------
    */
    public function destroy(PayrollRule $rule)
    {
        $name = $rule->name;
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => "\"{$name}\" deleted.",
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SEED DEFAULT RULES (FULL ZAMBIAN SET)
    |--------------------------------------------------------------------------
    */
    // public function seedDefaults()
    // {
    //     $rules = [

    //         // =========================
    //         // EARNINGS
    //         // =========================
    //         ['name' => 'Acting Allowance', 'code' => 'E001', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Responsibility Allowance', 'code' => 'E002', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Overtime', 'code' => 'E003', 'type' => 'earning', 'category' => 'overtime', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Double Overtime', 'code' => 'E004', 'type' => 'earning', 'category' => 'overtime', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Commission', 'code' => 'E005', 'type' => 'earning', 'category' => 'commission', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Bonus', 'code' => 'E006', 'type' => 'earning', 'category' => 'bonus', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Performance Bonus', 'code' => 'E007', 'type' => 'earning', 'category' => 'bonus', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Night Shift Allowance', 'code' => 'E008', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Risk Allowance', 'code' => 'E009', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Hardship Allowance', 'code' => 'E010', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Cellphone Allowance', 'code' => 'E011', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Fuel Allowance', 'code' => 'E012', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Mileage Claim', 'code' => 'E013', 'type' => 'earning', 'category' => 'reimbursement', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Travel Allowance', 'code' => 'E014', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Per Diem', 'code' => 'E015', 'type' => 'earning', 'category' => 'allowance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Ex-Gratia', 'code' => 'E016', 'type' => 'earning', 'category' => 'termination', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'napsa_only'],
    //         ['name' => 'Gratuity', 'code' => 'E017', 'type' => 'earning', 'category' => 'termination', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'napsa_only'],
    //         ['name' => 'Leave Pay', 'code' => 'E018', 'type' => 'earning', 'category' => 'leave', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Leave Encashment', 'code' => 'E019', 'type' => 'earning', 'category' => 'leave', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],
    //         ['name' => 'Payment In Lieu Of Notice', 'code' => 'E020', 'type' => 'earning', 'category' => 'termination', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'taxable'],

    //         // =========================
    //         // DEDUCTIONS
    //         // =========================
    //         ['name' => 'Staff Loan', 'code' => 'D001', 'type' => 'deduction', 'category' => 'loan', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Salary Advance', 'code' => 'D002', 'type' => 'deduction', 'category' => 'advance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Union Subscription', 'code' => 'D003', 'type' => 'deduction', 'category' => 'statutory', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Medical Scheme', 'code' => 'D004', 'type' => 'deduction', 'category' => 'benefit', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Life Insurance', 'code' => 'D005', 'type' => 'deduction', 'category' => 'insurance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Funeral Scheme', 'code' => 'D006', 'type' => 'deduction', 'category' => 'insurance', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Court Order Garnishment', 'code' => 'D007', 'type' => 'deduction', 'category' => 'legal', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Staff Welfare', 'code' => 'D008', 'type' => 'deduction', 'category' => 'welfare', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Savings Scheme', 'code' => 'D009', 'type' => 'deduction', 'category' => 'savings', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'BASICPAY', 'tax_profile' => 'non_taxable'],
    //         ['name' => 'Company Property Recovery', 'code' => 'D010', 'type' => 'deduction', 'category' => 'recovery', 'formula_type' => 'fixed', 'value' => 0, 'applies_to' => 'GROSSPAY', 'tax_profile' => 'non_taxable'],
    //     ];

    //     foreach ($rules as $rule) {
    //         PayrollRule::updateOrCreate(
    //             ['code' => $rule['code']],
    //             array_merge($rule, ['active' => true])
    //         );
    //     }

    //     return back()->with('success', 'Payroll rules seeded successfully.');
    // }


public function seedDefaults()
{
    $rules = [

        // =========================
        // BASIC PAY
        // =========================
        [
            'name' => 'Basic Pay',
            'code' => 'P00',
            'type' => 'earning',
            'category' => 'basic',
            'formula_type' => 'fixed',
            'value' => 0,
            'applies_to' => 'BASICPAY',
            'tax_profile' => 'non_taxable',
            'is_statutory' => 0,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 1,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 1,
            'sort_order' => 1,
            'description' => 'Base salary',
            'active' => 1,
        ],

        // =========================
        // BUILT-IN ALLOWANCES
        // =========================
        [
            'name' => 'Housing Allowance',
            'code' => 'P01',
            'type' => 'earning',
            'category' => 'allowance',
            'formula_type' => 'percentage',
            'value' => 30.00,
            'applies_to' => 'BASICPAY',
            'tax_profile' => 'taxable',
            'is_statutory' => 0,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 1,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 1,
            'sort_order' => 2,
            'description' => 'Housing allowance',
            'active' => 1,
        ],

        [
            'name' => 'Lunch Allowance',
            'code' => 'P02',
            'type' => 'earning',
            'category' => 'allowance',
            'formula_type' => 'percentage',
            'value' => 5.85,
            'applies_to' => 'BASICPAY',
            'tax_profile' => 'taxable',
            'is_statutory' => 0,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 1,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 1,
            'sort_order' => 3,
            'description' => 'Lunch allowance',
            'active' => 1,
        ],

        [
            'name' => 'Transport Allowance',
            'code' => 'P03',
            'type' => 'earning',
            'category' => 'allowance',
            'formula_type' => 'percentage',
            'value' => 7.00,
            'applies_to' => 'BASICPAY',
            'tax_profile' => 'taxable',
            'is_statutory' => 0,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 1,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 1,
            'sort_order' => 4,
            'description' => 'Transport allowance',
            'active' => 1,
        ],

        // =========================
        // VARIABLE EARNINGS
        // =========================
        [
            'name' => 'Overtime',
            'code' => 'P04',
            'type' => 'earning',
            'category' => 'overtime',
            'formula_type' => 'fixed',
            'value' => 0,
            'applies_to' => 'GROSSPAY',
            'tax_profile' => 'taxable',
            'is_statutory' => 0,
            'is_recurring' => 0,
            'requires_assignment' => 1,
            'affects_gross' => 1,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 0,
            'sort_order' => 10,
            'description' => 'Overtime payment',
            'active' => 1,
        ],

        [
            'name' => 'Bonus',
            'code' => 'P05',
            'type' => 'earning',
            'category' => 'bonus',
            'formula_type' => 'fixed',
            'value' => 0,
            'applies_to' => 'GROSSPAY',
            'tax_profile' => 'taxable',
            'is_statutory' => 0,
            'is_recurring' => 0,
            'requires_assignment' => 1,
            'affects_gross' => 1,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 0,
            'sort_order' => 11,
            'description' => 'Bonus payment',
            'active' => 1,
        ],

        // =========================
        // DEDUCTIONS
        // =========================
        [
            'name' => 'PAYE',
            'code' => 'D00',
            'type' => 'deduction',
            'category' => 'tax',
            'formula_type' => 'fixed',
            'value' => 0,
            'applies_to' => 'GROSSPAY',
            'tax_profile' => 'non_taxable',
            'is_statutory' => 1,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 0,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 0,
            'sort_order' => 90,
            'description' => 'Income tax (PAYE)',
            'active' => 1,
        ],

        [
            'name' => 'NAPSA',
            'code' => 'D01',
            'type' => 'deduction',
            'category' => 'pension',
            'formula_type' => 'percentage',
            'value' => 5.00,
            'applies_to' => 'GROSSPAY',
            'tax_profile' => 'non_taxable',
            'is_statutory' => 1,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 0,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 1,
            'sort_order' => 91,
            'description' => 'NAPSA contribution',
            'active' => 1,
        ],

        [
            'name' => 'NHIMA',
            'code' => 'D02',
            'type' => 'deduction',
            'category' => 'health',
            'formula_type' => 'percentage',
            'value' => 1.00,
            'applies_to' => 'BASICPAY',
            'tax_profile' => 'non_taxable',
            'is_statutory' => 1,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 0,
            'affects_net' => 1,
            'show_on_payslip' => 1,
            'is_pensionable' => 0,
            'sort_order' => 92,
            'description' => 'NHIMA contribution',
            'active' => 1,
        ],

        // =========================
        // CAPS (ENGINE CRITICAL)
        // =========================
        [
            'name' => 'NAPSA Cap',
            'code' => 'SYS01',
            'type' => 'system',
            'category' => 'cap',
            'formula_type' => 'fixed',
            'value' => 1861.80,
            'applies_to' => 'GROSSPAY',
            'tax_profile' => 'non_taxable',
            'is_statutory' => 1,
            'is_recurring' => 1,
            'requires_assignment' => 0,
            'affects_gross' => 0,
            'affects_net' => 0,
            'show_on_payslip' => 0,
            'is_pensionable' => 0,
            'sort_order' => 999,
            'description' => 'NAPSA contribution cap',
            'active' => 1,
        ],


            [
                'name' => 'Fuel Allowance',
                'code' => 'E012',
                'type' => 'earning',
                'category' => 'allowance',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 1,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 100,
                'description' => 'Fuel allowance',
                'active' => 1,
            ],
            [
                'name' => 'Travel Allowance',
                'code' => 'E014',
                'type' => 'earning',
                'category' => 'allowance',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 1,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 101,
                'description' => 'Travel allowance',
                'active' => 1,
            ],
            [
                'name' => 'Ex-Gratia',
                'code' => 'E016',
                'type' => 'earning',
                'category' => 'termination',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'GROSSPAY',
                'tax_profile' => 'napsa_only',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 1,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 102,
                'description' => 'Ex-gratia payment',
                'active' => 1,
            ],
            [
                'name' => 'Gratuity',
                'code' => 'E017',
                'type' => 'earning',
                'category' => 'termination',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'GROSSPAY',
                'tax_profile' => 'napsa_only',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 1,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 103,
                'description' => 'Gratuity payment',
                'active' => 1,
            ],
            [
                'name' => 'Leave Pay',
                'code' => 'E018',
                'type' => 'earning',
                'category' => 'leave',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 1,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 104,
                'description' => 'Leave pay',
                'active' => 1,
            ],
            [
                'name' => 'Payment In Lieu Of Notice',
                'code' => 'E020',
                'type' => 'earning',
                'category' => 'termination',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'GROSSPAY',
                'tax_profile' => 'taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 1,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 105,
                'description' => 'Payment in lieu of notice',
                'active' => 1,
            ],


            [
                'name' => 'Staff Loan',
                'code' => 'D001',
                'type' => 'deduction',
                'category' => 'loan',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'GROSSPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 200,
                'description' => 'Staff loan deduction',
                'active' => 1,
            ],
            [
                'name' => 'Salary Advance',
                'code' => 'D002',
                'type' => 'deduction',
                'category' => 'advance',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'GROSSPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 201,
                'description' => 'Salary advance deduction',
                'active' => 1,
            ],
            [
                'name' => 'Union Subscription',
                'code' => 'D003',
                'type' => 'deduction',
                'category' => 'statutory',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 202,
                'description' => 'Union subscription deduction',
                'active' => 1,
            ],
            [
                'name' => 'Medical Scheme',
                'code' => 'D004',
                'type' => 'deduction',
                'category' => 'benefit',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 203,
                'description' => 'Medical scheme deduction',
                'active' => 1,
            ],
            [
                'name' => 'Life Insurance',
                'code' => 'D005',
                'type' => 'deduction',
                'category' => 'insurance',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 204,
                'description' => 'Life insurance deduction',
                'active' => 1,
            ],
            [
                'name' => 'Funeral Scheme',
                'code' => 'D006',
                'type' => 'deduction',
                'category' => 'insurance',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 205,
                'description' => 'Funeral scheme deduction',
                'active' => 1,
            ],
            [
                'name' => 'Court Order Garnishment',
                'code' => 'D007',
                'type' => 'deduction',
                'category' => 'legal',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'GROSSPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 206,
                'description' => 'Court order garnishment',
                'active' => 1,
            ],
            [
                'name' => 'Savings Scheme',
                'code' => 'D009',
                'type' => 'deduction',
                'category' => 'savings',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'BASICPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 207,
                'description' => 'Savings scheme deduction',
                'active' => 1,
            ],
            [
                'name' => 'Company Property Recovery',
                'code' => 'D010',
                'type' => 'deduction',
                'category' => 'recovery',
                'formula_type' => 'fixed',
                'value' => 0,
                'applies_to' => 'GROSSPAY',
                'tax_profile' => 'non_taxable',
                'is_statutory' => 0,
                'is_recurring' => 1,
                'requires_assignment' => 1,
                'affects_gross' => 0,
                'affects_net' => 1,
                'show_on_payslip' => 1,
                'is_pensionable' => 0,
                'sort_order' => 208,
                'description' => 'Company property recovery',
                'active' => 1,
            ],

    ];

    foreach ($rules as $rule) {

        \App\Models\PayrollRule::updateOrCreate(
            ['code' => $rule['code']],
            $rule
        );
    }

    return back()->with('success', 'Payroll rules seeded successfully.');
}
}