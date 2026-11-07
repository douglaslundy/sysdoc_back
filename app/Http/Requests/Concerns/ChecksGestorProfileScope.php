<?php

namespace App\Http\Requests\Concerns;

use App\Services\Secretarias\GestorProfileScope;
use Illuminate\Validation\Validator;

trait ChecksGestorProfileScope
{
    /** Não-admin só concede páginas e capabilities que ele mesmo tem (422 com erro por campo). */
    protected function checkGestorScope(Validator $validator): void
    {
        $actor = $this->user();
        if (! $actor || $actor->profile === 'admin') {
            return;
        }

        $fora = array_diff(array_map('intval', (array) $this->input('page_ids', [])), GestorProfileScope::pageIds($actor));
        if ($fora) {
            $validator->errors()->add('page_ids', 'Há páginas que você não pode conceder: '.implode(', ', $fora).'.');
        }

        foreach (GestorProfileScope::capabilities($actor) as $cap => $tem) {
            if (! $tem && $this->boolean($cap)) {
                $validator->errors()->add($cap, 'Você não pode conceder uma permissão que não possui.');
            }
        }
    }
}
