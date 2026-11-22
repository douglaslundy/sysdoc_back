<?php

use Database\Seeders\OuvidoriaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new OuvidoriaSeeder())->run();
    }

    public function down(): void
    {
        DB::table('system_pages')->where('path', OuvidoriaSeeder::PAGINA)->delete();
        DB::table('access_profiles')->where('slug', OuvidoriaSeeder::OUVIDOR)->delete();
    }
};
