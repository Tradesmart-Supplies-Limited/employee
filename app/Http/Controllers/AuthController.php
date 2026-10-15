<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show login page
     */
    public function showLogin(Request $request)
    {
        $as = \App\Support\Roles::normalize($request->query('as'));

        return view('auth.login', [
            'as'         => $as ?: null,
            'asRole'     => $as ? \App\Support\Roles::options()[$as] : null,
            'asGuide'    => $as ? \App\Support\Roles::guide()[$as] : null,
            'needsSetup' => ! User::exists(),
        ]);
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Employee (portal-only) accounts go to the portal.
            if ($user->isPortalUser() && $user->canUsePortal()) {
                return redirect()->route('portal.dashboard')
                    ->with('success', 'Welcome back, ' . ($user->employee?->first_name ?? $user->name) . '!');
            }

            // Admin, HR, Director, Manager → admin area.
            if ($user->canUseAdmin()) {
                return redirect()->route('dashboard')
                    ->with('success', 'Welcome back!');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Your account has no access role yet. Please contact HR.'])->onlyInput('email');
        }


        return back()->withErrors([
            'email' => 'Invalid credentials provided.'
        ])->withInput();
    }

    /**
     * Show register page
     */
    public function showRegister()
    {
        // Self-registration only sets up the very first (Admin) account.
        if (User::exists()) {
            return redirect()->route('login')->withErrors(['email' => 'Accounts are created by HR. Ask HR or an Admin for a login.']);
        }

        return view('auth.register');
    }

    /**
     * Handle register request (first Admin account only)
     */
    public function register(Request $request)
    {
        if (User::exists()) {
            return redirect()->route('login')->withErrors(['email' => 'Accounts are created by HR. Ask HR or an Admin for a login.']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => \App\Support\Roles::ADMIN,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Admin account created. Add your team under Users & Roles.');
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}