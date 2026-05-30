<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Http\Requests\ListClientsRequest;
use App\Http\Resources\ClientListResource;
use App\Models\Addresses;
use App\Models\Client;
use App\Services\AuditService;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ListClientsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 10);
        $result = $this->listQuery($validated)->paginate($perPage);

        AuditService::record('VIEW', null, null, [
            'event' => 'LIST_CLIENTS',
            'has_search' => ! empty($validated['search']),
            'per_page' => $perPage,
            'total' => $result->total(),
        ]);

        return ClientListResource::collection($result);
    }

    public function select(ListClientsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 20);

        $clients = $this->listQuery($validated)
            ->limit($limit)
            ->get();

        return response()->json(ClientListResource::collection($clients)->resolve());
    }

    private function listQuery(array $filters): Builder
    {
        $query = Client::query()
            ->select(['id', 'name', 'mother', 'cpf', 'cns', 'phone', 'born_date'])
            ->with(['addresses:id_client,street,number,district,city'])
            ->where('active', true);

        if (! empty($filters['search'])) {
            $search = $this->escapeLike(trim($filters['search']));
            $digits = preg_replace('/\D/', '', $search);

            $query->where(function ($q) use ($search, $digits) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('mother', 'LIKE', "%{$search}%");

                if (strlen($digits) >= 3) {
                    $q->orWhere('cpf', 'LIKE', "%{$digits}%")
                        ->orWhere('cns', 'LIKE', "%{$digits}%")
                        ->orWhere('phone', 'LIKE', "%{$digits}%");
                }
            });
        }

        return $query->orderBy('name');
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ClientRequest $request)
    {
        $client = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $addressData = $data['addresses'];
            unset($data['addresses']);

            $client = Client::create($data);

            Addresses::create([
                'id_client' => $client->id,
                'zip_code' => $addressData['zip_code'],
                'city' => $addressData['city'],
                'street' => $addressData['street'],
                'number' => $addressData['number'],
                'district' => $addressData['district'],
                'complement' => $addressData['complement'] ?? null,
            ]);

            return $client;
        });

        return response()->json(['status' => 'created', 'client' => $client->load('addresses')], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! app(PagePermissionService::class)->canAccess(request()->user(), '/clients')) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        $client = Client::with(['addresses'])->find($id);
        if (! $client) {
            return response()->json([
                'error' => 'Client not found',
            ], 404);
        }
        AuditService::record('VIEW', $client, null, [
            'nome' => $client->name,
            'cpf' => $client->cpf,
            'cns' => $client->cns,
            'email' => $client->email,
            'phone' => $client->phone,
        ]);

        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(ClientRequest $request, Client $client)
    {
        DB::transaction(function () use ($request, $client) {
            $data = $request->validated();
            $addressData = $data['addresses'];
            unset($data['addresses']);

            // Proteção: se inativo E já tem data_obito, ignorar tentativa de alterar esses campos
            $jaInativoPorObito = !$client->active && $client->data_obito !== null;
            if ($jaInativoPorObito) {
                unset($data['data_obito'], $data['st_falecido'], $data['active']);
            }

            // Regra de óbito: cliente ativo + data_obito informada → inativar tudo
            $inativandoAgora = $client->active && !empty($data['data_obito']);
            if ($inativandoAgora) {
                $data['active']      = false;
                $data['st_falecido'] = true;
            }

            $client->update($data);

            if ($inativandoAgora) {
                $dtFormatada = Carbon::parse($data['data_obito'])->format('d/m/Y');
                $obsTexto = "Baixa automática devido ao óbito ocorrido em {$dtFormatada}";
                $client->queue()->where('done', false)->each(function ($queue) use ($obsTexto) {
                    $novaObs = $queue->obs
                        ? $queue->obs . ' | ' . $obsTexto
                        : $obsTexto;
                    $queue->update([
                        'done'             => true,
                        'date_of_realized' => now()->toDateString(),
                        'obs'              => substr($novaObs, 0, 200),
                    ]);
                });
            }

            $address = Addresses::firstOrNew(['id_client' => $client->id]);
            $address->fill([
                'id_client'  => $client->id,
                'zip_code'   => $addressData['zip_code'],
                'city'       => $addressData['city'],
                'street'     => $addressData['street'],
                'number'     => $addressData['number'],
                'district'   => $addressData['district'],
                'complement' => $addressData['complement'] ?? null,
            ])->save();
        });

        return response()->json([
            'message' => 'Client updated successfully!',
            'client'  => $client->fresh()->load('addresses'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! app(PagePermissionService::class)->canAccess(request()->user(), '/clients')) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        $client = Client::find($id);
        if (! $client) {
            return response()->json([
                'error' => 'Client not found',
            ], 404);
        }
        $client->update(['active' => false]);

        return response()->json([
            'message' => 'Client inactivated successfully!',
        ]);
    }

    // public function detailedClientReport(Request $request)
    // {
    //     $value = trim($request->query('value', ''));

    //     if ($value === '') {
    //         return response()->json([
    //             'message' => 'Parâmetro value é obrigatório'
    //         ], 400);
    //     }

    //     $client = Client::with(['trips', 'queue'])
    //         ->where(function ($query) use ($value) {
    //             $query->where('cpf', $value)
    //                 ->orWhere('cns', $value)
    //                 ->orWhere('id', $value);
    //         })
    //         ->first();

    //     if (!$client) {
    //         return response()->json([
    //             'message' => 'Cliente não encontrado'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'client' => $client
    //     ]);
    // }

    public function buscarPorCpfCns(Request $request)
    {
        if (! app(PagePermissionService::class)->canAccessAny($request->user(), ['/clients', '/laboratorio/pedidos'])) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        $termo = preg_replace('/\D/', '', trim($request->query('q', '')));

        if (strlen($termo) < 6) {
            return response()->json(['message' => 'Informe ao menos 6 dígitos do CPF ou CNS.'], 422);
        }

        $client = Client::where(function ($q) use ($termo) {
            $q->where('cpf', 'like', "%{$termo}%")
                ->orWhere('cns', 'like', "%{$termo}%");
        })->where('active', true)->first();

        if (! $client) {
            return response()->json(['message' => 'Paciente não encontrado.'], 404);
        }

        return response()->json($client);
    }

    public function detailedClientReport(Request $request)
    {
        if (! app(PagePermissionService::class)->canAccess($request->user(), '/client_report')) {
            return response()->json(['message' => 'Voce nao possui permissao para executar esta acao.'], 403);
        }

        $value = trim($request->query('value', ''));

        if ($value === '') {
            return response()->json([
                'message' => 'Parâmetro value é obrigatório',
            ], 400);
        }

        $client = Client::with([
            'trips.user',
            'trips.driver',
            'trips.vehicle',
            'trips.route',
            'queue.speciality',
            'queue.user',
            'pedidosExame.exames',
            'pedidosExame.medicoSolicitante',
            'pedidosExame.criadoPor',
            'pedidosExame.resultado.liberadoPor',
        ])
            ->where(function ($query) use ($value) {
                $query->where('cpf', $value)
                    ->orWhere('cns', $value)
                    ->orWhere('id', $value);
            })
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'Cliente não encontrado',
            ], 404);
        }

        AuditService::record('VIEW_REPORT', $client,
            ['termo_pesquisado' => $value],
            [
                'nome' => $client->name,
                'cpf' => $client->cpf,
                'cns' => $client->cns,
                'phone' => $client->phone,
                'total_viagens' => $client->trips ? $client->trips->count() : 0,
                'total_filas' => $client->queue ? $client->queue->count() : 0,
            ]
        );

        return response()->json([
            'client' => $client,
        ]);
    }
}
