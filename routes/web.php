<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\MyDonationsController;
use App\Http\Controllers\Public\AmbulanceController;
use App\Http\Controllers\Public\BloodController;
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

Route::get('blood-donors', [BloodController::class, 'donors'])->name('blood.donors');
Route::get('blood-requests', [BloodController::class, 'requests'])->name('blood.requests.index');
Route::get('blood-requests/create', [BloodController::class, 'requestCreate'])->name('blood.requests.create');
Route::get('blood-drives', [BloodController::class, 'drives'])->name('blood.drives');

Route::view('blood-donor/profile', 'blood.profile')
    ->middleware(['auth'])
    ->name('blood.donor.profile');

Route::get('ambulances', [AmbulanceController::class, 'index'])->name('ambulances.index');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('my-donations', [MyDonationsController::class, 'index'])
    ->middleware(['auth'])
    ->name('donations.index');

Route::middleware(['auth'])->prefix('seeker/campaigns')->name('seeker.campaigns.')->group(function () {
    Route::get('/', [SeekerCampaignController::class, 'index'])->name('index');
    Route::get('/create', [SeekerCampaignController::class, 'create'])->name('create');
    Route::get('/{campaign}/edit', [SeekerCampaignController::class, 'edit'])->name('edit');
    Route::get('/{campaign}', [SeekerCampaignController::class, 'show'])->name('show');
});

require __DIR__.'/auth.php';
