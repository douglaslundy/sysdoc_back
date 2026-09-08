<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplicateTripRequest;
use App\Http\Requests\TripClientRequest;
use App\Http\Requests\TripRequest;
use App\Models\Trip;
use App\Models\TripClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    /**
     * Lista mínima de motoristas ativos (id + nome) para preencher o select de
     * motorista no cadastro/edição de viagem. Qualquer usuário autenticado que
     * pode acessar o módulo de viagens (mesmo nível de acesso de /vehicles e
     * /routes) precisa disso — diferente de GET /users (dados completos de
     * usuário), que é restrito a admin.
     */
    public function drivers()
    {
        return User::query()
            ->where('active', true)
            ->where('is_driver', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

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
        if ($request->has('date_begin') && ! $request->has('date_end')) {
            // Caso tenha apenas o 'date_begin'
            $query->whereDate('departure_date', '=', $request->date_begin);
        }
        if ($request->has('client_id')) {
            $query->whereHas('clients', function ($clientsQuery) use ($request) {
                $clientsQuery->where('clients.id', $request->client_id);
            });
        }

        $trips = $query
            ->with(['driver', 'vehicle', 'route', 'clients'])
            ->orderBy('departure_date', 'asc') // ou 'desc' se quiser ordem decrescente
            ->get();

        // Adicionar o campo is_ok com base na confirmação dos clientes
        $trips->transform(function ($trip) {
            // Se a viagem não tiver clientes, define is_ok como false
            if ($trip->clients->isEmpty()) {
                $trip->is_ok = false;
            } else {
                // Verifica se todos os clientes confirmaram
                $allConfirmed = $trip->clients->every(fn ($client) => (bool) $client->pivot->is_confirmed);
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
        $trip = DB::transaction(function () use ($request) {
            $trip = Trip::create($request->all());

            return $trip->load('driver', 'route', 'vehicle', 'user', 'clients');
        });

        return response()->json(['status' => 'created', 'trip' => $trip], 201);
    }

    public function show($id)
    {
        return Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(TripRequest $request, Trip $trip)
    {
        DB::transaction(function () use ($request, $trip) {
            $trip->update($request->only(['vehicle_id', 'driver_id', 'route_id', 'departure_date', 'departure_time', 'obs']));
            $trip->load('driver', 'route', 'vehicle', 'user', 'clients');
        });

        return response()->json(['status' => 'updated', 'trip' => $trip], 200);
    }

    public function replicate(ReplicateTripRequest $request, Trip $trip)
    {
        $trip->load('clients');

        $newTrips = DB::transaction(function () use ($request, $trip) {
            $created = [];

            foreach ($request->dates as $date) {
                $newTrip = Trip::create([
                    'user_id' => auth()->id(),
                    'driver_id' => null,
                    'vehicle_id' => null,
                    'route_id' => $trip->route_id,
                    'departure_time' => null,
                    'departure_date' => $date,
                    'obs' => $trip->obs,
                ]);

                foreach ($trip->clients as $client) {
                    TripClient::create([
                        'trip_id' => $newTrip->id,
                        'client_id' => $client->id,
                        'person_type' => $client->pivot->person_type,
                        'phone' => $client->pivot->phone,
                        'departure_location' => $client->pivot->departure_location,
                        'destination_location' => $client->pivot->destination_location,
                        'time' => $client->pivot->time,
                        'is_confirmed' => false,
                    ]);
                }

                $created[] = $newTrip->load('driver', 'route', 'vehicle', 'user', 'clients');
            }

            return $created;
        });

        return response()->json(['status' => 'replicated', 'trips' => $newTrips], 201);
    }

    public function destroy($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->delete();

        return response()->json(null, 204);
    }

    public function deleteTripClient($id)
    {
        $tripId = DB::transaction(function () use ($id) {
            $cli = TripClient::findOrFail($id);
            $cli->delete();

            return $cli->trip_id;
        });

        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($tripId);

        return response()->json(['status' => 'client deleted', 'trip' => $trip], 201);
    }

    public function insertTripClient(TripClientRequest $request)
    {
        $tripClient = DB::transaction(fn () => TripClient::create($request->all()));

        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($request->trip_id);

        return response()->json(['status' => 'created', 'trip_client' => $tripClient, 'trip' => $trip], 201);
    }

    public function editTripClient(TripClientRequest $request, $id)
    {
        $tripClient = DB::transaction(function () use ($request, $id) {
            $tripClient = TripClient::findOrFail($id);
            $tripClient->update($request->only([
                'person_type', 'phone', 'departure_location', 'destination_location', 'time',
            ]));

            return $tripClient;
        });

        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($tripClient->trip_id);

        return response()->json(['status' => 'updated', 'trip_client' => $tripClient, 'trip' => $trip], 200);
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

        if (! $tripClient) {
            return response()->json(['error' => 'Cliente não encontrado'], 404);
        }

        // Atualiza a coluna is_confirmed para true
        $tripClient->update(['is_confirmed' => true]);

        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($tripClient->trip_id);

        // Verificar se todos os clientes da viagem estão confirmados
        if (! $trip->clients->isEmpty()) {
            $allConfirmed = $trip->clients->every(fn ($client) => (bool) $client->pivot->is_confirmed);
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

        if (! $tripClient) {
            return response()->json(['error' => 'Cliente não encontrado'], 404);
        }

        // Atualiza a coluna is_confirmed para false
        $tripClient->update(['is_confirmed' => false]);

        $trip = Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($tripClient->trip_id);

        // return response()->json($array, 201);

        return response()->json(['message' => 'Confirmação de viagem revogada com sucesso', 'trip' => $trip], 200);
    }
}
