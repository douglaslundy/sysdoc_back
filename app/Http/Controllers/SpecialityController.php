<?php

namespace App\Http\Controllers;

use App\Models\Speciality;
use Illuminate\Http\Request;

class SpecialityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Obtém todas as especialidades
        $specialities = Speciality::with(['user'])->get();

        // Retorna as especialidades em formato JSON
        return response()->json($specialities, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Valida os dados de entrada
        $validatedData = $request->validate([
            'id_user' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
        ]);

        // Cria uma nova especialidade
        $speciality = Speciality::create($validatedData);

        // Retorna a especialidade criada com código de status 201 (Created)
        return response()->json($speciality, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Speciality  $speciality
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Speciality $speciality)
    {
        // Retorna os detalhes da especialidade em formato JSON
        return response()->json($speciality, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Speciality  $speciality
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Speciality $speciality)
    {
        // Valida os dados de entrada
        $validatedData = $request->validate([
            'id_user' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
        ]);

        // Atualiza a especialidade com os dados validados
        $speciality->update($validatedData);

        // Retorna a especialidade atualizada com código de status 200 (OK)
        return response()->json($speciality, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Speciality  $speciality
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Speciality $speciality)
    {
        // Deleta a especialidade
        $speciality->delete();

        // Retorna uma resposta vazia com código de status 204 (No Content)
        return response()->json(null, 204);
    }
}
