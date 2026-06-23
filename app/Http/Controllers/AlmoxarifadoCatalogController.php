<?php

namespace App\Http\Controllers;

use App\Models\AlmoxarifadoCategoria;
use App\Models\AlmoxarifadoEspecie;
use App\Models\AlmoxarifadoFornecedor;
use App\Models\AlmoxarifadoLocalizacao;
use App\Models\AlmoxarifadoSecretaria;
use App\Models\AlmoxarifadoUnidadeMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class AlmoxarifadoCatalogController extends Controller
{
    private function resourceConfig(string $type): array
    {
        return match ($type) {
            'secretarias' => [
                'model' => AlmoxarifadoSecretaria::class,
                'orderBy' => ['nome'],
                'fields' => ['nome', 'sigla', 'responsavel', 'contato', 'observacoes', 'ativo'],
                'rules' => [
                    'nome' => ['required', 'string', 'max:120', Rule::unique('almoxarifado_secretarias', 'nome')],
                    'sigla' => ['nullable', 'string', 'max:20'],
                    'responsavel' => ['nullable', 'string', 'max:120'],
                    'contato' => ['nullable', 'string', 'max:120'],
                    'observacoes' => ['nullable', 'string'],
                    'ativo' => ['sometimes', 'boolean'],
                ],
            ],
            'categorias' => [
                'model' => AlmoxarifadoCategoria::class,
                'orderBy' => ['nome'],
                'fields' => ['nome', 'observacoes', 'ativo'],
                'rules' => [
                    'nome' => ['required', 'string', 'max:120', Rule::unique('almoxarifado_categorias', 'nome')],
                    'observacoes' => ['nullable', 'string'],
                    'ativo' => ['sometimes', 'boolean'],
                ],
            ],
            'especies' => [
                'model' => AlmoxarifadoEspecie::class,
                'orderBy' => ['nome'],
                'fields' => ['nome', 'observacoes', 'ativo'],
                'rules' => [
                    'nome' => ['required', 'string', 'max:120', Rule::unique('almoxarifado_especies', 'nome')],
                    'observacoes' => ['nullable', 'string'],
                    'ativo' => ['sometimes', 'boolean'],
                ],
            ],
            'unidades-medida' => [
                'model' => AlmoxarifadoUnidadeMedida::class,
                'orderBy' => ['nome'],
                'fields' => ['nome', 'sigla', 'observacoes', 'ativo'],
                'rules' => [
                    'nome' => ['required', 'string', 'max:80', Rule::unique('almoxarifado_unidades_medida', 'nome')],
                    'sigla' => ['nullable', 'string', 'max:20'],
                    'observacoes' => ['nullable', 'string'],
                    'ativo' => ['sometimes', 'boolean'],
                ],
            ],
            'fornecedores' => [
                'model' => AlmoxarifadoFornecedor::class,
                'orderBy' => ['nome'],
                'fields' => ['nome', 'documento', 'telefone', 'email', 'contato', 'endereco', 'observacoes', 'ativo'],
                'rules' => [
                    'nome' => ['required', 'string', 'max:150'],
                    'documento' => ['nullable', 'string', 'max:30'],
                    'telefone' => ['nullable', 'string', 'max:30'],
                    'email' => ['nullable', 'email', 'max:120'],
                    'contato' => ['nullable', 'string', 'max:120'],
                    'endereco' => ['nullable', 'string', 'max:255'],
                    'observacoes' => ['nullable', 'string'],
                    'ativo' => ['sometimes', 'boolean'],
                ],
            ],
            'localizacoes' => [
                'model' => AlmoxarifadoLocalizacao::class,
                'orderBy' => ['nome'],
                'fields' => ['nome', 'almoxarifado', 'sala', 'corredor', 'estante', 'prateleira', 'gaveta', 'caixa', 'posicao', 'observacoes', 'ativo'],
                'rules' => [
                    'nome' => ['required', 'string', 'max:150'],
                    'almoxarifado' => ['nullable', 'string', 'max:120'],
                    'sala' => ['nullable', 'string', 'max:80'],
                    'corredor' => ['nullable', 'string', 'max:80'],
                    'estante' => ['nullable', 'string', 'max:80'],
                    'prateleira' => ['nullable', 'string', 'max:80'],
                    'gaveta' => ['nullable', 'string', 'max:80'],
                    'caixa' => ['nullable', 'string', 'max:80'],
                    'posicao' => ['nullable', 'string', 'max:80'],
                    'observacoes' => ['nullable', 'string'],
                    'ativo' => ['sometimes', 'boolean'],
                ],
            ],
            default => abort(404, 'Tipo de cadastro não encontrado'),
        };
    }

    private function updateRules(string $type, int $id): array
    {
        $config = $this->resourceConfig($type);
        $rules = $config['rules'];

        foreach ($rules as $field => &$fieldRules) {
            $fieldRules = array_values(array_filter($fieldRules, fn ($rule) => !($rule instanceof Unique)));
            array_unshift($fieldRules, 'sometimes');
            if ($field === 'nome') {
                $table = match ($type) {
                    'secretarias' => 'almoxarifado_secretarias',
                    'categorias' => 'almoxarifado_categorias',
                    'especies' => 'almoxarifado_especies',
                    'unidades-medida' => 'almoxarifado_unidades_medida',
                    'fornecedores' => 'almoxarifado_fornecedores',
                    'localizacoes' => 'almoxarifado_localizacoes',
                    default => null,
                };

                if ($table) {
                    $fieldRules[] = Rule::unique($table, 'nome')->ignore($id);
                }
            }
        }

        return $rules;
    }

    public function index(string $type): JsonResponse
    {
        $config = $this->resourceConfig($type);
        $model = $config['model'];

        return response()->json(
            $model::query()->orderBy(...$config['orderBy'])->get()
        );
    }

    public function store(Request $request, string $type): JsonResponse
    {
        $config = $this->resourceConfig($type);
        $model = $config['model'];
        $validated = $request->validate($config['rules']);
        $payload = Arr::only($validated, $config['fields']);
        $payload['ativo'] = $validated['ativo'] ?? true;

        return response()->json($model::create($payload), 201);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $config = $this->resourceConfig($type);
        $model = $config['model'];
        $item = $model::find($id);

        if (! $item) {
            return response()->json(['message' => 'Registro não encontrado.'], 404);
        }

        $validated = $request->validate($this->updateRules($type, $item->id));
        $payload = Arr::only($validated, $config['fields']);
        $item->update($payload);

        return response()->json($item->fresh());
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $config = $this->resourceConfig($type);
        $model = $config['model'];
        $item = $model::find($id);

        if (! $item) {
            return response()->json(['message' => 'Registro não encontrado.'], 404);
        }

        if (array_key_exists('ativo', $item->getAttributes())) {
            $item->update(['ativo' => false]);
            return response()->json(['message' => 'Registro inativado com sucesso.']);
        }

        $item->delete();

        return response()->json(['message' => 'Registro removido com sucesso.']);
    }
}
