<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('protocol_configs', function (Blueprint $table) {
            $table->string('whatsapp_base_url', 255)->nullable()->after('evolution_enabled');
            $table->text('whatsapp_api_key')->nullable()->after('whatsapp_base_url');
            $table->string('whatsapp_instance_name', 120)->nullable()->after('whatsapp_api_key');
            $table->text('whatsapp_instance_token')->nullable()->after('whatsapp_instance_name');
            $table->boolean('whatsapp_ativo')->default(false)->after('whatsapp_instance_token');
        });
    }

    public function down(): void
    {
        Schema::table('protocol_configs', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_base_url',
                'whatsapp_api_key',
                'whatsapp_instance_name',
                'whatsapp_instance_token',
                'whatsapp_ativo',
            ]);
        });
    }
};
