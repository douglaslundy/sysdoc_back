<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\Letter;
use App\Models\Models;
use App\Services\AuditService;
use Exception;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class LetterController extends Controller
{
    public function index()
    {
        return Letter::with(['user'])->withCount('attachments')->orderBy('id', 'desc')->get();
    }

    public function store(Request $request)
    {
        $array = ['errors' => ''];

        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer',
            'subject_matter' => 'required',
            'sender' => 'required',
            'recipient' => 'required',
            // 'fileurl' => 'required',
            //'arquivo' => 'required|mimes:jpg, png, pdf
        ]);

        if (!$validator->fails()) {

            //$file = $request->file('photo')->store('public');

            $letter = new Letter();
            $letter->id_user = $request->input('id_user');

            $letter->number = (DB::table('letters')
                ->whereYear('created_at', date('Y')) // Filtra os registros pelo ano corrente
                ->orderBy('number', 'desc')
                ->value('number')
                // ->first()
            ) + 1;

            $letter->subject_matter = $request->input('subject_matter');
            $letter->sender = $request->input('sender');
            $letter->recipient = $request->input('recipient');
            $letter->obs = $request->input('obs');
            $letter->summary = $request->input('summary');
            $letter->fileurl = $request->input('fileurl');
            $letter->save();
            AuditService::record('CREATE', $letter, null, $letter->toArray());
            $array['letter'] = $letter->load('user')->loadCount('attachments');
        } else {
            $array['errors'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function update(Request $request)
    {
        $array = ['errors' => ''];

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'subject_matter' => 'required',
            'sender' => 'required',
            'recipient' => 'required',
            //'arquivo' => 'required|mimes:jpg, png, pdf
        ]);

        if (!$validator->fails()) {

            //$file = $request->file('photo')->store('public');

            $letter = Letter::find($request->input('id'));
            $old = $letter->toArray();
            $letter->subject_matter = $request->input('subject_matter');
            $letter->sender = $request->input('sender');
            $letter->recipient = $request->input('recipient');
            $letter->obs = $request->input('obs');
            $letter->summary = $request->input('summary');
            $letter->fileurl = $request->input('fileurl') ? $request->input('fileurl') : $letter->fileurl;
            $letter->save();
            AuditService::record('UPDATE', $letter, $old, $letter->toArray());
            $array['letter'] = $letter->load('user')->loadCount('attachments');
        } else {
            $array['errors'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function destroy($id)
    {
        $array = ['errors' => ''];

        $letter = Letter::find($id);

        if (is_null($letter)) {

            $array['errors'] = "letter has not found";
        } else {

            $lastLetter = Letter::latest()->first();

            if ($letter->id !== $lastLetter->id)
                throw new Exception('é permitido excluir Somente o ultimo Ofício');

            AuditService::record('DELETE', $letter, $letter->toArray(), null);
            $letter->delete();
            return $array;
        }
    }


    public function createLetterAi(Request $req)
    {
        $req->validate([
            'id_user'        => ['required'],
            'sender'         => ['required', 'string'],
            'recipient'      => ['required', 'string'],
            'subject_matter' => ['required', 'string'],
            'summary'        => ['nullable', 'string'],
            'wishes'         => ['nullable', 'string'],
            'treatment_pronoun' => ['nullable', 'string'],
        ]);

        $promptText = 'Elabore um OFICIO FORMAL com linguagem juridica precisa, objetiva e tecnica.'
            . "\n\nSiga rigorosamente a estrutura abaixo:\n"
            . "\n1. Vocativo:\nInicie com o pronome de tratamento: " . $req->treatment_pronoun
            . "\n\n2. Identificacao do destinatario:\n" . $req->recipient
            . "\n\n3. Linha de assunto:\nAssunto: " . $req->subject_matter
            . "\n\n4. Corpo do texto:"
            . "\n- Redija de forma clara, direta e fundamentada."
            . "\n- Utilize o resumo fornecido como base factual: " . $req->summary
            . "\n- Estruture o texto com:"
            . "\n  a) contextualizacao objetiva dos fatos"
            . "\n  b) fundamentacao juridica (quando aplicavel, mencione principios, normas ou deveres legais de forma especifica)"
            . "\n  c) pedido ou encaminhamento claro e inequivoco"
            . "\n- Evite expressoes genericas sem especificacao."
            . "\n- Nao use linguagem excessivamente rebuscada ou prolixa."
            . "\n\n5. Fecho:\nFinalize com votos de " . $req->wishes
            . "\n\n6. Assinatura:\n" . $req->sender
            . "\n\n7. Local e data:\nInsira ao final no formato: [Cidade], [data completa]"
            . "\n\nRequisitos obrigatorios:"
            . "\n- Texto coeso, assertivo e sem redundancias"
            . "\n- Paragrafos bem definidos"
            . "\n- Clareza no objetivo do oficio"
            . "\n- Tom institucional e profissional";

        $prompt = [['role' => 'user', 'content' => $promptText]];

        try {
            $result = OpenAI::chat()->create([
                'model'       => env('MODEL'),
                'temperature' => 0.7,
                'messages'    => $prompt,
            ]);

            $content = $result->choices[0]->message->content ?? null;

            if (!$content) {
                throw new Exception('A IA nao retornou conteudo para o oficio.');
            }

            $model = new Models();
            $model->id_user   = $req->id_user;
            $model->sender    = $req->sender;
            $model->recipient = $req->recipient;
            $model->subject   = $req->subject_matter;
            $model->summary   = $req->summary;
            $model->prompt    = $promptText;
            $model->model     = $content;
            $model->save();

            return response()->json([
                'errors'  => '',
                'content' => $content,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'errors' => 'Erro ao conectar com a Inteligencia Artificial, tente novamente. ' . $e->getMessage(),
            ], 500);
        }
    }
}
