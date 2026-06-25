<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\Models;
use App\Models\Protocol;
use App\Models\ProtocolAttachment;
use App\Models\ProtocolConfig;
use App\Models\ProtocolMovement;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Kanban\ProtocolKanbanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenAI\Laravel\Facades\OpenAI;

class LetterController extends Controller
{
    public function __construct(private readonly ProtocolKanbanService $kanbanService)
    {
    }

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

        if (! $validator->fails()) {

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

        if (! $validator->fails()) {

            //$file = $request->file('photo')->store('public');

            $letter = Letter::find($request->input('id'));
            $letter->subject_matter = $request->input('subject_matter');
            $letter->sender = $request->input('sender');
            $letter->recipient = $request->input('recipient');
            $letter->obs = $request->input('obs');
            $letter->summary = $request->input('summary');
            $letter->fileurl = $request->input('fileurl') ? $request->input('fileurl') : $letter->fileurl;
            $letter->save();
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

            $array['errors'] = 'letter has not found';
        } else {

            $lastLetter = Letter::latest()->first();

            if ($letter->id !== $lastLetter->id) {
                throw new Exception('é permitido excluir Somente o ultimo Ofício');
            }

            $letter->delete();

            return $array;
        }
    }

    public function createLetterAi(Request $req)
    {
        $req->validate([
            'id_user' => ['required'],
            'sender' => ['required', 'string'],
            'recipient' => ['required', 'string'],
            'subject_matter' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
            'wishes' => ['nullable', 'string'],
            'treatment_pronoun' => ['nullable', 'string'],
        ]);

        $promptText = 'Elabore um OFICIO FORMAL com linguagem juridica precisa, objetiva e tecnica.'
            ."\n\nSiga rigorosamente a estrutura abaixo:\n"
            ."\n1. Vocativo:\nInicie com o pronome de tratamento: ".$req->treatment_pronoun
            ."\n\n2. Identificacao do destinatario:\n".$req->recipient
            ."\n\n3. Linha de assunto:\nAssunto: ".$req->subject_matter
            ."\n\n4. Corpo do texto:"
            ."\n- Redija de forma clara, direta e fundamentada."
            ."\n- Utilize o resumo fornecido como base factual: ".$req->summary
            ."\n- Estruture o texto com:"
            ."\n  a) contextualizacao objetiva dos fatos"
            ."\n  b) fundamentacao juridica (quando aplicavel, mencione principios, normas ou deveres legais de forma especifica)"
            ."\n  c) pedido ou encaminhamento claro e inequivoco"
            ."\n- Evite expressoes genericas sem especificacao."
            ."\n- Nao use linguagem excessivamente rebuscada ou prolixa."
            ."\n\n5. Fecho:\nFinalize com votos de ".$req->wishes
            ."\n\n6. Assinatura:\n".$req->sender
            ."\n\n7. Local e data:\nInsira ao final no formato: [Cidade], [data completa]"
            ."\n\nRequisitos obrigatorios:"
            ."\n- Texto coeso, assertivo e sem redundancias"
            ."\n- Paragrafos bem definidos"
            ."\n- Clareza no objetivo do oficio"
            ."\n- Tom institucional e profissional";

        $prompt = [['role' => 'user', 'content' => $promptText]];

        try {
            $result = OpenAI::chat()->create([
                'model' => env('MODEL'),
                'temperature' => 0.7,
                'messages' => $prompt,
            ]);

            $content = $result->choices[0]->message->content ?? null;

            if (! $content) {
                throw new Exception('A IA nao retornou conteudo para o oficio.');
            }

            $model = new Models();
            $model->id_user = $req->id_user;
            $model->sender = $req->sender;
            $model->recipient = $req->recipient;
            $model->subject = $req->subject_matter;
            $model->summary = $req->summary;
            $model->prompt = $promptText;
            $model->model = $content;
            $model->save();

            return response()->json([
                'errors' => '',
                'content' => $content,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'errors' => 'Erro ao conectar com a Inteligencia Artificial, tente novamente. '.$e->getMessage(),
            ], 500);
        }
    }

