<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queue') || Schema::hasColumn('queue', 'done_by')) {
            return;
        }

        Schema::table('queue', function (Blueprint $table) {
            // Usuario que deu a baixa. Registros antigos ficam sem essa informacao.
            $table->unsignedBigInteger('done_by')->nullable()->after('done_at');
            $table->foreign('done_by', 'queue_done_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('queue') || ! Schema::hasColumn('queue', 'done_by')) {
            return;
        }

        Schema::table('queue', function (Blueprint $table) {
            $table->dropForeign('queue_done_by_foreign');
            $table->dropColumn('done_by');
        });
    }
};
