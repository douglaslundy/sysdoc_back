<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['code' => 'AC', 'name' => 'ACRE'],
            ['code' => 'AL', 'name' => 'ALAGOAS'],
            ['code' => 'AP', 'name' => 'AMAPÁ'],
            ['code' => 'AM', 'name' => 'AMAZONAS'],
            ['code' => 'BA', 'name' => 'BAHIA'],
            ['code' => 'CE', 'name' => 'CEARÁ'],
            ['code' => 'DF', 'name' => 'DISTRITO FEDERAL'],
            ['code' => 'ES', 'name' => 'ESPÍRITO SANTO'],
            ['code' => 'GO', 'name' => 'GOIÁS'],
            ['code' => 'MA', 'name' => 'MARANHÃO'],
            ['code' => 'MT', 'name' => 'MATO GROSSO'],
            ['code' => 'MS', 'name' => 'MATO GROSSO DO SUL'],
            ['code' => 'MG', 'name' => 'MINAS GERAIS'],
            ['code' => 'PA', 'name' => 'PARÁ'],
            ['code' => 'PB', 'name' => 'PARAÍBA'],
            ['code' => 'PR', 'name' => 'PARANÁ'],
            ['code' => 'PE', 'name' => 'PERNAMBUCO'],
            ['code' => 'PI', 'name' => 'PIAUÍ'],
            ['code' => 'RJ', 'name' => 'RIO DE JANEIRO'],
            ['code' => 'RN', 'name' => 'RIO GRANDE DO NORTE'],
            ['code' => 'RS', 'name' => 'RIO GRANDE DO SUL'],
            ['code' => 'RO', 'name' => 'RONDÔNIA'],
            ['code' => 'RR', 'name' => 'RORAIMA'],
            ['code' => 'SC', 'name' => 'SANTA CATARINA'],
            ['code' => 'SP', 'name' => 'SÃO PAULO'],
            ['code' => 'SE', 'name' => 'SERGIPE'],
            ['code' => 'TO', 'name' => 'TOCANTINS'],
        ];

        foreach ($states as $state) {
            State::firstOrCreate(
                ['code' => $state['code']],
                ['name' => $state['name']]
            );
        }
    }
}