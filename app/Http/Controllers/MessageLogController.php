<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListMessageLogsRequest;
use App\Models\MessageLog;
use Illuminate\Http\JsonResponse;

class MessageLogController extends Controller
{
    public function index(ListMessageLogsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = MessageLog::query()->with('user:id,name');

        if (! empty($data['canal'])) {
            $query->where('canal', $data['canal']);
        }

        if (! empty($data['busca'])) {
            $term = '%'.addcslashes($data['busca'], '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('destino', 'like', $term)
                    ->orWhere('assunto', 'like', $term)
                    ->orWhere('mensagem', 'like', $term);
            });
        }

        if (! empty($data['de'])) {
            $query->where('created_at', '>=', $data['de'].' 00:00:00');
        }

        if (! empty($data['ate'])) {
            $query->where('created_at', '<=', $data['ate'].' 23:59:59');
        }

        return response()->json(
            $query->orderByDesc('created_at')->orderByDesc('id')->paginate((int) ($data['per_page'] ?? 25))
        );
    }
}
