<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\EndedCall;

class EndedController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $calls = EndedCall::all();
        return response()->json($calls);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */  

     public function store(Request $request)
     {
         $call = new EndedCall;

         $call->user_id = $request->input('user_id');
         $call->client_id = $request->input('client_id');
         $call->call_service_forwarded_id = $request->input('call_service_forwarded_id');
         $call->call_id = $request->input('call_id');
         $call->descrition = $request->input('descrition');
         $call->service_status = $request->input('service_status');
         $call->save();
 
 
         return response()->json([
             'message' => 'EndedCall created successfully!',
             'call' => $call
         ], 201);
     }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
