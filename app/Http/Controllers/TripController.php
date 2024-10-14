<?php

namespace App\Http\Controllers;

use App\Http\Requests\TripRequest;
use App\Models\Trip;
use App\Models\TripClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function index()
    {
        return Trip::with(['driver', 'vehicle', 'route', 'clients'])->get();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TripRequest $request)
    {
        DB::beginTransaction();
        try {

            $array = ['status' => 'created'];
            $trip = Trip::create($request->all());
            $trip->load('driver', 'route', 'vehicle', 'user');
            $array['trip'] = $trip;
            DB::commit();

            return response()->json($array, 201);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show($id)
    {
        return Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Trip  $trip
     * @return \Illuminate\Http\Response
     */
    public function update(TripRequest $request, Trip $trip)
    {
        DB::beginTransaction();
        try {
            $array = ['status' => 'updated'];
            // $trip->update($request->all());

            $trip->update($request->only(['vehicle_id', 'driver_id', 'route_id', 'departure_date', 'departure_time']));
            $trip->clients()->sync($request->client_ids);

            $trip->load('driver', 'route', 'vehicle', 'user');

            $array['trip'] = $trip;

            DB::commit();

            return response()->json($array, 200);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->delete();

        return response()->json(null, 204);
    }


    public function deleteTripClient($client_id)
    {
        // Encontrar a linha que corresponde ao client_id
        $cli = TripClient::where('client_id', $client_id)->firstOrFail();

        // Excluir o registro encontrado
        $cli->delete();

        // Retornar resposta de sucesso (204 - No Content)
        return response()->json(null, 204);
    }


    public function insertTripClient(Request $request)
    {
        // Valida os dados da requisição
        $validatedData = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'client_id' => 'required|exists:clients,id',
            'person_type' => 'required|in:passenger,companion',
            'destination_location' => 'required|string|max:50',
        ]);

        // Cria o registro na tabela trip_clients
        $tripClient = TripClient::create($validatedData);

        // Retorna a resposta em JSON com o status 201
        return response()->json([
            'status' => 'created',
            'trip_client' => $tripClient,
        ], 201);
    }
}
