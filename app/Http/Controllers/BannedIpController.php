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
        return BannedIp::orderByDesc('id')->paginate($request->integer('per_page', 50));
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
