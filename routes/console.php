<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Shared cPanel hosting has no persistent process supervisor for
// `queue:work`, so it's driven from the scheduler instead: process
// whatever's queued, then exit, once a minute (see DEPLOYMENT.md §2).
// --max-time keeps a run from overrunning into the next minute's tick.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
