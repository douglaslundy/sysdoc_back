<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RtAccessFixSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('access_profiles')->updateOrInsert(
            ['slug' => 'rt'],
            [
                'nome' => 'Enfermeiro PSF',
                'descricao' => 'Acesso técnico à rotina de acompanhamento eSUS',
                'ativo' => true,
                'updated_at' => $now,
            ]
        );

        $profileId = DB::table('access_profiles')->where('slug', 'rt')->value('id');
        if (! $profileId) {
            return;
        }

        $paths = [
            '/painel-esus',
            '/client_report',
            '/painel-esus/statuses',
        ];

        foreach ($paths as $path) {
            $pageId = DB::table('system_pages')->where('path', $path)->value('id');
            if (! $pageId) {
                continue;
            }

            DB::table('profile_page_permissions')->updateOrInsert(
                [
                    'access_profile_id' => $profileId,
                    'system_page_id' => $pageId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
