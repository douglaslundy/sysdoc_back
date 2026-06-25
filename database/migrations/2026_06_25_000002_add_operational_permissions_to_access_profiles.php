<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_profiles', function (Blueprint $table) {
            $table->boolean('chat_enabled')->default(false)->after('ativo');
            $table->boolean('almoxarifado_create_enabled')->default(true)->after('chat_enabled');
            $table->boolean('almoxarifado_approve_enabled')->default(false)->after('almoxarifado_create_enabled');
            $table->boolean('almoxarifado_deliver_enabled')->default(false)->after('almoxarifado_approve_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('access_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'chat_enabled',
                'almoxarifado_create_enabled',
                'almoxarifado_approve_enabled',
                'almoxarifado_deliver_enabled',
            ]);
        });
    }
};
