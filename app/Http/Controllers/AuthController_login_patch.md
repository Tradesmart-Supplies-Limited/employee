# AuthController::login(): replace the success branch

Find:

    if (Auth::attempt($credentials, $request->remember)) {
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Welcome back!');
    }

Replace with:

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        $user = Auth::user();

        // Employee self-service accounts go to the portal.
        if ($user->isPortalUser()) {
            return redirect()->route('portal.dashboard')
                ->with('success', 'Welcome back, ' . $user->employee->first_name . '!');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Welcome back!');
    }

(Inactive employees are signed out automatically by the "portal" middleware on their first request.)
