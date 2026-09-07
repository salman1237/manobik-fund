<x-public-layout title="Ambulance Directory">
    <div class="py-10">
        <div class="max-w-4xl mx-auto px-6 sm:px-10 lg:px-16 flex flex-col gap-6">
            <h1 class="font-serif text-3xl font-semibold text-ink">{{ __('Ambulance Directory') }}</h1>

            <form method="GET" class="bg-warm-surface border border-warm-border-soft rounded-2xl p-5 flex gap-4 items-end">
                <div class="flex-1">
                    <x-input-label for="district" value="District" />
                    <select id="district" name="district" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district }}" @selected($selectedDistrict === $district)>{{ $district }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button type="submit">Filter</x-primary-button>
            </form>

            <div id="ambulance-map" style="height: 350px;" class="rounded-2xl border border-warm-border-soft"></div>

            @if ($ambulances->isEmpty())
                <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-10 text-center text-ink-faint">
                    No available ambulances found{{ $selectedDistrict ? " in {$selectedDistrict}" : '' }}.
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @foreach ($ambulances as $ambulance)
                        <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-5">
                            <p class="font-semibold text-ink">{{ $ambulance->name }} &middot; {{ ucfirst($ambulance->vehicle_type) }}</p>
                            <p class="text-sm text-ink-faint mt-0.5">{{ $ambulance->district }} &middot; {{ $ambulance->driver_contact }}</p>
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