    public function createProtocol(Request $request, Letter $letter): JsonResponse
    {
        $validated = $request->validate([
            'destino_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $destinationUser = User::query()
            ->whereKey($validated['destino_user_id'])
            ->where('active', true)
            ->first();

        if (! $destinationUser) {
            return response()->json(['message' => 'Selecione um destinatário ativo.'], 422);
        }

        $originUnit = $user?->protocolUnits()
            ->where('ativo', true)
            ->with('unit')
            ->first()
            ?->unit;

        if (! $originUnit) {
            return response()->json([
                'message' => 'Seu usuário não possui secretaria ou unidade vinculada para criar o protocolo.',
            ], 422);
        }

        $destinationUnit = $destinationUser->protocolUnits()
            ->where('ativo', true)
            ->with('unit')
            ->first()
            ?->unit;
        $config = ProtocolConfig::current();
        $storedPath = null;

        try {
            $protocol = DB::transaction(function () use (
                $letter,
                $user,
                $destinationUser,
                $originUnit,
                $destinationUnit,
                $config,
                &$storedPath
            ) {
                $protocol = Protocol::create([
                    'numero' => Protocol::gerarNumero(),
                    'assunto' => 'Ofício '.$letter->number.' - '.$letter->subject_matter,
                    'descricao' => $letter->summary ?: $letter->subject_matter,
                    'tipo' => 'oficio',
                    'status' => 'novo',
                    'prioridade' => $config->default_priority ?? 'normal',
                    'solicitante_tipo' => 'interno',
                    'solicitante_nome' => $user?->name,
                    'solicitante_documento' => $user?->cpf,
                    'origem_unit_id' => $originUnit->id,
                    'destino_unit_id' => $destinationUnit?->id,
                    'responsavel_atual_id' => $destinationUser->id,
                    'criado_por_id' => $user?->id,
                    'prazo_atendimento' => now()->addDays((int) $config->default_due_days)->toDateString(),
                    'novo' => true,
                    'vencido' => false,
                ]);

                ProtocolMovement::create([
                    'protocol_id' => $protocol->id,
                    'to_unit_id' => $destinationUnit?->id,
                    'to_user_id' => $destinationUser->id,
                    'acao' => 'criado_por_oficio',
                    'status_novo' => 'novo',
                    'observacao' => 'Protocolo criado a partir do Ofício '.$letter->number.'.',
                    'dados' => ['letter_id' => $letter->id, 'letter_number' => $letter->number],
                    'user_id' => $user?->id,
                ]);

                $pdf = Pdf::loadView('pdf.oficio-protocolo', [
                    'letter' => $letter->loadMissing('user'),
                ])->setPaper('a4');
                $filename = 'oficio-'.$letter->number.'-'.$letter->created_at?->format('Y').'.pdf';
                $storedPath = 'protocolos/'.$protocol->id.'/'.$filename;
                Storage::disk('public')->put($storedPath, $pdf->output());

                $attachment = ProtocolAttachment::create([
                    'protocol_id' => $protocol->id,
                    'user_id' => $user?->id,
                    'nome_original' => $filename,
                    'caminho' => $storedPath,
                    'mime_type' => 'application/pdf',
                    'tamanho_bytes' => Storage::disk('public')->size($storedPath),
                    'descricao' => 'Ofício que originou o protocolo.',
                    'ativo' => true,
                ]);

                ProtocolMovement::create([
                    'protocol_id' => $protocol->id,
                    'acao' => 'anexo',
                    'status_novo' => 'novo',
                    'observacao' => 'PDF do ofício anexado automaticamente.',
                    'dados' => ['attachment_id' => $attachment->id, 'letter_id' => $letter->id],
                    'user_id' => $user?->id,
                ]);

                $this->kanbanService->sync($protocol, [
                    'ativar' => true,
                    'status' => 'novo',
                    'responsavel_id' => $destinationUser->id,
                ], $user);

                AuditService::record('CREATE_PROTOCOL_FROM_LETTER', $protocol, null, [
                    'letter_id' => $letter->id,
                    'destination_user_id' => $destinationUser->id,
                    'attachment_id' => $attachment->id,
                ], $user);

                return $protocol->load([
                    'origemUnit:id,nome',
                    'destinoUnit:id,nome',
                    'responsavelAtual:id,name',
                    'attachments',
                    'kanbanTask',
                ]);
            });
        } catch (\Throwable $exception) {
            if ($storedPath && Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->delete($storedPath);
            }

            report($exception);

            return response()->json([
                'message' => 'Não foi possível criar o protocolo a partir do ofício.',
            ], 500);
        }

        return response()->json([
            'message' => 'Protocolo criado com sucesso.',
            'protocol' => $protocol,
        ], 201);
    }
}
