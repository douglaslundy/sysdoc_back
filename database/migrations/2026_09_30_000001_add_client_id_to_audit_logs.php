<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs') || Schema::hasColumn('audit_logs', 'client_id')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            // Cidadao a que a acao se refere (quando houver) - alimenta o historico do cidadao.
            $table->unsignedBigInteger('client_id')->nullable()->after('model_id');
            $table->index(['client_id', 'id'], 'audit_logs_client_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'client_id')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_client_id_index');
            $table->dropColumn('client_id');
        });
    }
};
