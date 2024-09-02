<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    /**
     * Listar todos os registros da tabela queue.
     */
    public function index()
    {
        $queues = Queue::with(['client', 'user', 'speciality'])
            ->orderBy('date_of_received', 'asc')
            ->get();

        return response()->json($queues, 200);
    }


    /**
     * Criar um novo registro na tabela queue.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'date_of_received' => 'required|date',
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
            'date_of_received' => 'sometimes|required|date',
            'id_client' => 'sometimes|required|exists:clients,id',
            'id_specialities' => 'sometimes|required|exists:specialities,id',
            'id_user' => 'sometimes|required|exists:users,id',
            'done' => 'boolean',
            'date_of_realized' => 'nullable|date',
            'urgency' => 'sometimes|required|boolean',
            'obs' => 'nullable|string|max:200',
        ]);

        $queue->update($validatedData);

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
}
