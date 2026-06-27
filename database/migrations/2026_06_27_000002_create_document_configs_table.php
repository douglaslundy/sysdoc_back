<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('triple_signature_enabled')->default(false);
            $table->json('triple_signature_sigilos')->nullable();
            $table->foreignId('signer_user_1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signer_user_2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signer_user_3_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_configs');
    }
};
