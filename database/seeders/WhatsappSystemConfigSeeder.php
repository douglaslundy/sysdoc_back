<?php

namespace Database\Seeders;

use App\Models\ProtocolConfig;
use Illuminate\Database\Seeder;

class WhatsappSystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        ProtocolConfig::current();
    }
}
