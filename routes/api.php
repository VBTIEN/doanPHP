<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
// Route::post('/password/reset', [AuthController::class, 'resetPassword']);
// Route::get('/password/validate-token', [AuthController::class, 'validateToken']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Route::get('/status', function (Request $request) {
//     return response()->json(['status' => 'ok', 'backend' => 'php']);
// });

Route::middleware('cors')->group(function () {
    Route::get('/status', function () {
        return response()->json(['status' => 'ok', 'backend' => 'php']);
    });
    Route::get('/password/validate-token', [AuthController::class, 'validateToken']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});
