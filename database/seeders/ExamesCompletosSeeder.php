<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed baseado em: SBPC/ML Manual de Exames, ANVISA RDC 302/2005,
 * Consenso Brasileiro de Dislipidemia 2017, valores de referência
 * Fleury/Hermes/Albert Einstein e literatura de bioquímica clínica.
 *
 * Idempotente: usa insertOrIgnore / firstOrCreate.
 */
class ExamesCompletosSeeder extends Seeder
{
    private array $categoriaIds = [];

    public function run(): void
    {
        $this->resolverCategorias();
        $this->seedHematologia();
        $this->seedBioquimica();
        $this->seedLipidograma();
        $this->seedFuncaoHepatica();
        $this->seedFuncaoRenal();
        $this->seedCoagulacao();
        $this->seedUrianalise();
        $this->seedFuncaoTireoidiana();
        $this->seedHormonios();
        $this->seedFerroVitaminas();
        $this->seedMarcadoresCardiacos();
        $this->seedMarcadoresTumorais();
        $this->seedSorologias();
        $this->seedInfeccoesIST();
        $this->seedInflamatorios();
        $this->seedDiabetes();
        $this->seedMicrobiologia();
        $this->seedParasitologia();
        $this->seedGasometria();
        $this->seedImunologia();
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function resolverCategorias(): void
    {
        $nomes = [
            'HEMATOLOGIA','BIOQUÍMICA','LIPIDOGRAMA','FUNÇÃO HEPÁTICA',
            'FUNÇÃO RENAL','COAGULAÇÃO / HEMOSTASIA','URIANÁLISE',
            'FUNÇÃO TIREOIDIANA','HORMÔNIOS','MARCADORES CARDÍACOS',
            'MARCADORES TUMORAIS','SOROLOGIAS','IST / DST',
            'DOENÇAS INFECCIOSAS','DOENÇAS AUTOIMUNES','DIABETES',
            'MICROBIOLOGIA / BACTERIOLOGIA','PARASITOLOGIA','GASOMETRIA',
            'IMUNOLOGIA','VITAMINAS E MICRONUTRIENTES','MINERAIS E ELETRÓLITOS',
        ];
        foreach ($nomes as $nome) {
            DB::table('categoria_exames')->insertOrIgnore([
                'nome' => $nome, 'ativo' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->categoriaIds[$nome] = DB::table('categoria_exames')->where('nome', $nome)->value('id');
        }
    }

    private function cat(string $nome): ?int
    {
        return $this->categoriaIds[$nome] ?? null;
    }

    private function exame(string $nome, string $codigo, string $cat, string $desc = ''): int
    {
        $catId = $this->cat($cat);
        DB::table('exames')->insertOrIgnore([
            'nome' => $nome, 'codigo' => $codigo,
            'categoria_exame_id' => $catId, 'descricao' => $desc ?: null,
            'ativo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return DB::table('exames')->where('codigo', $codigo)->value('id');
    }

    private function campo(int $exId, string $nome, string $tipo, ?string $unidade, int $ordem, bool $obrigatorio = true): int
    {
        DB::table('exame_campos')->insertOrIgnore([
            'exame_id' => $exId, 'nome' => $nome, 'tipo_valor' => $tipo,
            'unidade' => $unidade, 'ordem' => $ordem,
            'obrigatorio' => $obrigatorio, 'ativo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return DB::table('exame_campos')
            ->where('exame_id', $exId)->where('nome', $nome)->value('id');
    }

    private function ref(int $campoId, string $perfil, ?float $min, ?float $max, ?string $texto = null, ?string $desc = null): void
    {
        DB::table('campo_referencias')->insertOrIgnore([
            'exame_campo_id' => $campoId, 'perfil' => $perfil,
            'valor_min' => $min, 'valor_max' => $max,
            'valor_texto' => $texto, 'descricao' => $desc,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ─── HEMATOLOGIA ──────────────────────────────────────────────────────────

    private function seedHematologia(): void
    {
        $exId = $this->exame('HEMOGRAMA COMPLETO', '40301230', 'HEMATOLOGIA',
            'Eritrograma, leucograma e plaquetas com diferencial');

        $c = $this->campo($exId, 'Eritrócitos', 'numerico', 'milhões/mm³', 1);
        $this->ref($c, 'adulto_m', 4.5, 6.0); $this->ref($c, 'adulto_f', 4.0, 5.5);
        $this->ref($c, 'crianca', 4.0, 5.3); $this->ref($c, 'recem_nascido', 4.1, 6.7);

        $c = $this->campo($exId, 'Hemoglobina', 'numerico', 'g/dL', 2);
        $this->ref($c, 'adulto_m', 13.0, 17.0); $this->ref($c, 'adulto_f', 12.0, 16.0);
        $this->ref($c, 'crianca', 11.5, 14.5); $this->ref($c, 'recem_nascido', 15.0, 24.0);
        $this->ref($c, 'gestante', 11.0, 14.0);

        $c = $this->campo($exId, 'Hematócrito', 'numerico', '%', 3);
        $this->ref($c, 'adulto_m', 40.0, 54.0); $this->ref($c, 'adulto_f', 35.0, 45.0);
        $this->ref($c, 'crianca', 33.0, 43.0); $this->ref($c, 'recem_nascido', 44.0, 70.0);

        $c = $this->campo($exId, 'VCM', 'numerico', 'fL', 4);
        $this->ref($c, 'geral', 80.0, 100.0);

        $c = $this->campo($exId, 'HCM', 'numerico', 'pg', 5);
        $this->ref($c, 'geral', 26.0, 34.0);

        $c = $this->campo($exId, 'CHCM', 'numerico', 'g/dL', 6);
        $this->ref($c, 'geral', 31.0, 37.0);

        $c = $this->campo($exId, 'RDW', 'numerico', '%', 7);
        $this->ref($c, 'geral', 11.0, 15.0);

        $c = $this->campo($exId, 'Leucócitos', 'numerico', '/mm³', 8);
        $this->ref($c, 'adulto_m', 4000.0, 11000.0); $this->ref($c, 'adulto_f', 4000.0, 11000.0);
        $this->ref($c, 'crianca', 5000.0, 14500.0); $this->ref($c, 'recem_nascido', 10000.0, 26000.0);

        $c = $this->campo($exId, 'Neutrófilos', 'numerico', '%', 9);
        $this->ref($c, 'geral', 45.0, 75.0);

        $c = $this->campo($exId, 'Linfócitos', 'numerico', '%', 10);
        $this->ref($c, 'adulto_m', 20.0, 45.0); $this->ref($c, 'adulto_f', 20.0, 45.0);
        $this->ref($c, 'crianca', 25.0, 60.0);

        $c = $this->campo($exId, 'Monócitos', 'numerico', '%', 11);
        $this->ref($c, 'geral', 2.0, 10.0);

        $c = $this->campo($exId, 'Eosinófilos', 'numerico', '%', 12);
        $this->ref($c, 'geral', 1.0, 6.0);

        $c = $this->campo($exId, 'Basófilos', 'numerico', '%', 13);
        $this->ref($c, 'geral', 0.0, 2.0);

        $c = $this->campo($exId, 'Plaquetas', 'numerico', '/mm³', 14);
        $this->ref($c, 'geral', 150000.0, 450000.0);

        $c = $this->campo($exId, 'VPM', 'numerico', 'fL', 15);
        $this->ref($c, 'geral', 7.5, 12.5);

        // Reticulócitos
        $exId = $this->exame('RETICULÓCITOS', '40301370', 'HEMATOLOGIA');
        $c = $this->campo($exId, 'Reticulócitos', 'numerico', '%', 1);
        $this->ref($c, 'geral', 0.5, 2.5);
        $c = $this->campo($exId, 'Reticulócitos (absoluto)', 'numerico', '/mm³', 2);
        $this->ref($c, 'geral', 25000.0, 100000.0);

        // Velocidade de Hemossedimentação
        $exId = $this->exame('VHS — VELOCIDADE DE HEMOSSEDIMENTAÇÃO', '40301400', 'HEMATOLOGIA');
        $c = $this->campo($exId, 'VHS (1ª hora)', 'numerico', 'mm/h', 1);
        $this->ref($c, 'adulto_m', 0.0, 15.0); $this->ref($c, 'adulto_f', 0.0, 20.0);
        $this->ref($c, 'idoso_m', 0.0, 20.0); $this->ref($c, 'idoso_f', 0.0, 30.0);
    }

    // ─── BIOQUÍMICA ───────────────────────────────────────────────────────────

    private function seedBioquimica(): void
    {
        // Glicemia
        $exId = $this->exame('GLICEMIA EM JEJUM', '40302040', 'BIOQUÍMICA');
        $c = $this->campo($exId, 'Glicose', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', 70.0, 99.0, null, 'Normal em jejum de 8h');

        // HbA1c
        $exId = $this->exame('HEMOGLOBINA GLICADA (HbA1c)', '40302063', 'DIABETES');
        $c = $this->campo($exId, 'HbA1c', 'numerico', '%', 1);
        $this->ref($c, 'geral', null, 5.7, null, '< 5.7%: Normal; 5.7–6.4%: Pré-diabetes; ≥ 6.5%: Diabetes');

        // Ureia
        $exId = $this->exame('UREIA', '40302580', 'FUNÇÃO RENAL');
        $c = $this->campo($exId, 'Ureia', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'adulto_m', 10.0, 50.0); $this->ref($c, 'adulto_f', 10.0, 50.0);
        $this->ref($c, 'crianca', 10.0, 40.0); $this->ref($c, 'idoso', 10.0, 55.0);

        // Creatinina
        $exId = $this->exame('CREATININA', '40301630', 'FUNÇÃO RENAL');
        $c = $this->campo($exId, 'Creatinina', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'adulto_m', 0.6, 1.2); $this->ref($c, 'adulto_f', 0.5, 1.1);
        $this->ref($c, 'crianca', 0.3, 0.7); $this->ref($c, 'idoso', 0.6, 1.3);

        // Ácido Úrico
        $exId = $this->exame('ÁCIDO ÚRICO', '40300038', 'BIOQUÍMICA');
        $c = $this->campo($exId, 'Ácido Úrico', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'adulto_m', 2.4, 6.0); $this->ref($c, 'adulto_f', 1.4, 5.8);
        $this->ref($c, 'crianca', 2.0, 5.5); $this->ref($c, 'idoso', 2.4, 7.0);

        // Albumina
        $exId = $this->exame('ALBUMINA', '40300100', 'BIOQUÍMICA');
        $c = $this->campo($exId, 'Albumina', 'numerico', 'g/dL', 1);
        $this->ref($c, 'geral', 3.5, 5.0);

        // Proteínas Totais
        $exId = $this->exame('PROTEÍNAS TOTAIS E FRAÇÕES', '40301352', 'BIOQUÍMICA');
        $c = $this->campo($exId, 'Proteínas Totais', 'numerico', 'g/dL', 1);
        $this->ref($c, 'geral', 6.0, 8.0);
        $c = $this->campo($exId, 'Albumina', 'numerico', 'g/dL', 2);
        $this->ref($c, 'geral', 3.5, 5.0);
        $c = $this->campo($exId, 'Globulinas', 'numerico', 'g/dL', 3);
        $this->ref($c, 'geral', 2.0, 3.5);

        // Eletrólitos
        $exId = $this->exame('SÓDIO', '40302512', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Sódio', 'numerico', 'mEq/L', 1);
        $this->ref($c, 'geral', 135.0, 145.0);

        $exId = $this->exame('POTÁSSIO', '40301338', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Potássio', 'numerico', 'mEq/L', 1);
        $this->ref($c, 'geral', 3.5, 5.0);

        $exId = $this->exame('CLORETO', '40300767', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Cloreto', 'numerico', 'mEq/L', 1);
        $this->ref($c, 'geral', 96.0, 106.0);

        $exId = $this->exame('CÁLCIO TOTAL', '40300597', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Cálcio Total', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', 8.5, 10.5);

        $exId = $this->exame('CÁLCIO IÔNICO', '40300600', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Cálcio Iônico', 'numerico', 'mmol/L', 1);
        $this->ref($c, 'geral', 1.12, 1.32);

        $exId = $this->exame('MAGNÉSIO', '40300880', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Magnésio', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', 1.7, 2.2);

        $exId = $this->exame('FÓSFORO', '40301001', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Fósforo', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'adulto_m', 2.5, 4.5); $this->ref($c, 'adulto_f', 2.5, 4.5);
        $this->ref($c, 'crianca', 4.0, 6.5);

        // LDH
        $exId = $this->exame('DESIDROGENASE LÁCTICA (LDH)', '40301002', 'BIOQUÍMICA');
        $c = $this->campo($exId, 'LDH', 'numerico', 'U/L', 1);
        $this->ref($c, 'geral', 120.0, 246.0);

        // Amilase
        $exId = $this->exame('AMILASE', '40300147', 'FUNÇÃO PANCREÁTICA');
        $c = $this->campo($exId, 'Amilase', 'numerico', 'U/L', 1);
        $this->ref($c, 'geral', 25.0, 125.0);

        // Lipase
        $exId = $this->exame('LIPASE', '40300862', 'FUNÇÃO PANCREÁTICA');
        $c = $this->campo($exId, 'Lipase', 'numerico', 'U/L', 1);
        $this->ref($c, 'geral', 13.0, 60.0);
    }

    // ─── LIPIDOGRAMA ──────────────────────────────────────────────────────────

    private function seedLipidograma(): void
    {
        $exId = $this->exame('LIPIDOGRAMA COMPLETO', '40302750', 'LIPIDOGRAMA');

        $c = $this->campo($exId, 'Colesterol Total', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'adulto_m', null, 190.0, null, 'Desejável < 190 mg/dL (SBC 2017)');
        $this->ref($c, 'adulto_f', null, 190.0, null, 'Desejável < 190 mg/dL (SBC 2017)');
        $this->ref($c, 'crianca', null, 170.0, null, 'Desejável < 170 mg/dL (2–19 anos)');

        $c = $this->campo($exId, 'HDL-Colesterol', 'numerico', 'mg/dL', 2);
        $this->ref($c, 'adulto_m', 40.0, null, null, '≥ 40 mg/dL desejável');
        $this->ref($c, 'adulto_f', 50.0, null, null, '≥ 50 mg/dL desejável');
        $this->ref($c, 'crianca', 45.0, null);

        $c = $this->campo($exId, 'LDL-Colesterol', 'numerico', 'mg/dL', 3);
        $this->ref($c, 'geral', null, 130.0, null, '< 130 (baixo risco); < 100 (moderado); < 70 (alto); < 50 (muito alto)');
        $this->ref($c, 'crianca', null, 110.0);

        $c = $this->campo($exId, 'VLDL-Colesterol', 'numerico', 'mg/dL', 4);
        $this->ref($c, 'geral', null, 30.0);

        $c = $this->campo($exId, 'Triglicerídeos', 'numerico', 'mg/dL', 5);
        $this->ref($c, 'adulto_m', null, 150.0); $this->ref($c, 'adulto_f', null, 150.0);
        $this->ref($c, 'crianca', null, 75.0, null, '0–9 anos');
        $this->ref($c, 'adolescente', null, 90.0, null, '10–19 anos');
    }

    // ─── FUNÇÃO HEPÁTICA ──────────────────────────────────────────────────────

    private function seedFuncaoHepatica(): void
    {
        $exId = $this->exame('TGO (AST — ASPARTATO AMINOTRANSFERASE)', '40300530', 'FUNÇÃO HEPÁTICA');
        $c = $this->campo($exId, 'TGO (AST)', 'numerico', 'U/L', 1);
        $this->ref($c, 'adulto_m', 10.0, 40.0); $this->ref($c, 'adulto_f', 10.0, 35.0);
        $this->ref($c, 'crianca', 10.0, 55.0);

        $exId = $this->exame('TGP (ALT — ALANINA AMINOTRANSFERASE)', '40300523', 'FUNÇÃO HEPÁTICA');
        $c = $this->campo($exId, 'TGP (ALT)', 'numerico', 'U/L', 1);
        $this->ref($c, 'adulto_m', 7.0, 56.0); $this->ref($c, 'adulto_f', 7.0, 35.0);
        $this->ref($c, 'crianca', 7.0, 45.0);

        $exId = $this->exame('GAMA GT (GGT)', '40301036', 'FUNÇÃO HEPÁTICA');
        $c = $this->campo($exId, 'GGT', 'numerico', 'U/L', 1);
        $this->ref($c, 'adulto_m', 9.0, 48.0); $this->ref($c, 'adulto_f', 7.0, 32.0);
        $this->ref($c, 'crianca', 2.0, 30.0);

        $exId = $this->exame('FOSFATASE ALCALINA', '40300988', 'FUNÇÃO HEPÁTICA');
        $c = $this->campo($exId, 'Fosfatase Alcalina', 'numerico', 'U/L', 1);
        $this->ref($c, 'adulto_m', 44.0, 147.0); $this->ref($c, 'adulto_f', 44.0, 147.0);
        $this->ref($c, 'crianca', 100.0, 400.0, null, 'Valores maiores por crescimento ósseo');

        $exId = $this->exame('BILIRRUBINAS TOTAIS E FRAÇÕES', '40300457', 'FUNÇÃO HEPÁTICA');
        $c = $this->campo($exId, 'Bilirrubina Total', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', 0.3, 1.2);
        $c = $this->campo($exId, 'Bilirrubina Direta', 'numerico', 'mg/dL', 2);
        $this->ref($c, 'geral', 0.0, 0.3);
        $c = $this->campo($exId, 'Bilirrubina Indireta', 'numerico', 'mg/dL', 3);
        $this->ref($c, 'geral', 0.2, 0.8);

        $exId = $this->exame('ALBUMINA', '40300100-H', 'FUNÇÃO HEPÁTICA');
        $c = $this->campo($exId, 'Albumina', 'numerico', 'g/dL', 1);
        $this->ref($c, 'geral', 3.5, 5.0);
    }

    // ─── FUNÇÃO RENAL ─────────────────────────────────────────────────────────

    private function seedFuncaoRenal(): void
    {
        $exId = $this->exame('CLEARANCE DE CREATININA', '40301003', 'FUNÇÃO RENAL');
        $c = $this->campo($exId, 'Clearance de Creatinina', 'numerico', 'mL/min/1,73m²', 1);
        $this->ref($c, 'adulto_m', 90.0, 130.0); $this->ref($c, 'adulto_f', 80.0, 120.0);

        $exId = $this->exame('MICROALBUMINÚRIA', '40301004', 'FUNÇÃO RENAL');
        $c = $this->campo($exId, 'Albumina na Urina', 'numerico', 'mg/g creatinina', 1);
        $this->ref($c, 'geral', null, 30.0, null, '< 30: Normal; 30–300: Microalbuminúria; > 300: Macroalbuminúria');

        $exId = $this->exame('CISTATINA C', '40301005', 'FUNÇÃO RENAL');
        $c = $this->campo($exId, 'Cistatina C', 'numerico', 'mg/L', 1);
        $this->ref($c, 'adulto_m', 0.56, 0.98); $this->ref($c, 'adulto_f', 0.50, 0.96);
    }

    // ─── COAGULAÇÃO ───────────────────────────────────────────────────────────

    private function seedCoagulacao(): void
    {
        $exId = $this->exame('TEMPO DE PROTROMBINA (TAP/TP/INR)', '40302636', 'COAGULAÇÃO / HEMOSTASIA');
        $c = $this->campo($exId, 'Tempo de Protrombina', 'numerico', 'segundos', 1);
        $this->ref($c, 'geral', 11.0, 13.5);
        $c = $this->campo($exId, 'INR', 'numerico', null, 2);
        $this->ref($c, 'geral', 0.85, 1.2);
        $c = $this->campo($exId, 'Atividade de Protrombina', 'numerico', '%', 3);
        $this->ref($c, 'geral', 70.0, 120.0);

        $exId = $this->exame('TTPA — TEMPO DE TROMBOPLASTINA PARCIAL ATIVADO', '40302661', 'COAGULAÇÃO / HEMOSTASIA');
        $c = $this->campo($exId, 'TTPA', 'numerico', 'segundos', 1);
        $this->ref($c, 'geral', 25.0, 35.0);
        $c = $this->campo($exId, 'Relação TTPA', 'numerico', null, 2);
        $this->ref($c, 'geral', 0.8, 1.2);

        $exId = $this->exame('FIBRINOGÊNIO', '40300994', 'COAGULAÇÃO / HEMOSTASIA');
        $c = $this->campo($exId, 'Fibrinogênio', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', 200.0, 400.0); $this->ref($c, 'gestante', 300.0, 600.0);

        $exId = $this->exame('D-DÍMERO', '40301006', 'COAGULAÇÃO / HEMOSTASIA');
        $c = $this->campo($exId, 'D-Dímero', 'numerico', 'µg/L FEU', 1);
        $this->ref($c, 'geral', null, 500.0, null, '< 500 µg/L exclui TEV em baixa probabilidade clínica');
        $this->ref($c, 'gestante', null, 1000.0, null, 'Limiar ajustado na gestação');
    }

    // ─── URIANÁLISE ───────────────────────────────────────────────────────────

    private function seedUrianalise(): void
    {
        $exId = $this->exame('URINA TIPO 1 (EAS)', '40302830', 'URIANÁLISE');
        $c = $this->campo($exId, 'pH', 'numerico', null, 1);
        $this->ref($c, 'geral', 4.5, 8.0);
        $c = $this->campo($exId, 'Densidade', 'numerico', null, 2);
        $this->ref($c, 'geral', 1.005, 1.030);
        $c = $this->campo($exId, 'Proteínas', 'texto', null, 3);
        $this->ref($c, 'geral', null, null, 'Ausente ou traços');
        $c = $this->campo($exId, 'Glicose', 'texto', null, 4);
        $this->ref($c, 'geral', null, null, 'Ausente');
        $c = $this->campo($exId, 'Cetona', 'texto', null, 5);
        $this->ref($c, 'geral', null, null, 'Ausente');
        $c = $this->campo($exId, 'Nitrito', 'texto', null, 6);
        $this->ref($c, 'geral', null, null, 'Ausente');
        $c = $this->campo($exId, 'Leucócitos (campo)', 'numerico', '/campo', 7);
        $this->ref($c, 'geral', 0.0, 5.0);
        $c = $this->campo($exId, 'Hemácias (campo)', 'numerico', '/campo', 8);
        $this->ref($c, 'geral', 0.0, 3.0);
        $c = $this->campo($exId, 'Cilindros', 'texto', null, 9);
        $this->ref($c, 'geral', null, null, 'Ausentes ou raros cilindros hialinos');
        $c = $this->campo($exId, 'Bactérias', 'texto', null, 10);
        $this->ref($c, 'geral', null, null, 'Ausente');
    }

    // ─── FUNÇÃO TIREOIDIANA ───────────────────────────────────────────────────

    private function seedFuncaoTireoidiana(): void
    {
        $exId = $this->exame('TSH — HORMÔNIO ESTIMULANTE DA TIREOIDE', '40302714', 'FUNÇÃO TIREOIDIANA');
        $c = $this->campo($exId, 'TSH', 'numerico', 'µIU/mL', 1);
        $this->ref($c, 'geral', 0.4, 4.0);
        $this->ref($c, 'gestante_t1', 0.1, 2.5, null, '1º Trimestre');
        $this->ref($c, 'gestante_t2', 0.2, 3.0, null, '2º Trimestre');
        $this->ref($c, 'gestante_t3', 0.3, 3.5, null, '3º Trimestre');
        $this->ref($c, 'idoso', 0.5, 6.0);

        $exId = $this->exame('T4 LIVRE (T4L)', '40302703', 'FUNÇÃO TIREOIDIANA');
        $c = $this->campo($exId, 'T4 Livre', 'numerico', 'ng/dL', 1);
        $this->ref($c, 'geral', 0.8, 1.8);
        $this->ref($c, 'gestante_t1', 0.8, 1.5); $this->ref($c, 'gestante_t2', 0.7, 1.5); $this->ref($c, 'gestante_t3', 0.5, 1.3);

        $exId = $this->exame('T3 LIVRE (T3L)', '40302697', 'FUNÇÃO TIREOIDIANA');
        $c = $this->campo($exId, 'T3 Livre', 'numerico', 'pg/mL', 1);
        $this->ref($c, 'geral', 2.3, 4.2);

        $exId = $this->exame('T4 TOTAL', '40302700', 'FUNÇÃO TIREOIDIANA');
        $c = $this->campo($exId, 'T4 Total', 'numerico', 'µg/dL', 1);
        $this->ref($c, 'geral', 5.0, 12.0);

        $exId = $this->exame('T3 TOTAL', '40302695', 'FUNÇÃO TIREOIDIANA');
        $c = $this->campo($exId, 'T3 Total', 'numerico', 'ng/dL', 1);
        $this->ref($c, 'geral', 80.0, 200.0);

        $exId = $this->exame('ANTI-TPO (ANTITIREOPEROXIDASE)', '40300190', 'FUNÇÃO TIREOIDIANA');
        $c = $this->campo($exId, 'Anti-TPO', 'numerico', 'UI/mL', 1);
        $this->ref($c, 'geral', null, 34.0, null, '< 34 UI/mL: Normal');

        $exId = $this->exame('TIREOGLOBULINA', '40302725', 'FUNÇÃO TIREOIDIANA');
        $c = $this->campo($exId, 'Tireoglobulina', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', 1.4, 78.0);
    }

    // ─── HORMÔNIOS ────────────────────────────────────────────────────────────

    private function seedHormonios(): void
    {
        $exId = $this->exame('TESTOSTERONA TOTAL', '40302722', 'HORMÔNIOS');
        $c = $this->campo($exId, 'Testosterona Total', 'numerico', 'ng/dL', 1);
        $this->ref($c, 'adulto_m', 300.0, 1000.0); $this->ref($c, 'adulto_f', 15.0, 70.0);
        $this->ref($c, 'adolescente_m', 100.0, 1200.0); $this->ref($c, 'idoso_m', 200.0, 900.0);

        $exId = $this->exame('ESTRADIOL', '40300918', 'HORMÔNIOS');
        $c = $this->campo($exId, 'Estradiol', 'numerico', 'pg/mL', 1);
        $this->ref($c, 'adulto_m', null, 54.0);
        $this->ref($c, 'adulto_f', 12.4, 233.0, null, 'Fase folicular');
        $this->ref($c, 'idoso_f', null, 40.0, null, 'Pós-menopausa');

        $exId = $this->exame('FSH — HORMÔNIO FOLÍCULO ESTIMULANTE', '40301026', 'HORMÔNIOS');
        $c = $this->campo($exId, 'FSH', 'numerico', 'mUI/mL', 1);
        $this->ref($c, 'adulto_m', 1.4, 13.8);
        $this->ref($c, 'adulto_f', 3.5, 12.5, null, 'Fase folicular');
        $this->ref($c, 'idoso_f', 25.8, 134.8, null, 'Pós-menopausa');

        $exId = $this->exame('LH — HORMÔNIO LUTEINIZANTE', '40300868', 'HORMÔNIOS');
        $c = $this->campo($exId, 'LH', 'numerico', 'mUI/mL', 1);
        $this->ref($c, 'adulto_m', 1.7, 8.6);
        $this->ref($c, 'adulto_f', 2.4, 12.6, null, 'Fase folicular');

        $exId = $this->exame('PROLACTINA', '40301351', 'HORMÔNIOS');
        $c = $this->campo($exId, 'Prolactina', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'adulto_m', 2.0, 18.0); $this->ref($c, 'adulto_f', 2.0, 29.0);
        $this->ref($c, 'gestante', 10.0, 300.0);

        $exId = $this->exame('PROGESTERONA', '40301335', 'HORMÔNIOS');
        $c = $this->campo($exId, 'Progesterona', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'adulto_m', 0.2, 1.4);
        $this->ref($c, 'adulto_f', 0.1, 0.8, null, 'Fase folicular');
        $this->ref($c, 'gestante_t1', 11.0, 90.0); $this->ref($c, 'gestante_t2', 25.0, 90.0); $this->ref($c, 'gestante_t3', 48.0, 300.0);

        $exId = $this->exame('CORTISOL', '40300782', 'HORMÔNIOS');
        $c = $this->campo($exId, 'Cortisol (8h)', 'numerico', 'µg/dL', 1);
        $this->ref($c, 'geral', 4.0, 22.0);
        $c = $this->campo($exId, 'Cortisol (16h)', 'numerico', 'µg/dL', 2);
        $this->ref($c, 'geral', 3.0, 17.0);

        $exId = $this->exame('DHEA-S (SULFATO DE DEIDROEPIANDROSTERONA)', '40300820', 'HORMÔNIOS');
        $c = $this->campo($exId, 'DHEA-S', 'numerico', 'µg/dL', 1);
        $this->ref($c, 'adulto_m', 100.0, 617.0); $this->ref($c, 'adulto_f', 98.0, 340.0);
        $this->ref($c, 'idoso_m', 51.0, 295.0); $this->ref($c, 'idoso_f', 17.0, 90.0);

        $exId = $this->exame('INSULINA', '40301133', 'DIABETES');
        $c = $this->campo($exId, 'Insulina em Jejum', 'numerico', 'µUI/mL', 1);
        $this->ref($c, 'geral', 2.0, 25.0);

        $exId = $this->exame('BETA-HCG QUANTITATIVO', '40300447', 'HORMÔNIOS');
        $c = $this->campo($exId, 'Beta-HCG', 'numerico', 'mUI/mL', 1);
        $this->ref($c, 'adulto_m', null, 5.0, null, 'Homens e mulheres não gestantes');
        $this->ref($c, 'gestante_t1', 20.0, 200000.0, null, 'Valores variam muito por semana gestacional');
    }

    // ─── FERRO E VITAMINAS ────────────────────────────────────────────────────

    private function seedFerroVitaminas(): void
    {
        $exId = $this->exame('FERRO SÉRICO', '40300978', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, 'Ferro Sérico', 'numerico', 'µg/dL', 1);
        $this->ref($c, 'adulto_m', 65.0, 175.0); $this->ref($c, 'adulto_f', 50.0, 170.0);
        $this->ref($c, 'crianca', 50.0, 120.0);

        $exId = $this->exame('CTLF — CAPACIDADE TOTAL DE LIGAÇÃO DO FERRO', '40301007', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, 'CTLF', 'numerico', 'µg/dL', 1);
        $this->ref($c, 'geral', 250.0, 400.0);

        $exId = $this->exame('SATURAÇÃO DE TRANSFERRINA', '40301008', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, 'Saturação de Transferrina', 'numerico', '%', 1);
        $this->ref($c, 'geral', 15.0, 50.0);

        $exId = $this->exame('FERRITINA', '40300981', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, 'Ferritina', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'adulto_m', 22.0, 322.0); $this->ref($c, 'adulto_f', 10.0, 291.0);
        $this->ref($c, 'crianca', 7.0, 140.0);

        $exId = $this->exame('VITAMINA B12 (COBALAMINA)', '40302860', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, 'Vitamina B12', 'numerico', 'pg/mL', 1);
        $this->ref($c, 'geral', 200.0, 900.0, null, '< 200: Deficiência; 200–300: Limítrofe');

        $exId = $this->exame('ÁCIDO FÓLICO (VITAMINA B9)', '40300070', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, 'Ácido Fólico', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', 4.6, 18.7, null, '< 3: Deficiência; 3–4.6: Limítrofe');

        $exId = $this->exame('VITAMINA D (25-OH-VITAMINA D)', '40302868', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, '25-OH-Vitamina D', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', 30.0, 100.0, null, '< 20: Deficiência; 20–30: Insuficiência; 30–100: Suficiência; > 100: Toxicidade');

        $exId = $this->exame('VITAMINA B1 (TIAMINA)', '40302865', 'VITAMINAS E MICRONUTRIENTES');
        $c = $this->campo($exId, 'Tiamina', 'numerico', 'nmol/L', 1);
        $this->ref($c, 'geral', 78.0, 185.0);

        $exId = $this->exame('ZINCO', '40302890', 'MINERAIS E ELETRÓLITOS');
        $c = $this->campo($exId, 'Zinco', 'numerico', 'µg/dL', 1);
        $this->ref($c, 'geral', 70.0, 120.0);
    }

    // ─── MARCADORES CARDÍACOS ─────────────────────────────────────────────────

    private function seedMarcadoresCardiacos(): void
    {
        $exId = $this->exame('TROPONINA I ULTRASSENSÍVEL', '40302760', 'MARCADORES CARDÍACOS');
        $c = $this->campo($exId, 'Troponina I', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', null, 0.04, null, '≤ 0.04 ng/mL: Normal (99º percentil)');

        $exId = $this->exame('TROPONINA T ULTRASSENSÍVEL', '40302762', 'MARCADORES CARDÍACOS');
        $c = $this->campo($exId, 'Troponina T', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', null, 0.014, null, '≤ 0.014 ng/mL: Normal');

        $exId = $this->exame('BNP — PEPTÍDEO NATRIURÉTICO TIPO B', '40300450', 'MARCADORES CARDÍACOS');
        $c = $this->campo($exId, 'BNP', 'numerico', 'pg/mL', 1);
        $this->ref($c, 'geral', null, 100.0, null, '< 100: ICC improvável; 100–400: Zona cinza; > 400: ICC provável');

        $exId = $this->exame('NT-PROBNP', '40300452', 'MARCADORES CARDÍACOS');
        $c = $this->campo($exId, 'NT-proBNP', 'numerico', 'pg/mL', 1);
        $this->ref($c, 'adulto_m', null, 125.0); $this->ref($c, 'adulto_f', null, 125.0);
        $this->ref($c, 'idoso', null, 450.0, null, '≥ 75 anos');

        $exId = $this->exame('CK — CREATINO QUINASE TOTAL', '40300779', 'MARCADORES CARDÍACOS');
        $c = $this->campo($exId, 'CK Total', 'numerico', 'U/L', 1);
        $this->ref($c, 'adulto_m', 26.0, 192.0); $this->ref($c, 'adulto_f', 26.0, 140.0);

        $exId = $this->exame('CK-MB', '40300780', 'MARCADORES CARDÍACOS');
        $c = $this->campo($exId, 'CK-MB massa', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', null, 4.94, null, '< 4.94 ng/mL: Normal');

        $exId = $this->exame('HOMOCISTEÍNA', '40301085', 'MARCADORES CARDÍACOS');
        $c = $this->campo($exId, 'Homocisteína', 'numerico', 'µmol/L', 1);
        $this->ref($c, 'geral', null, 15.0, null, '< 15: Normal; 15–30: Leve; 30–100: Moderada; > 100: Grave');
    }

    // ─── MARCADORES TUMORAIS ──────────────────────────────────────────────────

    private function seedMarcadoresTumorais(): void
    {
        $exId = $this->exame('PSA TOTAL', '40301356', 'MARCADORES TUMORAIS');
        $c = $this->campo($exId, 'PSA Total', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'adulto_m', null, 4.0, null, 'Valores aumentam com idade; > 4 ng/mL requer investigação');

        $exId = $this->exame('PSA LIVRE', '40301357', 'MARCADORES TUMORAIS');
        $c = $this->campo($exId, 'PSA Livre', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'adulto_m', null, null);
        $c = $this->campo($exId, 'Relação PSA Livre/Total', 'numerico', '%', 2);
        $this->ref($c, 'adulto_m', 15.0, null, null, '≥ 15%: Menor risco de neoplasia');

        $exId = $this->exame('CEA — ANTÍGENO CARCINOEMBRIONÁRIO', '40300670', 'MARCADORES TUMORAIS');
        $c = $this->campo($exId, 'CEA', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', null, 3.5, null, 'Não fumantes < 3.5; Fumantes < 7 ng/mL');

        $exId = $this->exame('CA 125', '40300590', 'MARCADORES TUMORAIS');
        $c = $this->campo($exId, 'CA 125', 'numerico', 'U/mL', 1);
        $this->ref($c, 'adulto_f', null, 35.0);

        $exId = $this->exame('CA 19-9', '40300591', 'MARCADORES TUMORAIS');
        $c = $this->campo($exId, 'CA 19-9', 'numerico', 'U/mL', 1);
        $this->ref($c, 'geral', null, 37.0);

        $exId = $this->exame('CA 15-3', '40300592', 'MARCADORES TUMORAIS');
        $c = $this->campo($exId, 'CA 15-3', 'numerico', 'U/mL', 1);
        $this->ref($c, 'adulto_f', null, 30.0);

        $exId = $this->exame('AFP — ALFA-FETOPROTEÍNA', '40300115', 'MARCADORES TUMORAIS');
        $c = $this->campo($exId, 'AFP', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', null, 10.0);
        $this->ref($c, 'gestante_t1', 10.0, 150.0); $this->ref($c, 'gestante_t2', 15.0, 250.0); $this->ref($c, 'gestante_t3', 20.0, 350.0);
    }

    // ─── SOROLOGIAS ───────────────────────────────────────────────────────────

    private function seedSorologias(): void
    {
        foreach ([
            ['TOXOPLASMOSE IgG', '40302742', 'IgG Toxoplasmose'],
            ['TOXOPLASMOSE IgM', '40302743', 'IgM Toxoplasmose'],
            ['RUBÉOLA IgG', '40302490', 'IgG Rubéola'],
            ['RUBÉOLA IgM', '40302491', 'IgM Rubéola'],
            ['CITOMEGALOVÍRUS IgG', '40300754', 'IgG CMV'],
            ['CITOMEGALOVÍRUS IgM', '40300755', 'IgM CMV'],
            ['HERPES SIMPLES IgG', '40301080', 'IgG HSV'],
            ['HERPES SIMPLES IgM', '40301081', 'IgM HSV'],
        ] as [$nome, $codigo, $campo]) {
            $exId = $this->exame($nome, $codigo, 'SOROLOGIAS');
            $c = $this->campo($exId, $campo, 'texto', null, 1);
            $this->ref($c, 'geral', null, null, 'Não reagente', 'Resultado: Reagente ou Não Reagente');
        }

        $exId = $this->exame('ANTICORPOS HETERÓFILOS (MONOSPOT)', '40301009', 'SOROLOGIAS');
        $c = $this->campo($exId, 'Monospot', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');
    }

    // ─── IST / DST ────────────────────────────────────────────────────────────

    private function seedInfeccoesIST(): void
    {
        $exId = $this->exame('VDRL (SÍFILIS — TRIAGEM)', '40302815', 'IST / DST');
        $c = $this->campo($exId, 'VDRL', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');

        $exId = $this->exame('FTA-ABS (SÍFILIS — CONFIRMATÓRIO)', '40301027', 'IST / DST');
        $c = $this->campo($exId, 'FTA-ABS', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');

        $exId = $this->exame('ANTI-HIV 1 E 2 (4ª GERAÇÃO)', '40300237', 'IST / DST');
        $c = $this->campo($exId, 'Anti-HIV 1 e 2', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');

        $exId = $this->exame('HBSAG (HEPATITE B — ANTÍGENO DE SUPERFÍCIE)', '40301058', 'IST / DST');
        $c = $this->campo($exId, 'HBsAg', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');

        $exId = $this->exame('ANTI-HBS (HEPATITE B — ANTICORPO)', '40300228', 'IST / DST');
        $c = $this->campo($exId, 'Anti-HBs', 'numerico', 'UI/L', 1);
        $this->ref($c, 'geral', 10.0, null, null, '≥ 10 UI/L: Imune (vacinado ou recuperado); < 10: Suscetível');

        $exId = $this->exame('ANTI-HBC TOTAL (HEPATITE B)', '40300225', 'IST / DST');
        $c = $this->campo($exId, 'Anti-HBc Total', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');

        $exId = $this->exame('ANTI-HCV (HEPATITE C)', '40300230', 'IST / DST');
        $c = $this->campo($exId, 'Anti-HCV', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');

        $exId = $this->exame('HTLV I E II', '40301100', 'IST / DST');
        $c = $this->campo($exId, 'HTLV I/II', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');

        $exId = $this->exame('CLAMÍDIA (Chlamydia trachomatis) — IgG', '40300748', 'IST / DST');
        $c = $this->campo($exId, 'IgG Clamídia', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');
    }

    // ─── INFLAMATÓRIOS ────────────────────────────────────────────────────────

    private function seedInflamatorios(): void
    {
        $exId = $this->exame('PCR — PROTEÍNA C REATIVA', '40301342', 'DOENÇAS INFECCIOSAS');
        $c = $this->campo($exId, 'PCR', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', null, 0.5, null, '< 0.5 mg/dL: Normal; 0.5–1.0: Limítrofe; > 1.0: Elevado');

        $exId = $this->exame('PCR ULTRASSENSÍVEL (hs-CRP)', '40301343', 'DOENÇAS INFECCIOSAS');
        $c = $this->campo($exId, 'PCR ultrassensível', 'numerico', 'mg/L', 1);
        $this->ref($c, 'geral', null, 3.0, null, '< 1.0: Baixo risco CV; 1.0–3.0: Médio; > 3.0: Alto risco CV');

        $exId = $this->exame('PROCALCITONINA', '40301010', 'DOENÇAS INFECCIOSAS');
        $c = $this->campo($exId, 'Procalcitonina', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', null, 0.1, null, '< 0.1: Sepse improvável; 0.1–0.5: Possível; 0.5–2.0: Sepse moderada; > 2.0: Sepse grave');

        $exId = $this->exame('FATOR REUMATOIDE (FR)', '40300975', 'DOENÇAS AUTOIMUNES');
        $c = $this->campo($exId, 'Fator Reumatoide', 'numerico', 'UI/mL', 1);
        $this->ref($c, 'geral', null, 14.0, null, '< 14 UI/mL: Negativo');

        $exId = $this->exame('FAN (FATOR ANTINUCLEAR)', '40300965', 'DOENÇAS AUTOIMUNES');
        $c = $this->campo($exId, 'FAN', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente (< 1:80)', 'Título ≥ 1:80 considerado reagente');

        $exId = $this->exame('ANTI-DNA NATIVO', '40300180', 'DOENÇAS AUTOIMUNES');
        $c = $this->campo($exId, 'Anti-DNA nativo', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não reagente');
    }

    // ─── DIABETES ─────────────────────────────────────────────────────────────

    private function seedDiabetes(): void
    {
        $exId = $this->exame('TOTG — TESTE DE TOLERÂNCIA ORAL À GLICOSE (75g)', '40302048', 'DIABETES');
        $c = $this->campo($exId, 'Glicose em Jejum', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', null, 99.0, null, '< 100: Normal');
        $c = $this->campo($exId, 'Glicose 1h (75g)', 'numerico', 'mg/dL', 2);
        $this->ref($c, 'geral', null, 180.0);
        $c = $this->campo($exId, 'Glicose 2h (75g)', 'numerico', 'mg/dL', 3);
        $this->ref($c, 'geral', null, 139.0, null, '< 140: Normal; 140–199: Pré-diabetes; ≥ 200: Diabetes');

        $exId = $this->exame('PEPTÍDEO C', '40301011', 'DIABETES');
        $c = $this->campo($exId, 'Peptídeo C', 'numerico', 'ng/mL', 1);
        $this->ref($c, 'geral', 0.8, 3.1);

        $exId = $this->exame('FRUTOSAMINA', '40301030', 'DIABETES');
        $c = $this->campo($exId, 'Frutosamina', 'numerico', 'µmol/L', 1);
        $this->ref($c, 'geral', 195.0, 285.0);
    }

    // ─── MICROBIOLOGIA ────────────────────────────────────────────────────────

    private function seedMicrobiologia(): void
    {
        $culturas = [
            ['UROCULTURA COM ANTIBIOGRAMA', '40302795', 'Cultura de Urina'],
            ['HEMOCULTURA', '40301063', 'Hemocultura'],
            ['COPROCULTURA', '40300800', 'Coprocultura'],
            ['CULTURA DE SECREÇÃO', '40302800', 'Cultura de Secreção'],
            ['CULTURA DE ESCARRO', '40300802', 'Cultura de Escarro'],
        ];
        foreach ($culturas as [$nome, $codigo, $campo]) {
            $exId = $this->exame($nome, $codigo, 'MICROBIOLOGIA / BACTERIOLOGIA');
            $c = $this->campo($exId, $campo, 'texto', null, 1);
            $this->ref($c, 'geral', null, null, 'Sem crescimento bacteriano', 'Resultado qualitativo com antibiograma quando positivo');
            $c2 = $this->campo($exId, 'Antibiograma', 'texto', null, 2, false);
            $this->ref($c2, 'geral', null, null, 'N/A (cultura negativa)');
        }

        $exId = $this->exame('GRAM DE SECREÇÃO', '40301012', 'MICROBIOLOGIA / BACTERIOLOGIA');
        $c = $this->campo($exId, 'Coloração de Gram', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Ausência de microrganismos');
    }

    // ─── PARASITOLOGIA ────────────────────────────────────────────────────────

    private function seedParasitologia(): void
    {
        $exId = $this->exame('EPF — EXAME PARASITOLÓGICO DE FEZES', '40300940', 'PARASITOLOGIA');
        $c = $this->campo($exId, 'Pesquisa de Parasitos', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Negativo');
        $c = $this->campo($exId, 'Ovos e Larvas', 'texto', null, 2);
        $this->ref($c, 'geral', null, null, 'Não encontrados');
        $c = $this->campo($exId, 'Cistos', 'texto', null, 3);
        $this->ref($c, 'geral', null, null, 'Não encontrados');

        $exId = $this->exame('PESQUISA DE SANGUE OCULTO NAS FEZES', '40301013', 'PARASITOLOGIA');
        $c = $this->campo($exId, 'Sangue Oculto', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Negativo');

        $exId = $this->exame('PESQUISA DE ROTAVÍRUS NAS FEZES', '40301014', 'VIROLOGIA');
        $c = $this->campo($exId, 'Rotavírus', 'texto', null, 1);
        $this->ref($c, 'geral', null, null, 'Não detectado');
    }

    // ─── GASOMETRIA ───────────────────────────────────────────────────────────

    private function seedGasometria(): void
    {
        $exId = $this->exame('GASOMETRIA ARTERIAL', '40301040', 'GASOMETRIA');
        $c = $this->campo($exId, 'pH', 'numerico', null, 1);
        $this->ref($c, 'geral', 7.35, 7.45);
        $c = $this->campo($exId, 'pCO2', 'numerico', 'mmHg', 2);
        $this->ref($c, 'geral', 35.0, 45.0);
        $c = $this->campo($exId, 'pO2', 'numerico', 'mmHg', 3);
        $this->ref($c, 'geral', 80.0, 100.0);
        $c = $this->campo($exId, 'HCO3', 'numerico', 'mEq/L', 4);
        $this->ref($c, 'geral', 22.0, 26.0);
        $c = $this->campo($exId, 'SatO2', 'numerico', '%', 5);
        $this->ref($c, 'geral', 95.0, 100.0);
        $c = $this->campo($exId, 'Excesso de Base (BE)', 'numerico', 'mEq/L', 6);
        $this->ref($c, 'geral', -2.0, 2.0);

        $exId = $this->exame('GASOMETRIA VENOSA', '40301041', 'GASOMETRIA');
        $c = $this->campo($exId, 'pH venoso', 'numerico', null, 1);
        $this->ref($c, 'geral', 7.31, 7.41);
        $c = $this->campo($exId, 'pCO2 venoso', 'numerico', 'mmHg', 2);
        $this->ref($c, 'geral', 41.0, 51.0);
        $c = $this->campo($exId, 'HCO3 venoso', 'numerico', 'mEq/L', 3);
        $this->ref($c, 'geral', 22.0, 29.0);
    }

    // ─── IMUNOLOGIA ───────────────────────────────────────────────────────────

    private function seedImunologia(): void
    {
        $exId = $this->exame('IMUNOFENOTIPAGEM LINFOCITÁRIA (CD4/CD8)', '40301015', 'IMUNOLOGIA');
        $c = $this->campo($exId, 'Linfócitos CD4+', 'numerico', 'células/µL', 1);
        $this->ref($c, 'geral', 500.0, 1500.0);
        $c = $this->campo($exId, 'Linfócitos CD8+', 'numerico', 'células/µL', 2);
        $this->ref($c, 'geral', 200.0, 900.0);
        $c = $this->campo($exId, 'Relação CD4/CD8', 'numerico', null, 3);
        $this->ref($c, 'geral', 1.0, 4.0);

        $exId = $this->exame('ELETROFORESE DE PROTEÍNAS', '40300910', 'IMUNOLOGIA');
        $c = $this->campo($exId, 'Albumina', 'numerico', '%', 1);
        $this->ref($c, 'geral', 52.0, 65.0);
        $c = $this->campo($exId, 'Alfa-1 globulina', 'numerico', '%', 2);
        $this->ref($c, 'geral', 2.5, 5.0);
        $c = $this->campo($exId, 'Alfa-2 globulina', 'numerico', '%', 3);
        $this->ref($c, 'geral', 7.0, 13.0);
        $c = $this->campo($exId, 'Beta globulina', 'numerico', '%', 4);
        $this->ref($c, 'geral', 8.0, 14.0);
        $c = $this->campo($exId, 'Gama globulina', 'numerico', '%', 5);
        $this->ref($c, 'geral', 12.0, 22.0);

        $exId = $this->exame('IgE TOTAL', '40301117', 'IMUNOLOGIA');
        $c = $this->campo($exId, 'IgE Total', 'numerico', 'UI/mL', 1);
        $this->ref($c, 'adulto_m', null, 100.0); $this->ref($c, 'adulto_f', null, 100.0);
        $this->ref($c, 'crianca', null, 60.0);

        $exId = $this->exame('COMPLEMENTO C3', '40300770', 'IMUNOLOGIA');
        $c = $this->campo($exId, 'C3', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', 90.0, 180.0);

        $exId = $this->exame('COMPLEMENTO C4', '40300771', 'IMUNOLOGIA');
        $c = $this->campo($exId, 'C4', 'numerico', 'mg/dL', 1);
        $this->ref($c, 'geral', 16.0, 47.0);
    }
}
