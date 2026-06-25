<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('protocol_views', function (Blueprint $table) {
            $table->string('session_key', 64)->nullable()->after('equipe');
            $table->index(
                ['protocol_id', 'user_id', 'session_key'],
                'prot_views_session_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('protocol_views', function (Blueprint $table) {
            $table->dropIndex('prot_views_session_idx');
            $table->dropColumn('session_key');
        });
    }
};
