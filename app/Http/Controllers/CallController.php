<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Room;

class CallController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $calls = Call::all();
        return response()->json($calls);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        // $roomId = $request->input('room_id');
        // $userId = $request->input('user_id');

        // // Verifica se a sala está disponível
        // $room = Room::find($roomId);
        // if ($room->status != 'OPEN' && $room->status != 'BUSY') {
        //     return response()->json([
        //         'error' => 'Room is not available for use.'
        //     ], 400);
        // }


        // Cria a nova chamada
        $call = new Call;
        $call->call_datetime = now();
        $call->user_id = $request->input('user_id');
        $call->client_id = $request->input('client_id');
        $call->call_service_id = $request->input('call_service_id');
        $call->subject = $request->input('subject');
        $call->status = $request->input('status', 'NOT_STARTED');
        $call->save();


        return response()->json([
            'message' => 'Call created successfully!',
            'call' => $call
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
        $call = Call::find($id);
        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }
        return response()->json($call);
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
        $call = Call::find($id);
        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        $call->user_id = $request->input('user_id');
        $call->room_id = $request->input('room_id');
        $call->client_id = $request->input('client_id');
        $call->reason = $request->input('reason');
        $call->status = $request->input('status');

        $call->save();

        return response()->json([
            'message' => 'Call updated successfully!',
            'call' => $call
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
        return response()->json([
            'message' => 'method not was implemented!'
        ]);
    }

    /**
     * Start the specified call.
     *
     * @param  int  $id
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function start_time(Request $request, $id)
    {
        $userId = $request->input('user_id');
        $call = Call::find($id);
        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        if ($call->status != "NOT_STARTED" && $call->status != "ABANDONED") {
            return response()->json([
                'error' => 'Call cannot be started'
            ], 422);
        }

        $room = Room::find($request->room_id);

        // Verifica se a sala está aaberta
        if (!$room) {
            return response()->json([
                'error' => 'Room not found'
            ], 404);
        }

        if ($room->status != 'OPEN' && $room->status != 'BUSY') {
            return response()->json([
                'error' => 'Room is closed.'
            ], 400);
        }

        // Verifica se a sala está disponível
        // $room = Room::where('id', $request->room_id)->first();
        if ($room->status !== 'OPEN') {
            return response()->json([
                'error' => 'The room is currently busy'
            ], 422);
        }

        // Verifica se o usuario está disponível
        $callsInProgress = Call::where('user_id', $userId)
            ->where('status', 'IN_PROGRESS')
            ->count();
        if ($callsInProgress > 0) {
            return response()->json([
                'error' => 'This user is already attending a call.'
            ], 400);
        }


        $call->start_datetime = now();
        $call->status = "IN_PROGRESS";
        $call->room_id = $request->input('room_id');
        $call->user_id = $userId;
        $call->save();

        // Atualiza o status da sala para BUSY
        if ($room) {
            $room->status = 'BUSY';
            $room->save();
        }

        return response()->json([
            'message' => 'Call started successfully!',
            'call' => $call
        ]);
    }


    /**
     * End the specified call.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function end_time($id)
    {
        $call = Call::find($id);
        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        if ($call->status != "IN_PROGRESS") {
            return response()->json([
                'error' => 'Call cannot be ended'
            ], 422);
        }

        $call->end_datetime = now();
        $call->status = "CLOSED";
        $call->save();

        // atualiza o status da room para open

        $room = Room::where('id', $call->room_id)->first();

        if (!$room) {
            return response()->json([
                'error' => 'Room not found'
            ], 404);
        }

        if ($room->status !== 'BUSY' && $room->status !== 'CLOSED') {
            return response()->json([
                'error' => 'The room is already empty'
            ], 422);
        }

        if ($room) {
            $room->status = "OPEN";
            $room->save();
        }

        return response()->json([
            'message' => 'Call ended successfully!',
            'call' => $call
        ]);
    }


    /**
     * Mark the specified call as abandoned.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function abandon(Request $request, $id)
    {
        $call = Call::find($id);
        if (!$call) {
            return response()->json([
                'error' => 'Call not found'
            ], 404);
        }

        if ($call->status != "NOT_STARTED") {
            return response()->json([
                'error' => 'Call cannot be ended'
            ], 422);
        }

        $call->end_datetime = now();
        $call->status = 'ABANDONED';
        $call->save();

        return response()->json([
            'message' => 'Call marked as abandoned successfully!',
            'call' => $call
        ]);
    }
}
