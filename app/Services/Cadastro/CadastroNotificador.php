<?php

namespace App\Services\Cadastro;

use App\Models\CadastroSolicitacao;
use App\Models\Protocol;
use App\Models\User;
use App\Services\ConfiguredEmailService;
use App\Services\WhatsappEvolutionService;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Avisos do cadastro público por WhatsApp e e-mail. Nunca lança e nunca leva senha, hash,
 * token, captcha nem CPF no texto. Cada destinatário/canal é isolado: um erro não afeta os outros.
 */
class CadastroNotificador
{
    /**
     * Confirmação ao solicitante: texto fixo e idêntico em todos os ramos da resposta pública
     * (criado, pendente, conta existente), sem nome nem protocolo: não revela se o CPF já existe.
     */
    public function confirmarRecebimento(string $email, ?string $phone): void
    {
        $this->seguro(function () use ($email, $phone) {
            $texto = $this->texto('recebido', []);
            $meta = ['origem' => 'cadastro_recebido'];
            $numero = (new User())->forceFill(['phone' => $phone])->whatsappPhoneNumber();
            if ($numero) {
                $this->seguro(fn () => app(WhatsappEvolutionService::class)->sendTextToNumber($numero, $texto, $meta));
            }
            if (trim($email) !== '') {
                $this->seguro(fn () => app(ConfiguredEmailService::class)->sendText($email, 'Recebemos sua solicitação de cadastro', $texto, null, null, $meta));
            }
        });
    }

    /** Admins ativos + gestores ativos da secretaria do pedido. Sem CPF nem observação no texto. */
    public function novoPedido(CadastroSolicitacao $s): void
    {
        $this->seguro(function () use ($s) {
            $texto = $this->texto('novo-pedido', [
                'nome' => $s->nome, 'cargo' => $s->cargo, 'protocolo' => $this->numero($s),
                'secretaria' => $s->secretaria?->nome ?? '—',
            ]);
            $destinos = User::where('active', true)->where(function ($q) use ($s) {
                $q->where('profile', 'admin')->orWhere(fn ($g) => $g->where('profile', 'gestor')
                    ->whereHas('secretarias', fn ($x) => $x->where('secretarias.id', $s->secretaria_id)));
            })->get();
            $this->avisarUsuarios($destinos, 'Novo pedido de cadastro', $texto, 'cadastro_novo_pedido');
        });
    }

    public function aprovado(CadastroSolicitacao $s, User $user): void
    {
        $this->seguro(fn () => $this->avisarSolicitante($s, 'Seu cadastro foi aprovado', $this->texto('aprovado', []), 'cadastro_aprovado'));
    }

    public function recusado(CadastroSolicitacao $s): void
    {
        $this->seguro(fn () => $this->avisarSolicitante(
            $s, 'Seu pedido de cadastro não foi aprovado', $this->texto('recusado', ['motivo' => (string) $s->motivo_recusa]), 'cadastro_recusado'
        ));
    }

    /** Tentativa pública com CPF/e-mail de conta existente (flag cadastro.alertar_tentativa_conta_ativa). Roda depois da resposta. */
    public function tentativaContaExistente(User $existente): void
    {
        $this->seguro(function () use ($existente) {
            if (! config('cadastro.alertar_tentativa_conta_ativa')) {
                return;
            }
            app()->terminating(function () use ($existente) {
                $this->seguro(function () use ($existente) {
                    // Um aviso por hora por conta existente.
                    if (! Cache::add('cadastro-alerta:'.$existente->id, 1, 3600)) {
                        return;
                    }
                    $this->avisarUsuarios(
                        User::where('profile', 'admin')->where('active', true)->get(),
                        'Tentativa de cadastro com conta existente',
                        "Um pedido público de cadastro usou CPF ou e-mail de uma conta existente (usuário #{$existente->id}). Nenhum pedido foi criado.",
                        'cadastro_publico_conta_existente'
                    );
                });
            });
        });
    }

    private function texto(string $view, array $dados): string
    {
        return trim(view('emails.cadastro-'.$view, $dados)->render());
    }

    private function numero(CadastroSolicitacao $s): ?string
    {
        try {
            return $s->protocol_id ? Protocol::withoutGlobalScopes()->whereKey($s->protocol_id)->value('numero') : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function avisarSolicitante(CadastroSolicitacao $s, string $assunto, string $texto, string $origem): void
    {
        $meta = ['origem' => $origem];
        $numero = (new User())->forceFill(['phone' => $s->phone])->whatsappPhoneNumber();
        if ($numero) {
            $this->seguro(fn () => app(WhatsappEvolutionService::class)->sendTextToNumber($numero, $texto, $meta));
        }
        if (trim((string) $s->email) !== '') {
            $this->seguro(fn () => app(ConfiguredEmailService::class)->sendText($s->email, $assunto, $texto, null, null, $meta));
        }
    }

    private function avisarUsuarios(iterable $usuarios, string $assunto, string $texto, string $origem): void
    {
        $meta = ['origem' => $origem];
        foreach (collect($usuarios)->unique('id') as $u) {
            // Os serviços já registram "sem telefone/e-mail" no MessageLogger.
            $this->seguro(fn () => app(WhatsappEvolutionService::class)->sendTextToUser($u, $texto, $meta));
            $this->seguro(fn () => app(ConfiguredEmailService::class)->sendTextToUser($u, $assunto, $texto, $meta));
        }
    }

    private function seguro(callable $envio): void
    {
        try {
            $envio();
        } catch (Throwable $e) {
            report($e); // o serviço de envio já registra falhas em MessageLogger; aqui só exceções inesperadas
        }
    }
}
