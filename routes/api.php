<?php

use App\Http\Controllers\AccessProfileController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\AgendaColetaController;
use App\Http\Controllers\AlvaraController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CallServiceController;
use App\Http\Controllers\CampoReferenciaController;
use App\Http\Controllers\CategoriaExameController;
use App\Http\Controllers\CidadaoAcsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConformidadeCidadaoController;
use App\Http\Controllers\ConsultaPublicaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EndedController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\EstabelecimentoController;
use App\Http\Controllers\ExameCampoController;
use App\Http\Controllers\ExameController;
use App\Http\Controllers\LabConfigController;
use App\Http\Controllers\LetterAttachmentController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\MedicineComplianceController;
use App\Http\Controllers\MedicineDailyStatusController;
use App\Http\Controllers\MedicineItemController;
use App\Http\Controllers\MedicineMonthlyAcquisitionController;
use App\Http\Controllers\MedicinePublicationController;
use App\Http\Controllers\MedicinePanelSettingController;
use App\Http\Controllers\MedicineStockImportController;
use App\Http\Controllers\MedicineTransparencyPublicController;
use App\Http\Controllers\MedicoSolicitanteController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\MonitorApsConfigController;
use App\Http\Controllers\MonitorApsController;
use App\Http\Controllers\OrdinanceAttachmentController;
use App\Http\Controllers\OrdinanceController;
use App\Http\Controllers\PageCategoryController;
use App\Http\Controllers\PageViewAuditController;
use App\Http\Controllers\PainelEsusController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PedidoExameController;
use App\Http\Controllers\PharmacyCatalogAdminController;
use App\Http\Controllers\PharmacyCatalogController;
use App\Http\Controllers\QRCodeLogController;
use App\Http\Controllers\QueueAttachmentController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ResultadoExameController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\SpecialityController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SystemNoticeController;
use App\Http\Controllers\SystemPageController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEquipeApsController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VigilanciaConfigController;
use App\Http\Controllers\VisitaAcsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::get('/attendance/panel/state', [AttendanceController::class, 'panelState']);

// Painel de atendimento eSUS PEC — público (sala de espera)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/public/painel-esus/validar-cnes', [PainelEsusController::class, 'validarCnes']);
    Route::get('/public/painel-esus/estado',       [PainelEsusController::class, 'estado']);
});

