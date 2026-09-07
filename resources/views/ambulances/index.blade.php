<x-public-layout title="Ambulance Directory">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Ambulance Directory') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 flex gap-4 items-end">
                <div class="flex-1">
                    <label for="district" class="block text-sm font-medium text-gray-700">District</label>
                    <select id="district" name="district" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district }}" @selected($selectedDistrict === $district)>{{ $district }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">
                    Filter
                </button>
            </form>

            <div id="ambulance-map" style="height: 350px;" class="rounded-lg border border-gray-200"></div>

            @if ($ambulances->isEmpty())
                <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
                    No available ambulances found{{ $selectedDistrict ? " in {$selectedDistrict}" : '' }}.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($ambulances as $ambulance)
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <p class="font-medium text-gray-900">{{ $ambulance->name }} &middot; {{ ucfirst($ambulance->vehicle_type) }}</p>
                            <p class="text-sm text-gray-500">{{ $ambulance->district }} &middot; {{ $ambulance->driver_contact }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script data-navigate-once>
        (function () {
            const points = @json($mapPoints);

            const initMap = () => {
                const el = document.getElementById('ambulance-map');
                if (! el || typeof L === 'undefined') return;

                const map = L.map(el).setView(
                    points.length ? [points[0].lat, points[0].lng] : [23.685, 90.3563],
                    points.length ? 10 : 7
                );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                points.forEach(p => {
                    L.marker([p.lat, p.lng]).addTo(map).bindPopup(`${p.name} (${p.district})`);
                });
            };

            document.addEventListener('DOMContentLoaded', initMap);
            document.addEventListener('livewire:navigated', initMap);
            if (document.readyState !== 'loading') initMap();
        })();
    </script>
</x-public-layout>
