<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegacyMenuCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $dashboardCategoryId = DB::table('page_categories')
            ->where('nome', 'Dashboard')
            ->value('id');

        if ($dashboardCategoryId) {
            DB::table('system_pages')
                ->where('categoria', 'Geral')
                ->update([
                    'categoria' => 'Dashboard',
                    'category_id' => $dashboardCategoryId,
                    'updated_at' => now(),
                ]);
        }

        DB::table('system_pages')
            ->where('path', '/laboratorio/resultados')
            ->delete();

        DB::table('page_categories')
            ->whereIn('nome', ['Geral', 'Sistema'])
            ->delete();
    }
}
