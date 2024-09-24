<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class LogUserAction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Antes de executar a requisição, pode registrar algo se necessário
        return $next($request);
    }

    /**
     * Handle task after the response has been sent to the client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        // Obtém o usuário autenticado
        $user = Auth::user();

        // Verifica se o usuário está autenticado antes de tentar acessar o id
        if ($user) {
            // Nome da rota como ação
            $action = $request->route() ? $request->route()->getName() : 'rota_indefinida';

            // Descrição completa da ação
            $description = $request->method() . ' ' . $request->fullUrl();

            // Cria o log no banco de dados
            Log::create([
                'user_id' => $user->id,
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
            ]);
        } else {
            // Opcional: Logar alguma informação sobre tentativas de acesso sem usuário autenticado
            // Log::create([
            //     'user_id' => null,
            //     'action' => 'usuário_não_autenticado',
            //     'description' => 'Ação sem usuário autenticado',
            //     'ip_address' => $request->ip(),
            // ]);
        }
    }
}


// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use App\Models\Log;
// use Illuminate\Support\Facades\Auth;

// class LogUserAction
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  \Closure  $next
//      * @return mixed
//      */
//     public function handle(Request $request, Closure $next)
//     {
//         // Antes de executar a requisição, pode registrar algo
//         return $next($request);
//     }

//     /**
//      * Handle task after the response has been sent to the client.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  \Illuminate\Http\Response  $response
//      * @return void
//      */
//     public function terminate($request, $response)
//     {
//         $user = Auth::user();
//         $action = $request->route()->getName(); // Nome da rota como ação
//         $description = $request->method() . ' ' . $request->fullUrl(); // Descrição completa

//         Log::create([
//             'user_id' => $user ? $user->id : null,
//             'action' => $action,
//             'description' => $description,
//             'ip_address' => $request->ip(),
//         ]);
//     }
// }
