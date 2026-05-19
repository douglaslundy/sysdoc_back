<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FixAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $cpf = '08449222699';

        $existing = DB::table('users')->where('cpf', $cpf)->first();

        if ($existing) {
            DB::table('users')->where('cpf', $cpf)->update([
                'profile' => 'admin',
                'active' => true,
                'name' => 'Douglas',
                'email' => 'douglaslundy@gmail.com',
            ]);

            // Invalidar todos os tokens Sanctum do usuário para forçar novo login
            DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->where('tokenable_id', $existing->id)
                ->delete();

            $this->command->info("Usuário CPF {$cpf} atualizado: profile=admin, active=true. Faça login novamente.");
        } else {
            DB::table('users')->insert([
                'name' => 'Douglas',
                'email' => 'douglaslundy@gmail.com',
                'cpf' => $cpf,
                'password' => Hash::make('12345678'),
                'profile' => 'admin',
                'active' => true,
            ]);

            $this->command->info('Usuário admin criado: profile=admin, active=true. Senha inicial: 12345678');
        }
    }
}
