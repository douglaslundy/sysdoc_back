<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clients = Client::all();
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
        $client = new Client;
        $client->nome = $request->input('nome');
        $client->mother = $request->input('mother');
        $client->cpf = $request->input('cpf');
        $client->phone = $request->input('phone');
        $client->email = $request->input('email');
        $client->obs = $request->input('obs');
        $client->born_date = $request->input('born_date');
        $client->sexo = $request->input('sexo');
        $client->active = $request->input('active', true);
        $client->save();

        return response()->json([
            'message' => 'Client created successfully!',
            'client' => $client
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $client = Client::find($id);
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
        $client->nome = $request->input('nome');
        $client->mother = $request->input('mother');
        $client->phone = $request->input('phone');
        $client->email = $request->input('email');
        $client->obs = $request->input('obs');
        $client->born_date = $request->input('born_date');
        $client->sexo = $request->input('sexo');
        $client->active = $request->input('active', true);
        $client->save();

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
