<?php

namespace Database\Seeders;

use App\Models\Cnae;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class EstabVisaComplementoProducaoSeeder extends Seeder
{
    private const FONTES = [
        [
            'arquivo' => 'alvaras_faltavam_ou_atualizar.xlsx',
            'proposito' => 'Alvaras que faltavam ou precisavam atualizar',
        ],
        [
            'arquivo' => 'estabelecimentos_faltam_adicionar.xlsx',
            'proposito' => 'Estabelecimentos que faltam ser adicionados',
        ],
    ];

    public function run(): void
    {
        $agora = now();
        $estabelecimentosPorNome = $this->indexarEstabelecimentos();

        DB::transaction(function () use (&$estabelecimentosPorNome, $agora) {
            foreach ($this->carregarRegistros() as $registro) {
                $alvaraExistente = DB::table('alvaras')
                    ->where('numero_alvara', $registro['numero_alvara'])
                    ->first();

                $estabelecimentoId = $alvaraExistente?->estabelecimento_id
                    ?: $this->localizarEstabelecimento($registro, $estabelecimentosPorNome);

                $dadosEstabelecimento = [
                    'nome_responsavel' => $registro['nome_responsavel'],
                    'nome_estabelecimento' => $registro['nome_estabelecimento'],
                    'endereco' => $registro['endereco'],
                    'obs' => $this->obsFonte($registro),
                    'updated_at' => $agora,
                    'deleted_at' => null,
                ];

                if ($estabelecimentoId) {
                    DB::table('estabelecimentos')
                        ->where('id', $estabelecimentoId)
                        ->update($dadosEstabelecimento);
                } else {
                    $estabelecimentoId = DB::table('estabelecimentos')->insertGetId($dadosEstabelecimento + [
                        'created_at' => $agora,
                    ]);
                    $estabelecimentosPorNome[$this->chaveNome($registro['nome_estabelecimento'])] = $estabelecimentoId;
                }

                $this->sincronizarCnaes($estabelecimentoId, $registro['cnaes'], $agora);

                $dadosAlvara = [
                    'estabelecimento_id' => $estabelecimentoId,
                    'nivel_risco' => $registro['nivel_risco'] ?: '2',
                    'data_alvara' => $registro['data_alvara'],
                    'vencimento_alvara' => $registro['vencimento_alvara'],
                    'contato' => null,
                    'status' => $this->statusAlvara($registro['nivel_risco'], $registro['vencimento_alvara']),
                    'updated_at' => $agora,
                    'deleted_at' => null,
                ];

                if ($alvaraExistente) {
                    DB::table('alvaras')
                        ->where('id', $alvaraExistente->id)
                        ->update($dadosAlvara);
                } else {
                    DB::table('alvaras')->insert($dadosAlvara + [
                        'numero_alvara' => $registro['numero_alvara'],
                        'created_at' => $agora,
                    ]);
                }
            }
        });
    }

    private function carregarRegistros(): array
    {
        $registros = [];

        foreach (self::FONTES as $fonte) {
            $caminho = database_path('seeders/data/estabvisa/'.$fonte['arquivo']);

            foreach ($this->lerXlsx($caminho) as $registro) {
                $registro['arquivo_origem'] = $fonte['arquivo'];
                $registro['proposito_origem'] = $fonte['proposito'];
                $registros[] = $registro;
            }
        }

        return $registros;
    }

    private function indexarEstabelecimentos(): array
    {
        $index = [];

        DB::table('estabelecimentos')
            ->select('id', 'nome_estabelecimento')
            ->orderBy('id')
            ->get()
            ->each(function ($estabelecimento) use (&$index) {
                $index[$this->chaveNome($estabelecimento->nome_estabelecimento)] ??= $estabelecimento->id;
            });

        return $index;
    }

    private function localizarEstabelecimento(array $registro, array $estabelecimentosPorNome): ?int
    {
        return $estabelecimentosPorNome[$this->chaveNome($registro['nome_estabelecimento'])] ?? null;
    }

    private function obsFonte(array $registro): string
    {
        return sprintf(
            'Importado pelo seed %s a partir de "%s" (linha %d): %s.',
            self::class,
            $registro['arquivo_origem'],
            $registro['linha_origem'],
            $registro['proposito_origem']
        );
    }

    private function statusAlvara(?string $nivelRisco, ?string $vencimento): string
    {
        if ($nivelRisco === '1' && $vencimento === null) {
            return 'Dispensado';
        }

        if ($vencimento !== null && Carbon::parse($vencimento)->lt(now()->startOfDay())) {
            return 'Vencido';
        }

        return 'Vigente';
    }

    private function chaveNome(string $nome): string
    {
        return preg_replace('/[^a-z0-9]+/', '', Str::ascii(Str::lower($this->normalizarTexto($nome)))) ?? '';
    }

    private function normalizarTexto(mixed $valor): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $valor));
    }

    private function excelDate(mixed $valor): ?string
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }

        return gmdate('Y-m-d', (((int) floor((float) $valor)) - 25569) * 86400);
    }

    private function lerXlsx(string $caminho): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Extensao PHP zip indisponivel para ler '.$caminho);
        }

        $zip = new ZipArchive();
        if ($zip->open($caminho) !== true) {
            throw new RuntimeException('Nao foi possivel abrir '.$caminho);
        }

        try {
            $strings = $this->sharedStrings($zip);
            $sheetPath = $this->primeiraPlanilhaPath($zip);
            $sheet = simplexml_load_string($this->zipEntry($zip, $sheetPath));
            if (! $sheet instanceof SimpleXMLElement) {
                throw new RuntimeException('Planilha invalida em '.$caminho);
            }

            $registros = [];
            foreach ($sheet->sheetData->row as $row) {
                $linha = (int) $row['r'];
                if ($linha === 1) {
                    continue;
                }

                $valores = [];
                foreach ($row->c as $cell) {
                    $valores[$this->colunaNumero((string) $cell['r'])] = $this->valorCelula($cell, $strings);
                }

                $numero = $this->normalizarTexto($valores[1] ?? '');
                $nome = $this->normalizarTexto($valores[4] ?? '');

                if ($numero === '' && $nome === '') {
                    continue;
                }

                $registros[] = [
                    'linha_origem' => $linha,
                    'numero_alvara' => $numero,
                    'nivel_risco' => preg_replace('/\.0$/', '', $this->normalizarTexto($valores[2] ?? '')) ?: null,
                    'nome_responsavel' => $this->normalizarTexto($valores[3] ?? ''),
                    'nome_estabelecimento' => $nome,
                    'endereco' => $this->normalizarTexto($valores[5] ?? ''),
                    'cnaes' => $this->normalizarTexto($valores[6] ?? ''),
                    'data_alvara' => $this->excelDate($valores[7] ?? null),
                    'vencimento_alvara' => $this->excelDate($valores[8] ?? null),
                ];
            }

            return $registros;
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $this->zipEntry($zip, 'xl/sharedStrings.xml', false);
        if ($xml === null) {
            return [];
        }

        $strings = [];
        $sst = simplexml_load_string($xml);
        foreach ($sst->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }

            $parts = [];
            foreach ($si->r as $run) {
                $parts[] = (string) $run->t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function primeiraPlanilhaPath(ZipArchive $zip): string
    {
        $workbook = simplexml_load_string($this->zipEntry($zip, 'xl/workbook.xml'));
        $rels = simplexml_load_string($this->zipEntry($zip, 'xl/_rels/workbook.xml.rels'));

        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $attrs = $rel->attributes();
            $relMap[(string) $attrs['Id']] = (string) $attrs['Target'];
        }

        $sheet = $workbook->sheets->sheet[0] ?? null;
        if (! $sheet) {
            throw new RuntimeException('Arquivo XLSX sem planilhas.');
        }

        $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $target = $relMap[(string) $attrs['id']] ?? null;
        if (! $target) {
            throw new RuntimeException('Relacionamento da primeira planilha nao encontrado.');
        }

        $path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');

        return str_replace('xl/worksheets/../', 'xl/', $path);
    }

    private function zipEntry(ZipArchive $zip, string $entry, bool $required = true): ?string
    {
        $stream = $zip->getStream($entry);
        if (! $stream) {
            if ($required) {
                throw new RuntimeException('Entrada '.$entry.' nao encontrada no XLSX.');
            }

            return null;
        }

        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    private function valorCelula(SimpleXMLElement $cell, array $strings): ?string
    {
        $attrs = $cell->attributes();
        $type = isset($attrs['t']) ? (string) $attrs['t'] : '';

        if ($type === 's') {
            return $strings[(int) $cell->v] ?? null;
        }

        if ($type === 'inlineStr') {
            if (isset($cell->is->t)) {
                return (string) $cell->is->t;
            }

            $parts = [];
            foreach ($cell->is->r as $run) {
                $parts[] = (string) $run->t;
            }

            return implode('', $parts);
        }

        return isset($cell->v) ? (string) $cell->v : null;
    }

    private function colunaNumero(string $referencia): int
    {
        preg_match('/^[A-Z]+/', $referencia, $matches);
        $numero = 0;

        foreach (str_split($matches[0] ?? '') as $letra) {
            $numero = ($numero * 26) + (ord($letra) - 64);
        }

        return $numero;
    }

    private function sincronizarCnaes(int $estabelecimentoId, string $raw, $timestamp): void
    {
        preg_match_all('/\d{2}\.?\d{2}-?\d\/?-?\d{2}|\d{4}-\d\/\d{2}/', $raw, $matches);
        $codigos = [];

        foreach (($matches[0] ?? []) as $found) {
            $digits = preg_replace('/\D/', '', $found);
            if (strlen($digits) !== 7) {
                continue;
            }
            $codigos[] = substr($digits, 0, 4).'-'.substr($digits, 4, 1).'/'.substr($digits, 5, 2);
        }

        $codigos = array_values(array_unique($codigos));
        $ids = [];
        foreach ($codigos as $codigo) {
            $ids[] = Cnae::firstOrCreate(['codigo' => $codigo], ['descricao' => null])->id;
        }

        DB::table('estabelecimento_cnaes')->where('estabelecimento_id', $estabelecimentoId)->delete();
        foreach ($ids as $cnaeId) {
            DB::table('estabelecimento_cnaes')->insert([
                'estabelecimento_id' => $estabelecimentoId,
                'cnae_id' => $cnaeId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }
}
