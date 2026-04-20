<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdinanceRequest;
use App\Http\Requests\UpdateOrdinanceRequest;
use App\Models\Ordinance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Models;

class OrdinanceController extends Controller
{
    public function index()
    {
        return Ordinance::with(['user'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function show(Ordinance $ordinance)
    {
        return $ordinance->load('user');
    }

    public function store(StoreOrdinanceRequest $request)
    {
        $array = ['errors' => ''];

        try {
            $data = $request->validated();
            $currentYear = (int) date('Y');
            $today = date('Y-m-d');

            $ordinance = new Ordinance();
            $ordinance->user_id = $data['user_id'];

            $ordinance->number = (
                DB::table('ordinances')
                    ->where('year', $currentYear)
                    ->orderBy('number', 'desc')
                    ->value('number')
            ) + 1;

            $ordinance->year = $currentYear;
            $ordinance->type = $data['type'];
            $ordinance->title = $data['title'];
            $ordinance->subject = $data['subject'];
            $ordinance->summary = $data['summary'] ?? null;
            $ordinance->content = $data['content'] ?? null;
            $ordinance->legal_basis = $data['legal_basis'] ?? null;
            $ordinance->department = 'Secretaria Municipal de Saúde';
            $ordinance->signatory_name = $data['signatory_name'];
            $ordinance->signatory_role = $data['signatory_role'] ?? null;
            $ordinance->publication_date = $today;
            $ordinance->notes = $data['notes'] ?? null;

            if ($request->hasFile('file')) {
                $ordinance->file_path = $request->file('file')->store('ordinances', 'public');
            } elseif (!empty($data['file_path'])) {
                $ordinance->file_path = $data['file_path'];
            }

            $ordinance->save();

            $array['ordinance'] = $ordinance->load('user');

            return response()->json($array, 201);
        } catch (Exception $e) {
            $array['errors'] = $e->getMessage();
            return response()->json($array, 500);
        }
    }

    public function update(UpdateOrdinanceRequest $request, Ordinance $ordinance)
    {
        $array = ['errors' => ''];

        try {
            $data = $request->validated();

            $ordinance->type = $data['type'];
            $ordinance->title = $data['title'];
            $ordinance->subject = $data['subject'];
            $ordinance->summary = $data['summary'] ?? null;
            $ordinance->content = $data['content'] ?? null;
            $ordinance->legal_basis = $data['legal_basis'] ?? null;
            $ordinance->department = 'Secretaria Municipal de Saúde';
            $ordinance->signatory_name = $data['signatory_name'];
            $ordinance->signatory_role = $data['signatory_role'] ?? null;
            $ordinance->notes = $data['notes'] ?? null;

            if ($request->hasFile('file')) {
                if ($ordinance->file_path && Storage::disk('public')->exists($ordinance->file_path)) {
                    Storage::disk('public')->delete($ordinance->file_path);
                }

                $ordinance->file_path = $request->file('file')->store('ordinances', 'public');
            } elseif (array_key_exists('file_path', $data) && !empty($data['file_path'])) {
                $ordinance->file_path = $data['file_path'];
            }

            $ordinance->save();

            $array['ordinance'] = $ordinance->load('user');

            return response()->json($array);
        } catch (Exception $e) {
            $array['errors'] = $e->getMessage();
            return response()->json($array, 500);
        }
    }

    public function destroy(Ordinance $ordinance)
    {
        $array = ['errors' => ''];

        try {
            $lastOrdinance = Ordinance::latest()->first();

            if (!$lastOrdinance || $ordinance->id !== $lastOrdinance->id) {
                throw new Exception('É permitido excluir somente a última portaria');
            }

            if ($ordinance->file_path && Storage::disk('public')->exists($ordinance->file_path)) {
                Storage::disk('public')->delete($ordinance->file_path);
            }

            $ordinance->delete();

            return response()->json($array);
        } catch (Exception $e) {
            $array['errors'] = $e->getMessage();
            return response()->json($array, 422);
        }
    }

    public function createOrdinanceAi(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:normativa,ordinatoria'],
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'legal_basis' => ['nullable', 'string'],
            'signatory_name' => ['required', 'string', 'max:150'],
            'signatory_role' => ['nullable', 'string', 'max:150'],
            'additional_instructions' => ['nullable', 'string'],
        ]);

        $prompt = [[
            'role' => 'user',
            'content' => "
                Elabore uma PORTARIA ADMINISTRATIVA formal da Secretaria Municipal de Saúde, com linguagem técnica, clara, objetiva e institucional.

                Dados da portaria:
                - Tipo: {$request->type}
                - Título: {$request->title}
                - Assunto: {$request->subject}
                - Setor responsável: Secretaria Municipal de Saúde
                - Signatário: {$request->signatory_name}
                - Cargo do signatário: {$request->signatory_role}
                - Data de publicação: " . date('d/m/Y') . "

                Resumo base informado pelo usuário:
                {$request->summary}

                Fundamentação legal:
                {$request->legal_basis}

                Instruções obrigatórias:
                - Estruture a redação em formato oficial de portaria.
                - Se for do tipo 'normativa', redija com caráter normativo, geral e abstrato.
                - Se for do tipo 'ordinatoria', redija com foco em organização administrativa, designação, determinação ou disciplina interna.
                - O texto deve ser coeso, sem prolixidade e sem expressões vagas.
                - Sempre que couber, inclua considerandos antes da parte dispositiva.
                - Estruture os comandos em artigos, parágrafos ou incisos quando fizer sentido.
                - Finalize com data atual e assinatura institucional de {$request->signatory_name}" .
                ($request->signatory_role ? " ({$request->signatory_role})" : "") . ".
                " . ($request->additional_instructions ? "Instruções adicionais: {$request->additional_instructions}" : "") . "
            "
        ]];

        try {
            $result = OpenAI::chat()->create([
                'model' => env('MODEL'),
                'temperature' => 0.7,
                'messages' => $prompt,
            ]);

            $content = $result->choices[0]->message->content ?? null;
            
            if (!$content) {
                throw new Exception('A IA não retornou conteúdo para a portaria.');
            }
            
             if ($result->choices[0]->message->content) {
                $model = new Models();
                $model->id_user =  $request->user_id;
                $model->sender =  $request->signatory_name;
                $model->recipient =  $request->type;
                $model->subject =  $request->subject;
                $model->summary =  $request->summary;
                $model->prompt = implode(' ', array_column($prompt, 'content'));
                $model->model =  $content;
                $model->save();
            }


            return response()->json([
                'errors' => '',
                'content' => $content,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'errors' => 'Erro ao conectar com a Inteligência Artificial, tente novamente. ' . $e->getMessage(),
            ], 500);
        }
    }
}