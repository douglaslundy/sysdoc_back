<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAtalhoRequest;
use App\Models\UserAtalho;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\Request;

class UserAtalhoController extends Controller
{
    public function __construct(private PagePermissionService $pages)
    {
    }

    /** Atalhos do próprio usuário; os de páginas que ele deixou de poder abrir não aparecem. */
    public function index(Request $request)
    {
        $user = $request->user();

        return UserAtalho::where('user_id', $user->id)->orderBy('ordem')->orderBy('id')->get()
            ->filter(fn (UserAtalho $a) => $this->pode($user, $a->url))->values();
    }

    public function store(UserAtalhoRequest $request)
    {
        $user = $request->user();
        $dados = $request->validated();
        $path = $this->caminho($dados['url']);

        if (! $this->pode($user, $path)) {
            return response()->json(['message' => 'Você não tem acesso a esta página.'], 403);
        }
        if (UserAtalho::where('user_id', $user->id)->count() >= UserAtalho::MAXIMO) {
            return response()->json(['message' => 'Limite de '.UserAtalho::MAXIMO.' atalhos atingido.'], 422);
        }
        if (UserAtalho::where('user_id', $user->id)->where('url', $path)->exists()) {
            return response()->json(['message' => 'Esta página já está nos seus atalhos.'], 422);
        }

        $atalho = UserAtalho::create([
            'user_id' => $user->id,
            'titulo' => $dados['titulo'],
            'url' => $path,
            'icone' => $dados['icone'] ?? 'link',
            'ordem' => (int) UserAtalho::where('user_id', $user->id)->max('ordem') + 1,
        ]);

        return response()->json($atalho, 201);
    }

    public function destroy(Request $request, int $id)
    {
        UserAtalho::where('user_id', $request->user()->id)->where('id', $id)->firstOrFail()->delete();

        return response()->noContent();
    }

    private function caminho(string $url): string
    {
        return '/'.trim(explode('?', explode('#', $url)[0])[0], '/');
    }

    private function pode($user, string $url): bool
    {
        $path = $this->caminho($url);

        return $this->pages->canAccess($user, $path) || $this->pages->canAccessAny($user, [$path.'*']);
    }
}
