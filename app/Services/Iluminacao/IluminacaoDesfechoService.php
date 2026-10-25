<?php

namespace App\Services\Iluminacao;

use App\Models\IluminacaoPoste;
use App\Models\IluminacaoPosteMovimentacao;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class IluminacaoDesfechoService
{
    public function __construct(private IluminacaoFotoStorage $fotos)
    {
    }

    /**
     * Tudo ou nada: status, movimentação e fotos na mesma transação.
     *
     * @param  \Illuminate\Http\UploadedFile[]  $fotos
     *
     * @throws DomainException transição inválida
     */
    public function registrar(IluminacaoPoste $poste, User $user, string $resultado, ?string $motivo, array $fotos = []): IluminacaoPoste
    {
        return DB::transaction(function () use ($poste, $user, $resultado, $motivo, $fotos) {
            $atual = IluminacaoPoste::whereKey($poste->id)->lockForUpdate()->firstOrFail();

            if ($atual->status === IluminacaoPoste::CONCLUIDO) {
                throw new DomainException('Poste já concluído. Somente o administrador pode reabri-lo.');
            }

            $mov = $this->mover($atual, $user, 'desfecho', $resultado, $motivo);

            if ($fotos) {
                $this->fotos->guardar($atual, $fotos, 'desfecho', $user->id, $mov->id);
            }

            return $atual;
        });
    }

    /** @throws DomainException poste já pendente */
    public function reabrir(IluminacaoPoste $poste, User $user, string $motivo): IluminacaoPoste
    {
        return DB::transaction(function () use ($poste, $user, $motivo) {
            $atual = IluminacaoPoste::whereKey($poste->id)->lockForUpdate()->firstOrFail();

            if ($atual->status === IluminacaoPoste::PENDENTE) {
                throw new DomainException('O poste já está pendente.');
            }

            $this->mover($atual, $user, 'reabertura', IluminacaoPoste::PENDENTE, $motivo);

            return $atual;
        });
    }

    private function mover(IluminacaoPoste $poste, User $user, string $acao, string $novo, ?string $motivo): IluminacaoPosteMovimentacao
    {
        $anterior = $poste->status;

        $poste->forceFill([
            'status' => $novo,
            'concluido_em' => $novo === IluminacaoPoste::CONCLUIDO ? now() : null,
        ])->save();

        return IluminacaoPosteMovimentacao::create([
            'poste_id' => $poste->id,
            'user_id' => $user->id,
            'acao' => $acao,
            'status_anterior' => $anterior,
            'status_novo' => $novo,
            'motivo' => $motivo,
        ]);
    }
}
