<?php

use App\Models\Secretaria;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // A migration anterior criou as páginas mas não as liberou nas secretarias (regra de Secretaria::liberarPaginas).
    public function up(): void
    {
        Secretaria::liberarPaginas(['/sistema/painel-publico', '/sistema/atalhos']);
        Secretaria::liberarPaginas(['/dashboard/atalhos'], Secretaria::pluck('slug')->all());
    }

    public function down(): void
    {
        // No-op: liberação é configuração do admin.
    }
};
