<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Addresses;
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
        DB::beginTransaction();
        try {
            $array = ['status' => 'created'];

            $client = Client::create($request->all());

            $address = new Addresses();
            $address->id_client = $client->id;
            $address->zip_code = $request->input('addresses.zip_code');
            $address->city = $request->input('addresses.city');
            $address->street = $request->input('addresses.street');
            $address->number = $request->input('addresses.number');
            $address->district = $request->input('addresses.district');
            $address->complement = $request->input('addresses.complement');

            $address->save();
            $array['client'] = $client;

            DB::commit();

            return $array;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
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
        DB::beginTransaction();
        try {
            $array = ['status' => 'updated'];
            $client->update($request->all());
            $array['client'] = $client;

            $address = Addresses::where('id_client', $client->id)->first();

            if (!$address)
                $address = new Addresses();

            $address->id_client = $client->id;
            $address->zip_code = $request->input('addresses.zip_code');
            $address->city = $request->input('addresses.city');
            $address->street = $request->input('addresses.street');
            $address->number = $request->input('addresses.number');
            $address->district = $request->input('addresses.district');
            $address->complement = $request->input('addresses.complement');

            $address->save();

            DB::commit();

            return response()->json([
                'message' => 'Client updated successfully!',
                'client' => $client
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
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

        return response()->json([
            'client' => $client
        ]);
    }
}
