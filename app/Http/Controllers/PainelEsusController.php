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
        if ($this->hasTable('tb_atend'))              return 'tb_atend';
        if ($this->hasTable('tb_lista_atendimento'))  return 'tb_lista_atendimento';
        return null;
    }

    /**
     * Resolve colunas da tabela de fila dinamicamente.
     * Funciona tanto para tb_lista_atendimento quanto para ta_agendado.
     */
    private function resolveListaColumns(string $table): array
    {
        if ($table === 'tb_atend') {
            return [
                'cnesCol'     => 'nu_cnes',
                'cidadaoFk'   => 'co_prontuario',
                'profFk'      => 'co_atend_prof',
                'equipeFk'    => 'co_equipe',
                'hrInicioCol' => 'dt_inicio',
                'hrChegadaCol'=> 'dt_inicio',
                'hrSaidaCol'  => 'dt_fim',
                'dtCol'       => 'dt_inicio',
                'statusCol'   => 'st_atend',
                'pkCol'       => 'co_seq_atend',
            ];
        }

        if ($table === 'ta_agendado') {
            return [
                'cnesCol'     => 'nu_cnes',
                'cidadaoFk'   => 'co_prontuario',
                'profFk'      => 'co_lotacao_agendada',
                'equipeFk'    => 'co_lotacao_agendada',
                'hrInicioCol' => 'hr_inicial_agendado',
                'hrChegadaCol'=> 'hr_inicial_agendado',
                'hrSaidaCol'  => null,
                'dtCol'       => 'dt_agendado',
                'statusCol'   => 'st_agendado',
                'pkCol'       => 'co_seq_taagendado',
            ];
        }

        return [
            'cnesCol'     => $this->firstExistingColumn($table, ['nu_cnes', 'co_unico_saude', 'co_cnes']) ?? 'nu_cnes',
            'cidadaoFk'   => $this->firstExistingColumn($table, ['co_seq_cidadao', 'co_cidadao']) ?? 'co_seq_cidadao',
            'profFk'      => $this->firstExistingColumn($table, ['co_seq_profissional', 'co_profissional', 'co_lotacao']) ?? 'co_seq_profissional',
            'equipeFk'    => $this->firstExistingColumn($table, ['co_seq_equipe', 'co_equipe']) ?? 'co_seq_equipe',
            'hrInicioCol' => $this->firstExistingColumn($table, ['hr_inicio_atendimento', 'hr_atendimento', 'hr_agendado', 'hr_chegada']) ?? 'hr_inicio_atendimento',
            'hrChegadaCol'=> $this->firstExistingColumn($table, ['hr_chegada', 'hr_agendado', 'hr_inicio_atendimento']) ?? 'hr_chegada',
            'hrSaidaCol'  => $this->firstExistingColumn($table, ['hr_fim_atendimento', 'dt_fim', 'hr_saida']),
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

        if ($table === 'tb_atend') {
            $cidJoin = "
                LEFT JOIN tb_prontuario pr ON pr.co_seq_prontuario = la.{$cidFk}
                LEFT JOIN tb_cidadao c ON c.co_seq_cidadao = pr.co_cidadao
            ";
            $cidExpr = "COALESCE(c.no_cidadao, 'Cidadao')::text";

            $profJoin = "
                LEFT JOIN tb_atend_prof ap ON ap.co_seq_atend_prof = la.{$profFk}
                LEFT JOIN (
                    SELECT DISTINCT ON (co_ator_papel)
                        co_ator_papel, co_unidade_saude, co_prof, co_equipe
                    FROM tb_lotacao
                    ORDER BY co_ator_papel, dt_desativacao_lotacao NULLS FIRST
                ) l ON l.co_ator_papel = ap.co_lotacao
                LEFT JOIN tb_unidade_saude us ON us.co_seq_unidade_saude = la.co_unidade_saude
                LEFT JOIN (
                    SELECT DISTINCT ON (co_seq_prof)
                        co_seq_prof, no_profissional
                    FROM ta_prof
                    ORDER BY co_seq_prof, dt_auditoria DESC NULLS LAST, co_seq_taprof DESC
                ) p ON p.co_seq_prof = l.co_prof
            ";
            $profExpr = "COALESCE(p.no_profissional, '')::text";

            $eqJoin = "LEFT JOIN tb_equipe e ON e.co_seq_equipe = COALESCE(la.{$equipeFk}, l.co_equipe)";
            $eqExpr = "COALESCE(e.no_equipe, '')::text";

            return compact('cidJoin', 'cidExpr', 'profJoin', 'profExpr', 'eqJoin', 'eqExpr');
        }

        if ($table === 'ta_agendado') {
            $cidJoin = "
                LEFT JOIN tb_prontuario pr ON pr.co_seq_prontuario = la.{$cidFk}
                LEFT JOIN tb_cidadao c ON c.co_seq_cidadao = pr.co_cidadao
            ";
            $cidExpr = "COALESCE(c.no_cidadao, 'Cidadão')::text";

            $profJoin = "
                LEFT JOIN (
                    SELECT DISTINCT ON (co_ator_papel)
                        co_ator_papel, co_unidade_saude, co_prof, co_equipe
                    FROM tb_lotacao
                    ORDER BY co_ator_papel, dt_desativacao_lotacao NULLS FIRST
                ) l ON l.co_ator_papel = la.{$profFk}
                LEFT JOIN tb_unidade_saude us ON us.co_seq_unidade_saude = l.co_unidade_saude
                LEFT JOIN (
                    SELECT DISTINCT ON (co_seq_prof)
                        co_seq_prof, no_profissional
                    FROM ta_prof
                    ORDER BY co_seq_prof, dt_auditoria DESC NULLS LAST, co_seq_taprof DESC
                ) p ON p.co_seq_prof = l.co_prof
            ";
            $profExpr = "COALESCE(p.no_profissional, '')::text";

            $eqJoin = "LEFT JOIN tb_equipe e ON e.co_seq_equipe = l.co_equipe";
            $eqExpr = "COALESCE(e.no_equipe, '')::text";

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
            $cnesWhere = in_array($filaTable, ['ta_agendado', 'tb_atend'], true)
                ? 'us.nu_cnes = ?'
                : "la.{$cols['cnesCol']} = ?";

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
                WHERE {$cnesWhere}
                  AND la.{$cols['dtCol']}::date = ?
            ";

            // Status 4 = Em Atendimento, status 2 = Atendido (último fallback)
            $emAtendimentoStatus = $filaTable === 'tb_atend' ? 3 : 4;
            $atendidoStatus      = $filaTable === 'tb_atend' ? 4 : 2;
            $aguardandoStatus    = $filaTable === 'ta_agendado' ? 0 : 1;

            $emAtendimento = $db->selectOne(
                $baseSelect . " AND la.{$cols['statusCol']} = {$emAtendimentoStatus} ORDER BY la.{$cols['hrInicioCol']} DESC NULLS LAST LIMIT 1",
                [$cnes, $hoje]
            );

            if (!$emAtendimento) {
                $emAtendimento = $db->selectOne(
                    $baseSelect . " AND la.{$cols['statusCol']} = {$atendidoStatus} ORDER BY la.{$cols['hrInicioCol']} DESC NULLS LAST LIMIT 1",
                    [$cnes, $hoje]
                );
            }

            $ultimosAtendidos = $db->select(
                $baseSelect . " AND la.{$cols['statusCol']} = {$atendidoStatus} ORDER BY la.{$cols['hrInicioCol']} DESC NULLS LAST LIMIT 5",
                [$cnes, $hoje]
            );

            $aguardando = $db->select(
                $baseSelect . " AND la.{$cols['statusCol']} = {$aguardandoStatus} ORDER BY la.{$cols['hrChegadaCol']} ASC NULLS LAST LIMIT 8",
                [$cnes, $hoje]
            );

            return response()->json([
                'unidade'           => $unidadeRow?->nome ?? 'CNES ' . $cnes,
                'em_atendimento'    => $emAtendimento,
                'ultimos_atendidos' => $ultimosAtendidos,
                'aguardando'        => $aguardando,
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
            'data'         => 'nullable|date_format:Y-m-d',
            'situacao'     => 'nullable|in:aguardando,atendidos,nao_aguardaram',
        ]);

        $cnes     = trim($request->input('cnes'));
        $equipeId = $request->input('equipe');
        $profId   = $request->input('profissional');
        $hoje     = $request->input('data') ?: now()->toDateString();
        $situacao = $request->input('situacao', 'aguardando');

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
            $cnesWhere = in_array($filaTable, ['ta_agendado', 'tb_atend'], true)
                ? 'us.nu_cnes = ?'
                : "la.{$cols['cnesCol']} = ?";
            $aguardandoStatus = $filaTable === 'ta_agendado' ? 0 : 1;
            $counterJoins = in_array($filaTable, ['ta_agendado', 'tb_atend'], true) ? $joins['profJoin'] : '';

            $where  = "{$cnesWhere} AND la.{$cols['dtCol']}::date = ?";
            $params = [$cnes, $hoje];

            if ($equipeId !== null) {
                if ($filaTable === 'ta_agendado') {
                    $where .= " AND l.co_equipe = ?";
                } elseif ($filaTable === 'tb_atend') {
                    $where .= " AND COALESCE(la.{$cols['equipeFk']}, l.co_equipe) = ?";
                } else {
                    $where .= " AND la.{$cols['equipeFk']} = ?";
                }
                $params[] = (int) $equipeId;
            }
            if ($profId !== null) {
                $where   .= in_array($filaTable, ['ta_agendado', 'tb_atend'], true)
                    ? " AND l.co_prof = ?"
                    : " AND la.{$cols['profFk']} = ?";
                $params[] = (int) $profId;
            }

            $atendidosCond = $filaTable === 'tb_atend'
                ? "la.{$cols['statusCol']} = 4"
                : "la.{$cols['statusCol']} IN (2, 4)";
            $naoAguardaramStatus = $filaTable === 'tb_atend' ? 5 : 3;
            $listaStatusCond = match ($situacao) {
                'atendidos' => $atendidosCond,
                'nao_aguardaram' => "la.{$cols['statusCol']} = {$naoAguardaramStatus}",
                default => "la.{$cols['statusCol']} = {$aguardandoStatus}",
            };
            $listaOrder = $situacao === 'aguardando' ? 'ASC' : 'DESC';
            $saidaBaseExpr = $filaTable === 'tb_atend'
                ? "COALESCE(ap.dt_fim, la.dt_fim, CASE WHEN la.{$cols['statusCol']} IN (4, 5) THEN la.dt_ultima_alteracao_status END)"
                : ($cols['hrSaidaCol'] ? "la.{$cols['hrSaidaCol']}" : "NULL");
            $tempoFimExpr = "COALESCE({$saidaBaseExpr}, NOW())";
            $contadores = $db->selectOne("
                SELECT
                    COUNT(*) FILTER (WHERE la.{$cols['statusCol']} = {$aguardandoStatus}) AS aguardando,
                    COUNT(*) FILTER (WHERE {$atendidosCond}) AS atendidos,
                    COUNT(*) FILTER (WHERE la.{$cols['statusCol']} = {$naoAguardaramStatus}) AS nao_aguardaram,
                    CONCAT(
                        FLOOR(COALESCE(AVG(EXTRACT(EPOCH FROM ({$tempoFimExpr} - la.{$cols['hrChegadaCol']}))) FILTER (WHERE {$listaStatusCond}), 0) / 3600)::int,
                        'h ',
                        LPAD((FLOOR(COALESCE(AVG(EXTRACT(EPOCH FROM ({$tempoFimExpr} - la.{$cols['hrChegadaCol']}))) FILTER (WHERE {$listaStatusCond}), 0) / 60)::int % 60)::text, 2, '0'),
                        'min'
                    ) AS tempo_medio_espera
                FROM {$filaTable} la
                {$counterJoins}
                WHERE {$where}
            ", $params);

            $allJoins = implode("\n", array_filter([
                $joins['cidJoin'], $joins['profJoin'], $joins['eqJoin'],
            ]));
            $saidaExpr = "TO_CHAR({$saidaBaseExpr}, 'HH24:MI')";

            $aguardando = $db->select("
                SELECT
                    la.{$cols['pkCol']}                              AS id,
                    {$joins['cidExpr']}                              AS cidadao,
                    TO_CHAR(la.{$cols['dtCol']}, 'DD/MM/YYYY')       AS data_atendimento,
                    TO_CHAR(la.{$cols['hrChegadaCol']}, 'HH24:MI')  AS hr_chegada,
                    {$saidaExpr}                                     AS hr_saida,
                    CONCAT(
                        FLOOR(EXTRACT(EPOCH FROM ({$tempoFimExpr} - la.{$cols['hrChegadaCol']})) / 3600)::int,
                        'h ',
                        LPAD((FLOOR(EXTRACT(EPOCH FROM ({$tempoFimExpr} - la.{$cols['hrChegadaCol']})) / 60)::int % 60)::text, 2, '0'),
                        'min'
                    )                                                AS tempo_espera,
                    {$joins['eqExpr']}                               AS equipe,
                    {$joins['profExpr']}                             AS profissional
                FROM {$filaTable} la
                {$allJoins}
                WHERE {$where}
                  AND {$listaStatusCond}
                ORDER BY la.{$cols['hrChegadaCol']} {$listaOrder} NULLS LAST
            ", $params);

            return response()->json([
                'contadores' => [
                    'aguardando'     => (int) ($contadores?->aguardando ?? 0),
                    'atendidos'      => (int) ($contadores?->atendidos ?? 0),
                    'nao_aguardaram' => (int) ($contadores?->nao_aguardaram ?? 0),
                    'tempo_medio_espera' => $contadores?->tempo_medio_espera ?? '0h 00min',
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
        $request->validate([
            'cnes' => 'required|string|max:20',
            'data' => 'nullable|date_format:Y-m-d',
        ]);
        $cnes = trim($request->input('cnes'));
        $hoje = $request->input('data') ?: now()->toDateString();

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
            $cnesWhere = in_array($filaTable, ['ta_agendado', 'tb_atend'], true)
                ? 'us.nu_cnes = ?'
                : "la.{$cnesCol} = ?";

            if (in_array($filaTable, ['ta_agendado', 'tb_atend'], true)) {
                $equipes = [];
                if ($joins['eqJoin']) {
                    try {
                        $equipes = $db->select("
                            SELECT DISTINCT e.co_seq_equipe AS id, e.no_equipe AS nome
                            FROM {$filaTable} la
                            {$joins['profJoin']}
                            {$joins['eqJoin']}
                            WHERE us.nu_cnes = ? AND la.{$dtCol}::date = ?
                              AND e.co_seq_equipe IS NOT NULL
                            ORDER BY e.no_equipe
                        ", [$cnes, $hoje]);
                    } catch (\Throwable) {}
                }

                $profissionais = [];
                if ($joins['profJoin']) {
                    try {
                        $profissionais = $db->select("
                            SELECT DISTINCT p.co_seq_prof AS id, p.no_profissional AS nome
                            FROM {$filaTable} la
                            {$joins['profJoin']}
                            WHERE us.nu_cnes = ? AND la.{$dtCol}::date = ?
                              AND p.co_seq_prof IS NOT NULL
                            ORDER BY p.no_profissional
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
