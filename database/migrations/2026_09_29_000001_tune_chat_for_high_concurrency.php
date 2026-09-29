<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contagem de nao lidas e "ultima mensagem por conversa" filtram por
        // conversation_id + read_at; sem indice viram varredura por conversa.
        if (Schema::hasTable('chat_messages') && ! $this->hasIndex('chat_messages', 'chat_messages_unread_idx')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->index(['conversation_id', 'read_at', 'sender_id'], 'chat_messages_unread_idx');
            });
        }

        // A varredura de conexoes vencidas filtra apenas por last_seen_at.
        if (Schema::hasTable('chat_connections') && ! $this->hasIndex('chat_connections', 'chat_connections_last_seen_idx')) {
            Schema::table('chat_connections', function (Blueprint $table) {
                $table->index('last_seen_at', 'chat_connections_last_seen_idx');
            });
        }

        // O limite de sincronizacao (120/min) era apertado para ~100 usuarios com chat
        // ativo. So sobe quem ainda esta no valor padrao antigo; valores customizados
        // pelo administrador sao preservados.
        if (Schema::hasTable('chat_realtime_configs') && Schema::hasColumn('chat_realtime_configs', 'rate_limit_sync')) {
            DB::table('chat_realtime_configs')->where('rate_limit_sync', 120)->update(['rate_limit_sync' => 300]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_messages') && $this->hasIndex('chat_messages', 'chat_messages_unread_idx')) {
            Schema::table('chat_messages', fn (Blueprint $table) => $table->dropIndex('chat_messages_unread_idx'));
        }

        if (Schema::hasTable('chat_connections') && $this->hasIndex('chat_connections', 'chat_connections_last_seen_idx')) {
            Schema::table('chat_connections', fn (Blueprint $table) => $table->dropIndex('chat_connections_last_seen_idx'));
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index])) > 0;
    }
};
