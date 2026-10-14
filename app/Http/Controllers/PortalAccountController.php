<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\PortalAccounts;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * HR-side management of employee self-service logins.
 * The employee is emailed their login details; all HR / admins get a notice.
 */
class PortalAccountController extends Controller
{
    public function __construct(private PortalAccounts $accounts) {}

    /** Create a portal login for an employee. */
    public function store(Request $request, Employee $employee)
    {
        try {
            $result = $this->accounts->create($employee, $request->user()->name);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->respond($result, 'Portal account created.');
    }

    /** Issue a new temporary password and email it. */
    public function resetPassword(Request $request, Employee $employee)
    {
        try {
            $result = $this->accounts->reset($employee, $request->user()->name);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->respond($result, 'Password reset.');
    }

    /** Remove portal access (the employee record itself is untouched). */
    public function destroy(Request $request, Employee $employee)
    {
        try {
            $this->accounts->remove($employee, $request->user()->name);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Portal access removed. HR has been notified.');
    }

    private function respond(array $result, string $prefix)
    {
        $email = $result['user']->email;

        if ($result['emailed']) {
            return back()->with('success',
                "{$prefix} Login details were emailed to {$email}, and HR has been notified."
            );
        }

        // Email failed: show the password once so HR can hand it over another way.
        return back()->with('error',
            "{$prefix} BUT the email to {$email} could not be sent (check the mail settings). "
            . "Temporary password: {$result['password']}. It is shown once only, so share it securely. "
            . 'The employee must change it at first login.'
        );
    }
}
