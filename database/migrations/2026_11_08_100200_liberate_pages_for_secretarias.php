<?php

use App\Models\Secretaria;
use Database\Seeders\SecretariaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Páginas transversais (início, kanban, protocolo): liberadas às secretarias que não são Saúde.
    private const TRANSVERSAIS = ['/dashboard', '/dashboard/inicio', '/dashboard/chat', '/kanban', '/protocolo', '/protocolo/%'];

    public function up(): void
    {
        if (! Schema::hasTable('secretaria_pages') || ! Schema::hasTable('system_pages')) {
            return;
        }

        (new SecretariaSeeder())->run(); // garante as 6 secretarias e o vínculo dos usuários à Saúde

        $saude = Secretaria::where('slug', Secretaria::SAUDE_SLUG)->first();
        $saude?->pages()->syncWithoutDetaching(DB::table('system_pages')->pluck('id')->all());

        $ids = DB::table('system_pages')->where(function ($q) {
            foreach (self::TRANSVERSAIS as $path) {
                str_contains($path, '%') ? $q->orWhere('path', 'like', $path) : $q->orWhere('path', $path);
            }
        })->pluck('id')->all();

        Secretaria::whereIn('slug', ['educacao', 'cultura', 'agricultura', 'meio-ambiente', 'obras'])
            ->get()->each(fn (Secretaria $s) => $s->pages()->syncWithoutDetaching($ids));
    }

    public function down(): void
    {
        // No-op: a liberação é configuração do admin e não se distingue do que foi ajustado depois.
    }
};
