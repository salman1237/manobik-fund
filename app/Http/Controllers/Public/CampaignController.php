<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $query = Campaign::query()
            ->whereIn('status', [
                Campaign::STATUS_PUBLISHED,
                Campaign::STATUS_FUNDED,
                Campaign::STATUS_COMPLETED,
            ]);

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('hospital_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->latest('published_at')->paginate(12)->withQueryString();

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'category' => $category,
            'search' => $search,
        ]);
    }

    public function show(Campaign $campaign): View
    {
        abort_unless($campaign->isPublic(), 404);

        return view('campaigns.show', ['campaign' => $campaign]);
    }
}
