<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gates de acesso aos dashboards analíticos.
        // O campo `profile` na tabela users define o papel do usuário.
        // Valores conhecidos: 'admin', 'user' (default).
        // Para restringir um dashboard a perfis específicos, adicione o perfil
        // ao array correspondente. Ex: ['admin', 'tfd'] para restringir ao TFD.
        Gate::define('dashboard-laboratorio', fn($user) => in_array($user->profile, ['admin', 'user']));
        Gate::define('dashboard-fila',        fn($user) => in_array($user->profile, ['admin', 'user']));
        Gate::define('dashboard-tfd',         fn($user) => in_array($user->profile, ['admin', 'user']));
        Gate::define('dashboard-logs',        fn($user) => $user->profile === 'admin');
    }
}
