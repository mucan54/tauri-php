<?php

use App\Http\Controllers\MobilePluginExampleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Plugin Example Routes
|--------------------------------------------------------------------------
|
| These routes demonstrate how to use Tauri mobile plugins in your Laravel
| application. Copy these routes to your routes/api.php or routes/web.php
| file to enable the example endpoints.
|
| Note: These routes use the 'web' middleware to maintain session state
| for the Tauri plugin bridge.
|
*/

Route::middleware(['web'])->prefix('api/mobile')->group(function () {
    // Camera Plugin Examples
    Route::prefix('camera')->group(function () {
        Route::post('/take', [MobilePluginExampleController::class, 'takePhoto']);
        Route::post('/pick', [MobilePluginExampleController::class, 'pickPhoto']);
        Route::post('/pick-multiple', [MobilePluginExampleController::class, 'pickMultiplePhotos']);
    });

    // Notification Plugin Examples
    Route::prefix('notification')->group(function () {
        Route::post('/schedule', [MobilePluginExampleController::class, 'scheduleNotification']);
        Route::post('/instant', [MobilePluginExampleController::class, 'showInstantNotification']);
        Route::get('/pending', [MobilePluginExampleController::class, 'getPendingNotifications']);
    });

    // Vibration Plugin Examples
    Route::prefix('vibration')->group(function () {
        Route::post('/simple', [MobilePluginExampleController::class, 'vibrateSimple']);
        Route::post('/pattern', [MobilePluginExampleController::class, 'vibratePattern']);
        Route::post('/haptic', [MobilePluginExampleController::class, 'hapticFeedback']);
    });

    // Geolocation Plugin Examples
    Route::prefix('geolocation')->group(function () {
        Route::get('/current', [MobilePluginExampleController::class, 'getCurrentLocation']);
    });

    // Storage Plugin Examples
    Route::prefix('storage')->group(function () {
        Route::post('/set', [MobilePluginExampleController::class, 'storeData']);
        Route::get('/get', [MobilePluginExampleController::class, 'getData']);
        Route::post('/set-multiple', [MobilePluginExampleController::class, 'storeMultipleData']);
        Route::get('/keys', [MobilePluginExampleController::class, 'getStorageKeys']);
    });

    // Permissions Examples
    Route::prefix('permissions')->group(function () {
        Route::get('/check', [MobilePluginExampleController::class, 'checkPermissions']);
        Route::post('/request', [MobilePluginExampleController::class, 'requestPermissions']);
    });
});
