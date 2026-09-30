<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fiscalizacao_attachments') || Schema::hasColumn('fiscalizacao_attachments', 'origem')) {
            return;
        }

        Schema::table('fiscalizacao_attachments', function (Blueprint $table) {
            $table->string('origem', 20)->default('interno')->after('uploaded_by'); // interno | denunciante
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('fiscalizacao_attachments', 'origem')) {
            Schema::table('fiscalizacao_attachments', function (Blueprint $table) {
                $table->dropColumn('origem');
            });
        }
    }
};
