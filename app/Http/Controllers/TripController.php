<?php

namespace App\Http\Controllers;

use App\Http\Requests\TripClientRequest;
use App\Http\Requests\TripRequest;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\TripClient;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    // public function index(Request $request)
    // {

    //     $query = Trip::query();

    //     if ($request->has('year')) {
    //         $query->whereYear('departure_date', '=', $request->year);
    //     }
    //     if ($request->has('month')) {
    //         $query->whereMonth('departure_date', '=', $request->month);
    //     }
    //     if ($request->has('day')) {
    //         $query->whereDay('departure_date', '=', $request->day);
    //     }
    //     if ($request->has('date_begin')) {
    //         $query->whereDate('departure_date', '>=', $request->date_begin);
    //     }
    //     if ($request->has('date_end')) {
    //         $query->whereDate('departure_date', '<=', $request->date_end);
    //     }
    //     if ($request->has('date_begin') && !$request->has('date_end')) {
    //         // Caso tenha apenas o 'date_begin'
    //         $query->whereDate('departure_date', '=', $request->date_begin);
    //     }


    //     return $query->with(['driver', 'vehicle', 'route', 'clients'])->get();
    // }



    public function index(Request $request)
    {
        $query = Trip::query();

        if ($request->has('year')) {
            $query->whereYear('departure_date', '=', $request->year);
        }
        if ($request->has('month')) {
            $query->whereMonth('departure_date', '=', $request->month);
        }
        if ($request->has('day')) {
            $query->whereDay('departure_date', '=', $request->day);
        }
        if ($request->has('date_begin')) {
            $query->whereDate('departure_date', '>=', $request->date_begin);
        }
        if ($request->has('date_end')) {
            $query->whereDate('departure_date', '<=', $request->date_end);
        }
        if ($request->has('date_begin') && !$request->has('date_end')) {
            // Caso tenha apenas o 'date_begin'
            $query->whereDate('departure_date', '=', $request->date_begin);
        }

        $trips = $query->with(['driver', 'vehicle', 'route', 'clients'])->get();

        // Adicionar o campo is_ok com base na confirmação dos clientes
        $trips->transform(function ($trip) {
            // Se a viagem não tiver clientes, define is_ok como false
            if ($trip->clients->isEmpty()) {
                $trip->is_ok = false;
            } else {
                // Verifica se todos os clientes confirmaram
                $allConfirmed = $trip->clients->every(fn($client) => (bool) $client->pivot->is_confirmed);
                $trip->is_ok = $allConfirmed;
            }            
            return $trip;
        });
        
        return $trips;
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
            $trip->load('driver', 'route', 'vehicle', 'user', 'clients');
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

            $trip->update($request->only(['vehicle_id', 'driver_id', 'route_id', 'departure_date', 'departure_time', 'obs']));

            $trip->load('driver', 'route', 'vehicle', 'user', 'clients');

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


    public function deleteTripClient($id)
    {

        DB::beginTransaction();
        try {

            // Encontrar a linha que corresponde ao client_id
            $cli = TripClient::findOrFail($id);

            // Excluir o registro encontrado
            $cli->delete();

            DB::commit();

            $array = ['status' => 'client deleted'];

            $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($cli->trip_id);

            $array['trip'] = $trip;

            return response()->json($array, 201);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }


    public function insertTripClient(TripClientRequest $request)
    {

        DB::beginTransaction();
        try {

            $array = ['status' => 'created'];

            // Cria o registro na tabela trip_clients
            $tripClient = TripClient::create($request->all());
            $array['trip_client'] = $tripClient;
            DB::commit();


            // $trip->clients()->sync($request->client_ids);
            // $trip->clients()->sync($request->client_ids);

            $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($request->trip_id);

            $array['trip'] = $trip;


            return response()->json($array, 201);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // public function editTripClient(TripClientRequest $request)
    // {

    //     DB::beginTransaction();
    //     try {

    //         $array = ['status' => 'updated'];

    //         // Cria o registro na tabela trip_clients
    //         $tripClient = TripClient::create($request->all());
    //         $array['trip_client'] = $tripClient;
    //         DB::commit();


    //         // $trip->clients()->sync($request->client_ids);
    //         // $trip->clients()->sync($request->client_ids);

    //         $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($request->trip_id);

    //         $array['trip'] = $trip;


    //         return response()->json($array, 201);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         throw $e;
    //     }
    // }

    
    public function editTripClient(TripClientRequest $request, $id)
{
    DB::beginTransaction();
    try {
        // Busca o registro existente na tabela trip_clients
        $tripClient = TripClient::findOrFail($id);
        
        // Apenas os campos permitidos serão atualizados
        $tripClient->update($request->only([
            'person_type', 
            'phone', 
            'departure_location', 
            'destination_location', 
            'time'
        ]));
        
        DB::commit();
        
        // Busca a viagem com seus relacionamentos atualizados
        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])
            ->findOrFail($tripClient->trip_id);
        
        return response()->json([
            'status' => 'updated',
            'trip_client' => $tripClient,
            'trip' => $trip
        ], 200);
    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'error' => 'Failed to update trip client',
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Confirma a viagem de um cliente.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmTripClient($id)
    {
        // Busca o cliente na tabela trip_clients
        $tripClient = TripClient::find($id);

        if (!$tripClient) {
            return response()->json(['error' => 'Cliente não encontrado'], 404);
        }

        // Atualiza a coluna is_confirmed para true
        $tripClient->update(['is_confirmed' => true]);


        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($tripClient->trip_id);

        // Verificar se todos os clientes da viagem estão confirmados
        if (!$trip->clients->isEmpty()) {
            $allConfirmed = $trip->clients->every(fn($client) => (bool) $client->pivot->is_confirmed);
            $trip->is_ok = $allConfirmed;
        } else {
            $trip->is_ok = false;
        }

        return response()->json(['message' => 'Viagem confirmada com sucesso', 'trip' => $trip], 200);
    }

    public function unconfirmTripClient($id)
    {
        // Busca o cliente na tabela trip_clients
        $tripClient = TripClient::find($id);

        if (!$tripClient) {
            return response()->json(['error' => 'Cliente não encontrado'], 404);
        }

        // Atualiza a coluna is_confirmed para false
        $tripClient->update(['is_confirmed' => false]);




        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($tripClient->trip_id);

        // return response()->json($array, 201);

        return response()->json(['message' => 'Confirmação de viagem revogada com sucesso', 'trip' => $trip], 200);
    }
}
