<?php

use Database\Seeders\OrdemServicoSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new OrdemServicoSeeder())->run();
    }

    public function down(): void
    {
        DB::table('system_pages')->whereIn('path', array_column(OrdemServicoSeeder::PAGINAS, 'path'))->delete();
        DB::table('access_profiles')->where('slug', OrdemServicoSeeder::TECNICO)->delete();
    }
};
