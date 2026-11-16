<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardAtalhoRequest;
use App\Models\DashboardAtalho;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\Request;

class DashboardAtalhoController extends Controller
{
    /** Atalhos ativos que o usuário pode abrir (admin vê todos; externos aparecem para todos). */
    public function index(Request $request, PagePermissionService $pages)
    {
        $user = $request->user();

        return DashboardAtalho::where('ativo', true)->orderBy('ordem')->orderBy('id')->get()
            ->filter(function (DashboardAtalho $a) use ($user, $pages) {
                if (preg_match('#^https?://#i', $a->url) || $user->profile === 'admin') {
                    return true;
                }
                $path = '/'.trim(explode('?', explode('#', $a->url)[0])[0], '/');

                return $pages->canAccess($user, $path) || $pages->canAccessAny($user, [$path.'*']);
            })->values();
    }

    public function adminIndex()
    {
        return DashboardAtalho::orderBy('ordem')->orderBy('id')->get();
    }

    public function store(DashboardAtalhoRequest $request)
    {
        $dados = $request->validated();
        $dados['ordem'] = $dados['ordem'] ?? ((int) DashboardAtalho::max('ordem') + 1);
        $dados['icone'] = $dados['icone'] ?? 'link';

        return response()->json(DashboardAtalho::create($dados), 201);
    }

    public function update(DashboardAtalhoRequest $request, DashboardAtalho $atalho)
    {
        $dados = $request->validated();
        $dados['icone'] = $dados['icone'] ?? $atalho->icone;
        $atalho->update($dados);

        return response()->json($atalho->fresh());
    }

    public function destroy(DashboardAtalho $atalho)
    {
        $atalho->delete();

        return response()->noContent();
    }
}
