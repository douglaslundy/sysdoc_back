<?php

namespace App\Console\Commands;

use App\Models\ProtocolAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrarAnexosProtocoloParaDiscoPrivado extends Command
{
    protected $signature = 'protocolo:migrar-anexos-para-privado {--dry-run : Só lista o que seria migrado, sem mover nada}';

    protected $description = 'Move anexos de protocolo (incluindo os originados de Ofícios) do disco público para o privado, fechando o acesso direto por URL';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $private = Storage::disk('private');

        $migrated = 0;
        $missing = 0;
        $alreadyPrivate = 0;

        ProtocolAttachment::query()->orderBy('id')->chunk(100, function ($attachments) use ($public, $private, $dryRun, &$migrated, &$missing, &$alreadyPrivate) {
            foreach ($attachments as $attachment) {
                if ($private->exists($attachment->caminho) && ! $public->exists($attachment->caminho)) {
                    $alreadyPrivate++;

                    continue;
                }

                if (! $public->exists($attachment->caminho)) {
                    $this->warn("Anexo {$attachment->id}: arquivo não encontrado em nenhum disco ({$attachment->caminho}).");
                    $missing++;

                    continue;
                }

                $this->line("Anexo {$attachment->id}: {$attachment->caminho}".($dryRun ? ' (dry-run)' : ''));

                if (! $dryRun) {
                    $private->put($attachment->caminho, $public->get($attachment->caminho));
                    $public->delete($attachment->caminho);
                }

                $migrated++;
            }
        });

        $this->info(($dryRun ? '[dry-run] ' : '')."Migrados: {$migrated} | Já no privado: {$alreadyPrivate} | Não encontrados: {$missing}");

        return Command::SUCCESS;
    }
}
