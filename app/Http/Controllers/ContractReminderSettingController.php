<?php

namespace App\Http\Controllers;

use App\Mail\ContractEndingReminder;
use App\Models\ContractReminderSetting;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContractReminderSettingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SHOW SETTINGS SCREEN
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $setting = ContractReminderSetting::first();

        if (! $setting) {
            $setting = ContractReminderSetting::create([
                'name' => 'Default',
                'recipients' => [],
                'use_days_before' => true,
                'days_before' => [14, 7],
                'use_monthly_fixed' => false,
                'monthly_fixed_days' => [1, 14],
                'monthly_lookahead_days' => 60,
                'is_active' => true,
            ]);
        }

        // Preview list: anyone with a contract ending in the next 90 days,
        // just so admins can sanity-check the settings against real data.
        $upcoming = Employee::whereNotNull('contract_end')
            ->whereDate('contract_end', '>=', now()->toDateString())
            ->orderBy('contract_end')
            ->get()
            ->filter(fn ($e) => Carbon::parse($e->contract_end)->diffInDays(now()) <= 90)
            ->values();

        return view('dashboard.settings.contract-reminders', compact('setting', 'upcoming'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE SETTINGS
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, ContractReminderSetting $setting)
    {
        $validated = $request->validate([
            'recipients' => 'required|string',
            'use_days_before' => 'nullable|boolean',
            'days_before' => 'nullable|string',
            'use_monthly_fixed' => 'nullable|boolean',
            'monthly_fixed_days' => 'nullable|string',
            'monthly_lookahead_days' => 'nullable|integer|min:1|max:365',
            'is_active' => 'nullable|boolean',
        ]);

        // "a@b.com, c@d.com" -> ['a@b.com', 'c@d.com'] (valid emails only)
        $recipients = collect(preg_split('/[,;\r\n]+/', $validated['recipients']))
            ->map(fn ($e) => trim($e))
            ->filter()
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        // "14, 7" -> [14, 7]
        $daysBefore = collect(preg_split('/[,;\s]+/', $validated['days_before'] ?? ''))
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        // "1, 14" -> [1, 14]  (clamp to a sane day-of-month range)
        $monthlyDays = collect(preg_split('/[,;\s]+/', $validated['monthly_fixed_days'] ?? ''))
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d >= 1 && $d <= 28)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($recipients)) {
            return back()
                ->withInput()
                ->with('error', 'Enter at least one valid recipient email address.');
        }

        $setting->update([
            'recipients' => $recipients,
            'use_days_before' => $request->boolean('use_days_before'),
            'days_before' => $daysBefore,
            'use_monthly_fixed' => $request->boolean('use_monthly_fixed'),
            'monthly_fixed_days' => $monthlyDays,
            'monthly_lookahead_days' => $validated['monthly_lookahead_days'] ?? 60,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Contract reminder settings updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | SEND A TEST DIGEST RIGHT NOW (bypasses schedule / dedup log)
    |--------------------------------------------------------------------------
    */
    public function sendTest(ContractReminderSetting $setting)
    {
        if (empty($setting->recipients)) {
            return back()->with('error', 'Add at least one recipient email before sending a test.');
        }

        $employees = Employee::whereNotNull('contract_end')
            ->whereDate('contract_end', '>=', now()->toDateString())
            ->orderBy('contract_end')
            ->limit(10)
            ->get();

        if ($employees->isEmpty()) {
            return back()->with('error', 'No employees with upcoming contract end dates to preview.');
        }

        Mail::to($setting->recipients)->send(
            new ContractEndingReminder($employees, 'Test Reminder')
        );

        return back()->with('success', 'Test reminder sent to ' . count($setting->recipients) . ' recipient(s).');
    }
}
