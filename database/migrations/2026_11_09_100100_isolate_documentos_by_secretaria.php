<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        IsolaPorSecretaria::up(['documents']);
    }

    public function down(): void
    {
        IsolaPorSecretaria::down(['documents']);
    }
};
