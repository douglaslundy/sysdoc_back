<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Addresses;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clients = Client::with(['addresses'])
            ->where('active', true)
            ->orderBy('name', 'asc')->get();

        return response()->json($clients);
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
            $client = Client::create($request->all());

            Addresses::create([
                'id_client'  => $client->id,
                'zip_code'   => $request->input('addresses.zip_code'),
                'city'       => $request->input('addresses.city'),
                'street'     => $request->input('addresses.street'),
                'number'     => $request->input('addresses.number'),
                'district'   => $request->input('addresses.district'),
                'complement' => $request->input('addresses.complement'),
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
        $client = Client::with(['addresses'])->find($id);
        if (!$client) {
            return response()->json([
                'error' => 'Client not found'
            ], 404);
        }
        AuditService::record('VIEW', $client, null, [
            'nome'  => $client->name,
            'cpf'   => $client->cpf,
            'cns'   => $client->cns,
            'email' => $client->email,
            'phone' => $client->phone,
        ]);
        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Client  $client
     * @return \Illuminate\Http\Response
     */
    public function update(ClientRequest $request, Client $client)
    {
        DB::transaction(function () use ($request, $client) {
            $client->update($request->all());

            $address = Addresses::firstOrNew(['id_client' => $client->id]);
            $address->fill([
                'id_client'  => $client->id,
                'zip_code'   => $request->input('addresses.zip_code'),
                'city'       => $request->input('addresses.city'),
                'street'     => $request->input('addresses.street'),
                'number'     => $request->input('addresses.number'),
                'district'   => $request->input('addresses.district'),
                'complement' => $request->input('addresses.complement'),
            ])->save();
        });

        return response()->json([
            'message' => 'Client updated successfully!',
            'client'  => $client->load('addresses'),
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
        $client = Client::find($id);
        if (!$client) {
            return response()->json([
                'error' => 'Client not found'
            ], 404);
        }
        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully!'
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
        $termo = preg_replace('/\D/', '', trim($request->query('q', '')));

        if (strlen($termo) < 6) {
            return response()->json(['message' => 'Informe ao menos 6 dígitos do CPF ou CNS.'], 422);
        }

        $client = Client::where(function ($q) use ($termo) {
            $q->where('cpf', 'like', "%{$termo}%")
              ->orWhere('cns', 'like', "%{$termo}%");
        })->where('active', true)->first();

        if (!$client) {
            return response()->json(['message' => 'Paciente não encontrado.'], 404);
        }

        return response()->json($client);
    }

    public function detailedClientReport(Request $request)
    {
        $value = trim($request->query('value', ''));

        if ($value === '') {
            return response()->json([
                'message' => 'Parâmetro value é obrigatório'
            ], 400);
        }

        $client = Client::with([
            'trips.user',
            'trips.driver',
            'trips.vehicle',
            'trips.route',
            'queue.speciality',
            'queue.user',
        ])
            ->where(function ($query) use ($value) {
                $query->where('cpf', $value)
                    ->orWhere('cns', $value)
                    ->orWhere('id', $value);
            })
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Cliente não encontrado'
            ], 404);
        }

        AuditService::record('VIEW_REPORT', $client,
            ['termo_pesquisado' => $value],
            [
                'nome'          => $client->name,
                'cpf'           => $client->cpf,
                'cns'           => $client->cns,
                'phone'         => $client->phone,
                'total_viagens' => $client->trips ? $client->trips->count() : 0,
                'total_filas'   => $client->queue ? $client->queue->count() : 0,
            ]
        );
        return response()->json([
            'client' => $client
        ]);
    }
}
