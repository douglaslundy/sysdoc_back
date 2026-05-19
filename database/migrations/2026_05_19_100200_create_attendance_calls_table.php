<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_calls', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('attendance_ticket_id');
            $table->foreign('attendance_ticket_id')->references('id')->on('attendance_tickets')->onDelete('cascade');

            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('room_id');
            $table->foreign('room_id')->references('id')->on('attendance_rooms');

            $table->dateTime('called_at');
            $table->timestamps();

            $table->index('called_at');
            $table->index('client_id');
            $table->index('user_id');
            $table->index('room_id');
            $table->index('attendance_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_calls');
    }
};
