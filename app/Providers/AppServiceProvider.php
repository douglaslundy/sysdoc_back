<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Client;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Models\Trip;
use App\Models\AccessProfile;
use App\Models\Speciality;
use App\Observers\UserObserver;
use App\Observers\ClientObserver;
use App\Observers\PedidoExameObserver;
use App\Observers\ResultadoExameObserver;
use App\Observers\TripObserver;
use App\Observers\AccessProfileObserver;
use App\Observers\SpecialityObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        User::observe(UserObserver::class);
        Client::observe(ClientObserver::class);
        PedidoExame::observe(PedidoExameObserver::class);
        ResultadoExame::observe(ResultadoExameObserver::class);
        Trip::observe(TripObserver::class);
        AccessProfile::observe(AccessProfileObserver::class);
        Speciality::observe(SpecialityObserver::class);
    }
}
