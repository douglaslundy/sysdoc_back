<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PainelEsusController extends MonitorApsBaseController
{
    /**
     * Tenta candidatos de nome de coluna até encontrar um que existe.
     * Evita quebrar em versões diferentes do e-SUS PEC.
     */
    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if ($this->hasColumn($table, $col)) {
                return $col;
            }
        }
        return null;
    }

    /**
     * Detecta dinamicamente os nomes de colunas em tb_lista_atendimento
     * que variam entre versões do e-SUS PEC.
     */
    private function resolveListaColumns(): array
    {
        return [
            'cnesCol'     => $this->firstExistingColumn('tb_lista_atendimento', ['nu_cnes', 'co_unico_saude']) ?? 'nu_cnes',
            'cidadaoFk'   => $this->firstExistingColumn('tb_lista_atendimento', ['co_seq_cidadao', 'co_cidadao']) ?? 'co_seq_cidadao',
            'profFk'      => $this->firstExistingColumn('tb_lista_atendimento', ['co_seq_profissional', 'co_profissional']) ?? 'co_seq_profissional',
            'equipeFk'    => $this->firstExistingColumn('tb_lista_atendimento', ['co_seq_equipe', 'co_equipe']) ?? 'co_seq_equipe',
            'hrInicioCol' => $this->firstExistingColumn('tb_lista_atendimento', ['hr_inicio_atendimento', 'hr_atendimento']) ?? 'hr_inicio_atendimento',
        ];
    }

    /**
     * GET /public/painel-esus/validar-cnes?cnes=XXXXXXX
     * Público — sem autenticação.
     * Verifica se o CNES existe em tb_unidade_saude.
     */
    public function validarCnes(Request $request): JsonResponse
    {
        $request->validate(['cnes' => 'required|string|max:20']);
        $cnes = trim($request->input('cnes'));

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $row = $db->selectOne(
                "SELECT no_unidade_saude FROM tb_unidade_saude WHERE co_cnes = ? LIMIT 1",
                [$cnes]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PainelEsus.validarCnes: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar o banco de dados.'], 500);
        }

        if (!$row) {
            return response()->json(['error' => 'CNES não encontrado na base do e-SUS.'], 404);
        }

        return response()->json([
            'cnes' => $cnes,
            'nome' => $row->no_unidade_saude,
        ]);
    }

    /**
     * GET /public/painel-esus/estado?cnes=XXXXXXX
     * Público — sem autenticação.
     * Retorna quem está em atendimento agora e os últimos 5 atendidos no dia.
     */
    public function estado(Request $request): JsonResponse
    {
        $request->validate(['cnes' => 'required|string|max:20']);
        $cnes = trim($request->input('cnes'));
        $hoje = now()->toDateString();

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $cols        = $this->resolveListaColumns();
            $cnesCol     = $cols['cnesCol'];
            $cidadaoFk   = $cols['cidadaoFk'];
            $profFk      = $cols['profFk'];
            $hrInicioCol = $cols['hrInicioCol'];

            // Nome da unidade de saúde
            $unidadeRow = null;
            try {
                $unidadeRow = $db->selectOne(
                    "SELECT no_unidade_saude FROM tb_unidade_saude WHERE co_cnes = ? LIMIT 1",
                    [$cnes]
                );
            } catch (\Throwable) {}

            // Query base reutilizada nos 3 selects abaixo
            $baseSelect = "
                SELECT
                    COALESCE(c.no_cidadao, 'Cidadão')::text    AS cidadao,
                    COALESCE(p.no_profissional, '')::text       AS profissional,
                    TO_CHAR(la.{$hrInicioCol}, 'HH24:MI')      AS hr_inicio
                FROM tb_lista_atendimento la
                LEFT JOIN tb_cidadao c    ON c.co_seq_cidadao       = la.{$cidadaoFk}
                LEFT JOIN tb_profissional p ON p.co_seq_profissional = la.{$profFk}
                WHERE la.{$cnesCol} = ?
                  AND la.dt_lista_atendimento = ?
            ";

            // Prioridade: status 4 (Em Atendimento) → status 2 (último Atendido)
            $emAtendimento = $db->selectOne(
                $baseSelect . " AND la.tp_situacao_lista_atendimento = 4 ORDER BY la.{$hrInicioCol} DESC NULLS LAST LIMIT 1",
                [$cnes, $hoje]
            );

            if (!$emAtendimento) {
                $emAtendimento = $db->selectOne(
                    $baseSelect . " AND la.tp_situacao_lista_atendimento = 2 ORDER BY la.{$hrInicioCol} DESC NULLS LAST LIMIT 1",
                    [$cnes, $hoje]
                );
            }

            // Últimos 5 finalizados
            $ultimosAtendidos = $db->select(
                $baseSelect . " AND la.tp_situacao_lista_atendimento = 2 ORDER BY la.{$hrInicioCol} DESC NULLS LAST LIMIT 5",
                [$cnes, $hoje]
            );

            return response()->json([
                'unidade'           => $unidadeRow?->no_unidade_saude ?? 'CNES ' . $cnes,
                'em_atendimento'    => $emAtendimento,
                'ultimos_atendidos' => $ultimosAtendidos,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PainelEsus.estado: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar os dados de atendimento.'], 500);
        }
    }

    /**
     * GET /painel-esus/fila?cnes=X&equipe=Y&profissional=Z
     * Autenticado. Retorna contadores e lista de aguardando para a gestão de fila.
     */
    public function fila(Request $request): JsonResponse
    {
        $request->validate([
            'cnes'         => 'required|string|max:20',
            'equipe'       => 'nullable|integer',
            'profissional' => 'nullable|integer',
        ]);

        $cnes     = trim($request->input('cnes'));
        $equipeId = $request->input('equipe');
        $profId   = $request->input('profissional');
        $hoje     = now()->toDateString();

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $cols      = $this->resolveListaColumns();
            $cnesCol   = $cols['cnesCol'];
            $cidadaoFk = $cols['cidadaoFk'];
            $profFk    = $cols['profFk'];
            $equipeFk  = $cols['equipeFk'];

            $where  = "la.{$cnesCol} = ? AND la.dt_lista_atendimento = ?";
            $params = [$cnes, $hoje];

            if ($equipeId !== null) {
                $where   .= " AND la.{$equipeFk} = ?";
                $params[] = (int) $equipeId;
            }
            if ($profId !== null) {
                $where   .= " AND la.{$profFk} = ?";
                $params[] = (int) $profId;
            }

            $contadores = $db->selectOne("
                SELECT
                    COUNT(*) FILTER (WHERE la.tp_situacao_lista_atendimento = 1)       AS aguardando,
                    COUNT(*) FILTER (WHERE la.tp_situacao_lista_atendimento IN (2, 4)) AS atendidos,
                    COUNT(*) FILTER (WHERE la.tp_situacao_lista_atendimento = 3)       AS nao_aguardaram
                FROM tb_lista_atendimento la
                WHERE {$where}
            ", $params);

            $aguardando = $db->select("
                SELECT
                    la.co_seq_lista_atendimento              AS id,
                    COALESCE(c.no_cidadao, 'Cidadão')::text AS cidadao,
                    TO_CHAR(la.hr_chegada, 'HH24:MI')        AS hr_chegada,
                    COALESCE(e.no_equipe, '')::text           AS equipe,
                    COALESCE(p.no_profissional, '')::text     AS profissional
                FROM tb_lista_atendimento la
                LEFT JOIN tb_cidadao      c ON c.co_seq_cidadao       = la.{$cidadaoFk}
                LEFT JOIN tb_profissional p ON p.co_seq_profissional   = la.{$profFk}
                LEFT JOIN tb_equipe       e ON e.co_seq_equipe         = la.{$equipeFk}
                WHERE {$where}
                  AND la.tp_situacao_lista_atendimento = 1
                ORDER BY la.hr_chegada ASC NULLS LAST
            ", $params);

            return response()->json([
                'contadores' => [
                    'aguardando'     => (int) ($contadores?->aguardando ?? 0),
                    'atendidos'      => (int) ($contadores?->atendidos ?? 0),
                    'nao_aguardaram' => (int) ($contadores?->nao_aguardaram ?? 0),
                ],
                'aguardando' => $aguardando,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PainelEsus.fila: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar a fila de atendimento.'], 500);
        }
    }

    /**
     * GET /painel-esus/filtros?cnes=X
     * Autenticado. Retorna equipes e profissionais do dia para popular os dropdowns.
     */
    public function filtros(Request $request): JsonResponse
    {
        $request->validate(['cnes' => 'required|string|max:20']);
        $cnes = trim($request->input('cnes'));
        $hoje = now()->toDateString();

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $cols     = $this->resolveListaColumns();
            $cnesCol  = $cols['cnesCol'];
            $profFk   = $cols['profFk'];
            $equipeFk = $cols['equipeFk'];

            $equipes = $db->select("
                SELECT DISTINCT e.co_seq_equipe AS id, e.no_equipe AS nome
                FROM tb_lista_atendimento la
                JOIN tb_equipe e ON e.co_seq_equipe = la.{$equipeFk}
                WHERE la.{$cnesCol} = ? AND la.dt_lista_atendimento = ?
                ORDER BY e.no_equipe
            ", [$cnes, $hoje]);

            $profissionais = $db->select("
                SELECT DISTINCT p.co_seq_profissional AS id, p.no_profissional AS nome
                FROM tb_lista_atendimento la
                JOIN tb_profissional p ON p.co_seq_profissional = la.{$profFk}
                WHERE la.{$cnesCol} = ? AND la.dt_lista_atendimento = ?
                ORDER BY p.no_profissional
            ", [$cnes, $hoje]);

            return response()->json([
                'equipes'       => $equipes,
                'profissionais' => $profissionais,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PainelEsus.filtros: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao buscar os filtros de atendimento.'], 500);
        }
    }
}
