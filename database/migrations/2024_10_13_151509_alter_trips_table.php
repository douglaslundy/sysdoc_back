<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE `trips` MODIFY `user_id` BIGINT UNSIGNED NULL, MODIFY `vehicle_id` BIGINT UNSIGNED NULL, MODIFY `departure_time` TIMESTAMP NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("ALTER TABLE `trips` MODIFY `user_id` BIGINT UNSIGNED NOT NULL, MODIFY `vehicle_id` BIGINT UNSIGNED NOT NULL, MODIFY `departure_time` TIMESTAMP NOT NULL");
    }
};
