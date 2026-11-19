<?php

namespace App\Services\Cadastro;

use App\Models\CadastroSolicitacao;
use App\Models\Protocol;
use App\Models\User;
use App\Services\Alerts\MensagemDoSistema;
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
            $msg = MensagemDoSistema::montar('cadastros', 'cadastro_recebido');
            if (! $msg) {
                return;
            }
            $texto = $msg['texto'];
            $meta = ['origem' => 'cadastro_recebido'];
            $numero = (new User())->forceFill(['phone' => $phone])->whatsappPhoneNumber();
            if ($numero) {
                $this->seguro(fn () => app(WhatsappEvolutionService::class)->sendTextToNumber($numero, $texto, $meta));
            }
            if (trim($email) !== '') {
                $this->seguro(fn () => app(ConfiguredEmailService::class)->sendText($email, (string) $msg['assunto'], $texto, null, null, $meta));
            }
        });
    }

    /** Admins ativos + gestores ativos da secretaria do pedido. Sem CPF nem observação no texto. */
    public function novoPedido(CadastroSolicitacao $s): void
    {
        $this->seguro(function () use ($s) {
            $protocolo = $this->numero($s);
            $msg = MensagemDoSistema::montar('cadastros', 'cadastro_novo_pedido', [
                'nome' => $s->nome, 'cargo' => $s->cargo, 'protocolo' => $protocolo,
                'protocolo_sufixo' => $protocolo ? ' (protocolo '.$protocolo.')' : '',
                'secretaria' => $s->secretaria?->nome ?? '—',
            ]);
            if (! $msg) {
                return;
            }
            $texto = $msg['texto'];
            $destinos = User::where('active', true)->where(function ($q) use ($s) {
                $q->where('profile', 'admin')->orWhere(fn ($g) => $g->withProfile('gestor')
                    ->whereHas('secretarias', fn ($x) => $x->where('secretarias.id', $s->secretaria_id)));
            })->get();
            $this->avisarUsuarios($destinos, (string) $msg['assunto'], $texto, 'cadastro_novo_pedido');
        });
    }

    public function aprovado(CadastroSolicitacao $s, User $user): void
    {
        $this->seguro(function () use ($s) {
            if ($msg = MensagemDoSistema::montar('cadastros', 'cadastro_aprovado')) {
                $this->avisarSolicitante($s, (string) $msg['assunto'], $msg['texto'], 'cadastro_aprovado');
            }
        });
    }

    public function recusado(CadastroSolicitacao $s): void
    {
        $this->seguro(function () use ($s) {
            if ($msg = MensagemDoSistema::montar('cadastros', 'cadastro_recusado', ['motivo' => (string) $s->motivo_recusa])) {
                $this->avisarSolicitante($s, (string) $msg['assunto'], $msg['texto'], 'cadastro_recusado');
            }
        });
    }

    /** Tentativa pública com CPF/e-mail de conta existente (flag cadastro.alertar_tentativa_conta_ativa). Chamar já no pós-resposta, depois da confirmação. */
    public function tentativaContaExistente(User $existente): void
    {
        $this->seguro(function () use ($existente) {
            // Um aviso por hora por conta existente.
            if (! config('cadastro.alertar_tentativa_conta_ativa') || ! Cache::add('cadastro-alerta:'.$existente->id, 1, 3600)) {
                return;
            }
            if ($msg = MensagemDoSistema::montar('cadastros', 'cadastro_conta_existente', ['usuario_id' => $existente->id])) {
                $this->avisarUsuarios(
                    User::where('profile', 'admin')->where('active', true)->get(),
                    (string) $msg['assunto'],
                    $msg['texto'],
                    'cadastro_publico_conta_existente'
                );
            }
        });
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
