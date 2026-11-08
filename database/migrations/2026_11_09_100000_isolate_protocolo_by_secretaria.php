<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const TABELAS = ['protocols', 'kanban_tasks', 'protocol_organizational_units'];

    public function up(): void
    {
        IsolaPorSecretaria::up(self::TABELAS);
    }

    public function down(): void
    {
        // protocols e kanban_tasks já tinham a coluna (nullable) antes desta migration.
        IsolaPorSecretaria::down(self::TABELAS, ['protocols', 'kanban_tasks']);
    }
};
