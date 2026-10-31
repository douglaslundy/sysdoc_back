<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePessoaFotoRequest;
use App\Models\Client;
use App\Models\PessoaFoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PessoaFotoController extends Controller
{
    private const DISK = 'private';

    public function showClient(Client $client)
    {
        return $this->exibir($client);
    }

    public function storeClient(StorePessoaFotoRequest $request, Client $client)
    {
        return $this->guardar($request, $client);
    }

    public function destroyClient(Request $request, Client $client)
    {
        return $this->remover($request, $client);
    }

    public function showUser(User $user)
    {
        return $this->exibir($user);
    }

    public function storeUser(StorePessoaFotoRequest $request, User $user)
    {
        if (! $this->podeAlterarUsuario($request, $user)) {
            return $this->negado();
        }

        return $this->guardar($request, $user);
    }

    public function destroyUser(Request $request, User $user)
    {
        if (! $this->podeAlterarUsuario($request, $user)) {
            return $this->negado();
        }

        return $this->remover($request, $user);
    }

    /** Cada usuário troca a própria foto; o admin troca a de qualquer um. */
    private function podeAlterarUsuario(Request $request, User $user): bool
    {
        $logado = $request->user();

        return $logado->profile === 'admin' || $logado->id === $user->id;
    }

    private function negado()
    {
        return response()->json(['message' => 'Você só pode alterar a sua própria foto.'], 403);
    }

    private function exibir(Client|User $pessoa)
    {
        $foto = PessoaFoto::where('chave', PessoaFoto::chaveDe($pessoa))->first();

        abort_unless($foto && Storage::disk($foto->disk)->exists($foto->path), 404);

        // Content-Type vem do banco (só imagens validadas no upload); no-cache porque a foto pode ser trocada.
        return Storage::disk($foto->disk)->response($foto->path, null, [
            'Content-Type' => $foto->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache',
        ], 'inline');
    }

    private function guardar(StorePessoaFotoRequest $request, Client|User $pessoa)
    {
        $arquivo = $request->file('photo');
        $chave = PessoaFoto::chaveDe($pessoa);
        $anterior = PessoaFoto::where('chave', $chave)->first();

        $path = $arquivo->store('pessoas', self::DISK);

        $foto = PessoaFoto::updateOrCreate(['chave' => $chave], [
            'disk' => self::DISK,
            'path' => $path,
            'mime_type' => $arquivo->getMimeType() ?? $arquivo->getClientMimeType(),
            'size_bytes' => (int) $arquivo->getSize(),
            'updated_by' => $request->user()->id,
        ]);

        if ($anterior && $anterior->path !== $path) {
            Storage::disk($anterior->disk)->delete($anterior->path);
        }

        return response()->json(['message' => 'Foto salva.', 'updated_at' => $foto->updated_at], 201);
    }

    private function remover(Request $request, Client|User $pessoa)
    {
        $foto = PessoaFoto::where('chave', PessoaFoto::chaveDe($pessoa))->first();

        if ($foto) {
            Storage::disk($foto->disk)->delete($foto->path);
            $foto->delete();
        }

        return response()->json(['message' => 'Foto removida.']);
    }
}
