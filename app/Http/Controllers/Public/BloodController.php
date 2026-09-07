<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BloodDriveEvent;
use App\Models\BloodRequest;
use Illuminate\Contracts\View\View;

class BloodController extends Controller
{
    public function donors(): View
    {
        return view('blood.donors');
    }

    public function requests(): View
    {
        $requests = BloodRequest::query()
            ->where('status', BloodRequest::STATUS_OPEN)
            ->latest()
            ->paginate(15);

        return view('blood.requests', ['requests' => $requests]);
    }

    public function requestCreate(): View
    {
        return view('blood.request-create');
    }

    public function drives(): View
    {
        $drives = BloodDriveEvent::query()
            ->where('status', BloodDriveEvent::STATUS_UPCOMING)
            ->orderBy('scheduled_at')
            ->get();

        return view('blood.drives', ['drives' => $drives]);
    }
}
