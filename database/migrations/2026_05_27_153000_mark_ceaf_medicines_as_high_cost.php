<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('medicine_items')
            ->where(function ($query) {
                $query->where('internal_code', 'LIKE', 'MG-CEAF-%')
                    ->orWhere('technical_notes', 'LIKE', '%(CEAF)%');
            })
            ->update(['is_high_cost' => true]);
    }

    public function down(): void
    {
        DB::table('medicine_items')
            ->where(function ($query) {
                $query->where('internal_code', 'LIKE', 'MG-CEAF-%')
                    ->orWhere('technical_notes', 'LIKE', '%(CEAF)%');
            })
            ->update(['is_high_cost' => false]);
    }
};
