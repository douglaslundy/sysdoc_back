<?php

namespace App\Services\Cadastro;

use App\Models\CadastroSolicitacao;
use App\Models\User;
use App\Services\ConfiguredEmailService;
use App\Services\WhatsappEvolutionService;
use App\Support\Cpf;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Throwable;

class CadastroSolicitacaoService
{
    /**
     * A resposta ao chamador é sempre a mesma: não revela se CPF/e-mail já existem.
     * Mesmo trabalho em todos os casos (hash + as mesmas consultas) antes de decidir.
     */
    public function registrar(array $dados): void
    {
        $hash = Hash::make((string) $dados['password']);
        $digitos = Cpf::digits((string) $dados['cpf']);
        $email = (string) $dados['email'];

        try {
            // Trava por CPF: duas requisições simultâneas não criam dois pedidos pendentes.
            Cache::lock('cadastro:cpf:'.$digitos, 10)->block(5, fn () => $this->criarSeCabivel($dados, $digitos, $email, $hash));
        } catch (LockTimeoutException) {
            // Trava ocupada: responde igual, sem criar (o outro pedido em andamento vale).
        }
    }

    private function criarSeCabivel(array $dados, string $digitos, string $email, string $hash): void
    {
        $porCpf = User::whereCpfIgual($digitos)->first();
        $porEmail = User::where('email', $email)->first();
        $pendente = CadastroSolicitacao::where('status', 'pendente')
            ->where(fn ($q) => $q->whereCpfIgual($digitos)->orWhere('email', $email))
            ->exists();

        // Conta ativa com o CPF, ou e-mail de OUTRA conta: nada é criado. CPF de conta inativa segue (reativação).
        $bloqueado = ($porCpf && $porCpf->active) || ($porEmail && (! $porCpf || $porEmail->id !== $porCpf->id));

        if ($bloqueado) {
            if (config('cadastro.alertar_tentativa_conta_ativa')) {
                // Depois da resposta (FPM), para o envio do alerta não virar diferença de tempo.
                $existente = $porCpf ?? $porEmail;
                app()->terminating(function () use ($existente) {
                    // Um aviso por hora por conta existente.
                    if (Cache::add('cadastro-alerta:'.$existente->id, 1, 3600)) {
                        $this->alertarAdmins($existente);
                    }
                });
            }

            return;
        }
        if ($pendente) {
            return;
        }

        CadastroSolicitacao::create([
            'nome' => $dados['name'],
            'preferred_name' => $dados['preferred_name'] ?? null,
            'email' => $email,
            'cpf' => $digitos,
            'phone' => $dados['phone'] ?? null,
            'password_hash' => $hash,
            'secretaria_id' => $dados['secretaria_id'],
            'cargo' => $dados['cargo'],
            'observacao' => $dados['observacao'] ?? null,
        ]);
    }

    /** Nunca lança. Não inclui dados digitados pelo solicitante. */
    private function alertarAdmins(User $existente): void
    {
        $assunto = 'Tentativa de cadastro com conta existente';
        $mensagem = "Um pedido público de cadastro usou CPF ou e-mail de uma conta existente (usuário #{$existente->id}). Nenhum pedido foi criado.";
        $meta = ['origem' => 'cadastro_publico_conta_existente'];

        try {
            $admins = User::where('profile', 'admin')->where('active', true)->get();
        } catch (Throwable $e) {
            report($e);

            return;
        }

        foreach ($admins as $admin) {
            try {
                app(WhatsappEvolutionService::class)->sendTextToUser($admin, $mensagem, $meta);
            } catch (Throwable $e) {
                report($e);
            }
            try {
                app(ConfiguredEmailService::class)->sendTextToUser($admin, $assunto, $mensagem, $meta);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
