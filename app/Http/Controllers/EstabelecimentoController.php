<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstabelecimentoRequest;
use App\Http\Requests\UpdateEstabelecimentoRequest;
use App\Http\Resources\EstabelecimentoResource;
use App\Models\Cnae;
use App\Models\Estabelecimento;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class EstabelecimentoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Estabelecimento::with(['cnaes'])->withCount('alvaras')->orderBy('nome_estabelecimento');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome_estabelecimento', 'LIKE', "%{$busca}%")
                    ->orWhere('nome_responsavel', 'LIKE', "%{$busca}%")
                    ->orWhereHas('cnaes', function ($cnaeQuery) use ($busca) {
                        $cnaeQuery->where('codigo', 'LIKE', "%{$busca}%")
                            ->orWhere('descricao', 'LIKE', "%{$busca}%");
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        return EstabelecimentoResource::collection($query->paginate($perPage));
    }

    public function select(): JsonResponse
    {
        $estabelecimentos = Estabelecimento::select('id', 'nome_estabelecimento', 'nome_responsavel', 'endereco')
            ->orderBy('nome_estabelecimento')
            ->get();

        return response()->json($estabelecimentos);
    }

    public function cnaesSelect(Request $request): JsonResponse
    {
        $busca = trim((string) $request->input('busca', ''));

        $query = Cnae::query()->orderBy('codigo');
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('codigo', 'LIKE', "%{$busca}%")
                    ->orWhere('descricao', 'LIKE', "%{$busca}%");
            });
        }

        return response()->json(
            $query->limit(100)->get(['codigo', 'descricao'])
        );
    }

    public function show(int $id): JsonResponse
    {
        $estabelecimento = Estabelecimento::with(['cnaes'])->withCount('alvaras')->find($id);
        if (! $estabelecimento) {
            return response()->json(['error' => 'Estabelecimento não encontrado'], 404);
        }

        AuditService::record('VIEW', $estabelecimento, null, ['nome_estabelecimento' => $estabelecimento->nome_estabelecimento]);
        return response()->json(new EstabelecimentoResource($estabelecimento));
    }

    public function store(StoreEstabelecimentoRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $codigos = $dados['cnaes'] ?? [];
        unset($dados['cnaes']);

        $estabelecimento = DB::transaction(function () use ($dados, $codigos) {
            $estabelecimento = Estabelecimento::create($dados);
            $this->syncCnaes($estabelecimento, $codigos);
            $estabelecimento->load('cnaes');
            return $estabelecimento;
        });

        return response()->json(new EstabelecimentoResource($estabelecimento), 201);
    }

    public function update(UpdateEstabelecimentoRequest $request, int $id): JsonResponse
    {
        $estabelecimento = Estabelecimento::find($id);
        if (! $estabelecimento) {
            return response()->json(['error' => 'Estabelecimento não encontrado'], 404);
        }

        $dados = $request->validated();
        $codigos = $dados['cnaes'] ?? null;
        unset($dados['cnaes']);

        DB::transaction(function () use ($estabelecimento, $dados, $codigos) {
            $estabelecimento->update($dados);
            if (is_array($codigos)) {
                $this->syncCnaes($estabelecimento, $codigos);
            }
        });

        $estabelecimento->load('cnaes');
        return response()->json(new EstabelecimentoResource($estabelecimento));
    }

    public function destroy(int $id): JsonResponse
    {
        $estabelecimento = Estabelecimento::find($id);
        if (! $estabelecimento) {
            return response()->json(['error' => 'Estabelecimento não encontrado'], 404);
        }

        $estabelecimento->delete();
        return response()->json(['message' => 'Estabelecimento excluído com sucesso']);
    }

    private function syncCnaes(Estabelecimento $estabelecimento, array $codigos): void
    {
        $codigos = array_values(array_unique($codigos));
        foreach ($codigos as $codigo) {
            Cnae::firstOrCreate(['codigo' => $codigo], ['descricao' => null]);
        }
        $ids = Cnae::whereIn('codigo', $codigos)->pluck('id')->all();
        $estabelecimento->cnaes()->sync($ids);
    }
}
