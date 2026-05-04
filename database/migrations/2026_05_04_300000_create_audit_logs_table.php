<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 100)->default('Sistema');
            $table->enum('action', ['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE']);
            $table->string('model_type', 60)->nullable()->index();
            $table->unsignedBigInteger('model_id')->nullable()->index();
            $table->string('endpoint', 200);
            $table->string('method', 10);
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
