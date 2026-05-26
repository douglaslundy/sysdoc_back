<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PainelEsusController extends MonitorApsBaseController
{
    /**
     * Detecta qual tabela usar para a fila.
     * Tenta tb_lista_atendimento (Módulo de Gestão de Fila do PEC) e cai para
     * ta_agendado (agendamentos transacionais) quando MGF não está habilitado.
     */
    private function resolveFilaTable(): ?string
    {
        if ($this->hasTable('tb_lista_atendimento')) return 'tb_lista_atendimento';
        if ($this->hasTable('ta_agendado'))          return 'ta_agendado';
        return null;
    }

    /**
     * Resolve colunas da tabela de fila dinamicamente.
     * Funciona tanto para tb_lista_atendimento quanto para ta_agendado.
     */
    private function resolveListaColumns(string $table): array
    {
        return [
            'cnesCol'     => $this->firstExistingColumn($table, ['nu_cnes', 'co_unico_saude', 'co_cnes']) ?? 'nu_cnes',
            'cidadaoFk'   => $this->firstExistingColumn($table, ['co_seq_cidadao', 'co_cidadao']) ?? 'co_seq_cidadao',
            'profFk'      => $this->firstExistingColumn($table, ['co_seq_profissional', 'co_profissional', 'co_lotacao']) ?? 'co_seq_profissional',
            'equipeFk'    => $this->firstExistingColumn($table, ['co_seq_equipe', 'co_equipe']) ?? 'co_seq_equipe',
            'hrInicioCol' => $this->firstExistingColumn($table, ['hr_inicio_atendimento', 'hr_atendimento', 'hr_agendado', 'hr_chegada']) ?? 'hr_inicio_atendimento',
            'hrChegadaCol'=> $this->firstExistingColumn($table, ['hr_chegada', 'hr_agendado', 'hr_inicio_atendimento']) ?? 'hr_chegada',
            'dtCol'       => $this->firstExistingColumn($table, ['dt_lista_atendimento', 'dt_agendado', 'dt_consulta']) ?? 'dt_lista_atendimento',
            'statusCol'   => $this->firstExistingColumn($table, ['tp_situacao_lista_atendimento', 'tp_situacao_agendado', 'co_situacao_agendado']) ?? 'tp_situacao_lista_atendimento',
            'pkCol'       => $this->firstExistingColumn($table, ['co_seq_lista_atendimento', 'co_seq_agendado', 'co_agendado']) ?? 'co_seq_lista_atendimento',
        ];
    }

    /**
     * Constrói JOINs e expressões de nome para cidadão, profissional e equipe.
     * Para ta_agendado usa as tabelas ta_* correspondentes.
     *
     * @return array{cidJoin:string, cidExpr:string, profJoin:string, profExpr:string, eqJoin:string, eqExpr:string}
     */
    private function buildFilaJoins(string $table, array $cols): array
    {
        $cidFk    = $cols['cidadaoFk'];
        $profFk   = $cols['profFk'];
        $equipeFk = $cols['equipeFk'];

        if ($table === 'ta_agendado') {
            // Cidadão
            $cidJoin = '';
            $cidExpr = "'Cidadão'::text";
            if ($this->hasTable('ta_cidadao')) {
                $cidPk   = $this->firstExistingColumn('ta_cidadao', ['co_seq_cidadao', 'co_cidadao']) ?? 'co_seq_cidadao';
                $cidNome = $this->firstExistingColumn('ta_cidadao', ['no_cidadao', 'nm_cidadao']) ?? 'no_cidadao';
                $cidJoin = "LEFT JOIN ta_cidadao c ON c.{$cidPk} = la.{$cidFk}";
                $cidExpr = "COALESCE(c.{$cidNome}, 'Cidadão')::text";
            }

            // Profissional — tenta join direto ou via ta_lotacao
            $profJoin = '';
            $profExpr = "''::text";
            if ($profFk !== 'co_lotacao' && $this->hasTable('tb_profissional')) {
                $profJoin = "LEFT JOIN tb_profissional p ON p.co_seq_profissional = la.{$profFk}";
                $profExpr = "COALESCE(p.no_profissional, '')::text";
            } elseif ($this->hasTable('ta_lotacao')) {
                $lotPk   = $this->firstExistingColumn('ta_lotacao', ['co_seq_lotacao', 'co_lotacao']) ?? 'co_seq_lotacao';
                $lotNome = $this->firstExistingColumn('ta_lotacao', ['no_profissional']) ?? null;
                if ($lotNome) {
                    $profJoin = "LEFT JOIN ta_lotacao p ON p.{$lotPk} = la.{$profFk}";
                    $profExpr = "COALESCE(p.{$lotNome}, '')::text";
                }
            }

            // Equipe
            $eqJoin = '';
            $eqExpr = "''::text";
            if ($this->hasTable('ta_equipe')) {
                $eqPk   = $this->firstExistingColumn('ta_equipe', ['co_seq_equipe', 'co_equipe']) ?? 'co_seq_equipe';
                $eqNome = $this->firstExistingColumn('ta_equipe', ['no_equipe', 'ds_nome_equipe']) ?? 'no_equipe';
                $eqJoin = "LEFT JOIN ta_equipe e ON e.{$eqPk} = la.{$equipeFk}";
                $eqExpr = "COALESCE(e.{$eqNome}, '')::text";
            }

            return compact('cidJoin', 'cidExpr', 'profJoin', 'profExpr', 'eqJoin', 'eqExpr');
        }

        // tb_lista_atendimento — joins padrão MGF
        return [
            'cidJoin'  => "LEFT JOIN tb_cidadao c ON c.co_seq_cidadao = la.{$cidFk}",
            'cidExpr'  => "COALESCE(c.no_cidadao, 'Cidadão')::text",
            'profJoin' => "LEFT JOIN tb_profissional p ON p.co_seq_profissional = la.{$profFk}",
            'profExpr' => "COALESCE(p.no_profissional, '')::text",
            'eqJoin'   => "LEFT JOIN tb_equipe e ON e.co_seq_equipe = la.{$equipeFk}",
            'eqExpr'   => "COALESCE(e.no_equipe, '')::text",
        ];
    }

    private function resolveUnidadeColumns(): array
    {
        return [
            'cnesCol' => $this->firstExistingColumn('tb_unidade_saude',
                ['co_cnes', 'nu_cnes', 'co_unico_saude']) ?? 'co_cnes',
            'nomeCol' => $this->firstExistingColumn('tb_unidade_saude',
                ['no_unidade_saude', 'ds_nome', 'no_estabelecimento']) ?? 'no_unidade_saude',
        ];
    }

    /**
     * GET /painel-esus/unidades
     * Autenticado. Lista unidades de saúde do banco e-SUS para o seletor de CNES.
     */
    public function unidades(): JsonResponse
    {
        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $cols = $this->resolveUnidadeColumns();
            $rows = $db->select(
                "SELECT {$cols['cnesCol']} AS cnes, {$cols['nomeCol']} AS nome
                 FROM tb_unidade_saude
                 ORDER BY {$cols['nomeCol']}"
            );
            return response()->json(['unidades' => $rows]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PainelEsus.unidades: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar unidades de saúde.'], 500);
        }
    }

    /**
     * GET /public/painel-esus/validar-cnes?cnes=XXXXXXX
     * Público — sem autenticação.
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
            $cols = $this->resolveUnidadeColumns();
            $row  = $db->selectOne(
                "SELECT {$cols['nomeCol']} AS nome FROM tb_unidade_saude WHERE {$cols['cnesCol']} = ? LIMIT 1",
                [$cnes]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PainelEsus.validarCnes: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar o banco de dados.'], 500);
        }

        if (!$row) {
            return response()->json(['error' => 'CNES não encontrado na base do e-SUS.'], 404);
        }

        return response()->json(['cnes' => $cnes, 'nome' => $row->nome]);
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
            $filaTable = $this->resolveFilaTable();

            if (!$filaTable) {
                return response()->json([
                    'unidade'             => null,
                    'em_atendimento'      => null,
                    'ultimos_atendidos'   => [],
                    'modulo_indisponivel' => true,
                ]);
            }

            $cols  = $this->resolveListaColumns($filaTable);
            $joins = $this->buildFilaJoins($filaTable, $cols);

            $allJoins = implode("\n", array_filter([$joins['cidJoin'], $joins['profJoin']]));

            // Nome da unidade de saúde
            $unidadeRow = null;
            try {
                $uCols      = $this->resolveUnidadeColumns();
                $unidadeRow = $db->selectOne(
                    "SELECT {$uCols['nomeCol']} AS nome FROM tb_unidade_saude WHERE {$uCols['cnesCol']} = ? LIMIT 1",
                    [$cnes]
                );
            } catch (\Throwable) {}

            $baseSelect = "
                SELECT
                    {$joins['cidExpr']}  AS cidadao,
                    {$joins['profExpr']} AS profissional,
                    TO_CHAR(la.{$cols['hrInicioCol']}, 'HH24:MI') AS hr_inicio
                FROM {$filaTable} la
                {$allJoins}
                WHERE la.{$cols['cnesCol']} = ?
                  AND la.{$cols['dtCol']} = ?
            ";

            // Status 4 = Em Atendimento, status 2 = Atendido (último fallback)
            $emAtendimento = $db->selectOne(
                $baseSelect . " AND la.{$cols['statusCol']} = 4 ORDER BY la.{$cols['hrInicioCol']} DESC NULLS LAST LIMIT 1",
                [$cnes, $hoje]
            );

            if (!$emAtendimento) {
                $emAtendimento = $db->selectOne(
                    $baseSelect . " AND la.{$cols['statusCol']} = 2 ORDER BY la.{$cols['hrInicioCol']} DESC NULLS LAST LIMIT 1",
                    [$cnes, $hoje]
                );
            }

            $ultimosAtendidos = $db->select(
                $baseSelect . " AND la.{$cols['statusCol']} = 2 ORDER BY la.{$cols['hrInicioCol']} DESC NULLS LAST LIMIT 5",
                [$cnes, $hoje]
            );

            return response()->json([
                'unidade'           => $unidadeRow?->nome ?? 'CNES ' . $cnes,
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
            $filaTable = $this->resolveFilaTable();

            if (!$filaTable) {
                return response()->json(['error' => 'Módulo de Gestão de Fila não habilitado neste eSUS PEC.'], 503);
            }

            $cols  = $this->resolveListaColumns($filaTable);
            $joins = $this->buildFilaJoins($filaTable, $cols);

            $where  = "la.{$cols['cnesCol']} = ? AND la.{$cols['dtCol']} = ?";
            $params = [$cnes, $hoje];

            if ($equipeId !== null) {
                $where   .= " AND la.{$cols['equipeFk']} = ?";
                $params[] = (int) $equipeId;
            }
            if ($profId !== null) {
                $where   .= " AND la.{$cols['profFk']} = ?";
                $params[] = (int) $profId;
            }

            $contadores = $db->selectOne("
                SELECT
                    COUNT(*) FILTER (WHERE la.{$cols['statusCol']} = 1)        AS aguardando,
                    COUNT(*) FILTER (WHERE la.{$cols['statusCol']} IN (2, 4))  AS atendidos,
                    COUNT(*) FILTER (WHERE la.{$cols['statusCol']} = 3)        AS nao_aguardaram
                FROM {$filaTable} la
                WHERE {$where}
            ", $params);

            $allJoins = implode("\n", array_filter([
                $joins['cidJoin'], $joins['profJoin'], $joins['eqJoin'],
            ]));

            $aguardando = $db->select("
                SELECT
                    la.{$cols['pkCol']}                              AS id,
                    {$joins['cidExpr']}                              AS cidadao,
                    TO_CHAR(la.{$cols['hrChegadaCol']}, 'HH24:MI')  AS hr_chegada,
                    {$joins['eqExpr']}                               AS equipe,
                    {$joins['profExpr']}                             AS profissional
                FROM {$filaTable} la
                {$allJoins}
                WHERE {$where}
                  AND la.{$cols['statusCol']} = 1
                ORDER BY la.{$cols['hrChegadaCol']} ASC NULLS LAST
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
            $filaTable = $this->resolveFilaTable();

            if (!$filaTable) {
                return response()->json(['error' => 'Módulo de Gestão de Fila não habilitado neste eSUS PEC.'], 503);
            }

            $cols     = $this->resolveListaColumns($filaTable);
            $joins    = $this->buildFilaJoins($filaTable, $cols);
            $cnesCol  = $cols['cnesCol'];
            $equipeFk = $cols['equipeFk'];
            $profFk   = $cols['profFk'];
            $dtCol    = $cols['dtCol'];

            if ($filaTable === 'ta_agendado') {
                $equipes = [];
                if ($joins['eqJoin']) {
                    $eqPk   = $this->firstExistingColumn('ta_equipe', ['co_seq_equipe', 'co_equipe']) ?? 'co_seq_equipe';
                    $eqNome = $this->firstExistingColumn('ta_equipe', ['no_equipe', 'ds_nome_equipe']) ?? 'no_equipe';
                    try {
                        $equipes = $db->select("
                            SELECT DISTINCT e.{$eqPk} AS id, e.{$eqNome} AS nome
                            FROM {$filaTable} la
                            {$joins['eqJoin']}
                            WHERE la.{$cnesCol} = ? AND la.{$dtCol} = ?
                            ORDER BY e.{$eqNome}
                        ", [$cnes, $hoje]);
                    } catch (\Throwable) {}
                }

                $profissionais = [];
                if ($joins['profJoin']) {
                    $profIsLot = str_contains($joins['profJoin'], 'ta_lotacao');
                    $pPk   = $profIsLot
                        ? ($this->firstExistingColumn('ta_lotacao', ['co_seq_lotacao', 'co_lotacao']) ?? 'co_seq_lotacao')
                        : 'co_seq_profissional';
                    $pNome = $profIsLot
                        ? ($this->firstExistingColumn('ta_lotacao', ['no_profissional']) ?? 'no_profissional')
                        : 'no_profissional';
                    try {
                        $profissionais = $db->select("
                            SELECT DISTINCT p.{$pPk} AS id, p.{$pNome} AS nome
                            FROM {$filaTable} la
                            {$joins['profJoin']}
                            WHERE la.{$cnesCol} = ? AND la.{$dtCol} = ?
                            ORDER BY p.{$pNome}
                        ", [$cnes, $hoje]);
                    } catch (\Throwable) {}
                }
            } else {
                $equipes = $db->select("
                    SELECT DISTINCT e.co_seq_equipe AS id, e.no_equipe AS nome
                    FROM {$filaTable} la
                    JOIN tb_equipe e ON e.co_seq_equipe = la.{$equipeFk}
                    WHERE la.{$cnesCol} = ? AND la.{$dtCol} = ?
                    ORDER BY e.no_equipe
                ", [$cnes, $hoje]);

                $profissionais = $db->select("
                    SELECT DISTINCT p.co_seq_profissional AS id, p.no_profissional AS nome
                    FROM {$filaTable} la
                    JOIN tb_profissional p ON p.co_seq_profissional = la.{$profFk}
                    WHERE la.{$cnesCol} = ? AND la.{$dtCol} = ?
                    ORDER BY p.no_profissional
                ", [$cnes, $hoje]);
            }

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
