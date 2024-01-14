<?php

namespace App\Http\Controllers;

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
    
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $array = ['status' => 'created'];

            $client = new Client();
            $client->name = $request->input('name');
            $client->mother = $request->input('mother');
            $client->cpf = $request->input('cpf');
            $client->cns = $request->input('cns');
            $client->phone = $request->input('phone');
            $client->email = $request->input('email');
            $client->obs = $request->input('obs');
            $client->born_date = $request->input('born_date');
            $client->sexo = $request->input('sexo');
            $client->active = $request->input('active', true);
            $client->save();

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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $client = Client::find($id);
        if (!$client) {
            return response()->json([
                'error' => 'Client not found'
            ], 404);
        }
        $client->name = $request->input('name');
        $client->mother = $request->input('mother');
        $client->phone = $request->input('phone');
        $client->cns = $request->input('cns');
        $client->email = $request->input('email');
        $client->obs = $request->input('obs');
        $client->born_date = $request->input('born_date');
        $client->sexo = $request->input('sexo');
        $client->active = $request->input('active', true);
        $client->save();

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

        return response()->json([
            'message' => 'Client updated successfully!',
            'client' => $client
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
}
