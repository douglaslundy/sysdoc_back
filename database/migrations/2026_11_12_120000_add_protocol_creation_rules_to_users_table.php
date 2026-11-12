<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // null = sem restrição (comportamento atual). Lista = só esses tipos/status ao CRIAR protocolo.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'protocol_allowed_types')) {
                $table->json('protocol_allowed_types')->nullable();
            }
            if (! Schema::hasColumn('users', 'protocol_allowed_statuses')) {
                $table->json('protocol_allowed_statuses')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['protocol_allowed_types', 'protocol_allowed_statuses']);
        });
    }
};
