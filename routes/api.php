<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CompareController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TestDriveController;
use App\Http\Controllers\Api\VehicleController;
use App\Jobs\RunCatalogSyncJob;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ---------------------------------------------------------------- auth
    Route::prefix('auth')->group(function () {
        Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:10,1');
        Route::post('resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:10,1');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware(['throttle:20,1', 'idempotency']);
        Route::post('email/register', [AuthController::class, 'registerEmail'])->middleware(['throttle:10,1', 'idempotency']);
        Route::post('email/login', [AuthController::class, 'loginEmail'])->middleware('throttle:10,1');
        Route::post('email/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('email/reset-password', [AuthController::class, 'resetPassword'])->middleware(['throttle:10,1', 'idempotency']);
        Route::post('google', [AuthController::class, 'google'])->middleware(['throttle:10,1', 'idempotency']);
        Route::post('apple', [AuthController::class, 'apple'])->middleware(['throttle:10,1', 'idempotency']);
        Route::post('apple/challenge', [AuthController::class, 'appleChallenge'])->middleware('throttle:10,1');
        Route::post('apple/callback', [AuthController::class, 'appleCallback'])->middleware('throttle:30,1');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('google/link', [AuthController::class, 'linkGoogle'])->middleware('throttle:10,1');
            Route::post('apple/link', [AuthController::class, 'linkApple'])->middleware('throttle:10,1');
            Route::post('complete-profile', [AuthController::class, 'completeProfile'])->middleware('throttle:10,1');
            Route::post('email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
            Route::post('email/resend-verification', [AuthController::class, 'resendVerificationEmail'])->middleware('throttle:5,1');
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // -------------------------------------------------------------- public
    Route::get('app-config', AppConfigController::class);
    Route::get('catalog/version', [CatalogController::class, 'version']);
    Route::get('catalog/brands', [CatalogController::class, 'brands']);
    Route::get('catalog/vehicles', [CatalogController::class, 'vehicles']);
    Route::get('catalog/trims', [CatalogController::class, 'trims']);
    Route::get('/home', [HomeController::class, 'index']);

    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/search', [VehicleController::class, 'search']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::get('vehicles/{vehicle}/trims', [VehicleController::class, 'trims']);
    Route::get('trims/{trim}', [VehicleController::class, 'trimDetail']);

    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{brand}', [BrandController::class, 'show']);

    Route::post('finance/calculate', [FinanceController::class, 'calculate']);
    Route::post('finance/eligibility', [FinanceController::class, 'eligibility']);

    Route::get('compare/selection', [CompareController::class, 'selection']);
    Route::get('compare', [CompareController::class, 'results']);
    Route::post('compare', [CompareController::class, 'add'])->middleware('throttle:30,1');
    Route::delete('compare/{trimId}', [CompareController::class, 'remove'])->middleware('throttle:30,1');

    Route::get('branches', [BranchController::class, 'index']);
    Route::get('branches/{branch}', [BranchController::class, 'show']);

    Route::get('test-drives/fleet', [TestDriveController::class, 'fleet']);
    Route::get('test-drives/slots', [TestDriveController::class, 'slots']);

    Route::get('cities', [CityController::class, 'index']);
    Route::post('analytics/event', [AnalyticsController::class, 'log'])->middleware('throttle:60,1');

    // ------------------------------------------------------- authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('trims/{trim}/favorite', [VehicleController::class, 'toggleFavorite']);

        Route::get('test-drives', [TestDriveController::class, 'index']);
        Route::post('test-drives', [TestDriveController::class, 'store'])->middleware('idempotency');
        Route::delete('test-drives/{booking}', [TestDriveController::class, 'destroy']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar'])->middleware('throttle:10,1');
        Route::get('profile/garage', [ProfileController::class, 'garage']);
        Route::get('profile/garage/{garageCar}/service-records', [ProfileController::class, 'garageServiceRecords']);
        Route::get('profile/garage-link-requests', [ProfileController::class, 'garageLinkRequests']);
        Route::post('profile/garage-link-requests', [ProfileController::class, 'requestGarageLink'])->middleware(['throttle:5,1', 'idempotency']);
        Route::delete('profile/garage-link-requests/{garageLinkRequest}', [ProfileController::class, 'cancelGarageLinkRequest']);
        Route::get('profile/favorites', [ProfileController::class, 'favorites']);
        Route::get('profile/points-history', [ProfileController::class, 'pointsHistory']);
        Route::get('profile/rewards', [ProfileController::class, 'rewards']);
        Route::get('profile/redemptions', [ProfileController::class, 'redemptions']);
        Route::post('profile/rewards/redeem', [ProfileController::class, 'redeem'])->middleware('idempotency');
        Route::get('profile/vip/benefits', [ProfileController::class, 'benefits']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    });
});

Route::post('/webhook/trigger-catalog-sync', function () {
    RunCatalogSyncJob::dispatch();

    return response()->json(['success' => true, 'message' => 'Sync queued'], 202);
})->middleware(['signed', 'throttle:2,1']);
