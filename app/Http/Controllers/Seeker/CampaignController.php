<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class CampaignController extends Controller
{
    public function index(): View
    {
        return view('seeker.campaigns.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Campaign::class);

        return view('seeker.campaigns.create');
    }

    public function edit(Campaign $campaign): View
    {
        Gate::authorize('update', $campaign);

        return view('seeker.campaigns.edit', ['campaign' => $campaign]);
    }

    public function show(Campaign $campaign): View
    {
        Gate::authorize('view', $campaign);

        return view('seeker.campaigns.show', ['campaign' => $campaign]);
    }
}
