<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Models\Call;
use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $rooms = Room::with(['call_service', 'calls_per_service'])->orderBy('id', 'desc')->get();

        return response()->json($rooms);

        // return Room::orderBy('id', 'desc')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoomRequest $request)
    {
        $room = new Room;
        $room->name = $request->input('name');
        $room->description = $request->input('description');
        $room->status = $request->input('status', 'OPEN');
        $room->call_service_id = $request->input('call_service_id');
        $room->save();

        return response()->json([
            'message' => 'Room created successfully!',
            'room' => $room
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
        $room = Room::find($id);
        if (!$room) {
            return response()->json([
                'error' => 'Room not found'
            ], 404);
        }
        return response()->json($room);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRoomRequest $request, $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json([
                'error' => 'Room not found'
            ], 404);
        }
        $room->name = $request->input('name');
        $room->description = $request->input('description');
        $room->status = $request->input('status');
        $room->call_service_id = $request->input('call_service_id');
        $room->save();

        return response()->json([
            'message' => 'Room updated successfully!',
            'room' => $room
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
        $room = Room::find($id);
        if (!$room) {
            return response()->json([
                'error' => 'Room not found'
            ], 404);
        }
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully!'
        ]);
    }
}
