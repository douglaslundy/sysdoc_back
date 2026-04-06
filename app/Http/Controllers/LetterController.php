<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\Letter;
use App\Models\Models;
use Exception;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class LetterController extends Controller
{
    public function index()
    {
        return Letter::with(['user'])->orderBy('id', 'desc')->get();
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
            $array['letter'] = $letter;
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
            $letter->subject_matter = $request->input('subject_matter');
            $letter->sender = $request->input('sender');
            $letter->recipient = $request->input('recipient');
            $letter->obs = $request->input('obs');
            $letter->summary = $request->input('summary');
            $letter->fileurl = $request->input('fileurl') ? $request->input('fileurl') : $letter->fileurl;
            $letter->save();
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

            $letter->delete();
            return $array;
        }
    }


    public function createLetterAi(Request $req)
    {
        // $prompt = [
        //     ['role' => 'user', 'content' => 'escreva um texto em linguagem formal, no formato de ofício, seguindo rigorosamente a seguinte ordem, primeiro chame pelo pronome de tratamento ' . $req->treatment_pronoun],
        //     ['role' => 'user', 'content' => ' em seguida informe o seguinte destinatário  ' . $req->recipient],
        //     ['role' => 'user', 'content' => ' crie uma linha para "Assunto"  com seguinte assunto: ' . $req->subject],
        //     ['role' => 'user', 'content' => ' utilize o resumo a seguir para elaborar o texto ' . $req->summary],
        //     ['role' => 'user', 'content' => ' agora despeça com votos de ' . $req->whishes],
        //     ['role' => 'user', 'content' => ' Insira o nome  ' . $req->sender . ' em seguida escreva  Local e Data']
        // ];

        $prompt = [
            [
                'role' => 'user',
                'content' => "
                    Elabore um OFÍCIO FORMAL com linguagem jurídica precisa, objetiva e técnica, evitando termos genéricos ou vagos.

                    Siga rigorosamente a estrutura abaixo:

                    1. Vocativo:
                    Inicie com o pronome de tratamento: {$req->treatment_pronoun}

                    2. Identificação do destinatário:
                    {$req->recipient}

                    3. Linha de assunto:
                    Assunto: {$req->subject}

                    4. Corpo do texto:
                    - Redija de forma clara, direta e fundamentada.
                    - Utilize o resumo fornecido como base factual: {$req->summary}
                    - Estruture o texto com:
                        a) contextualização objetiva dos fatos
                        b) fundamentação jurídica (quando aplicável, mencione princípios, normas ou deveres legais de forma específica)
                        c) pedido ou encaminhamento claro e inequívoco
                    - Evite expressões genéricas como “conforme legislação vigente” sem especificação.
                    - Não use linguagem excessivamente rebuscada ou prolixa.

                    5. Fecho:
                    Finalize com votos de {$req->whishes}

                    6. Assinatura:
                    {$req->sender}

                    7. Local e data:
                    Insira ao final no formato: [Cidade], [data completa]

                    Requisitos obrigatórios:
                    - Texto coeso, assertivo e sem redundâncias
                    - Parágrafos bem definidos
                    - Clareza no objetivo do ofício
                    - Tom institucional e profissional
                    "
            ]
        ];

        try {

            $result = OpenAI::chat()->create([
                'model' => env('MODEL'),
                'temperature' => 0.7,  // Define a temperatura para 0.7
                'messages' => $prompt
            ]);

            if ($result->choices[0]->message->content) {
                $model = new Models();
                $model->id_user =  $req->id_user;
                $model->sender =  $req->sender;
                $model->recipient =  $req->recipient;
                $model->subject =  $req->subject;
                $model->summary =  $req->summary;
                // $model->prompt =  $prompt[0]['content'];
                $model->prompt =  $prompt[0]['content'] . $prompt[1]['content'] . $prompt[2]['content'] . $prompt[3]['content'] . $prompt[4]['content'] . $prompt[5]['content'];
                $model->model =  $result->choices[0]->message->content;
                $model->save();
            }

            return $result->choices[0]->message->content;
        } catch (Exception $e) {
            // throw new Exception("erro ao conectar com a Inteligência Artificial,  tente novamente");
            throw new Exception("erro ao conectar com a Inteligência Artificial,  tente novamente" . $e->getMessage());
        }
    }
}
