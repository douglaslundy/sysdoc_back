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
            'razao_social'         => $this->faker->optional()->company(),
            'nome_fantasia'        => $this->faker->optional()->company(),
            'cnpj'                 => null,
            'telefone'             => $this->faker->optional()->numerify('(##) #####-####'),
            'endereco'             => $this->faker->address(),
            'cnaes'                => $this->faker->numerify('##.##-#-##'),
            'obs'                  => $this->faker->optional()->sentence(),
        ];
    }
}
