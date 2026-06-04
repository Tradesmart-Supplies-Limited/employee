<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        // placeholder data (replace later with DB queries)
        $stats = [
            'employees' => 0,
            'branches' => 0,
            'departments' => 0,
        ];

        return view('dashboard.index', compact('stats'));
    }
}