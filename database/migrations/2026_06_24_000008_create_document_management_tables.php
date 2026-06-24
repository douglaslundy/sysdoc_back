<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('resumo')->nullable();
            $table->string('sigilo', 30)->default('publico');
            $table->string('status', 30)->default('rascunho');
            $table->unsignedInteger('current_version_number')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sigilo', 'status'], 'documents_sigilo_status_idx');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 30)->default('private');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->boolean('encrypted')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['document_id', 'version_number'], 'document_versions_unique');
            $table->index(['document_id', 'created_at'], 'document_versions_doc_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
    }
};
