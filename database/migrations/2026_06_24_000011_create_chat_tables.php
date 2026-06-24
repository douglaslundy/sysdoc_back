<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('direct');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index('last_message_at');
        });

        Schema::create('chat_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id'], 'chat_participant_unique');
            $table->index(['user_id', 'deleted_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('message_type', 20)->default('text');
            $table->string('status', 20)->default('sent');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->string('storage_path');
            $table->timestamps();
        });

        Schema::create('chat_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->date('usage_date')->unique();
            $table->unsignedBigInteger('messages_sent')->default(0);
            $table->unsignedBigInteger('events_published')->default(0);
            $table->unsignedBigInteger('connection_events')->default(0);
            $table->unsignedBigInteger('peak_connections')->default(0);
            $table->unsignedBigInteger('attachments_sent')->default(0);
            $table->unsignedBigInteger('attachment_bytes')->default(0);
            $table->unsignedBigInteger('failed_events')->default(0);
            $table->timestamps();
        });

        Schema::table('user_presences', function (Blueprint $table) {
            $table->string('status', 20)->default('offline')->after('last_path');
            $table->unsignedInteger('connection_count')->default(0)->after('status');
            $table->timestamp('connected_at')->nullable()->after('connection_count');
        });
    }

    public function down(): void
    {
        Schema::table('user_presences', function (Blueprint $table) {
            $table->dropColumn(['status', 'connection_count', 'connected_at']);
        });

        Schema::dropIfExists('chat_usage_daily');
        Schema::dropIfExists('chat_attachments');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversation_participants');
        Schema::dropIfExists('chat_conversations');
    }
};
