<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Seeker\CampaignController as SeekerCampaignController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->prefix('seeker/campaigns')->name('seeker.campaigns.')->group(function () {
    Route::get('/', [SeekerCampaignController::class, 'index'])->name('index');
    Route::get('/create', [SeekerCampaignController::class, 'create'])->name('create');
    Route::get('/{campaign}/edit', [SeekerCampaignController::class, 'edit'])->name('edit');
    Route::get('/{campaign}', [SeekerCampaignController::class, 'show'])->name('show');
});

require __DIR__.'/auth.php';
