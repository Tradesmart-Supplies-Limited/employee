<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Change-password page for admin-area logins that have no self-service portal
 * (Directors, Admins without an employee record). Logins with a portal use
 * the portal's own page. Temporary passwords must be changed here first.
 */
class AccountPasswordController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        if ($user->canUsePortal()) {
            return redirect()->route('portal.password.edit');
        }

        return view('auth.account-password', ['forced' => (bool) $user->must_change_password, 'user' => $user]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'That isn\'t your current password. If it was a temporary one, copy it exactly from the email.',
            'password.different'                => 'Choose a password that is different from the temporary one.',
            'password.confirmed'                => 'The two new passwords don\'t match.',
        ]);

        $request->user()->update([
            'password'             => $request->password, // hashed by the model cast
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Password changed. You\'re all set.');
    }
}
