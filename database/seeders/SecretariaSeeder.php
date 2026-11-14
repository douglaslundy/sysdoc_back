<?php

namespace Database\Seeders;

use App\Models\Secretaria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecretariaSeeder extends Seeder
{
    public function run(): void
    {
        $secretarias = [
            'saude' => 'Saúde',
            'educacao' => 'Educação',
            'cultura' => 'Cultura',
            'agricultura' => 'Agricultura',
            'meio-ambiente' => 'Meio Ambiente',
            'obras' => 'Obras',
        ];

        foreach ($secretarias as $slug => $nome) {
            $existente = Secretaria::where('slug', $slug)->orWhere('nome', $nome)->first();
            if ($existente) {
                $existente->update(['slug' => $slug]);
            } else {
                Secretaria::create(['slug' => $slug, 'nome' => $nome, 'ativa' => true]);
            }
        }

        $saudeId = Secretaria::where('slug', Secretaria::SAUDE_SLUG)->value('id');
        $semVinculo = DB::table('users')->whereNotIn('id', DB::table('user_secretarias')->select('user_id'))->pluck('id');
        foreach ($semVinculo as $userId) {
            DB::table('user_secretarias')->insertOrIgnore(['user_id' => $userId, 'secretaria_id' => $saudeId]);
        }
    }
}
