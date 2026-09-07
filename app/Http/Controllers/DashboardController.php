<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Staff roles operate the internal Filament panel, not the public
     * dashboard - send them there instead (spec Phase 1: role-based
     * dashboard redirects after login).
     */
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isStaff()) {
            return redirect('/control');
        }

        return view('dashboard', [
            'campaignsCount' => $user->campaigns()->count(),
            'totalRaised' => (int) $user->campaigns()->sum('raised_amount'),
            'donationsCount' => $user->donations()->count(),
        ]);
    }
}
