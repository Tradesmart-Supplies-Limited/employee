<?php

namespace App\Http\Controllers;

use App\Models\PayrollRule;
use Illuminate\Http\Request;

class PayrollRuleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:earning,deduction',
            'formula_type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric',
            'applies_to' => 'nullable|string',
        ]);

        PayrollRule::create([
            'name' => $request->name,
            'type' => $request->type,
            'formula_type' => $request->formula_type,
            'value' => $request->value,
            'applies_to' => $request->applies_to,
            'active' => true,
        ]);

        return back()->with('success', 'Payroll rule added successfully.');
    }

        public function bulkUpdate(Request $request)
    {
        $rules = $request->input('rules', []);

        foreach ($rules as $id => $data) {

            $rule = PayrollRule::find($id);

            if (!$rule) {
                continue;
            }

            // ❌ DELETE RULE
            if (isset($data['delete']) && $data['delete'] == 1) {
                $rule->delete();
                continue;
            }

            // 🔄 UPDATE RULE
            $rule->update([
                'name' => $data['name'] ?? $rule->name,
                'type' => $data['type'] ?? $rule->type,
                'formula_type' => $data['formula_type'] ?? $rule->formula_type,
                'value' => $data['value'] ?? $rule->value,
                'applies_to' => $data['applies_to'] ?? $rule->applies_to,
                'active' => isset($data['active']) ? 1 : 0,
            ]);
        }

        return back()->with('success', 'Payroll rules updated successfully.');
    }
}