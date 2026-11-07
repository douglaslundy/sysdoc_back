<?php

use Database\Seeders\SecretariaPageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new SecretariaPageSeeder())->run();
    }

    public function down(): void
    {
        // Permissões saem em cascata com a página.
        DB::table('system_pages')->where('path', SecretariaPageSeeder::PATH)->delete();
    }
};
