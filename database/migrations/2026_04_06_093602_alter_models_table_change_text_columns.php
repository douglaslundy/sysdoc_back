<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('models', function (Blueprint $table) {
            $table->longText('prompt')->nullable()->change();
            $table->longText('model')->nullable()->change();
            $table->text('summary')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('models', function (Blueprint $table) {
            $table->string('prompt', 2000)->nullable()->change();
            $table->string('summary', 500)->nullable()->change();
            $table->string('model', 3000)->nullable()->change();
        });
    }
};