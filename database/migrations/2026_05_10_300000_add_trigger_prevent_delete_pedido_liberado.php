<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_soft_delete_pedido_liberado');

        DB::unprepared("
            CREATE TRIGGER prevent_soft_delete_pedido_liberado
            BEFORE UPDATE ON pedidos_exame
            FOR EACH ROW
            BEGIN
                IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL AND OLD.status = 'liberado' THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Pedido liberado não pode ser excluído.';
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_soft_delete_pedido_liberado');
    }
};
