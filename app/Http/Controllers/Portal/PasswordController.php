<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return view('portal.password.edit', [
            'forced' => $request->user()->must_change_password,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ]);

        $request->user()->update([
            'password'             => $request->password, // hashed by the model cast
            'must_change_password' => false,
        ]);

        return redirect()
            ->route('portal.dashboard')
            ->with('success', 'Password changed successfully.');
    }
}
