<?php

use Illuminate\Support\Facades\Route;
use Mucan54\TauriPhp\Http\Controllers\TauriPluginController;

/*
|--------------------------------------------------------------------------
| Tauri Plugin API Routes
|--------------------------------------------------------------------------
|
| These routes handle communication between the Tauri JavaScript bridge
| and the Laravel backend for mobile plugin functionality.
|
| All routes use the 'web' middleware to maintain session state.
|
*/

Route::middleware(['web'])->prefix('api/tauri')->group(function () {
    // Mark Tauri mobile environment as active
    Route::post('/mark-active', [TauriPluginController::class, 'markActive'])
        ->name('tauri.mark-active');

    // Get pending plugin calls for execution
    Route::get('/plugin-calls', [TauriPluginController::class, 'getPluginCalls'])
        ->name('tauri.plugin-calls');

    // Receive plugin execution results
    Route::post('/plugin-response', [TauriPluginController::class, 'receivePluginResponse'])
        ->name('tauri.plugin-response');
});
