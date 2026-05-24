<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                if (! $this->hasIndex('clients', 'clients_active_name_index')) {
                    $table->index(['active', 'name'], 'clients_active_name_index');
                }
                if (! $this->hasIndex('clients', 'clients_phone_index')) {
                    $table->index('phone', 'clients_phone_index');
                }
            });
        }

        if (Schema::hasTable('addresses')) {
            Schema::table('addresses', function (Blueprint $table) {
                if (! $this->hasIndex('addresses', 'addresses_id_client_index')) {
                    $table->index('id_client', 'addresses_id_client_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropIndexIfExists('clients_active_name_index');
                $table->dropIndexIfExists('clients_phone_index');
            });
        }

        if (Schema::hasTable('addresses')) {
            Schema::table('addresses', function (Blueprint $table) {
                $table->dropIndexIfExists('addresses_id_client_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = collect(\DB::select("SHOW INDEX FROM `{$table}`"));

        return $indexes->contains('Key_name', $indexName);
    }
};
