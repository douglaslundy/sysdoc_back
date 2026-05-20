<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_aps_configs', function (Blueprint $table) {
            $table->id();
            $table->string('aps_db_host')->default('');
            $table->unsignedSmallInteger('aps_db_port')->default(5432);
            $table->string('aps_db_database')->default('esus');
            $table->string('aps_db_username')->default('');
            $table->string('aps_db_password', 500)->default('');
            $table->string('municipio_ibge', 10)->default('');
            $table->string('municipio_nome')->default('');
            $table->unsignedTinyInteger('estrato_ied')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_aps_configs');
    }
};
