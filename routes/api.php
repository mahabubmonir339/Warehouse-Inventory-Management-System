<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RfidController;
use App\Http\Controllers\Api\RfidScanController;
use App\Http\Controllers\Api\RfidTagController;
use App\Http\Controllers\Api\RfidReaderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * RFID API Routes
 * Public endpoint for hardware scanners (without auth)
 */
Route::post('/rfid/scan', [RfidScanController::class, 'processScan'])
    ->name('api.rfid.scan');

Route::post('/rfid/bulk-scan', [RfidScanController::class, 'bulkScan'])
    ->name('api.rfid.bulk-scan');

/**
 * Authenticated RFID API Routes
 */
Route::middleware('auth:sanctum')->prefix('rfid')->group(function () {
    
    // RFID Scan Operations
    Route::get('/recent-scans', [RfidScanController::class, 'getRecentScans'])
        ->name('api.rfid.recent-scans');
    
    Route::get('/scan-summary', [RfidScanController::class, 'getScanSummary'])
        ->name('api.rfid.scan-summary');
    
    Route::get('/scan-logs', [RfidScanController::class, 'listScans'])
        ->name('api.rfid.logs.list');
    
    Route::delete('/scan-logs/{scanLog}', [RfidScanController::class, 'deleteScan'])
        ->name('api.rfid.logs.delete');

    // RFID Tags Management
    Route::apiResource('tags', RfidTagController::class)
        ->names('api.rfid.tags');
    
    Route::post('/tags/{tag}/assign', [RfidTagController::class, 'assignToItem'])
        ->name('api.rfid.tags.assign');
    
    Route::post('/tags/{tag}/unassign', [RfidTagController::class, 'unassignFromItem'])
        ->name('api.rfid.tags.unassign');
    
    Route::post('/tags/bulk-create', [RfidTagController::class, 'bulkCreate'])
        ->name('api.rfid.tags.bulk-create');

    // RFID Readers Management
    Route::apiResource('readers', RfidReaderController::class)
        ->names('api.rfid.readers');
    
    Route::post('/readers/{reader}/health', [RfidReaderController::class, 'checkHealth'])
        ->name('api.rfid.readers.health');
    
    Route::get('/readers/{reader}/scans', [RfidReaderController::class, 'getReaderScans'])
        ->name('api.rfid.readers.scans');
    
    Route::put('/readers/{reader}/status', [RfidReaderController::class, 'updateStatus'])
        ->name('api.rfid.readers.status');
}); 