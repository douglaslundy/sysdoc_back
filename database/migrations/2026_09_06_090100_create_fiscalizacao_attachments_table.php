<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscalizacao_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fiscalizacao_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('disk', 30)->default('private');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->foreign('fiscalizacao_id')->references('id')->on('fiscalizacoes')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['fiscalizacao_id', 'created_at'], 'fiscalizacao_attachments_fisc_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscalizacao_attachments');
    }
};
