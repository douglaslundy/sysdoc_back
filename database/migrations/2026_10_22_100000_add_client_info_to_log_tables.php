<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tabelas de log que passam a guardar o dispositivo (client_info) e a localização do IP (ip_origin). */
    private const TABLES = ['audit_logs', 'error_logs', 'qrcode_logs', 'public_queue_logs'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            // hasColumn: em bancos onde as colunas já foram criadas à mão a migration não pode falhar.
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (! Schema::hasColumn($name, 'ip_origin')) {
                    $table->string('ip_origin', 255)->nullable();
                }
                if (! Schema::hasColumn($name, 'client_info')) {
                    $table->json('client_info')->nullable(); // navegador, SO, aparelho, robô, idioma, proxies etc.
                }
            });
        }

        if (! Schema::hasColumn('banned_ips', 'client_info')) {
            Schema::table('banned_ips', function (Blueprint $table) {
                $table->json('client_info')->nullable(); // dispositivo/ferramenta que fez a varredura
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['ip_origin', 'client_info']));
        }

        Schema::table('banned_ips', fn (Blueprint $table) => $table->dropColumn('client_info'));
    }
};
