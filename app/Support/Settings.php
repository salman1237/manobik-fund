<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    protected const CACHE_KEY = 'app.settings';

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, $default);
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'type' => $type, 'group' => $group],
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function group(string $group): array
    {
        return Setting::query()->where('group', $group)->get()
            ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->castValue()])
            ->all();
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();

        Cache::forget(self::CACHE_KEY);
    }

    protected function all(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()->get()
                ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->castValue()]);
        });
    }
}
