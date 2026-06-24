<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('action', 40)->default('delete');
            $table->string('status', 30)->default('approved');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('signer_user_ids');
            $table->unsignedInteger('signer_count')->default(3);
            $table->json('snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'action', 'status'], 'document_approvals_doc_action_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};
