<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicMedicinesAuditThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function hit(string $uri, string $ip = '10.0.0.1'): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])->getJson($uri)->assertOk();
    }

    public function test_painel_publico_consultado_varias_vezes_grava_uma_unica_auditoria_por_visitante(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->hit('/api/public/pharmacy/medicines/panel');
        }

        $this->assertSame(1, AuditLog::where('action', 'VIEW_PUBLIC_MEDICINES_PANEL')->count());
        $this->assertSame('10.0.0.1', AuditLog::where('action', 'VIEW_PUBLIC_MEDICINES_PANEL')->value('ip_address'));
    }

    public function test_visitantes_diferentes_e_paineis_diferentes_sao_registrados_separadamente(): void
    {
        $this->hit('/api/public/pharmacy/medicines/panel', '10.0.0.1');
        $this->hit('/api/public/pharmacy/medicines/panel', '10.0.0.2');
        $this->hit('/api/public/pharmacy/medicines/daily', '10.0.0.1');
        $this->hit('/api/public/pharmacy/medicines/monthly-acquisitions', '10.0.0.1');

        $this->assertSame(2, AuditLog::where('action', 'VIEW_PUBLIC_MEDICINES_PANEL')->count());
        $this->assertSame(1, AuditLog::where('action', 'VIEW_PUBLIC_MEDICINES_DAILY')->count());
        $this->assertSame(1, AuditLog::where('action', 'VIEW_PUBLIC_MEDICINES_MONTHLY')->count());
    }

    public function test_depois_da_janela_volta_a_registrar(): void
    {
        Carbon::setTestNow('2026-09-29 08:00:00');
        $this->hit('/api/public/pharmacy/medicines/panel');
        $this->hit('/api/public/pharmacy/medicines/panel');
        $this->assertSame(1, AuditLog::where('action', 'VIEW_PUBLIC_MEDICINES_PANEL')->count());

        Carbon::setTestNow('2026-09-29 09:00:01');
        $this->hit('/api/public/pharmacy/medicines/panel');
        $this->assertSame(2, AuditLog::where('action', 'VIEW_PUBLIC_MEDICINES_PANEL')->count());
    }
}
