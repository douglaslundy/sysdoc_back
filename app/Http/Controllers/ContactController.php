<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Services\ConfiguredEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    private const DESTINATARIO = 'douglaslundy@gmail.com';

    public function store(ContactRequest $request, ConfiguredEmailService $email): RedirectResponse
    {
        $data = $request->validated();

        $corpo = "Novo contato pela landing page do Sysdoc\n\n"
            ."Nome: {$data['nome']}\n"
            ."E-mail: {$data['email']}\n"
            ."Telefone: {$data['telefone']}\n\n"
            ."Mensagem:\n{$data['mensagem']}\n";

        $result = $email->sendText(
            self::DESTINATARIO,
            'Contato Sysdoc - '.$data['nome'],
            $corpo,
            $data['email'],
            $data['nome']
        );

        if (! ($result['ok'] ?? false)) {
            Log::warning('Falha ao enviar contato da landing page', ['error' => $result['error'] ?? null]);

            return redirect('/#contato')
                ->withInput($request->except('website'))
                ->with('contato_erro', 'Não foi possível enviar sua mensagem agora. Tente novamente em instantes ou use o WhatsApp.');
        }

        return redirect('/#contato')->with('contato_ok', true);
    }
}
