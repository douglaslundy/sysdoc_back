<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListIluminacaoPostesRequest;
use App\Http\Requests\StoreIluminacaoPosteRequest;
use App\Http\Requests\UpdateIluminacaoPosteRequest;
use App\Http\Resources\IluminacaoPosteResource;
use App\Models\IluminacaoPoste;
use App\Models\IluminacaoPosteMovimentacao;
use App\Services\Iluminacao\IluminacaoFotoStorage;
use App\Services\Iluminacao\IluminacaoProtocolo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IluminacaoPosteController extends Controller
{
    public function __construct(private IluminacaoFotoStorage $fotos)
    {
    }

    public function index(ListIluminacaoPostesRequest $request)
    {
        $ate = $request->input('ate', now()->toDateString());
        $de = $request->input('de', Carbon::parse($ate)->subDays(29)->toDateString());

        $query = IluminacaoPoste::query()
            ->ativos()
            ->noPeriodo($de, $ate)
            ->with('cadastrador:id,name')
            ->withCount('fotos')
            ->withExists(['movimentacoes as tem_desfecho' => fn ($q) => $q->where('acao', 'desfecho')])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        match ($request->input('status', 'pendentes')) {
            'concluidos' => $query->concluidos(),
            'pendentes' => $query->pendentes(),
            default => null,
        };

        if ($busca = trim((string) $request->input('busca'))) {
            $like = '%'.addcslashes($busca, '%_\\').'%';
            $query->where(fn ($q) => $q->where('ponto_referencia', 'like', $like)
                ->orWhere('logradouro', 'like', $like)
                ->orWhere('bairro', 'like', $like)
                ->orWhere('protocolo', 'like', $like));
        }

        return IluminacaoPosteResource::collection($query->paginate((int) $request->input('per_page', 15)));
    }

    public function show(IluminacaoPoste $poste)
    {
        return new IluminacaoPosteResource($this->completo($poste));
    }

    public function store(StoreIluminacaoPosteRequest $request)
    {
        $user = $request->user();

        if ($uuid = $request->input('client_uuid')) {
            $existente = IluminacaoPoste::where('client_uuid', $uuid)->first();
            if ($existente) {
                // Reenvio após falha de rede: devolve o mesmo poste, sem duplicar.
                if ($existente->cadastrado_por !== $user->id) {
                    return response()->json(['message' => 'Identificador de envio já utilizado.'], 409);
                }

                return (new IluminacaoPosteResource($this->completo($existente)))->response()->setStatusCode(200);
            }
        }

        $poste = DB::transaction(function () use ($request, $user) {
            $poste = new IluminacaoPoste($request->validated());
            $poste->cadastrado_por = $user->id;
            $poste->save();

            $poste->protocolo = IluminacaoProtocolo::for($poste->id, $poste->created_at);
            $poste->save();

            IluminacaoPosteMovimentacao::create(['poste_id' => $poste->id, 'user_id' => $user->id, 'acao' => 'cadastro']);

            if ($request->hasFile('fotos')) {
                $this->fotos->guardar($poste, $request->file('fotos'), 'cadastro', $user->id);
            }

            return $poste;
        });

        return (new IluminacaoPosteResource($this->completo($poste)))->response()->setStatusCode(201);
    }

    public function update(UpdateIluminacaoPosteRequest $request, IluminacaoPoste $poste)
    {
        $user = $request->user();

        if ($user->profile !== 'admin' && $poste->cadastrado_por !== $user->id) {
            return response()->json(['message' => 'Você só pode editar os postes que cadastrou.'], 403);
        }

        if (! $poste->editavelPor($user)) {
            return response()->json(['message' => 'Este poste não pode mais ser editado.'], 422);
        }

        DB::transaction(function () use ($request, $poste, $user) {
            $poste->fill($request->validated())->save();

            $campos = array_values(array_diff(array_keys($poste->getChanges()), ['updated_at']));
            if ($campos) {
                IluminacaoPosteMovimentacao::create([
                    'poste_id' => $poste->id, 'user_id' => $user->id, 'acao' => 'edicao', 'dados' => ['campos' => $campos],
                ]);
            }
        });

        return new IluminacaoPosteResource($this->completo($poste));
    }

    public function destroy(Request $request, IluminacaoPoste $poste)
    {
        if ($request->user()->profile !== 'admin') {
            return response()->json(['message' => 'Somente o administrador pode excluir postes.'], 403);
        }

        DB::transaction(function () use ($request, $poste) {
            $poste->forceFill(['active' => false])->save();
            IluminacaoPosteMovimentacao::create(['poste_id' => $poste->id, 'user_id' => $request->user()->id, 'acao' => 'inativacao']);
        });

        return response()->json(['message' => 'Poste excluído.']);
    }

    public function historico(IluminacaoPoste $poste)
    {
        return response()->json(
            $poste->movimentacoes()->with('user:id,name')->orderBy('id')->get()->map(fn ($m) => [
                'id' => $m->id,
                'acao' => $m->acao,
                'status_anterior' => $m->status_anterior,
                'status_novo' => $m->status_novo,
                'motivo' => $m->motivo,
                'dados' => $m->dados,
                'created_at' => $m->created_at,
                'user' => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
            ])->values()
        );
    }

    private function completo(IluminacaoPoste $poste): IluminacaoPoste
    {
        return $poste->load(['cadastrador:id,name', 'fotos'])->loadCount('fotos');
    }
}
