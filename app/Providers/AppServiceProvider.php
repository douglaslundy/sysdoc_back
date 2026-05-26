<?php

namespace App\Providers;

use App\Models\AccessProfile;
use App\Models\CategoriaExame;
use App\Models\Client;
use App\Models\Exame;
use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Models\MedicineMonthlyAcquisition;
use App\Models\MedicoSolicitante;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Models\Speciality;
use App\Models\Trip;
use App\Models\User;
use App\Models\VigilanciaConfig;
use App\Observers\AccessProfileObserver;
use App\Observers\CategoriaExameObserver;
use App\Observers\ClientObserver;
use App\Observers\ExameObserver;
use App\Observers\MedicineDailyStatusObserver;
use App\Observers\MedicineItemObserver;
use App\Observers\MedicineMonthlyAcquisitionObserver;
use App\Observers\MedicoSolicitanteObserver;
use App\Observers\PedidoExameObserver;
use App\Observers\ResultadoExameObserver;
use App\Observers\SpecialityObserver;
use App\Observers\TripObserver;
use App\Observers\UserObserver;
use App\Observers\VigilanciaConfigObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
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
    }
}
