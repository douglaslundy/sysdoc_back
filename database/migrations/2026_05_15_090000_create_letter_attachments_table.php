<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLetterAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('letter_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('disk', 30)->default('private');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->foreign('letter_id')->references('id')->on('letters')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['letter_id', 'created_at'], 'letter_attachments_letter_created_index');
        });
    }

    public function down()
    {
        Schema::table('letter_attachments', function (Blueprint $table) {
            $table->dropForeign(['letter_id']);
            $table->dropForeign(['uploaded_by']);
        });

        Schema::dropIfExists('letter_attachments');
    }
}