// Redefinição de senha (throttle: 3 req/min por IP)
Route::middleware('throttle:forgot-password')->post('/forgot-password', [PasswordResetController::class, 'sendLink']);
Route::middleware('throttle:5,1')->post('/reset-password', [PasswordResetController::class, 'reset']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/validate', [AuthController::class, 'validateToken']);
    Route::post('/audit/page-view', [PageViewAuditController::class, 'store']);

    // Atendimento ao cliente por senha
    Route::post('/attendance/tickets', [AttendanceController::class, 'createTicket']);
    Route::get('/attendance/tickets', [AttendanceController::class, 'listTickets']);
    Route::get('/attendance/tickets/{id}', [AttendanceController::class, 'showTicket']);
    Route::patch('/attendance/tickets/{id}/cancel', [AttendanceController::class, 'cancelTicket']);
    Route::patch('/attendance/tickets/{id}/no-show', [AttendanceController::class, 'noShowTicket']);
    Route::get('/attendance/rooms-admin', [AttendanceController::class, 'roomsIndex']);
    Route::post('/attendance/rooms-admin', [AttendanceController::class, 'roomsStore']);
    Route::get('/attendance/rooms-admin/{id}', [AttendanceController::class, 'roomsShow']);
    Route::put('/attendance/rooms-admin/{id}', [AttendanceController::class, 'roomsUpdate']);
    Route::patch('/attendance/rooms-admin/{id}/inactivate', [AttendanceController::class, 'roomsInactivate']);
    Route::delete('/attendance/rooms-admin/{id}', [AttendanceController::class, 'roomsDestroy']);
    Route::get('/attendance/rooms', [AttendanceController::class, 'rooms']);
    Route::get('/attendance/attendants', [AttendanceController::class, 'attendants']);
    Route::get('/attendance/queue', [AttendanceController::class, 'queue']);
    Route::post('/attendance/queue/call-next', [AttendanceController::class, 'callNext']);
    Route::post('/attendance/queue/{ticketId}/call', [AttendanceController::class, 'callSpecific']);
    Route::get('/attendance/service/{ticketId}', [AttendanceController::class, 'serviceData']);
    Route::post('/attendance/service/{ticketId}/start', [AttendanceController::class, 'serviceStart']);
    Route::patch('/attendance/service/{ticketId}/notes', [AttendanceController::class, 'serviceNotes']);
    Route::post('/attendance/service/{ticketId}/finish', [AttendanceController::class, 'serviceFinish']);

    // Monitor APS — indicadores Portaria GM/MS 6.907/2025
    // audit.read removido: auditoria granular feita no frontend (1 VIEW por page load, 1 READ por filtro aplicado)
    Route::prefix('monitor-aps')->middleware(['throttle:60,1', 'equipe.aps'])->group(function () {
        Route::get('/minhas-equipes', [MonitorApsController::class, 'minhasEquipes']);
        Route::prefix('indicadores')->group(function () {
            Route::get('/resumo', [MonitorApsController::class, 'resumo']);
            Route::get('/vinculo', [MonitorApsController::class, 'vinculo']);
            Route::get('/qualidade', [MonitorApsController::class, 'qualidade']);
            Route::get('/qualidade/{id}', [MonitorApsController::class, 'qualidadeIndicador']);
            Route::get('/repasse', [MonitorApsController::class, 'repasse']);
            Route::get('/historico', [MonitorApsController::class, 'historico']);
        });
        Route::prefix('visitas')->group(function () {
            Route::get('/', [VisitaAcsController::class, 'index']);    // por mês (granular)
            Route::get('/resumo', [VisitaAcsController::class, 'resumo']);   // totais + por_mes (quadrimestre)
            Route::get('/lista', [VisitaAcsController::class, 'lista']);    // paginado por quadrimestre
            Route::get('/mapa', [VisitaAcsController::class, 'mapa']);     // pins georreferenciados
            Route::get('/equipes', [VisitaAcsController::class, 'equipes']);  // equipes com ACS
            Route::get('/agentes', [VisitaAcsController::class, 'agentes']);  // stats por agente
            Route::get('/evolucao/anos', [VisitaAcsController::class, 'anosDisponiveis']);
            Route::get('/evolucao', [VisitaAcsController::class, 'evolucao']);
            Route::get('/responsabilidade', [VisitaAcsController::class, 'responsabilidade']);
            Route::get('/debug/{id}', [VisitaAcsController::class, 'showDebug'])->whereNumber('id');
            Route::get('/{id}', [VisitaAcsController::class, 'show'])->whereNumber('id');
        });
        Route::get('/config/status', [MonitorApsConfigController::class, 'status']);
        Route::get('/config/load', [MonitorApsConfigController::class, 'load']);
        Route::get('/config/equipes', [MonitorApsConfigController::class, 'equipes']);
        Route::middleware('admin')->group(function () {
            Route::post('/config/test', [MonitorApsConfigController::class, 'testar']);
            Route::post('/config/save', [MonitorApsConfigController::class, 'save']);
            Route::get('/config/explorar', [MonitorApsConfigController::class, 'explorar']);
        });
        Route::prefix('cidadaos')->group(function () {
            Route::get('/',        [CidadaoAcsController::class, 'index']);
            Route::get('/agentes', [CidadaoAcsController::class, 'agentes']);
        });

    });

    // Painel de atendimento eSUS PEC — gestão de fila (autenticado)
    Route::prefix('painel-esus')->group(function () {
        Route::get('/fila',     [PainelEsusController::class, 'fila']);
        Route::get('/filtros',  [PainelEsusController::class, 'filtros']);
        Route::get('/unidades', [PainelEsusController::class, 'unidades']);
        Route::get('/default-cnes', [PainelEsusController::class, 'defaultCnes'])->middleware('equipe.aps');
        Route::get('/statuses', [PainelEsusController::class, 'statuses']);
    });

    // Dashboard analítico — throttle: 120 req/min + controle de acesso por perfil
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/dashboard/inicio', [DashboardController::class, 'inicio']);
        Route::get('/dashboard/conformidades', [DashboardController::class, 'conformidades']);
        Route::get('/dashboard/laboratorio', [DashboardController::class, 'laboratorio'])->middleware('can:dashboard-laboratorio');
        Route::get('/dashboard/fila', [DashboardController::class, 'fila'])->middleware('can:dashboard-fila');
        Route::get('/dashboard/tfd', [DashboardController::class, 'tfd'])->middleware('can:dashboard-tfd');
        Route::get('/dashboard/farmacia', [DashboardController::class, 'farmacia']);
        Route::get('/dashboard/logs', [DashboardController::class, 'logs'])->middleware('can:dashboard-logs');
        Route::get('/dashboard/vigilancia', [DashboardController::class, 'vigilancia']);
    });

    // Permissões do usuário logado
    Route::get('/auth/my-permissions', [AccessProfileController::class, 'myPermissions']);
    Route::post('/users/presence/ping', [UserController::class, 'presence']);

    // Configurações do laboratório (somente admin)
    Route::middleware('admin')->group(function () {
        Route::get('/laboratorio/config', [LabConfigController::class, 'show']);
        Route::put('/laboratorio/config', [LabConfigController::class, 'update']);
        Route::get('/pharmacy/catalogs/{type}', [PharmacyCatalogAdminController::class, 'index']);
        Route::post('/pharmacy/catalogs/{type}', [PharmacyCatalogAdminController::class, 'store']);
        Route::put('/pharmacy/catalogs/{type}/{id}', [PharmacyCatalogAdminController::class, 'update']);
        Route::delete('/pharmacy/catalogs/{type}/{id}', [PharmacyCatalogAdminController::class, 'destroy']);
    });

    // Backup do banco de dados (somente admin)
    Route::middleware('admin')->group(function () {
        Route::get('/admin/backup/download', [BackupController::class, 'download']);
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
        Route::get('/page-categories', [PageCategoryController::class, 'index']);
        Route::post('/page-categories', [PageCategoryController::class, 'store']);
        Route::put('/page-categories/{id}', [PageCategoryController::class, 'update']);
        Route::delete('/page-categories/{id}', [PageCategoryController::class, 'destroy']);
    });

    Route::get('/system-notices', [SystemNoticeController::class, 'index']);
    Route::post('/system-notices', [SystemNoticeController::class, 'store']);
    Route::delete('/system-notices/{id}', [SystemNoticeController::class, 'destroy']);
    Route::get('/system-notices/active', [SystemNoticeController::class, 'active']);
    Route::post('/system-notices/{id}/views', [SystemNoticeController::class, 'recordView']);

    // sector
    Route::get('/sectors', [SectorController::class, 'getAll']);
    Route::post('/sector', [SectorController::class, 'insert']);
    Route::get('/sector/{id}', [SectorController::class, 'edit']);
    Route::put('/sector', [SectorController::class, 'update']);
    Route::delete('/sector/{id}', [SectorController::class, 'delete']);

    // letters
    Route::apiResource('letters', LetterController::class);
    Route::post('/letters/newLetter', [LetterController::class, 'createLetterAi'])->name('newLetter');
    Route::get('/letters/{letter}/attachments', [LetterAttachmentController::class, 'index']);
    Route::post('/letters/{letter}/attachments', [LetterAttachmentController::class, 'store']);
    Route::get('/letters/{letter}/attachments/{attachment}/download', [LetterAttachmentController::class, 'download']);
    Route::delete('/letters/{letter}/attachments/{attachment}', [LetterAttachmentController::class, 'destroy']);

    // users
    Route::apiResource('users', UserController::class);
    Route::post('/users/presence/ping', [UserController::class, 'presence']);

    // Equipes APS por usuário (admin)
    Route::middleware('admin')->group(function () {
        Route::get('/users/{user}/equipe-aps', [UserEquipeApsController::class, 'show']);
        Route::put('/users/{user}/equipe-aps', [UserEquipeApsController::class, 'update']);
    });

    // Models
    Route::apiResource('models', ModelController::class);

    // Rooms
    Route::get('/rooms/todaycalls', [RoomController::class, 'rooms_with_today_calls']);
    Route::apiResource('rooms', RoomController::class);

    // Clients
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/clients/buscar-cpf-cns', [ClientController::class, 'buscarPorCpfCns']);
        Route::get('/clients/select', [ClientController::class, 'select']);
        Route::apiResource('clients', ClientController::class);
        Route::get('/detailed-client-report', [ClientController::class, 'detailedClientReport']);
    });

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
    Route::middleware('throttle:60,1')->group(function () {
        Route::apiResource('queues', QueueController::class);
        Route::get('/queues/{queue}/attachments', [QueueAttachmentController::class, 'index']);
        Route::post('/queues/{queue}/attachments', [QueueAttachmentController::class, 'store']);
        Route::get('/queues/{queue}/attachments/{attachment}/download', [QueueAttachmentController::class, 'download']);
        Route::delete('/queues/{queue}/attachments/{attachment}', [QueueAttachmentController::class, 'destroy']);
    });

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
    Route::get('/ordinances/{ordinance}/attachments', [OrdinanceAttachmentController::class, 'index']);
    Route::post('/ordinances/{ordinance}/attachments', [OrdinanceAttachmentController::class, 'store']);
    Route::get('/ordinances/{ordinance}/attachments/{attachment}/download', [OrdinanceAttachmentController::class, 'download']);
    Route::delete('/ordinances/{ordinance}/attachments/{attachment}', [OrdinanceAttachmentController::class, 'destroy']);

    // Alvarás e Estabelecimentos (Vigilância Sanitária)
    // ATENCAO: rotas estaticas ANTES de apiResource para nao conflitar com {id}
    Route::get('/estabelecimentos/select', [EstabelecimentoController::class, 'select']);
    Route::get('/cnaes/select', [EstabelecimentoController::class, 'cnaesSelect']);
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
    Route::get('/pharmacy/medicines/stock-import/current-stock', [MedicineStockImportController::class, 'downloadCurrentStock']);
    Route::post('/pharmacy/medicines/stock-import', [MedicineStockImportController::class, 'store']);
    Route::get('/pharmacy/medicines/panel-settings', [MedicinePanelSettingController::class, 'show']);
    Route::put('/pharmacy/medicines/panel-settings', [MedicinePanelSettingController::class, 'update']);
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

    // Conformidade de Cidadãos — sync Sysdoc x e-SUS PEC
    Route::prefix('conformidade-cidadao')->group(function () {
        Route::post('analisar', [ConformidadeCidadaoController::class, 'analisar']);
        Route::get('status/{job_id}', [ConformidadeCidadaoController::class, 'status']);
        Route::post('aplicar/{job_id}', [ConformidadeCidadaoController::class, 'aplicar']);
        Route::post('cancelar/{job_id}', [ConformidadeCidadaoController::class, 'cancelar']);
        Route::get('erros/{job_id}', [ConformidadeCidadaoController::class, 'erros']);
        Route::get('itens/{job_id}', [ConformidadeCidadaoController::class, 'itens']);
        Route::get('historico', [ConformidadeCidadaoController::class, 'historico']);
    });
});
