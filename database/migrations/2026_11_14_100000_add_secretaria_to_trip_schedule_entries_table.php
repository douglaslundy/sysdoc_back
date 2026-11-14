<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;

// Em produção a tabela foi criada antes do isolamento por secretaria (sem a coluna).
return new class extends Migration
{
    public function up(): void
    {
        IsolaPorSecretaria::up(['trip_schedule_entries']);
    }

    public function down(): void
    {
        // Mantém a coluna: a migration de criação nova já a inclui.
    }
};
