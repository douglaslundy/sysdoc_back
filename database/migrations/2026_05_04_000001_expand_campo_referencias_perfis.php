<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
     * Expande campo_referencias para suportar perfis fisiológicos completos.
     *
     * Baseado em: SBPC/ML Manual de Exames, ANVISA RDC 302/2005,
     * HL7 FHIR ObservationReferenceRange e literatura de bioquímica clínica.
     *
     * Novos perfis: recem_nascido (0-28 dias), adolescente (12-17 anos).
     * Novas colunas: sexo (M/F), idade_min_dias, idade_max_dias, gestante (bool).
     * Isso permite definir referências para recém-nascido do sexo masculino,
     * adulto gestante 1º trimestre, etc., com precisão clínica completa.
     */
    public function up(): void
    {
        // MySQL não suporta ALTER COLUMN em enum diretamente — modifica com CHANGE
        DB::statement("ALTER TABLE campo_referencias MODIFY COLUMN perfil ENUM(
            'geral','adulto_m','adulto_f','crianca','idoso','gestante',
            'recem_nascido','adolescente','adolescente_m','adolescente_f',
            'crianca_m','crianca_f','idoso_m','idoso_f',
            'gestante_t1','gestante_t2','gestante_t3'
        ) NOT NULL DEFAULT 'geral'");

        Schema::table('campo_referencias', function (Blueprint $table) {
            $table->enum('sexo', ['M', 'F'])->nullable()->after('perfil')
                  ->comment('M=masculino, F=feminino, null=ambos');
            $table->unsignedInteger('idade_min_dias')->nullable()->after('sexo')
                  ->comment('Limite inferior de idade em dias (null=sem limite)');
            $table->unsignedInteger('idade_max_dias')->nullable()->after('idade_min_dias')
                  ->comment('Limite superior de idade em dias (null=sem limite)');
        });
    }

    public function down(): void
    {
        Schema::table('campo_referencias', function (Blueprint $table) {
            $table->dropColumn(['sexo', 'idade_min_dias', 'idade_max_dias']);
        });

        DB::statement("ALTER TABLE campo_referencias MODIFY COLUMN perfil ENUM(
            'geral','adulto_m','adulto_f','crianca','idoso','gestante'
        ) NOT NULL DEFAULT 'geral'");
    }
};
