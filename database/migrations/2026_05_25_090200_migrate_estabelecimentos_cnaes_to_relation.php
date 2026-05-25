<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $estabelecimentos = DB::table('estabelecimentos')->select('id', 'cnaes')->get();

        foreach ($estabelecimentos as $estabelecimento) {
            preg_match_all('/\d{4}-\d\/\d{2}/', (string) $estabelecimento->cnaes, $matches);
            $codigos = array_values(array_unique($matches[0] ?? []));

            foreach ($codigos as $codigo) {
                DB::table('cnaes')->updateOrInsert(
                    ['codigo' => $codigo],
                    ['descricao' => null, 'updated_at' => $now, 'created_at' => $now]
                );

                $id = DB::table('cnaes')->where('codigo', $codigo)->value('id');
                if (! $id) {
                    continue;
                }

                DB::table('estabelecimento_cnaes')->updateOrInsert(
                    ['estabelecimento_id' => $estabelecimento->id, 'cnae_id' => $id],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('estabelecimento_cnaes')->delete();
    }
};
