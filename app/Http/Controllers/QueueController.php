<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    /**
     * Listar todos os registros da tabela queue.
     */
    // public function index()
    // {
    //     $queues = Queue::with(['client', 'user', 'speciality'])
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     return response()->json($queues, 200);
    // }



    // public function index()
    // {
    //     $queues = Queue::with(['client', 'user', 'speciality'])
    //         ->select('queue.*', DB::raw('
    //             ROW_NUMBER() OVER (
    //                 PARTITION BY id_specialities, urgency 
    //                 ORDER BY created_at ASC
    //             ) as position
    //         '))
    //         ->where('done', 0)
    //         ->orderBy('id_specialities')
    //         ->orderBy('urgency', 'desc') // Mantém urgência como prioridade dentro de cada especialidade
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     return response()->json($queues, 200);
    // }

    // public function index()
    // {
    //     // Buscar todos os registros com as relações necessárias, filtrando apenas os não finalizados (done = 0) e ordenando por created_at
    //     $queues = Queue::with(['client', 'user', 'speciality'])
    //         ->where('done', 0)
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     // Agrupar os registros por especialidade e urgência
    //     $groupedQueues = $queues->groupBy(function ($item) {
    //         return $item->id_specialities . '-' . $item->urgency;
    //     });

    //     // Adicionar a posição de cada registro dentro de sua fila específica
    //     foreach ($groupedQueues as $group) {
    //         foreach ($group as $index => $queue) {
    //             $queue->position = $index + 1; // Definir a posição iniciando de 1
    //         }
    //     }

    //     return response()->json($queues, 200);
    // }

    public function index()
    {
        // Buscar todos os registros não finalizados com as relações necessárias
        $queues = Queue::with(['client', 'user', 'speciality'])
            ->where('done', 0)
            ->orderBy('created_at', 'asc')
            ->get();
    
        // Adicionar a posição correta sem carregar todas as filas na memória
        foreach ($queues as $queue) {
            $queue->position = Queue::where('id_specialities', $queue->id_specialities)
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

    /**
     * Atualizar um registro específico na tabela queue.
     */
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


    /**
     * Mostrar um registro específico da tabela queue pelo UUID.
     */
    // public function showByUuid($uuid)
    // {
    //     $queue = Queue::with(['client', 'user', 'speciality'])
    //         ->where('uuid', $uuid)
    //         ->first();

    //     if (!$queue) {
    //         return response()->json(['message' => 'Registro não encontrado'], 404);
    //     }

    //     return response()->json($queue, 200);
    // }


    // public function showByUuid($uuid)
    // {
    //     // Buscar o registro específico pelo UUID, incluindo as relações necessárias
    //     $queue = Queue::with(['client', 'user', 'speciality'])
    //         ->where('uuid', $uuid)
    //         ->first();

    //     if (!$queue) {
    //         return response()->json(['message' => 'Registro não encontrado'], 404);
    //     }

    //     // Buscar todos os registros não finalizados da mesma especialidade e urgência
    //     $relatedQueues = Queue::where('id_specialities', $queue->id_specialities)
    //         ->where('urgency', $queue->urgency)
    //         ->where('done', 0)
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     // Determinar a posição do registro na fila
    //     $queue->position = $relatedQueues->search(function ($item) use ($queue) {
    //         return $item->id === $queue->id;
    //     }) + 1;

    //     return response()->json($queue, 200);
    // }


    public function showByUuid($uuid)
    {
        // Buscar o registro específico pelo UUID
        $queue = Queue::with(['client', 'user', 'speciality'])
            ->where('uuid', $uuid)
            ->first();

        if (!$queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        // Determinar a posição corretamente incluindo o próprio registro na contagem
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

        // Adicionar a posição ao objeto sem precisar carregar toda a fila
        $queue->position = $position;

        return response()->json($queue, 200);
    }
}
