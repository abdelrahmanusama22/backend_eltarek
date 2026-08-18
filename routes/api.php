<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BootstrapController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CompareController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TestDriveController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ---------------------------------------------------------------- auth
    Route::prefix('auth')->group(function () {
        Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:10,1');
        Route::post('resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:10,1');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:20,1');
        Route::post('social', [AuthController::class, 'social']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('complete-profile', [AuthController::class, 'completeProfile'])->middleware('throttle:10,1');
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // -------------------------------------------------------------- public
    Route::get('bootstrap', BootstrapController::class);
    Route::get('home', HomeController::class);

    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/search', [VehicleController::class, 'search']);
    Route::get('vehicles/{vehicle}/trims', [VehicleController::class, 'trims']);
    Route::get('trims/{trim}', [VehicleController::class, 'trimDetail']);

    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{brand}', [BrandController::class, 'show']);

    Route::post('finance/calculate', [FinanceController::class, 'calculate']);
    Route::post('finance/eligibility', [FinanceController::class, 'eligibility']);

    Route::get('compare', [CompareController::class, 'results']);
    Route::post('compare', [CompareController::class, 'add']);
    Route::delete('compare/{trimId}', [CompareController::class, 'remove']);

    Route::get('branches', [BranchController::class, 'index']);
    Route::get('branches/{branch}', [BranchController::class, 'show']);

    Route::get('test-drives/fleet', [TestDriveController::class, 'fleet']);
    Route::get('test-drives/slots', [TestDriveController::class, 'slots']);

    Route::get('cities', [CityController::class, 'index']);

    // ------------------------------------------------------- authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('trims/{trim}/favorite', [VehicleController::class, 'toggleFavorite']);

        Route::get('test-drives', [TestDriveController::class, 'index']);
        Route::post('test-drives', [TestDriveController::class, 'store']);
        Route::delete('test-drives/{booking}', [TestDriveController::class, 'destroy']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::get('profile/garage', [ProfileController::class, 'garage']);
        Route::get('profile/favorites', [ProfileController::class, 'favorites']);
        Route::get('profile/rewards', [ProfileController::class, 'rewards']);
        Route::post('profile/rewards/redeem', [ProfileController::class, 'redeem']);
        Route::get('profile/vip/benefits', [ProfileController::class, 'benefits']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    });
});
