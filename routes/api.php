<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RfidTagController;
use App\Http\Controllers\RfidAssignmentController;
use App\Http\Controllers\RfidScanController;
use App\Http\Controllers\RfidReaderController;
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
Route::post('/rfid/tag', [RfidTagController::class, 'store']);
Route::get('/rfid/tags', [RfidTagController::class, 'index']);

Route::post('/rfid/assign', [RfidAssignmentController::class, 'assign']);

Route::post('/rfid/reader', [RfidReaderController::class, 'store']);

Route::post('/rfid/scan', [RfidScanController::class, 'scan']);
