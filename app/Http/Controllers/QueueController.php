<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Queue;
use App\Models\QRCodeLog;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{

    public function index()
    {
        // Buscar todos os registros com as relações necessárias (independente de 'done')
        $queues = Queue::with(['client', 'user', 'speciality'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc') // Garante ordenação consistente em caso de created_at iguais
            ->get();

        // Adicionar a posição correta
        foreach ($queues as $queue) {
            if ($queue->done == 1) {
                $queue->position = 0; // Se o registro estiver finalizado, posição é 0
            } else {
                $queue->position = Queue::where('id_specialities', $queue->id_specialities)
                    ->where('urgency', $queue->urgency)
                    ->where('done', 0)
                    ->where(function ($query) use ($queue) {
                        $query->where('created_at', '<', $queue->created_at)
                            ->orWhere(function ($query) use ($queue) {
                                $query->where('created_at', '=', $queue->created_at)
                                    ->where('id', '<', $queue->id); // Garante ordenação estável
                            });
                    })
                    ->count() + 1;
            }
        }

        return response()->json($queues, 200);
    }


    /**
     * Criar um novo registro na tabela queue.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_client' => 'required|exists:clients,id',
            'id_specialities' => 'required|exists:specialities,id',
            'id_user' => 'required|exists:users,id',
            'done' => 'boolean',
            'date_of_realized' => 'nullable|date',
            'urgency' => 'required|boolean',
            'obs' => 'nullable|string|max:200',
        ]);

        $queue = Queue::create($validatedData);

        // Carrega as relações client e speciality
        $queue->load('client', 'speciality');

        // Retorna a fila com as relações carregadas
        return response()->json($queue, 201);
    }

    /**
     * Mostrar um registro específico da tabela queue.
     */
    public function show($id)
    {
        $queue = Queue::find($id);

        if (!$queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        return response()->json($queue, 200);
    }


    public function update(Request $request, $id)
    {
        $queue = Queue::find($id);

        if (!$queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        $validatedData = $request->validate([
            'id_client' => 'sometimes|required|exists:clients,id',
            'id_specialities' => 'sometimes|required|exists:specialities,id',
            'id_user' => 'sometimes|required|exists:users,id',
            'done' => 'boolean',
            'date_of_realized' => 'nullable|date',
            'urgency' => 'sometimes|required|boolean',
            'obs' => 'nullable|string|max:200',
        ]);

        $queue->update($validatedData);

        // Se o campo done for verdadeiro (true ou 1)
        if ($queue->done == true) {
            // Atualiza o campo updated_at de todos os registros com o mesmo id_specialities e done = 0
            Queue::where('id_specialities', $queue->id_specialities)
                ->where('urgency', $queue->urgency)
                ->where('done', 0)
                ->update(['updated_at' => now()]);
        }

        // Carrega as relações client e speciality
        $queue->load('client', 'speciality');

        return response()->json($queue, 200);
    }

    /**
     * Deletar um registro específico na tabela queue.
     */
    public function destroy($id)
    {
        $queue = Queue::find($id);

        if (!$queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        $queue->delete();

        return response()->json(['message' => 'Registro deletado com sucesso'], 200);
    }



    // public function showByUuid($uuid)
    // {
    //     // Buscar o registro específico pelo UUID
    //     $queue = Queue::with(['client', 'user', 'speciality'])
    //         ->where('uuid', $uuid)
    //         ->first();

    //     if (!$queue) {
    //         return response()->json(['message' => 'Registro não encontrado'], 404);
    //     }

    //     // Determinar a posição corretamente incluindo o próprio registro na contagem
    //     $position = Queue::where('id_specialities', $queue->id_specialities)
    //         ->where('urgency', $queue->urgency)
    //         ->where('done', 0)
    //         ->where(function ($query) use ($queue) {
    //             $query->where('created_at', '<', $queue->created_at)
    //                 ->orWhere(function ($query) use ($queue) {
    //                     $query->where('created_at', '=', $queue->created_at)
    //                         ->where('id', '<', $queue->id);
    //                 });
    //         })
    //         ->count() + 1;

    //     // Adicionar a posição ao objeto sem precisar carregar toda a fila

    //     if ($queue->done == 0) {
    //         $queue->position = $position;
    //     } else if ($queue->done == 1) {
    //         $queue->position = 0;
    //     }



    //     return response()->json($queue, 200);
    // }

    public function showByUuid($uuid, Request $request)
    {
        $queue = Queue::with(['client', 'user', 'speciality'])
            ->where('uuid', $uuid)
            ->first();

        if (!$queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        // Calcula posição (como já estava)
        $position = Queue::where('id_specialities', $queue->id_specialities)
            ->where('urgency', $queue->urgency)
            ->where('done', 0)
            ->where(function ($query) use ($queue) {
                $query->where('created_at', '<', $queue->created_at)
                    ->orWhere(function ($query) use ($queue) {
                        $query->where('created_at', '=', $queue->created_at)
                            ->where('id', '<', $queue->id);
                    });
            })
            ->count() + 1;

        $queue->position = $queue->done ? 0 : $position;

        // Captura IP e User-Agent
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Opcional: buscar localização via IP (ex: usando ipapi.co ou ipwho.is)
        $location = null;
        try {
            $locationResponse = Http::get("https://ipwho.is/{$ip}");
            if ($locationResponse->ok()) {
                $location = $locationResponse->json();
            }
        } catch (\Exception $e) {
            $location = null;
        }

        // Salvar log
        QRCodeLog::create([
            'uuid' => $uuid,
            'queue_id' => $queue->id,
            'position' => $queue->position,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'location' => $location ? json_encode($location) : null,
            'referer' => $request->headers->get('referer'),
            'accessed_at' => now(),
        ]);

        return response()->json($queue, 200);
    }


    public function storeLocationLog(Request $request)
    {
        $data = $request->validate([
            'uuid' => 'required|uuid',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Atualize o log mais recente desse UUID com localização
        QRCodeLog::where('uuid', $data['uuid'])
            ->latest()
            ->first()
                ?->update([
                'location' => json_encode([
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                ]),
            ]);

        return response()->json(['message' => 'Localização salva com sucesso']);
    }


}
