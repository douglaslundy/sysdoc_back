<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // trips — usada em filtros por departure_date, driver_id, route_id
        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                if (!$this->hasIndex('trips', 'trips_departure_date_index')) {
                    $table->index('departure_date', 'trips_departure_date_index');
                }
                if (!$this->hasIndex('trips', 'trips_driver_id_index')) {
                    $table->index('driver_id', 'trips_driver_id_index');
                }
                if (!$this->hasIndex('trips', 'trips_route_id_index')) {
                    $table->index('route_id', 'trips_route_id_index');
                }
            });
        }

        // trip_clients — usada em JOINs com trips por trip_id
        if (Schema::hasTable('trip_clients')) {
            Schema::table('trip_clients', function (Blueprint $table) {
                if (!$this->hasIndex('trip_clients', 'trip_clients_trip_id_index')) {
                    $table->index('trip_id', 'trip_clients_trip_id_index');
                }
            });
        }

        // queue — filtrada por done (boolean) e created_at
        if (Schema::hasTable('queue')) {
            Schema::table('queue', function (Blueprint $table) {
                if (!$this->hasIndex('queue', 'queue_done_index')) {
                    $table->index('done', 'queue_done_index');
                }
                if (!$this->hasIndex('queue', 'queue_created_at_index')) {
                    $table->index('created_at', 'queue_created_at_index');
                }
            });
        }

        // qrcode_logs — full table scan no count() total + filtro por accessed_at
        if (Schema::hasTable('qrcode_logs')) {
            Schema::table('qrcode_logs', function (Blueprint $table) {
                if (!$this->hasIndex('qrcode_logs', 'qrcode_logs_accessed_at_index')) {
                    $table->index('accessed_at', 'qrcode_logs_accessed_at_index');
                }
            });
        }

        // public_queue_logs — mesmo problema do qrcode_logs
        if (Schema::hasTable('public_queue_logs')) {
            Schema::table('public_queue_logs', function (Blueprint $table) {
                if (!$this->hasIndex('public_queue_logs', 'public_queue_logs_accessed_at_index')) {
                    $table->index('accessed_at', 'public_queue_logs_accessed_at_index');
                }
            });
        }

        // pedidos_exame — filtrado por deleted_at e status
        if (Schema::hasTable('pedidos_exame')) {
            Schema::table('pedidos_exame', function (Blueprint $table) {
                if (!$this->hasIndex('pedidos_exame', 'pedidos_exame_deleted_at_index')) {
                    $table->index('deleted_at', 'pedidos_exame_deleted_at_index');
                }
                if (!$this->hasIndex('pedidos_exame', 'pedidos_exame_status_index')) {
                    $table->index('status', 'pedidos_exame_status_index');
                }
                if (!$this->hasIndex('pedidos_exame', 'pedidos_exame_created_at_index')) {
                    $table->index('created_at', 'pedidos_exame_created_at_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->dropIndexIfExists('trips_departure_date_index');
                $table->dropIndexIfExists('trips_driver_id_index');
                $table->dropIndexIfExists('trips_route_id_index');
            });
        }

        if (Schema::hasTable('trip_clients')) {
            Schema::table('trip_clients', function (Blueprint $table) {
                $table->dropIndexIfExists('trip_clients_trip_id_index');
            });
        }

        if (Schema::hasTable('queue')) {
            Schema::table('queue', function (Blueprint $table) {
                $table->dropIndexIfExists('queue_done_index');
                $table->dropIndexIfExists('queue_created_at_index');
            });
        }

        if (Schema::hasTable('qrcode_logs')) {
            Schema::table('qrcode_logs', function (Blueprint $table) {
                $table->dropIndexIfExists('qrcode_logs_accessed_at_index');
            });
        }

        if (Schema::hasTable('public_queue_logs')) {
            Schema::table('public_queue_logs', function (Blueprint $table) {
                $table->dropIndexIfExists('public_queue_logs_accessed_at_index');
            });
        }

        if (Schema::hasTable('pedidos_exame')) {
            Schema::table('pedidos_exame', function (Blueprint $table) {
                $table->dropIndexIfExists('pedidos_exame_deleted_at_index');
                $table->dropIndexIfExists('pedidos_exame_status_index');
                $table->dropIndexIfExists('pedidos_exame_created_at_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = collect(\DB::select("SHOW INDEX FROM `{$table}`"));
        return $indexes->contains('Key_name', $indexName);
    }
};
