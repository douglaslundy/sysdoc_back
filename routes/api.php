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
use App\Http\Controllers\EndedController;
use App\Http\Controllers\SpecialityController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ErrorLogController;
use Illuminate\Http\Request;
use App\Http\Controllers\TripController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\QRCodeLogController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\OrdinanceController;
use App\Http\Controllers\ExameController;
use App\Http\Controllers\ExameCampoController;
use App\Http\Controllers\CampoReferenciaController;
use App\Http\Controllers\PedidoExameController;
use App\Http\Controllers\ResultadoExameController;
use App\Http\Controllers\ConsultaPublicaController;
use App\Http\Controllers\CategoriaExameController;


Route::get('/ping', function () {
    return ['pong' => true];
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

// Rota pública para consulta da Queue por UUID
Route::post('/queues/log-location', [QueueController::class, 'storeLocationLog']);

// Consulta pública de resultado de exame (throttle: 10 req/min por IP)
Route::middleware('throttle:10,1')->post('/consulta-exame', [ConsultaPublicaController::class, 'consultar']);



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
    Route::get('/rooms/todaycalls', [RoomController::class, 'rooms_with_today_calls']);
    Route::apiResource('rooms', RoomController::class);

    // Clients
    Route::apiResource('clients', ClientController::class);
    Route::get('/detailed-client-report', [ClientController::class, 'detailedClientReport']);

    // Calls
    Route::get('/calls/called', [CallController::class, 'called_call']);
    Route::get('/calls/lasts', [CallController::class, 'lasts_calls']);
    Route::get('/calls/today', [CallController::class, 'today_calls']);

    Route::apiResource('calls', CallController::class);
    Route::put('/calls/{id}/start', [CallController::class, 'start_time']);
    Route::put('/calls/{id}/end', [CallController::class, 'end_time']);
    Route::put('/calls/{id}/abandon', [CallController::class, 'abandon']);

    // Call_service
    Route::apiResource('services', CallServiceController::class);

    // EndedCall
    Route::apiResource('endedcalls', EndedController::class);

    // Speciality
    Route::apiResource('specialities', SpecialityController::class);

    // QueueCall
    Route::apiResource('queues', QueueController::class);

    // Logs
    Route::apiResource('logs', logController::class);

    // Logs
    Route::apiResource('errorlogs', ErrorLogController::class);

    //Trip
    Route::apiResource('trips', TripController::class);
    Route::post('/trip-clients', [TripController::class, 'insertTripClient']);
    Route::patch('/confirm-trip-client/{client_id}', [TripController::class, 'confirmTripClient']);
    Route::patch('/unconfirm-trip-client/{client_id}', [TripController::class, 'unconfirmTripClient']);
    Route::delete('/trip-clients/{client_id}', [TripController::class, 'deleteTripClient']);
    Route::put('/trip-clients/{id}', [TripController::class, 'editTripClient']);

    // Rota para Veículos (Vehicle)
    Route::apiResource('vehicles', VehicleController::class);

    // Rota para Rotas (Route)
    Route::apiResource('routes', RouteController::class);

    // Rota para States
    Route::get('/states', [StateController::class, 'index']);

    // rota para logs do qrcode
    Route::get('/qrcode-logs', [QRCodeLogController::class, 'index']);

    //rota para portarias
    Route::apiResource('ordinances', OrdinanceController::class);
    Route::post('/ordinances/newOrdinance', [OrdinanceController::class, 'createOrdinanceAi'])->name('newOrdinance');

    // Laboratório
    Route::prefix('laboratorio')->group(function () {
        // Categorias de exame
        Route::get('/categorias', [CategoriaExameController::class, 'index']);
        Route::post('/categorias', [CategoriaExameController::class, 'store']);
        Route::put('/categorias/{categoria}', [CategoriaExameController::class, 'update']);
        Route::delete('/categorias/{categoria}', [CategoriaExameController::class, 'destroy']);

        // Exames
        Route::apiResource('exames', ExameController::class);

        // Campos do exame
        Route::get('/exames/{exame}/campos', [ExameCampoController::class, 'index']);
        Route::post('/exames/{exame}/campos', [ExameCampoController::class, 'store']);
        Route::put('/exames/{exame}/campos/{campo}', [ExameCampoController::class, 'update']);
        Route::delete('/exames/{exame}/campos/{campo}', [ExameCampoController::class, 'destroy']);
        Route::patch('/exames/{exame}/campos/reordenar', [ExameCampoController::class, 'reordenar']);

        // Referências por campo
        Route::get('/campos/{campo}/referencias', [CampoReferenciaController::class, 'index']);
        Route::post('/campos/{campo}/referencias', [CampoReferenciaController::class, 'store']);
        Route::put('/campos/{campo}/referencias/{referencia}', [CampoReferenciaController::class, 'update']);
        Route::delete('/campos/{campo}/referencias/{referencia}', [CampoReferenciaController::class, 'destroy']);

        // Pedidos de exame
        Route::apiResource('pedidos', PedidoExameController::class);
        Route::patch('/pedidos/{pedido}/status', [PedidoExameController::class, 'atualizarStatus']);

        // Resultados
        Route::post('/pedidos/{pedido}/resultado', [ResultadoExameController::class, 'store']);
        Route::get('/resultados/{resultado}', [ResultadoExameController::class, 'show']);
        Route::post('/resultados/{resultado}/campos', [ResultadoExameController::class, 'salvarCampos']);
        Route::post('/resultados/{resultado}/liberar', [ResultadoExameController::class, 'liberar']);
        Route::get('/resultados/{resultado}/pdf', [ResultadoExameController::class, 'downloadPdf']);
    });
});
