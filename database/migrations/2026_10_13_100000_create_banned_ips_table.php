<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('reason', 255)->nullable();
            $table->string('source', 20)->default('auto'); // auto | manual
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('expires_at')->nullable(); // null = permanente
            $table->timestamps();
        });

        $categoria = DB::table('page_categories')->where('nome', 'Sistema')->first();

        DB::table('system_pages')->updateOrInsert(
            ['path' => '/sistema/ips-banidos'],
            [
                'titulo' => 'IPs banidos',
                'icone' => 'slash',
                'categoria' => 'Sistema',
                'category_id' => $categoria?->id,
                'ordem' => 6,
                'ativo' => true,
                'updated_at' => now(),
            ]
        );

        $profile = DB::table('access_profiles')->where('slug', 'admin')->first();
        $page = DB::table('system_pages')->where('path', '/sistema/ips-banidos')->first();

        if ($profile && $page) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $profile->id, 'system_page_id' => $page->id],
                ['updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_ips');
    }
};
