<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdUnitController;
use App\Http\Controllers\Api\AdvertiserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerGeneratorController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\PublisherController;
use App\Http\Controllers\Api\ServeController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/auth/wallet/callback', [AuthController::class, 'walletCallback'])->middleware('throttle:20,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/sync-from-wallet', [AuthController::class, 'syncFromWallet']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Upload & Generate
    Route::post('/upload/image', [UploadController::class, 'image']);
    Route::post('/generate/banner', [BannerGeneratorController::class, 'generate'])->middleware('throttle:5,1');

    /*
    |--------------------------------------------------------------------------
    | Advertiser Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/advertiser/dashboard', [AdvertiserController::class, 'dashboard']);
    Route::post('/advertiser/register', [AdvertiserController::class, 'register']);
    Route::post('/advertiser/deposit', fn () => response()->json(['message' => 'Payments are unavailable in this release.'], 503));

    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::patch('/campaigns/{campaign}/status', [CampaignController::class, 'updateStatus']);
    Route::get('/campaigns/{campaign}/stats', [CampaignController::class, 'stats']);

    Route::get('/ads', [AdController::class, 'index']);
    Route::post('/ads', [AdController::class, 'store']);
    Route::put('/ads/{ad}', [AdController::class, 'update']);
    Route::delete('/ads/{ad}', [AdController::class, 'destroy']);

    // Stats
    Route::get('/stats/advertiser', [StatsController::class, 'advertiserOverview']);
    Route::get('/stats/campaign/{campaignId}', [StatsController::class, 'campaignStats']);
    Route::get('/stats/publisher', [StatsController::class, 'publisherOverview']);
    Route::get('/stats/ad-unit/{adUnitId}', [StatsController::class, 'adUnitStats']);

    /*
    |--------------------------------------------------------------------------
    | Publisher Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/publisher/dashboard', [PublisherController::class, 'dashboard']);
    Route::post('/publisher/register', [PublisherController::class, 'register']);
    Route::get('/publisher/site', [PublisherController::class, 'site']);
    Route::post('/publisher/verify', [PublisherController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('/publisher/resubmit', [PublisherController::class, 'resubmit'])->middleware('throttle:10,1');
    Route::get('/publisher/earnings', [PublisherController::class, 'earnings']);
    Route::post('/publisher/withdraw', fn () => response()->json(['message' => 'Payments are unavailable in this release.'], 503));

    Route::get('/ad-units', [AdUnitController::class, 'index']);
    Route::post('/ad-units', [AdUnitController::class, 'store']);
    Route::put('/ad-units/{adUnit}', [AdUnitController::class, 'update']);
    Route::delete('/ad-units/{adUnit}', [AdUnitController::class, 'destroy']);
    Route::get('/ad-units/{adUnit}/code', [AdUnitController::class, 'code']);

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/publishers', [AdminController::class, 'publishers']);
        Route::patch('/publishers/{publisher}/approve', [AdminController::class, 'approvePublisher']);
        Route::get('/ads', [AdminController::class, 'ads']);
        Route::patch('/ads/{ad}/approve', [AdminController::class, 'approveAd']);
    });
});

/*
|--------------------------------------------------------------------------
| Ad Serving Routes (Public, no auth)
|--------------------------------------------------------------------------
*/
Route::get('/serve', [ServeController::class, 'serve'])->middleware('throttle:120,1');
Route::post('/track/viewable', [ServeController::class, 'trackViewable']);
Route::post('/track/impression', [ServeController::class, 'trackImpression']);
Route::get('/track/click/{adId}', [ServeController::class, 'trackClick']);
