<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LetterController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;


Route::get('/ping', function(){
    return ['pong' => true];
});


Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Route::middleware('auth:api')->group(function(){

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/validate', [AuthController::class, 'validateToken']);

     // sector
     Route::get('/sectors', [SectorController::class, 'getAll']);
     Route::post('/sector', [SectorController::class, 'insert']);
     Route::get('/sector/{id}', [SectorController::class, 'edit']);
     Route::put('/sector', [SectorController::class, 'update']);
     Route::delete('/sector/{id}', [SectorController::class, 'delete']);

    // // letters
    // Route::get('/letters', [LetterController::class, 'getAll']);
    // Route::post('/letter', [LetterController::class, 'insert']);
    // Route::get('/letter/{id}', [LetterController::class, 'edit']);
    // Route::post('/letter/file', [LetterController::class, 'addFile']);
    // Route::put('/letter', [LetterController::class, 'update']);
    // Route::delete('/letter/{id}', [LetterController::class, 'delete']);
    Route::apiResource('letters', LetterController::class);
    Route::post('/letters/newLetter', [LetterController::class, 'createLetterAi'])->name('newLetter');

     // user
    //  Route::get('/users', [UserController::class, 'getAll']);
    //  Route::post('/user', [UserController::class, 'insert']);
    //  Route::get('/user/{id}', [UserController::class, 'edit']);
    //  Route::put('/user', [UserController::class, 'update']);
    //  Route::delete('/user/{id}', [UserController::class, 'delete']);

    Route::apiResource('users', UserController::class);

});
