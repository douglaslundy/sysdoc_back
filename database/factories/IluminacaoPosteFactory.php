<?php

namespace Database\Factories;

use App\Models\IluminacaoPoste;
use Illuminate\Database\Eloquent\Factories\Factory;

class IluminacaoPosteFactory extends Factory
{
    protected $model = IluminacaoPoste::class;

    public function definition(): array
    {
        return [
            'ponto_referencia' => $this->faker->sentence(3),
            'logradouro' => $this->faker->streetName(),
            'numero' => (string) $this->faker->numberBetween(1, 999),
            'bairro' => $this->faker->word(),
            'latitude' => $this->faker->latitude(-24, -20),
            'longitude' => $this->faker->longitude(-48, -44),
            'precisao_m' => $this->faker->randomFloat(2, 3, 30),
        ];
    }
}
