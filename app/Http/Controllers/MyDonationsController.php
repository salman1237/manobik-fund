<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class MyDonationsController extends Controller
{
    public function index(): View
    {
        $donations = Auth::user()->donations()
            ->with(['campaign', 'refundRequests'])
            ->latest()
            ->get();

        return view('donations.index', [
            'donations' => $donations,
            'points' => Auth::user()->humanityBadgePoints(),
        ]);
    }
}
