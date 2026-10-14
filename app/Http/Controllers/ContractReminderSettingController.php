<?php

namespace App\Http\Controllers;

use App\Mail\ContractEndingReminder;
use App\Models\ContractReminderLog;
use App\Models\ContractReminderSetting;
use App\Services\ContractMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContractReminderSettingController extends Controller
{
    public function __construct(private ContractMonitor $monitor) {}

    /** Status board + settings. */
    public function index(Request $request)
    {
        $setting = $this->monitor->setting();
        $items = $this->monitor->items(null, (int) $request->input('ahead', 90), 60, $setting);

        return view('dashboard.settings.contract-reminders', [
            'setting' => $setting,
            'items'   => $items,
            'counts'  => [
                'expired'  => $items->where('state', 'expired')->count(),
                'critical' => $items->where('state', 'critical')->count(),
                'soon'     => $items->where('state', 'soon')->count(),
                'upcoming' => $items->where('state', 'upcoming')->count(),
            ],
            'logs'    => ContractReminderLog::with('employee')->latest('id')->limit(15)->get(),
            'ahead'   => (int) $request->input('ahead', 90),
        ]);
    }

    public function update(Request $request, ContractReminderSetting $setting)
    {
        $validated = $request->validate([
            'recipients'             => 'required|string',
            'days_before'            => 'nullable|string',
            'monthly_fixed_days'     => 'nullable|string',
            'monthly_lookahead_days' => 'nullable|integer|min:1|max:365',
        ]);

        $recipients = collect(preg_split('/[,;\s]+/', $validated['recipients']))
            ->map(fn ($e) => strtolower(trim($e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()->values()->all();

        if (empty($recipients)) {
            return back()->withInput()->with('error', 'Enter at least one valid recipient email address.');
        }

        $daysBefore = collect(preg_split('/[,;\s]+/', $validated['days_before'] ?? ''))
            ->map(fn ($d) => (int) trim($d))->filter(fn ($d) => $d > 0 && $d <= 365)
            ->unique()->sortDesc()->values()->all();

        $monthlyDays = collect(preg_split('/[,;\s]+/', $validated['monthly_fixed_days'] ?? ''))
            ->map(fn ($d) => (int) trim($d))->filter(fn ($d) => $d >= 1 && $d <= 28)
            ->unique()->sort()->values()->all();

        $setting->update([
            'recipients'             => $recipients,
            'use_days_before'        => $request->boolean('use_days_before'),
            'days_before'            => $daysBefore ?: ContractMonitor::DEFAULT_THRESHOLDS,
            'use_monthly_fixed'      => $request->boolean('use_monthly_fixed'),
            'monthly_fixed_days'     => $monthlyDays ?: [1],
            'monthly_lookahead_days' => $validated['monthly_lookahead_days'] ?? 60,
            'track_contracts'        => $request->boolean('track_contracts'),
            'track_probation'        => $request->boolean('track_probation'),
            'include_expired'        => $request->boolean('include_expired'),
            'notify_supervisor'      => $request->boolean('notify_supervisor'),
            'is_active'              => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Contract reminder settings saved.');
    }

    /** Run the check now. Never sends the same reminder twice. */
    public function run()
    {
        $r = $this->monitor->run();

        $msg = $r['alerts'] || $r['digest']
            ? "Check complete: {$r['alerts']} reminder(s)" . ($r['digest'] ? " and a digest of {$r['digest']} date(s)" : '') . " emailed."
            : 'Check complete: nothing new to send. Everyone due has already been reminded.';

        return back()->with($r['errors'] ? 'error' : 'success', $msg . ($r['errors'] ? ' Problems: ' . implode('; ', $r['errors']) : ''));
    }

    /** Send a preview to the recipients (not logged, doesn't affect real reminders). */
    public function sendTest(ContractReminderSetting $setting)
    {
        if (empty($setting->recipients)) {
            return back()->with('error', 'Add at least one recipient email before sending a test.');
        }

        $items = $this->monitor->items(null, 90, 60, $setting)->take(15);

        if ($items->isEmpty()) {
            return back()->with('error', 'No contract or probation dates in the next 90 days to preview.');
        }

        Mail::to($setting->recipients)->send(new ContractEndingReminder($this->monitor->rows($items), 'Preview of the next 90 days', 'test'));

        return back()->with('success', 'Test email sent to ' . count($setting->recipients) . ' recipient(s).');
    }
}
