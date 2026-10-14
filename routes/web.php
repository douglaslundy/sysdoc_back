<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // Anti-robô do formulário de contato: soma simples, resposta guardada na sessão.
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    session(['contato_captcha' => $a + $b]);

    return view('welcome', ['captchaPergunta' => "{$a} + {$b}"]);
});

Route::get('/contato', fn () => redirect('/#contato'));
Route::post('/contato', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contato.enviar');

Route::get('/showPublicQueue', [QueueController::class, 'showPublicQueue'])->name('showPublicQueue');
Route::get('/showqueue/{uuid}', [QueueController::class, 'showByUuid'])->name('showByUuid');
