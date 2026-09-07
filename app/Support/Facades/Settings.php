<?php

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value, string $type = 'string', string $group = 'general')
 * @method static array group(string $group)
 * @method static void forget(string $key)
 *
 * @see \App\Support\Settings
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Support\Settings::class;
    }
}
