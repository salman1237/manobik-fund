<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Ambulance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AmbulanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ambulance::query()->where('is_available', true);

        if ($district = $request->string('district')->toString()) {
            $query->where('district', $district);
        }

        $ambulances = $query->orderBy('district')->get();

        $districts = Ambulance::query()->distinct()->orderBy('district')->pluck('district');

        $mapPoints = $ambulances
            ->filter(fn (Ambulance $ambulance) => $ambulance->latitude && $ambulance->longitude)
            ->map(fn (Ambulance $ambulance) => [
                'lat' => (float) $ambulance->latitude,
                'lng' => (float) $ambulance->longitude,
                'name' => $ambulance->name,
                'district' => $ambulance->district,
            ])
            ->values();

        return view('ambulances.index', [
            'ambulances' => $ambulances,
            'districts' => $districts,
            'selectedDistrict' => $district,
            'mapPoints' => $mapPoints,
        ]);
    }
}
