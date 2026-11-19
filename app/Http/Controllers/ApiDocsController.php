<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Documentação da API (docs/api) servida a usuários autenticados dentro do sistema.
 * Os slugs vêm de uma lista montada a partir dos arquivos do disco: nenhum caminho é aceito do cliente.
 */
class ApiDocsController extends Controller
{
    private const PASTA = 'docs/api';

    /** @return array<string, array{slug: string, titulo: string, grupo: string, arquivo: string}> */
    private function catalogo(): array
    {
        $base = base_path(self::PASTA);
        $itens = [];

        $guias = glob($base.'/*.md') ?: [];
        sort($guias);
        foreach ($guias as $arquivo) {
            $nome = basename($arquivo, '.md');
            $slug = $nome === 'README' ? 'inicio' : $nome;
            $itens[$slug] = ['slug' => $slug, 'grupo' => 'Guia', 'arquivo' => $arquivo];
        }

        $modulos = glob($base.'/referencia/*.md') ?: [];
        sort($modulos);
        foreach ($modulos as $arquivo) {
            $slug = 'ref-'.basename($arquivo, '.md');
            $itens[$slug] = ['slug' => $slug, 'grupo' => 'Referência por módulo', 'arquivo' => $arquivo];
        }

        foreach ($itens as $slug => $item) {
            $itens[$slug]['titulo'] = $this->titulo($item['arquivo'], $slug);
        }

        return $itens;
    }

    private function titulo(string $arquivo, string $padrao): string
    {
        foreach (file($arquivo, FILE_IGNORE_NEW_LINES) ?: [] as $linha) {
            if (str_starts_with($linha, '# ')) {
                return trim(substr($linha, 2));
            }
        }

        return $padrao;
    }

    public function index(): JsonResponse
    {
        $itens = array_map(fn ($i) => ['slug' => $i['slug'], 'titulo' => $i['titulo'], 'grupo' => $i['grupo']], array_values($this->catalogo()));

        return response()->json([
            'itens' => $itens,
            'openapi' => is_file(base_path(self::PASTA.'/openapi.json')),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $item = $this->catalogo()[$slug] ?? null;
        abort_if($item === null, 404, 'Documento não encontrado.');

        $html = Str::markdown(file_get_contents($item['arquivo']), ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return response()->json(['slug' => $slug, 'titulo' => $item['titulo'], 'html' => $html])
            ->header('Cache-Control', 'private, max-age=300');
    }

    public function openapi(): BinaryFileResponse
    {
        $arquivo = base_path(self::PASTA.'/openapi.json');
        abort_unless(is_file($arquivo), 404, 'Especificação não encontrada.');

        return response()->download($arquivo, 'sysdoc-openapi.json', ['Content-Type' => 'application/json']);
    }
}
