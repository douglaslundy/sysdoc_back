<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LetterController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CallServiceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\RoomController;

Route::get('/ping', function () {
    return ['pong' => true];
});


Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/validate', [AuthController::class, 'validateToken']);

    // sector
    Route::get('/sectors', [SectorController::class, 'getAll']);
    Route::post('/sector', [SectorController::class, 'insert']);
    Route::get('/sector/{id}', [SectorController::class, 'edit']);
    Route::put('/sector', [SectorController::class, 'update']);
    Route::delete('/sector/{id}', [SectorController::class, 'delete']);

    // letters
    Route::apiResource('letters', LetterController::class);
    Route::post('/letters/newLetter', [LetterController::class, 'createLetterAi'])->name('newLetter');

    // users
    Route::apiResource('users', UserController::class);

    // Models
    Route::apiResource('models', ModelController::class);

    // Rooms
    Route::apiResource('rooms', RoomController::class);

    // Clients
    Route::apiResource('clients', ClientController::class);

    // Calls
    Route::apiResource('calls', CallController::class);
    Route::put('/calls/{id}/start', [CallController::class, 'start_time']);
    Route::put('/calls/{id}/end', [CallController::class, 'end_time']);
    Route::put('/calls/{id}/abandon', [CallController::class, 'abandon']);

    // Call_service
    Route::apiResource('services', CallServiceController::class);
});
