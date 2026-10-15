<?php

namespace App\Http\Controllers;

use App\Support\Themes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Saves the colour theme picked in the top bar (admin area and portal). */
class AccountThemeController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate(['accent' => ['required', Rule::in(array_keys(Themes::ALL))]]);

        $request->user()->forceFill(['ui_accent' => $data['accent']])->save();

        return $request->expectsJson()
            ? response()->json(['accent' => $data['accent']])
            : back();
    }
}
