<?php

namespace App\Http\Controllers;

use App\Models\SystemNotice;
use App\Models\SystemNoticeView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemNoticeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notices = SystemNotice::query()
            ->with(['targetUser:id,name,email', 'createdBy:id,name'])
            ->orderByDesc('id')
            ->get();

        return response()->json($notices);
    }

    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([]);
        }

        $notices = SystemNotice::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('target_user_id')
                    ->orWhere('target_user_id', $user->id);
            })
            ->orderByDesc('id')
            ->get();

        $views = SystemNoticeView::query()
            ->where('user_id', $user->id)
            ->whereDate('shown_at', now()->toDateString())
            ->orderByDesc('shown_at')
            ->get()
            ->groupBy('system_notice_id');

        $result = $notices->filter(function (SystemNotice $notice) use ($views) {
            $todayViews = $views->get($notice->id, collect());
            if ($todayViews->count() >= $notice->times_per_day) {
                return false;
            }

            $lastShownAt = $todayViews->first()?->shown_at;
            if ($lastShownAt && $lastShownAt->greaterThan(now()->subMinutes($notice->interval_minutes))) {
                return false;
            }

            return true;
        })->values();

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string'],
            'image_data' => ['nullable', 'string'],
            'times_per_day' => ['required', 'integer', 'min:1', 'max:24'],
            'interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['created_by_user_id'] = $request->user()?->id;
        $data['is_active'] = $request->boolean('is_active', true);

        $notice = SystemNotice::create($data);

        return response()->json($notice->load(['targetUser:id,name,email', 'createdBy:id,name']), 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $notice = SystemNotice::find($id);
        if (! $notice) {
            return response()->json(['message' => 'Aviso não encontrado.'], 404);
        }

        $notice->delete();

        return response()->json(['message' => 'Aviso removido com sucesso.']);
    }

    public function recordView(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        $notice = SystemNotice::find($id);
        if (! $notice) {
            return response()->json(['message' => 'Aviso não encontrado.'], 404);
        }

        SystemNoticeView::create([
            'system_notice_id' => $notice->id,
            'user_id' => $user->id,
            'shown_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
