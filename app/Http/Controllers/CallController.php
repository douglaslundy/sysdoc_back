<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Call;
use App\Models\CallService;
use App\Models\EndedCall;
use App\Models\Room;
use Exception;

class CallController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $calls = Call::with(['client'])->orderBy('id', 'desc')->get();
        return response()->json($calls);
    }

    public function today_calls()
    {
        $calls = Call::with(['client'])
        ->whereRaw('DATE(created_at) = ?', [now()->toDateString()])
        ->orderBy('id', 'asc')->get();
        return response()->json($calls);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function last_call_number_per_prefix($prefix)
    {

        // Neste código:
        // now()->toDateString() obtém a data atual no formato "Y-m-d" (ano-mês-dia).
        // DATE(created_at) extrai a parte da data de created_at.
        // whereRaw é usado para permitir expressões SQL brutas.
        // Dessa forma, você estará comparando apenas as partes da data (ano, mês e dia) de created_at com a data atual.


        return $number = Call::where('call_prefix', $prefix)
            ->whereRaw('DATE(created_at) = ?', [now()->toDateString()])
            ->orderBy('id', 'desc')
            ->value('call_number');
    }


    public function store(Request $request)
    {
        // Cria a nova chamada
        $call = new Call;
        $call->call_datetime = now();
        $call->user_id = $request->input('user_id');
        $call->client_id = $request->input('client_id');
        $call->call_service_id = $request->input('call_service_id');
        $call->subject = $request->input('subject');
        $call->status = $request->input('status', 'NOT_STARTED');

        $prefix = CallService::find($call->call_service_id);

        $call->call_number = $this->last_call_number_per_prefix(strtoupper(substr($prefix->name, 0, 3))) + 1;

        $call->call_prefix = strtoupper(substr($prefix->name, 0, 3));

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
        $call = Call::with('client')->find($id);
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
        $call->is_called = $request->input('is_called');

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

        // Verifica se a sala está aberta
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


    public function saveEndedCall(Request $request)
    {
        // Seu código de criação de EndedCall aqui
        $endedCall = new EndedCall;
        $endedCall->user_id = $request->input('user_id');
        $endedCall->client_id = $request->input('client_id');
        $endedCall->call_service_forwarded_id = $request->input('call_service_forwarded_id');
        $endedCall->call_id = $request->input('call_id');
        $endedCall->description = $request->input('description');
        $endedCall->service_status = $request->input('service_status');
        $endedCall->save();
    }

    // metodo utilizado para atualizar o serviço ao qual cliente foi encaminhado
    public function updateForwardedCall($id, $idCallServiceForwardedId)
    {
        $call = Call::find($id);
        $call->status = 'NOT_STARTED';
        $call->is_called = 'NO';
        $call->call_service_id = $idCallServiceForwardedId;
        $call->save();
        return $call;
    }

    // metodo utilizado para atualizar o serviço ao qual cliente foi encaminhado
    public function openRoom($id)
    {
        $room = Room::find($id);
        $room->status = 'OPEN';
        $room->save();
    }


    // metodo utilizado quando a chamada for encaminhada
    public function forwardCall($id, Request $request)
    {
        // Iniciar a transação manualmente
        DB::beginTransaction();

        try {

            $endedCall = $this->saveEndedCall($request);

            // Atualizar o status do call
            $call = $this->updateForwardedCall($id, $request->input('call_service_forwarded_id'));

            // Atualizar o status do room
            $this->openRoom($request->input('room_id'));

            // Confirmar a transação
            DB::commit();
        } catch (Exception $e) {
            // Reverter a transação em caso de exceção
            DB::rollback();
            throw $e;
        }


        return response()->json([
            'message' => 'EndedCall created successfully!',
            'call' => $call,
            'endedCall' => $endedCall
        ], 201);
    }
    // 


    /**
     * End the specified call.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function end_time($id, Request $request)
    {
        if ($request->input('service_status') == 'forwarded')
            return $this->forwardCall($id, $request);

        // falta salvar o ended call aqui
        if ($request->input('service_status') == 'finished')
            return $this->end_call($id, $request);
    }


    public function end_call($id, Request $request)
    {
        // Iniciar a transação manualmente
        DB::beginTransaction();

        try {

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

            $endedCall = $this->saveEndedCall($request);

            // Confirmar a transação
            DB::commit();
        } catch (Exception $e) {
            // Reverter a transação em caso de exceção
            DB::rollback();
            throw $e;
        }

        return response()->json([
            'message' => 'Call ended successfully!',
            'call' => $call,
            'endedCall' => $endedCall
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

    /**
     * Mark the specified call as called
     *
     * @return \Illuminate\Http\Response
     */

    public function called_call()
    {
        $getCall = Call::where('is_called', 'NOW')
            ->with(['client'])
            ->where('status', 'NOT_STARTED')
            ->first();

        if ($getCall) {
            $getCall->is_called = 'YES';
            $getCall->when_was_called = now();
            $getCall->save();
        }

        return response()->json($getCall);
    }


    public function lasts_calls()
    {
        // Obter o ID do último registro
        $lastCallDateTime = Call::where('is_called', 'YES')
            ->orderBy('when_was_called', 'desc')
            ->value('when_was_called');

        // Obter os 4 registros mais recentes, excluindo o último
        $getCalls = Call::where('is_called', 'YES')
            ->where('when_was_called', '<', $lastCallDateTime)
            ->orderBy('when_was_called', 'desc')
            ->take(4)
            ->get();

        return response()->json($getCalls);
    }
}
