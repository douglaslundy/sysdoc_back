<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripClient;
use App\Models\TripScheduleEntry;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Agenda de viagens: fila por data de passageiros (+ acompanhantes) que ainda não têm viagem. */
class TripScheduleController extends Controller
{
    private const STATUSES = ['agendado', 'confirmado'];

    private function autorizar(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || ! app(PagePermissionService::class)->canAccess($user, '/trips')) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->autorizar($request)) {
            return $negado;
        }
        $v = $request->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from']);

        // Passageiro primeiro, acompanhantes logo abaixo dele.
        $entries = TripScheduleEntry::with('client')
            ->whereBetween('scheduled_date', [$v['from'], $v['to']])
            ->orderBy('scheduled_date')
            ->orderByRaw('COALESCE(companion_of, id), companion_of IS NOT NULL, id')
            ->get();

        return response()->json($entries);
    }

    public function store(Request $request): JsonResponse
    {
        if ($negado = $this->autorizar($request)) {
            return $negado;
        }

        $v = $request->validate([
            'scheduled_date' => 'required_without:companion_of|nullable|date_format:Y-m-d',
            'client_id' => 'required|integer|exists:clients,id',
            'companion_of' => 'nullable|integer|exists:trip_schedule_entries,id',
            'phone' => 'nullable|string|max:20',
            'departure_location' => 'nullable|string|max:50',
            'destination_location' => 'nullable|string|max:50',
            'time' => 'nullable|date_format:H:i',
            'obs' => 'nullable|string|max:300',
            'companions' => 'nullable|array|max:10',
            'companions.*.client_id' => 'required|integer|exists:clients,id',
            'companions.*.phone' => 'nullable|string|max:20',
        ]);

        $parent = null;
        if (! empty($v['companion_of'])) {
            $parent = TripScheduleEntry::find($v['companion_of']);
            if (! $parent || $parent->person_type !== 'passenger') {
                return response()->json(['message' => 'Passageiro da agenda não encontrado.'], 422);
            }
        }

        $date = $parent ? $parent->scheduled_date->toDateString() : $v['scheduled_date'];

        $clientIds = array_merge([$v['client_id']], array_column($v['companions'] ?? [], 'client_id'));
        if (count($clientIds) !== count(array_unique($clientIds))) {
            return response()->json(['message' => 'Há pessoa repetida no mesmo agendamento.'], 422);
        }
        if (TripScheduleEntry::whereDate('scheduled_date', $date)->whereIn('client_id', $clientIds)->exists()) {
            return response()->json(['message' => 'Alguém deste agendamento já está na agenda desse dia.'], 422);
        }

        $criados = DB::transaction(function () use ($request, $v, $parent, $date) {
            $base = [
                'scheduled_date' => $date,
                'phone' => $v['phone'] ?? null,
                'departure_location' => $v['departure_location'] ?? $parent?->departure_location,
                'destination_location' => $v['destination_location'] ?? $parent?->destination_location,
                'time' => $v['time'] ?? $parent?->time,
                'obs' => $v['obs'] ?? null,
                'created_by' => $request->user()?->id,
            ];
            $main = TripScheduleEntry::create($base + [
                'client_id' => $v['client_id'],
                'person_type' => $parent ? 'companion' : 'passenger',
                'companion_of' => $parent?->id,
            ]);
            $criados = [$main];
            foreach ($v['companions'] ?? [] as $c) {
                $criados[] = TripScheduleEntry::create([
                    'client_id' => $c['client_id'],
                    'phone' => $c['phone'] ?? null,
                    'person_type' => 'companion',
                    'companion_of' => $main->id,
                ] + $base);
            }

            return $criados;
        });

        return response()->json(collect($criados)->map->load('client'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($negado = $this->autorizar($request)) {
            return $negado;
        }
        $entry = TripScheduleEntry::findOrFail($id);
        $v = $request->validate([
            'scheduled_date' => 'sometimes|date_format:Y-m-d',
            'phone' => 'nullable|string|max:20',
            'departure_location' => 'nullable|string|max:50',
            'destination_location' => 'nullable|string|max:50',
            'time' => 'nullable|date_format:H:i',
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'obs' => 'nullable|string|max:300',
        ]);

        DB::transaction(function () use ($entry, $v) {
            $entry->update($v);
            if (isset($v['scheduled_date']) && $entry->person_type === 'passenger') {
                // Acompanhantes acompanham o passageiro na mudança de data.
                TripScheduleEntry::where('companion_of', $entry->id)->update(['scheduled_date' => $v['scheduled_date']]);
            }
        });

        return response()->json($entry->fresh('client'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($negado = $this->autorizar($request)) {
            return $negado;
        }
        $entry = TripScheduleEntry::findOrFail($id);
        DB::transaction(function () use ($entry) {
            TripScheduleEntry::where('companion_of', $entry->id)->update(['companion_of' => null]); // acompanhantes ficam na agenda
            $entry->delete();
        });

        return response()->json(null, 204);
    }

    /** Envia passageiros (com seus acompanhantes) da agenda para uma viagem existente ou nova; saem da agenda. */
    public function send(Request $request): JsonResponse
    {
        if ($negado = $this->autorizar($request)) {
            return $negado;
        }
        $v = $request->validate([
            'entry_ids' => 'required|array|min:1|max:100',
            'entry_ids.*' => 'integer',
            'trip_id' => 'nullable|integer|exists:trips,id',
            'new_trip' => 'nullable|array',
            'new_trip.route_id' => 'required_with:new_trip|integer|exists:routes,id',
            'new_trip.departure_date' => 'required_with:new_trip|date_format:Y-m-d',
            'new_trip.departure_time' => 'nullable|date_format:H:i',
            'new_trip.vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'new_trip.driver_id' => 'nullable|integer|exists:users,id',
        ]);
        if (empty($v['trip_id']) === empty($v['new_trip'])) {
            return response()->json(['message' => 'Escolha uma viagem existente ou os dados da nova viagem.'], 422);
        }

        $selecionadas = TripScheduleEntry::whereIn('id', $v['entry_ids'])->get();
        if ($selecionadas->isEmpty()) {
            return response()->json(['message' => 'Nenhum item da agenda encontrado.'], 422);
        }
        // Acompanhantes sempre vão junto do passageiro; passageiros entram antes (para religar companion_of).
        $paxIds = $selecionadas->where('person_type', 'passenger')->pluck('id');
        $todas = $selecionadas->merge(TripScheduleEntry::whereIn('companion_of', $paxIds)->get())
            ->unique('id')
            ->sortBy(fn ($e) => $e->person_type === 'passenger' ? 0 : 1)
            ->values();

        try {
            $trip = DB::transaction(function () use ($v, $todas, $request) {
                if (! empty($v['trip_id'])) {
                    $trip = Trip::findOrFail($v['trip_id']);
                } else {
                    $n = $v['new_trip'];
                    $trip = Trip::create([
                        'user_id' => $request->user()->id,
                        'route_id' => $n['route_id'],
                        'departure_date' => $n['departure_date'],
                        'departure_time' => $n['departure_time'] ?? null,
                        'vehicle_id' => $n['vehicle_id'] ?? null,
                        'driver_id' => $n['driver_id'] ?? null,
                    ]);
                }

                $ja = TripClient::where('trip_id', $trip->id)->get();
                if ($todas->pluck('client_id')->intersect($ja->pluck('client_id'))->isNotEmpty()) {
                    abort(422, 'Alguém da seleção já está nesta viagem.');
                }
                if ($trip->vehicle && ($ja->count() + $todas->count()) > $trip->vehicle->capacity - 1) {
                    abort(422, 'A capacidade máxima do veículo seria ultrapassada.');
                }

                $novoId = [];
                foreach ($todas as $e) {
                    $time = $e->time ?: ($trip->departure_time ? substr((string) $trip->departure_time, 0, 5) : null);
                    if (! $time) {
                        abort(422, 'Informe o horário do compromisso (ou o horário de saída da viagem).');
                    }
                    $novoId[$e->id] = TripClient::create([
                        'trip_id' => $trip->id,
                        'client_id' => $e->client_id,
                        'person_type' => $e->person_type,
                        'companion_of' => $e->companion_of ? ($novoId[$e->companion_of] ?? null) : null,
                        'phone' => $e->phone,
                        'departure_location' => $e->departure_location,
                        'destination_location' => $e->destination_location,
                        'time' => $time,
                        'is_confirmed' => $e->status === 'confirmado',
                    ])->id;
                }
                TripScheduleEntry::whereIn('id', $todas->pluck('id'))->delete();

                return $trip;
            });
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['status' => 'sent', 'trip' => Trip::with(['driver', 'vehicle', 'route', 'clients'])->find($trip->id)]);
    }
}
