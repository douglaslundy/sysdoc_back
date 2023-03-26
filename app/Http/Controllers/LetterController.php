<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\Letter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class LetterController extends Controller
{
    public function index()
    {
        return Letter::orderBy('id', 'desc')->get();
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
                ->orderBy('number', 'desc')
                ->value('number')
                // ->first()
                ) + 1;

            $letter->date = Carbon::now()->format('Y-m-d');

            $letter->subject_matter = $request->input('subject_matter');
            $letter->sender = $request->input('sender');
            $letter->recipient = $request->input('recipient');
            $letter->obs = $request->input('obs');
            $letter->fileurl = $request->input('fileurl');
            $letter->save();
            $array['letter'] = $letter;
        } else {
            $array['errors'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    // public function edit($id)
    // {
    //     $array = ['errors' => ''];
    //     $array['letters'] = Letter::find($id);
    //     return $array;
    // }

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

            $letter->delete();
        }
        return $array;
    }


    public function createLetterAi(Request $req){
        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'escreva um texto em linguagem formal, seguindo rigorosamente a seguinte ordem, primeiro chame pelo pronome de tratamento ' .$req->treatment_pronoun ],
                ['role' => 'user', 'content' => ' em seguida informe o nome para quem é destinado '.$req->recipient],
                ['role' => 'user', 'content' => 'Escreva a palavra "Assunto"  e na frente ' .$req->subject ],
                ['role' => 'user', 'content' => ' disserte de forma mais resumida possivel, e não insira novas informações ou escreva sobre nada além de ' .$req->article ],
                ['role' => 'user', 'content' => ' agora despeça com votos de '.$req->whishes],
                ['role' => 'user', 'content' => ' Insira o nome  '.$req->sender . ' e na frente escreva  Local e Data']
            ]
        ]);

        return $result->choices[0]->message->content;
    }
}
