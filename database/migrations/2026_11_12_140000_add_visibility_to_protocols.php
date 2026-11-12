<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // protocols.visibility: 'private' (só quem tem acesso: criador, responsável, unidade) ou 'public' (todos os usuários).
    // Os protocolos que já existem ficam privados: o padrão da coluna cobre as linhas antigas.
    // access_profiles.protocol_*: o que quem tem o perfil pode cadastrar (null = sem restrição de tipo/status;
    // visibilidades null = só privado) e se pode mudar a visibilidade de um protocolo já cadastrado.
    public function up(): void
    {
        if (! Schema::hasColumn('protocols', 'visibility')) {
            Schema::table('protocols', function (Blueprint $table) {
                $table->string('visibility', 10)->default('private')->after('tipo')->index();
            });
        }

        if (! Schema::hasColumn('access_profiles', 'protocol_allowed_types')) {
            Schema::table('access_profiles', function (Blueprint $table) {
                $table->json('protocol_allowed_types')->nullable();
                $table->json('protocol_allowed_statuses')->nullable();
                $table->json('protocol_allowed_visibilities')->nullable();
                $table->boolean('protocol_can_change_visibility')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('access_profiles', 'protocol_allowed_types')) {
            Schema::table('access_profiles', function (Blueprint $table) {
                $table->dropColumn(['protocol_allowed_types', 'protocol_allowed_statuses', 'protocol_allowed_visibilities', 'protocol_can_change_visibility']);
            });
        }
        if (Schema::hasColumn('protocols', 'visibility')) {
            Schema::table('protocols', function (Blueprint $table) {
                $table->dropIndex(['visibility']);
                $table->dropColumn('visibility');
            });
        }
    }
};
