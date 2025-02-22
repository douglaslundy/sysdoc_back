<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddUuidToQueueTable extends Migration
{
    public function up()
    {
        // 1. Adiciona a coluna 'uuid' como nullable
        Schema::table('queue', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // 2. Atualiza os registros existentes com um UUID único
        // Importante: use o modelo ou query builder para garantir que cada registro receba um valor único
        \DB::table('queue')->whereNull('uuid')->chunkById(100, function ($queues) {
            foreach ($queues as $queue) {
                \DB::table('queue')
                    ->where('id', $queue->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        });

        // 3. Altera a coluna para não ser mais nullable e adiciona a restrição unique
        Schema::table('queue', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('queue', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
}
