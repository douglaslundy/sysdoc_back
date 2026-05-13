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
use App\Http\Controllers\MedicoSolicitanteController;
use App\Http\Controllers\AgendaColetaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AccessProfileController;
use App\Http\Controllers\SystemPageController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\LabConfigController;
use App\Http\Controllers\EstabelecimentoController;
use App\Http\Controllers\AlvaraController;
use App\Http\Controllers\VigilanciaConfigController;
use App\Http\Controllers\MedicineItemController;
use App\Http\Controllers\MedicineDailyStatusController;
use App\Http\Controllers\MedicineMonthlyAcquisitionController;
use App\Http\Controllers\MedicinePublicationController;
use App\Http\Controllers\MedicineTransparencyPublicController;
use App\Http\Controllers\MedicineComplianceController;
use App\Http\Controllers\PharmacyCatalogController;
use App\Http\Controllers\PharmacyCatalogAdminController;
use App\Http\Controllers\PageViewAuditController;


Route::get('/ping', function () {
    return ['pong' => true];
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('throttle:5,1')->post('/register', [AuthController::class, 'register']);

// Rota pública para consulta da Queue por UUID
Route::post('/queues/log-location', [QueueController::class, 'storeLocationLog']);

// Consulta pública de resultado de exame (throttle: 10 req/min por IP)
Route::middleware('throttle:10,1')->post('/consulta-exame', [ConsultaPublicaController::class, 'consultar']);
Route::middleware('throttle:10,1')->post('/consulta-exame/pdf/{protocolo}', [ConsultaPublicaController::class, 'downloadPdf']);

// Transparência pública - Farmácia básica (Lei 2488)
Route::get('/public/pharmacy/medicines/daily', [MedicineTransparencyPublicController::class, 'daily']);
Route::get('/public/pharmacy/medicines/panel', [MedicineTransparencyPublicController::class, 'panel']);
Route::get('/public/pharmacy/medicines/monthly-acquisitions', [MedicineTransparencyPublicController::class, 'monthly']);

// Redefinição de senha (throttle: 3 req/min por IP)
Route::middleware('throttle:3,1')->post('/forgot-password', [PasswordResetController::class, 'sendLink']);
Route::middleware('throttle:5,1')->post('/reset-password', [PasswordResetController::class, 'reset']);



Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/validate', [AuthController::class, 'validateToken']);
    Route::post('/audit/page-view', [PageViewAuditController::class, 'store']);

    // Dashboard analítico — throttle: 20 req/min + controle de acesso por perfil
    Route::middleware('throttle:20,1')->group(function () {
        Route::get('/dashboard/inicio',       [DashboardController::class, 'inicio']);
        Route::get('/dashboard/laboratorio', [DashboardController::class, 'laboratorio'])->middleware('can:dashboard-laboratorio');
        Route::get('/dashboard/fila',        [DashboardController::class, 'fila'])->middleware('can:dashboard-fila');
        Route::get('/dashboard/tfd',         [DashboardController::class, 'tfd'])->middleware('can:dashboard-tfd');
        Route::get('/dashboard/farmacia',    [DashboardController::class, 'farmacia']);
        Route::get('/dashboard/logs',        [DashboardController::class, 'logs'])->middleware('can:dashboard-logs');
        Route::get('/dashboard/vigilancia',  [DashboardController::class, 'vigilancia']);
    });

    // Permissões do usuário logado
    Route::get('/auth/my-permissions', [AccessProfileController::class, 'myPermissions']);

    // Configurações do laboratório (somente admin)
    Route::middleware('admin')->group(function () {
        Route::get('/laboratorio/config', [LabConfigController::class, 'show']);
        Route::put('/laboratorio/config', [LabConfigController::class, 'update']);
        Route::get('/pharmacy/catalogs/{type}', [PharmacyCatalogAdminController::class, 'index']);
        Route::post('/pharmacy/catalogs/{type}', [PharmacyCatalogAdminController::class, 'store']);
        Route::put('/pharmacy/catalogs/{type}/{id}', [PharmacyCatalogAdminController::class, 'update']);
        Route::delete('/pharmacy/catalogs/{type}/{id}', [PharmacyCatalogAdminController::class, 'destroy']);
    });

    // Auditoria + perfis de acesso e páginas do sistema (somente admin)
    Route::middleware('admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/users', [AuditLogController::class, 'users']);
        Route::apiResource('access-profiles', AccessProfileController::class);
        Route::get('/system-pages', [SystemPageController::class, 'index']);
        Route::post('/system-pages', [SystemPageController::class, 'store']);
        Route::put('/system-pages/{id}', [SystemPageController::class, 'update']);
        Route::delete('/system-pages/{id}', [SystemPageController::class, 'destroy']);
    });

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
    Route::get('/clients/buscar-cpf-cns', [ClientController::class, 'buscarPorCpfCns']);
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

    // Logs de Erro
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

    // Alvarás e Estabelecimentos (Vigilância Sanitária)
    // ATENÇÃO: rotas estáticas ANTES de apiResource para não conflitar com {id}
    Route::get('/estabelecimentos/select', [EstabelecimentoController::class, 'select']);
    Route::apiResource('estabelecimentos', EstabelecimentoController::class);
    Route::get('/alvaras/{id}/pdf', [AlvaraController::class, 'downloadPdf']);
    Route::apiResource('alvaras', AlvaraController::class);

    // Configuração da Vigilância Sanitária (somente admin)
    Route::middleware('admin')->group(function () {
        Route::get('/vigilancia/config', [VigilanciaConfigController::class, 'show']);
        Route::put('/vigilancia/config', [VigilanciaConfigController::class, 'update']);
    });

    // Pharmacy / medicines transparency
    Route::get('/pharmacy/medicines/select', [MedicineItemController::class, 'select']);
    Route::apiResource('medicines', MedicineItemController::class);
    Route::get('/pharmacy/medicines/daily-statuses', [MedicineDailyStatusController::class, 'index']);
    Route::post('/pharmacy/medicines/daily-statuses', [MedicineDailyStatusController::class, 'store']);
    Route::put('/pharmacy/medicines/daily-statuses/{id}', [MedicineDailyStatusController::class, 'update']);
    Route::delete('/pharmacy/medicines/daily-statuses/{id}', [MedicineDailyStatusController::class, 'destroy']);

    Route::get('/pharmacy/medicines/monthly-acquisitions', [MedicineMonthlyAcquisitionController::class, 'index']);
    Route::post('/pharmacy/medicines/monthly-acquisitions', [MedicineMonthlyAcquisitionController::class, 'store']);
    Route::put('/pharmacy/medicines/monthly-acquisitions/{id}', [MedicineMonthlyAcquisitionController::class, 'update']);
    Route::delete('/pharmacy/medicines/monthly-acquisitions/{id}', [MedicineMonthlyAcquisitionController::class, 'destroy']);

    Route::get('/pharmacy/medicines/publications', [MedicinePublicationController::class, 'index']);
    Route::post('/pharmacy/medicines/publications', [MedicinePublicationController::class, 'store']);
    Route::delete('/pharmacy/medicines/publications/{id}', [MedicinePublicationController::class, 'destroy']);
    Route::get('/pharmacy/medicines/compliance', [MedicineComplianceController::class, 'index']);
    Route::get('/pharmacy/catalogs', [PharmacyCatalogController::class, 'index']);

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

        // Médicos solicitantes
        Route::get('/medicos', [MedicoSolicitanteController::class, 'index']);
        Route::post('/medicos', [MedicoSolicitanteController::class, 'store']);
        Route::put('/medicos/{medico}', [MedicoSolicitanteController::class, 'update']);
        Route::delete('/medicos/{medico}', [MedicoSolicitanteController::class, 'destroy']);

        // Pedidos de exame
        Route::apiResource('pedidos', PedidoExameController::class);
        Route::patch('/pedidos/{pedido}/status', [PedidoExameController::class, 'atualizarStatus']);

        // Agenda de coleta
        Route::get('/agenda', [AgendaColetaController::class, 'index']);

        // Resultados
        Route::post('/pedidos/{pedido}/resultado', [ResultadoExameController::class, 'store']);
        Route::get('/resultados/{resultado}', [ResultadoExameController::class, 'show']);
        Route::post('/resultados/{resultado}/campos', [ResultadoExameController::class, 'salvarCampos']);
        Route::post('/resultados/{resultado}/liberar', [ResultadoExameController::class, 'liberar']);
        Route::get('/resultados/{resultado}/pdf', [ResultadoExameController::class, 'downloadPdf']);
    });
});
