<?php

namespace Database\Seeders;

use App\Models\Cnae;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ReconciliarCnaesEstabvisaSeeder extends Seeder
{
    private const SQL_DUMP = 'C:/Users/dougl/workspace/estabvisa/dlsistemcombr_sysdoc.sql';
    private const XLSX_FILES = [
        'C:/Users/dougl/workspace/estabvisa/Alvarás que faltavam ou precisavam atualizar (1).xlsx',
        'C:/Users/dougl/workspace/estabvisa/Estabelecimentos que faltam ser adicionados (1).xlsx',
    ];

    public function run(): void
    {
        $mapa = [];
        $this->coletarDoDumpSql($mapa);
        $this->coletarDasPlanilhas($mapa);

        $estabelecimentos = DB::table('estabelecimentos')->select('id', 'nome_estabelecimento')->get();
        $agora = now();
        $vinculos = 0;

        foreach ($estabelecimentos as $est) {
            $chave = $this->chaveNome((string) $est->nome_estabelecimento);
            $codigos = array_values(array_unique($mapa[$chave] ?? []));
            if (count($codigos) === 0) {
                continue;
            }

            $ids = [];
            foreach ($codigos as $codigo) {
                $ids[] = Cnae::firstOrCreate(['codigo' => $codigo], ['descricao' => null])->id;
            }

            DB::table('estabelecimento_cnaes')->where('estabelecimento_id', $est->id)->delete();
            foreach ($ids as $cnaeId) {
                DB::table('estabelecimento_cnaes')->insert([
                    'estabelecimento_id' => $est->id,
                    'cnae_id' => $cnaeId,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
                $vinculos++;
            }
        }

        $this->command?->info("Reconciliacao concluida. Vinculos criados: {$vinculos}");
    }

    private function coletarDoDumpSql(array &$mapa): void
    {
        if (! file_exists(self::SQL_DUMP)) {
            throw new RuntimeException('Dump SQL nao encontrado em '.self::SQL_DUMP);
        }

        $handle = fopen(self::SQL_DUMP, 'rb');
        if (! $handle) {
            throw new RuntimeException('Nao foi possivel abrir dump SQL.');
        }

        try {
            $inInsert = false;
            while (($line = fgets($handle)) !== false) {
                if (stripos($line, 'INSERT INTO `estabelecimentos`') !== false) {
                    $inInsert = true;
                    continue;
                }

                if (! $inInsert) {
                    continue;
                }

                $trim = trim($line);
                if ($trim === '' || $trim === 'VALUES') {
                    continue;
                }
                if ($trim === ';') {
                    $inInsert = false;
                    continue;
                }
                if ($trim[0] !== '(') {
                    continue;
                }

                $tuple = rtrim($trim, ",;");
                $tuple = trim($tuple, '()');
                $cols = str_getcsv($tuple, ',', "'", '\\');
                if (! is_array($cols) || count($cols) < 9) {
                    continue;
                }

                // Layout dump: id,nome_responsavel,razao_social,nome_fantasia,cnpj,telefone,nome_estabelecimento,endereco,cnaes,...
                $nome = $this->normalizarTexto($cols[6] ?? '');
                $cnaesRaw = $this->normalizarTexto($cols[8] ?? '');
                $codigos = $this->extrairCnaes($cnaesRaw);
                if ($nome === '' || count($codigos) === 0) {
                    continue;
                }

                $chave = $this->chaveNome($nome);
                $mapa[$chave] = array_values(array_unique(array_merge($mapa[$chave] ?? [], $codigos)));
            }
        } finally {
            fclose($handle);
        }
    }

    private function coletarDasPlanilhas(array &$mapa): void
    {
        foreach (self::XLSX_FILES as $xlsx) {
            if (! file_exists($xlsx)) {
                continue;
            }

            foreach ($this->lerXlsx($xlsx) as $registro) {
                $nome = $this->normalizarTexto($registro['nome_estabelecimento'] ?? '');
                $codigos = $this->extrairCnaes((string) ($registro['cnaes'] ?? ''));
                if ($nome === '' || count($codigos) === 0) {
                    continue;
                }

                $chave = $this->chaveNome($nome);
                $mapa[$chave] = array_values(array_unique(array_merge($mapa[$chave] ?? [], $codigos)));
            }
        }
    }

    private function lerXlsx(string $caminho): array
    {
        $zip = new ZipArchive();
        if ($zip->open($caminho) !== true) {
            throw new RuntimeException('Nao foi possivel abrir '.$caminho);
        }

        try {
            $strings = $this->sharedStrings($zip);
            $sheetPath = $this->primeiraPlanilhaPath($zip);
            $sheet = simplexml_load_string($this->zipEntry($zip, $sheetPath));
            if (! $sheet instanceof SimpleXMLElement) {
                return [];
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

                $nome = $this->normalizarTexto($valores[4] ?? '');
                if ($nome === '') {
                    continue;
                }

                $registros[] = [
                    'nome_estabelecimento' => $nome,
                    'cnaes' => $this->normalizarTexto($valores[6] ?? ''),
                ];
            }

            return $registros;
        } finally {
            $zip->close();
        }
    }

    private function extrairCnaes(string $raw): array
    {
        preg_match_all('/\d{2}\.?\d{2}-?\d\/?-?\d{2}|\d{4}-\d\/\d{2}/', $raw, $matches);
        $out = [];
        foreach (($matches[0] ?? []) as $found) {
            $digits = preg_replace('/\D/', '', $found);
            if (strlen($digits) !== 7) {
                continue;
            }
            $out[] = substr($digits, 0, 4).'-'.substr($digits, 4, 1).'/'.substr($digits, 5, 2);
        }
        return array_values(array_unique($out));
    }

    private function chaveNome(string $nome): string
    {
        return preg_replace('/[^a-z0-9]+/', '', Str::ascii(Str::lower($this->normalizarTexto($nome)))) ?? '';
    }

    private function normalizarTexto(mixed $valor): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $valor));
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
}
