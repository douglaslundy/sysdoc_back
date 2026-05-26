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

        // Gates de acesso às abas do dashboard analítico.
        // Permissões gerenciadas via system_pages + profile_page_permissions (tela de Perfis de Acesso).
        // Admin tem acesso irrestrito; demais perfis consultam o banco.
        $perm = fn (string $path) => fn ($user) =>
            $user->profile === 'admin' ||
            app(\App\Services\Authorization\PagePermissionService::class)->canAccess($user, $path);

        Gate::define('dashboard-laboratorio', $perm('/dashboard/laboratorio'));
        Gate::define('dashboard-fila',        $perm('/dashboard/fila'));
        Gate::define('dashboard-tfd',         $perm('/dashboard/tfd'));
        Gate::define('dashboard-logs',        $perm('/dashboard/logs'));
    }
}
