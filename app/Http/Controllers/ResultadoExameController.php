<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarCamposResultadoRequest;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Services\AuditService;
use App\Services\Laboratorio\ResultadoExameService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultadoExameController extends Controller
{
    public function __construct(private ResultadoExameService $service) {}

    public function store($pedidoId)
    {
        $pedido = PedidoExame::find($pedidoId);
        if (!$pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        $resultado = DB::transaction(function () use ($pedidoId) {
            $existing = ResultadoExame::where('pedido_exame_id', $pedidoId)
                ->lockForUpdate()
                ->first();

            return $existing ?? ResultadoExame::create([
                'pedido_exame_id' => $pedidoId,
                'ativo'           => true,
            ]);
        });

        return response()->json([
            'message'   => 'Resultado iniciado!',
            'resultado' => $resultado->load([
                'campos.campo',
                'pedido.cliente',
                'pedido.exames.camposAtivos.referencias',
            ]),
        ], 201);
    }

    public function show($id)
    {
        $resultado = ResultadoExame::with([
            'pedido.cliente',
            'pedido.exames.camposAtivos.referencias',
            'campos.campo',
            'liberadoPor',
        ])->find($id);

        if (!$resultado) {
            return response()->json(['error' => 'Resultado não encontrado'], 404);
        }

        AuditService::record('VIEW', $resultado);

        return response()->json($resultado);
    }

    public function salvarCampos(SalvarCamposResultadoRequest $request, $id)
    {
        $resultado = ResultadoExame::find($id);
        if (!$resultado) {
            return response()->json(['error' => 'Resultado não encontrado'], 404);
        }

        if ($resultado->data_liberacao) {
            return response()->json(['error' => 'Resultado já liberado, não pode ser alterado'], 422);
        }

        try {
            $this->service->salvarCampos($resultado, $request->input('campos'));
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'   => 'Campos salvos com sucesso!',
            'resultado' => $resultado->load('campos.campo'),
        ]);
    }

    public function liberar($id)
    {
        $resultado = ResultadoExame::find($id);
        if (!$resultado) {
            return response()->json(['error' => 'Resultado não encontrado'], 404);
        }

        try {
            ['resultado' => $resultado, 'senha' => $senha] = $this->service->liberar($resultado, Auth::id());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'    => 'Resultado liberado com sucesso!',
            'protocolo'  => $resultado->protocolo,
            'senha'      => $senha,
            'resultado'  => $resultado,
        ]);
    }

    public function downloadPdf($id): StreamedResponse|array
    {
        $resultado = ResultadoExame::find($id);
        if (!$resultado || !$resultado->pdf_path) {
            return response()->json(['error' => 'PDF não disponível'], 404);
        }

        if (!Storage::exists($resultado->pdf_path)) {
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        AuditService::record('DOWNLOAD', $resultado);

        return Storage::download($resultado->pdf_path, 'laudo-' . $resultado->protocolo . '.pdf');
    }
}
