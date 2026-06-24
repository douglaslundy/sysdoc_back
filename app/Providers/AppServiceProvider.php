<?php

namespace App\Providers;

use App\Models\AccessProfile;
use App\Models\CategoriaExame;
use App\Models\Client;
use App\Models\Exame;
use App\Models\Letter;
use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Models\MedicineMonthlyAcquisition;
use App\Models\MedicoSolicitante;
use App\Models\Ordinance;
use App\Models\PageCategory;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Models\Route;
use App\Models\Speciality;
use App\Models\SystemPage;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Alvara;
use App\Models\Estabelecimento;
use App\Models\VigilanciaConfig;
use App\Observers\AccessProfileObserver;
use App\Observers\AlvaraObserver;
use App\Observers\EstabelecimentoObserver;
use App\Observers\CategoriaExameObserver;
use App\Observers\ClientObserver;
use App\Observers\ExameObserver;
use App\Observers\LetterObserver;
use App\Observers\MedicineDailyStatusObserver;
use App\Observers\MedicineItemObserver;
use App\Observers\MedicineMonthlyAcquisitionObserver;
use App\Observers\MedicoSolicitanteObserver;
use App\Observers\OrdinanceObserver;
use App\Observers\PageCategoryObserver;
use App\Observers\PedidoExameObserver;
use App\Observers\ResultadoExameObserver;
use App\Observers\RouteObserver;
use App\Observers\SpecialityObserver;
use App\Observers\SystemPageObserver;
use App\Observers\TripObserver;
use App\Observers\UserObserver;
use App\Observers\VehicleObserver;
use App\Observers\VigilanciaConfigObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        app(\App\Services\ChatBroadcastConfigService::class)->apply();
        User::observe(UserObserver::class);
        Client::observe(ClientObserver::class);
        PedidoExame::observe(PedidoExameObserver::class);
        ResultadoExame::observe(ResultadoExameObserver::class);
        Trip::observe(TripObserver::class);
        AccessProfile::observe(AccessProfileObserver::class);
        Speciality::observe(SpecialityObserver::class);
        MedicineItem::observe(MedicineItemObserver::class);
        MedicineDailyStatus::observe(MedicineDailyStatusObserver::class);
        MedicineMonthlyAcquisition::observe(MedicineMonthlyAcquisitionObserver::class);
        VigilanciaConfig::observe(VigilanciaConfigObserver::class);
        Exame::observe(ExameObserver::class);
        CategoriaExame::observe(CategoriaExameObserver::class);
        MedicoSolicitante::observe(MedicoSolicitanteObserver::class);
        Vehicle::observe(VehicleObserver::class);
        Route::observe(RouteObserver::class);
        Letter::observe(LetterObserver::class);
        Ordinance::observe(OrdinanceObserver::class);
        Estabelecimento::observe(EstabelecimentoObserver::class);
        Alvara::observe(AlvaraObserver::class);
        SystemPage::observe(SystemPageObserver::class);
        PageCategory::observe(PageCategoryObserver::class);
    }
}
