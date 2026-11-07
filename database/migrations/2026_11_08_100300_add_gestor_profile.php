<?php

use Database\Seeders\GestorProfileSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new GestorProfileSeeder())->run();
    }

    public function down(): void
    {
        // Permissões saem em cascata com o perfil.
        DB::table('access_profiles')->where('slug', GestorProfileSeeder::SLUG)->delete();
    }
};
