<?php

namespace Database\Seeders;

use App\Models\AlmoxarifadoConfig;
use Illuminate\Database\Seeder;

class AlmoxarifadoConfigSeeder extends Seeder
{
    public function run(): void
    {
        AlmoxarifadoConfig::current();
    }
}
