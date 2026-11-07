<?php

namespace App\Services\Cadastro;

use App\Models\CadastroSolicitacao;
use App\Models\User;
use App\Services\ConfiguredEmailService;
use App\Services\WhatsappEvolutionService;
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
        $digitos = preg_replace('/\D/', '', (string) $dados['cpf']);
        $formatado = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digitos);
        $email = (string) $dados['email'];

        $porCpf = User::whereIn('cpf', [$digitos, $formatado])->first();
        $porEmail = User::where('email', $email)->first();
        $pendente = CadastroSolicitacao::where('status', 'pendente')
            ->where(fn ($q) => $q->where('cpf', $digitos)->orWhere('email', $email))
            ->exists();

        // Conta ativa com o CPF, ou e-mail de OUTRA conta: nada é criado. CPF de conta inativa segue (reativação).
        $bloqueado = ($porCpf && $porCpf->active) || ($porEmail && (! $porCpf || $porEmail->id !== $porCpf->id));

        if ($bloqueado) {
            // Depois da resposta, para o envio do alerta não virar diferença de tempo.
            $existente = $porCpf ?? $porEmail;
            app()->terminating(fn () => $this->alertarAdmins($existente));

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
