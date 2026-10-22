<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBannedIpSettingsRequest;
use App\Models\BannedIp;
use App\Services\BannedIpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannedIpController extends Controller
{
    public function __construct(private BannedIpService $bans)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $page = BannedIp::query()
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 10));

        // Preenche a origem de registros antigos (anteriores a este recurso), no máx. 5 por carga.
        $page->getCollection()->whereNull('origin')->take(5)->each(function (BannedIp $row) {
            if ($origin = $this->bans->lookupOrigin($row->ip)) {
                $row->update(['origin' => $origin]);
            }
        });

        return $page;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'ip'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $banned = $this->bans->ban($data['ip'], $data['reason'] ?? 'Banimento manual', 'manual');

        return response()->json($banned, 201);
    }

    public function settings(): JsonResponse
    {
        return response()->json([
            'strike_limit' => $this->bans->strikeLimit(),
            'default_strike_limit' => BannedIpService::STRIKE_LIMIT,
        ]);
    }

    public function updateSettings(UpdateBannedIpSettingsRequest $request): JsonResponse
    {
        $this->bans->setStrikeLimit($request->integer('strike_limit'));

        return $this->settings();
    }

    public function destroy(BannedIp $bannedIp): JsonResponse
    {
        $this->bans->unban($bannedIp->ip);

        return response()->json(['message' => 'IP desbanido.']);
    }
}
