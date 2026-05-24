<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('queue')) {
            Schema::table('queue', function (Blueprint $table) {
                if (! $this->hasIndex('queue', 'queue_list_position_index')) {
                    $table->index(['done', 'urgency', 'id_specialities', 'created_at', 'id'], 'queue_list_position_index');
                }
                if (! $this->hasIndex('queue', 'queue_speciality_status_index')) {
                    $table->index(['id_specialities', 'done', 'created_at', 'id'], 'queue_speciality_status_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('queue')) {
            Schema::table('queue', function (Blueprint $table) {
                $table->dropIndexIfExists('queue_list_position_index');
                $table->dropIndexIfExists('queue_speciality_status_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = collect(\DB::select("SHOW INDEX FROM `{$table}`"));

        return $indexes->contains('Key_name', $indexName);
    }
};
