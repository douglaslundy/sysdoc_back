<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM original só tinha LOGIN, LOGOUT, CREATE, UPDATE, DELETE.
        // VIEW, VIEW_REPORT, LIBERAR, DOWNLOAD eram rejeitados silenciosamente.
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(30) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('LOGIN','LOGOUT','CREATE','UPDATE','DELETE','VIEW','VIEW_REPORT','LIBERAR','DOWNLOAD') NOT NULL");
    }
};
