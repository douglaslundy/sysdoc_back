<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('models', function (Blueprint $table) {
            $table->string('subject', 100)->nullable()->after('model');
            $table->string('sender', 50)->nullable()->after('subject');
            $table->string('recipient', 50)->nullable()->after('sender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('models', function (Blueprint $table) {
            $table->dropColumn('subject');
            $table->dropColumn('sender');
            $table->dropColumn('recipient');
        });
    }
};
