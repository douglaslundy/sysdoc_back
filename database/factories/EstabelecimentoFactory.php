<?php

namespace Database\Factories;

use App\Models\Estabelecimento;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstabelecimentoFactory extends Factory
{
    protected $model = Estabelecimento::class;

    public function definition(): array
    {
        return [
            'nome_responsavel'     => $this->faker->name(),
            'nome_estabelecimento' => $this->faker->company(),
            'endereco'             => $this->faker->address(),
            'cnaes'                => $this->faker->numerify('##.##-#-##'),
        ];
    }
}
