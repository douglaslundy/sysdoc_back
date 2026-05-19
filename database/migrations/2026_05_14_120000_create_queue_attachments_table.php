<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('queue_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('queue_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('disk', 30)->default('private');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->foreign('queue_id')->references('id')->on('queue')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['queue_id', 'created_at'], 'queue_attachments_queue_created_index');
        });
    }

    public function down()
    {
        Schema::table('queue_attachments', function (Blueprint $table) {
            $table->dropForeign(['queue_id']);
            $table->dropForeign(['uploaded_by']);
        });

        Schema::dropIfExists('queue_attachments');
    }
}
