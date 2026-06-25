<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_realtime_configs', function (Blueprint $table) {
            $table->unsignedTinyInteger('rate_limit_decay_minutes')->default(1)->after('use_tls');
            $table->unsignedSmallInteger('rate_limit_global')->default(300)->after('rate_limit_decay_minutes');
            $table->unsignedSmallInteger('rate_limit_sync')->default(120)->after('rate_limit_global');
            $table->unsignedSmallInteger('rate_limit_messages')->default(30)->after('rate_limit_sync');
            $table->unsignedSmallInteger('rate_limit_typing')->default(60)->after('rate_limit_messages');
            $table->unsignedSmallInteger('rate_limit_presence')->default(60)->after('rate_limit_typing');
        });
    }

    public function down(): void
    {
        Schema::table('chat_realtime_configs', function (Blueprint $table) {
            $table->dropColumn([
                'rate_limit_decay_minutes',
                'rate_limit_global',
                'rate_limit_sync',
                'rate_limit_messages',
                'rate_limit_typing',
                'rate_limit_presence',
            ]);
        });
    }
};
