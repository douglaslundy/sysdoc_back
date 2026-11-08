<?php

namespace App\Services\Cadastro;

use App\Models\AccessProfile;
use App\Models\CadastroSolicitacao;
use App\Models\KanbanTask;
use App\Models\Protocol;
use App\Models\ProtocolMovement;
use App\Models\Scopes\CadastroProtocoloScope;
use App\Models\Secretaria;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Secretarias\GestorAlert;
use App\Support\Cpf;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
            app(CadastroNotificador::class)->tentativaContaExistente($porCpf ?? $porEmail);

            return;
        }
        if ($pendente) {
            return;
        }

        // Pedido + protocolo + card juntos: se algo falhar, nada fica pela metade (a resposta pública não muda em caso de sucesso).
        $pedido = DB::transaction(function () use ($dados, $digitos, $email, $hash) {
            $pedido = CadastroSolicitacao::create([
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
            app(CadastroProtocoloService::class)->abrir($pedido);

            return $pedido;
        });

        // Depois da resposta (e da transação): sem diferença de tempo entre os ramos da resposta pública.
        app()->terminating(function () use ($pedido) {
            app(CadastroNotificador::class)->pedidoRecebido($pedido);
            app(CadastroNotificador::class)->novoPedido($pedido);
        });
    }

    /**
     * Aprova o pedido: cria a conta (ou reativa a inativa do mesmo CPF) com o perfil e as secretarias escolhidos.
     * Tudo em uma transação com trava no pedido. A secretaria do pedido passa a ser a primeira de $secretariaIds
     * (protocolo e card acompanham, para os gestores certos enxergarem o registro encerrado).
     *
     * @param  int[]  $secretariaIds
     */
    public function aprovar(CadastroSolicitacao $s, User $por, string $perfilSlug, array $secretariaIds): User
    {
        $ids = collect($secretariaIds)->map(fn ($i) => (int) $i)->unique()->values()->all();

        $user = DB::transaction(function () use ($s, $por, $perfilSlug, $ids) {
            $s = $this->pedidoTravado($s, $por);
            $perfilSlug = $this->validarEscolhas($por, $perfilSlug, $ids);

            $contas = User::whereCpfIgual((string) $s->cpf)->lockForUpdate()->get();
            if ($contas->contains('active', true)) {
                abort(409, 'Já existe conta ativa com este CPF.');
            }
            $existente = $contas->first();

            $emailEmUso = User::where('email', $s->email)
                ->when($existente, fn ($q) => $q->where('id', '<>', $existente->id))
                ->exists();
            if ($emailEmUso) {
                throw ValidationException::withMessages(['email' => 'Já existe um usuário cadastrado com este e-mail.']);
            }

            $dados = [
                'name' => $s->nome, 'preferred_name' => $s->preferred_name, 'phone' => $s->phone,
                'email' => $s->email, 'profile' => $perfilSlug,
                'password' => $s->password_hash, // já é bcrypt: não faz hash de novo
                'active' => true, 'inactive_date' => null,
            ];

            if ($existente) {
                if ($por->profile === 'gestor') {
                    // Mesma trava de UserRequest::reactivatableUser (Task 5).
                    $vinculos = $existente->secretarias()->pluck('secretarias.id');
                    $divide = $vinculos->intersect($por->secretarias()->pluck('secretarias.id'))->isNotEmpty();
                    if ($existente->profile === 'admin' || ($vinculos->isNotEmpty() && ! $divide)) {
                        abort(403, 'Esta conta só pode ser reativada por um administrador.');
                    }
                }
                // Reativação não herda nada da conta antiga além do registro.
                $existente->forceFill($dados + [
                    'is_driver' => false, 'is_rt_psf' => false, 'rt_all_teams' => false, 'chat_access_override' => null,
                ])->save();
                $existente->protocolUnits()->update(['ativo' => false]);
                $existente->tokens()->delete();
                // Permissões clínicas e equipes APS da conta antiga não valem para o novo pedido.
                DB::table('user_speciality_permissions')->where('user_id', $existente->id)->delete();
                DB::table('user_equipe_aps')->where('user_id', $existente->id)->delete();
                $user = $existente;
            } else {
                $user = new User();
                $user->forceFill($dados + ['cpf' => $s->cpf])->save();
            }

            // Substitui TODOS os vínculos (inclusive os de fora do alcance do gestor): sem herdar acesso de outra secretaria.
            $user->secretarias()->sync($ids);

            $this->encerrarRegistros($s, $por, $ids[0], 'Cadastro aprovado por '.$por->name.'.');
            $s->forceFill([
                'status' => 'aprovada', 'user_id' => $user->id, 'secretaria_id' => $ids[0],
                'decidido_por_id' => $por->id, 'decidido_em' => now(), 'password_hash' => null,
            ])->save();
            AuditService::record('CADASTRO_APROVADO', null, null, [
                'solicitacao_id' => $s->id, 'user_id' => $user->id, 'perfil' => $perfilSlug,
                'secretaria_ids' => $ids, 'reativado' => (bool) $existente,
            ], $por);

            return $user;
        });

        if ($por->profile === 'gestor' && $user->profile === 'gestor') {
            GestorAlert::perfilGestorAtribuido($por, $user);
        }
        $this->notificarAprovado($s, $user);

        return $user;
    }

    public function recusar(CadastroSolicitacao $s, User $por, string $motivo): void
    {
        DB::transaction(function () use ($s, $por, $motivo) {
            $s = $this->pedidoTravado($s, $por);
            $this->encerrarRegistros($s, $por, (int) $s->secretaria_id, 'Cadastro recusado: '.$motivo);
            $s->forceFill([
                'status' => 'recusada', 'motivo_recusa' => $motivo,
                'decidido_por_id' => $por->id, 'decidido_em' => now(), 'password_hash' => null,
            ])->save();
            AuditService::record('CADASTRO_RECUSADO', null, null, ['solicitacao_id' => $s->id], $por);
        });

        $this->notificarRecusado($s, $motivo);
    }

    /** Roda depois do commit; nunca lança. */
    protected function notificarAprovado(CadastroSolicitacao $s, User $user): void
    {
        app(CadastroNotificador::class)->aprovado($s, $user);
    }

    /** Roda depois do commit; nunca lança. */
    protected function notificarRecusado(CadastroSolicitacao $s, string $motivo): void
    {
        $s->motivo_recusa = $motivo;
        app(CadastroNotificador::class)->recusado($s);
    }

    /** Relê o pedido com trava; só um pendente, e só para quem pode abrir o pedido (404 se não). */
    private function pedidoTravado(CadastroSolicitacao $s, User $por): CadastroSolicitacao
    {
        $s = CadastroSolicitacao::lockForUpdate()->find($s->id);
        abort_unless($s && CadastroProtocoloScope::pode($por, $s->secretaria_id), 404, 'Pedido não encontrado.');
        abort_unless($s->status === 'pendente', 409, 'Este pedido já foi decidido.');

        return $s;
    }

    /** Devolve o slug canônico do perfil (a coluna é case-insensitive: 'Admin' casaria com 'admin'). */
    private function validarEscolhas(User $por, string $perfilSlug, array $ids): string
    {
        $erros = [];
        $perfilSlug = AccessProfile::where('slug', trim($perfilSlug))->where('ativo', true)->value('slug');
        if ($perfilSlug === null) {
            $erros['perfil_slug'] = 'Perfil inexistente ou inativo.';
        } elseif ($por->profile !== 'admin' && $perfilSlug === 'admin') {
            $erros['perfil_slug'] = 'Somente administrador atribui o perfil de administrador.';
        }

        if ($ids === []) {
            $erros['secretaria_ids'] = 'Informe ao menos uma secretaria.';
        } elseif (Secretaria::whereIn('id', $ids)->where('ativa', true)->count() !== count($ids)) {
            $erros['secretaria_ids'] = 'Secretaria inexistente ou inativa.';
        } elseif ($por->profile !== 'admin'
            && array_diff($ids, $por->secretarias()->pluck('secretarias.id')->all()) !== []) {
            $erros['secretaria_ids'] = 'Você só pode vincular secretarias das quais faz parte.';
        }

        if ($erros) {
            throw ValidationException::withMessages($erros);
        }

        return $perfilSlug;
    }

    /** Fecha protocolo (encerrado, com movimentação) e card (concluído) e move ambos para a secretaria final. */
    private function encerrarRegistros(CadastroSolicitacao $s, User $por, int $secretariaId, string $justificativa): void
    {
        $protocolo = $s->protocol_id ? Protocol::withoutGlobalScopes()->lockForUpdate()->find($s->protocol_id) : null;
        if (! $protocolo) {
            return;
        }

        $anterior = $protocolo->status;
        $protocolo->forceFill([
            'status' => 'encerrado', 'encerrado_em' => now(), 'novo' => false,
            'justificativa_encerramento' => mb_substr($justificativa, 0, 1000), 'secretaria_id' => $secretariaId,
        ])->save();
        ProtocolMovement::create([
            'protocol_id' => $protocolo->id, 'acao' => 'encerrado', 'status_anterior' => $anterior,
            'status_novo' => 'encerrado', 'observacao' => $justificativa, 'user_id' => $por->id,
        ]);
        KanbanTask::withoutGlobalScopes()->where('protocol_id', $protocolo->id)->first()?->forceFill([
            'status' => 'concluido', 'concluido_at' => now(), 'updated_by_id' => $por->id, 'secretaria_id' => $secretariaId,
        ])->save();
        AuditService::record('ENCERRADO', $protocolo, ['status' => $anterior], ['status' => 'encerrado'], $por);
    }
}
