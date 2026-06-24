<?php

namespace Database\Seeders;

use App\Models\NotificationChannelConfig;
use Illuminate\Database\Seeder;

class NotificationChannelConfigSeeder extends Seeder
{
    public function run(): void
    {
        NotificationChannelConfig::current('whatsapp');
        NotificationChannelConfig::current('email');
    }
}
