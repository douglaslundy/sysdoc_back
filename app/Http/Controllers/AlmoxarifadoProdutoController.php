<?php

namespace App\Http\Controllers;

use App\Models\AlmoxarifadoCategoria;
use App\Models\AlmoxarifadoEspecie;
use App\Models\AlmoxarifadoFornecedor;
use App\Models\AlmoxarifadoConfig;
use App\Models\AlmoxarifadoLocalizacao;
use App\Models\AlmoxarifadoProduto;
use App\Models\AlmoxarifadoUnidadeMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AlmoxarifadoProdutoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AlmoxarifadoProduto::query()
            ->with([
                'categoria:id,nome',
                'especie:id,nome',
                'unidade:id,nome,sigla',
                'fornecedor:id,nome',
                'localizacao:id,nome,almoxarifado,sala,corredor,estante,prateleira,gaveta,caixa,posicao',
            ])
            ->withSum('estoques as quantidade_disponivel_total', 'quantidade_disponivel')
            ->withSum('estoques as quantidade_reservada_total', 'quantidade_reservada')
            ->withSum('estoques as quantidade_em_separacao_total', 'quantidade_em_separacao')
            ->orderBy('nome');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('codigo_interno', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('almoxarifado_categoria_id', $request->integer('categoria_id'));
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginator);
    }

    public function show(int $id): JsonResponse
    {
        $produto = AlmoxarifadoProduto::with([
            'categoria:id,nome',
            'especie:id,nome',
            'unidade:id,nome,sigla',
            'fornecedor:id,nome',
            'localizacao:id,nome,almoxarifado,sala,corredor,estante,prateleira,gaveta,caixa,posicao',
            'estoques',
        ])->find($id);

        if (! $produto) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }

        return response()->json($produto);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
            'codigo_interno' => ['nullable', 'string', 'max:60', 'unique:almoxarifado_produtos,codigo_interno'],
            'codigo_barras' => ['nullable', 'string', 'max:80'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'almoxarifado_categoria_id' => ['nullable', 'integer', 'exists:almoxarifado_categorias,id'],
            'almoxarifado_especie_id' => ['nullable', 'integer', 'exists:almoxarifado_especies,id'],
            'almoxarifado_unidade_medida_id' => ['nullable', 'integer', 'exists:almoxarifado_unidades_medida,id'],
            'almoxarifado_fornecedor_id' => ['nullable', 'integer', 'exists:almoxarifado_fornecedores,id'],
            'almoxarifado_localizacao_id' => ['nullable', 'integer', 'exists:almoxarifado_localizacoes,id'],
            'marca' => ['nullable', 'string', 'max:120'],
            'modelo' => ['nullable', 'string', 'max:120'],
            'fabricante' => ['nullable', 'string', 'max:120'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'lote' => ['nullable', 'string', 'max:80'],
            'validade' => ['nullable', 'date'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0'],
            'estoque_maximo' => ['nullable', 'numeric', 'min:0'],
            'almoxarifado' => ['nullable', 'string', 'max:120'],
            'sala' => ['nullable', 'string', 'max:80'],
            'corredor' => ['nullable', 'string', 'max:80'],
            'estante' => ['nullable', 'string', 'max:80'],
            'prateleira' => ['nullable', 'string', 'max:80'],
            'gaveta' => ['nullable', 'string', 'max:80'],
            'caixa' => ['nullable', 'string', 'max:80'],
            'posicao' => ['nullable', 'string', 'max:80'],
            'observacao_localizacao' => ['nullable', 'string'],
            'imagem_url' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $config = AlmoxarifadoConfig::current();
        if ($config->exigir_localizacao_produto && empty($validated['almoxarifado_localizacao_id'])) {
            abort(422, 'A localização do produto é obrigatória nas configurações do almoxarifado.');
        }

        $validated['codigo_interno'] = $validated['codigo_interno'] ?: strtoupper('ALM-' . Str::random(10));
        $validated['ativo'] = $validated['ativo'] ?? true;

        return response()->json(
            AlmoxarifadoProduto::create($validated)->load([
                'categoria:id,nome',
                'especie:id,nome',
                'unidade:id,nome,sigla',
                'fornecedor:id,nome',
                'localizacao:id,nome,almoxarifado,sala,corredor,estante,prateleira,gaveta,caixa,posicao',
            ]),
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $produto = AlmoxarifadoProduto::find($id);

        if (! $produto) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }

        $validated = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
            'codigo_interno' => ['sometimes', 'nullable', 'string', 'max:60', 'unique:almoxarifado_produtos,codigo_interno,'.$id],
            'codigo_barras' => ['nullable', 'string', 'max:80'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'almoxarifado_categoria_id' => ['nullable', 'integer', 'exists:almoxarifado_categorias,id'],
            'almoxarifado_especie_id' => ['nullable', 'integer', 'exists:almoxarifado_especies,id'],
            'almoxarifado_unidade_medida_id' => ['nullable', 'integer', 'exists:almoxarifado_unidades_medida,id'],
            'almoxarifado_fornecedor_id' => ['nullable', 'integer', 'exists:almoxarifado_fornecedores,id'],
            'almoxarifado_localizacao_id' => ['nullable', 'integer', 'exists:almoxarifado_localizacoes,id'],
            'marca' => ['nullable', 'string', 'max:120'],
            'modelo' => ['nullable', 'string', 'max:120'],
            'fabricante' => ['nullable', 'string', 'max:120'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'lote' => ['nullable', 'string', 'max:80'],
            'validade' => ['nullable', 'date'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0'],
            'estoque_maximo' => ['nullable', 'numeric', 'min:0'],
            'almoxarifado' => ['nullable', 'string', 'max:120'],
            'sala' => ['nullable', 'string', 'max:80'],
            'corredor' => ['nullable', 'string', 'max:80'],
            'estante' => ['nullable', 'string', 'max:80'],
            'prateleira' => ['nullable', 'string', 'max:80'],
            'gaveta' => ['nullable', 'string', 'max:80'],
            'caixa' => ['nullable', 'string', 'max:80'],
            'posicao' => ['nullable', 'string', 'max:80'],
            'observacao_localizacao' => ['nullable', 'string'],
            'imagem_url' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $config = AlmoxarifadoConfig::current();
        if ($config->exigir_localizacao_produto && empty($validated['almoxarifado_localizacao_id'] ?? $produto->almoxarifado_localizacao_id)) {
            abort(422, 'A localização do produto é obrigatória nas configurações do almoxarifado.');
        }

        $produto->update($validated);

        return response()->json($produto->fresh()->load([
            'categoria:id,nome',
            'especie:id,nome',
            'unidade:id,nome,sigla',
            'fornecedor:id,nome',
            'localizacao:id,nome,almoxarifado,sala,corredor,estante,prateleira,gaveta,caixa,posicao',
        ]));
    }

    public function destroy(int $id): JsonResponse
    {
        $produto = AlmoxarifadoProduto::find($id);

        if (! $produto) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }

        $produto->update(['ativo' => false]);

        return response()->json(['message' => 'Produto inativado com sucesso.']);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'categorias' => AlmoxarifadoCategoria::orderBy('nome')->get(['id', 'nome']),
            'especies' => AlmoxarifadoEspecie::orderBy('nome')->get(['id', 'nome']),
            'unidades' => AlmoxarifadoUnidadeMedida::orderBy('nome')->get(['id', 'nome', 'sigla']),
            'fornecedores' => AlmoxarifadoFornecedor::orderBy('nome')->get(['id', 'nome']),
            'localizacoes' => AlmoxarifadoLocalizacao::orderBy('nome')->get(['id', 'nome']),
        ]);
    }
}
