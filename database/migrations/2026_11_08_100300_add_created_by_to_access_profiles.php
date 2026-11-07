<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('access_profiles', 'created_by_id')) {
            Schema::table('access_profiles', function (Blueprint $table) {
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('access_profiles', 'created_by_id')) {
            Schema::table('access_profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by_id');
            });
        }
    }
};
