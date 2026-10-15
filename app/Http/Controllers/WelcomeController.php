<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\HrRecipients;
use App\Support\Roles;
use Illuminate\Http\Request;

/**
 * The home page: greets people and helps them pick the right kind of login.
 * Signed-in people go straight to where their role lands.
 */
class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        if ($user = $request->user()) {
            return redirect()->route($user->isPortalUser() && $user->canUsePortal() ? 'portal.dashboard' : 'dashboard');
        }

        return view('welcome', [
            'roles'      => Roles::options(),
            'guide'      => Roles::guide(),
            'needsSetup' => ! User::exists(),
            'hrContact'  => HrRecipients::contact(),
        ]);
    }
}
