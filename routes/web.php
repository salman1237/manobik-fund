<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\Public\CampaignController as PublicCampaignController;
use App\Http\Controllers\Seeker\CampaignController as SeekerCampaignController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCampaignController::class, 'index'])->name('home');

Route::get('campaigns', [PublicCampaignController::class, 'index'])->name('campaigns.index');
Route::get('campaigns/{campaign:slug}', [PublicCampaignController::class, 'show'])->name('campaigns.show');

Route::get('donations/{donation}/success', [DonationController::class, 'success'])->name('donations.success');
Route::get('donations/{donation}/cancel', [DonationController::class, 'cancel'])->name('donations.cancel');

Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');

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
