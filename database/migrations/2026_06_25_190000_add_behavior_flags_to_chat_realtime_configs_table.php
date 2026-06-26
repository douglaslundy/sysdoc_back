<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_realtime_configs', function (Blueprint $table) {
            $table->boolean('auto_open_on_message')->default(false)->after('rate_limit_presence');
            $table->boolean('play_sound_on_message')->default(true)->after('auto_open_on_message');
        });
    }

    public function down(): void
    {
        Schema::table('chat_realtime_configs', function (Blueprint $table) {
            $table->dropColumn([
                'auto_open_on_message',
                'play_sound_on_message',
            ]);
        });
    }
};
