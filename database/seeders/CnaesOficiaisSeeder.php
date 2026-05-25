<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class CnaesOficiaisSeeder extends Seeder
{
    private const FONTE_URL = 'https://cnae.ibge.gov.br/images/concla/documentacao/CNAE_Subclasses_2_3_Estrutura_Detalhada.xlsx';

    public function run(): void
    {
        $arquivo = $this->baixarArquivo();
        $registros = $this->lerXlsx($arquivo);
        $agora = now();

        foreach ($registros as $registro) {
            DB::table('cnaes')->updateOrInsert(
                ['codigo' => $registro['codigo']],
                ['descricao' => $registro['descricao'], 'updated_at' => $agora, 'created_at' => $agora]
            );
        }
    }

    private function baixarArquivo(): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'cnae23_');
        if ($temp === false) {
            throw new RuntimeException('Nao foi possivel criar arquivo temporario para CNAE.');
        }

        $data = @file_get_contents(self::FONTE_URL);
        if ($data === false) {
            throw new RuntimeException('Nao foi possivel baixar a planilha oficial de CNAEs: '.self::FONTE_URL);
        }

        file_put_contents($temp, $data);
        return $temp;
    }

    private function lerXlsx(string $caminho): array
    {
        $zip = new ZipArchive();
        if ($zip->open($caminho) !== true) {
            throw new RuntimeException('Nao foi possivel abrir planilha CNAE.');
        }

        try {
            $strings = $this->sharedStrings($zip);
            $sheetPath = $this->primeiraPlanilhaPath($zip);
            $sheet = simplexml_load_string($this->zipEntry($zip, $sheetPath));
            if (! $sheet instanceof SimpleXMLElement) {
                throw new RuntimeException('Planilha CNAE invalida.');
            }

            $out = [];
            foreach ($sheet->sheetData->row as $row) {
                $valores = [];
                foreach ($row->c as $cell) {
                    $coluna = $this->colunaNumero((string) $cell['r']);
                    $valores[$coluna] = trim((string) $this->valorCelula($cell, $strings));
                }

                ksort($valores);
                if (count($valores) === 0) {
                    continue;
                }

                $codigo = '';
                $descricao = '';
                $colCodigo = null;

                foreach ($valores as $col => $valor) {
                    $normalizado = $this->normalizarCodigo($valor);
                    if ($normalizado !== null) {
                        $codigo = $normalizado;
                        $colCodigo = $col;
                        break;
                    }
                }

                if ($codigo === '' || $colCodigo === null) {
                    continue;
                }

                foreach ($valores as $col => $valor) {
                    if ($col > $colCodigo && $valor !== '' && $this->normalizarCodigo($valor) === null) {
                        $descricao = $valor;
                        break;
                    }
                }

                if ($descricao === '') {
                    continue;
                }

                $out[] = ['codigo' => $codigo, 'descricao' => $descricao];
            }

            return $out;
        } finally {
            $zip->close();
            @unlink($caminho);
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

        return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
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
            return isset($cell->is->t) ? (string) $cell->is->t : null;
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

    private function normalizarCodigo(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d\/\d{2}$/', $valor)) {
            return $valor;
        }

        $digits = preg_replace('/\D/', '', $valor);
        if (strlen($digits) !== 7) {
            return null;
        }

        return substr($digits, 0, 4).'-'.substr($digits, 4, 1).'/'.substr($digits, 5, 2);
    }
}
