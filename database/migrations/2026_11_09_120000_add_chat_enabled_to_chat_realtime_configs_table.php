<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chat_realtime_configs', 'chat_enabled')) {
            Schema::table('chat_realtime_configs', function (Blueprint $table) {
                // Interruptor geral: desligado, ninguem (nem admin) usa o chat.
                $table->boolean('chat_enabled')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chat_realtime_configs', 'chat_enabled')) {
            Schema::table('chat_realtime_configs', function (Blueprint $table) {
                $table->dropColumn('chat_enabled');
            });
        }
    }
};
