<?php

namespace Database\Factories;

use App\Models\Alvara;
use App\Models\Estabelecimento;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlvaraFactory extends Factory
{
    protected $model = Alvara::class;

    public function definition(): array
    {
        $dataAlvara = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'numero_alvara'      => sprintf('%02d-%02d/%04d',
                $this->faker->unique()->numberBetween(1, 99),
                $dataAlvara->format('m'),
                $dataAlvara->format('Y')
            ),
            'nivel_risco'        => $this->faker->randomElement(['1', '2', '3', 'N/A']),
            'estabelecimento_id' => Estabelecimento::factory(),
            'data_alvara'        => $dataAlvara->format('Y-m-d'),
            'vencimento_alvara'  => $this->faker->optional()->dateTimeBetween('now', '+2 years')?->format('Y-m-d'),
            'contato'            => $this->faker->optional()->phoneNumber(),
        ];
    }
}
