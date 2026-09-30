<?php

namespace App\Http\Middleware;

use App\Services\Authorization\PagePermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Aplica a tabela central config/route_permissions.php a todas as rotas autenticadas:
 * o usuário precisa ter, em Perfis, alguma das páginas que usam aquele endpoint.
 */
class EnforceRoutePagePolicy
{
    public function __construct(private PagePermissionService $pages)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $user = $request->user();

        if (! $route || ! $user) {
            return $next($request);
        }

        $uri = Str::after($route->uri(), 'api/');
        $method = $request->method();

        foreach ((array) config('route_permissions.rules', []) as $rule) {
            if (! $this->matches($rule, $uri, $method)) {
                continue;
            }

            $allowed = ($rule['admin'] ?? false)
                ? $user->profile === 'admin'
                : $this->pages->canAccessAny($user, $rule['pages'] ?? []);

            if (! $allowed) {
                return response()->json(['message' => 'Usuário sem permissão para esta página.'], 403);
            }

            return $next($request);
        }

        return $next($request);
    }

    private function matches(array $rule, string $uri, string $method): bool
    {
        $prefix = $rule['prefix'];

        if ($uri !== $prefix && ! str_starts_with($uri, $prefix.'/')) {
            return false;
        }

        if (! empty($rule['methods']) && ! in_array($method, $rule['methods'], true)) {
            return false;
        }

        foreach ((array) ($rule['except'] ?? []) as $except) {
            if ($uri === $except || str_starts_with($uri, $except.'/')) {
                return false;
            }
        }

        return true;
    }
}
