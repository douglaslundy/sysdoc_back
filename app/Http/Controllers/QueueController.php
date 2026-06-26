<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListQueuesRequest;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Http\Resources\QueueListResource;
use App\Models\PublicQueueLog;
use App\Models\QRCodeLog;
use App\Models\Queue;
use App\Services\AuditService;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class QueueController extends Controller
{
    public function index(ListQueuesRequest $request)
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 10);

        $queues = $this->listQuery($validated)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        return QueueListResource::collection($queues);
    }

    public function show(Request $request, $id)
    {
        if (! $this->canAccessQueue($request)) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $queue = Queue::with(['client', 'user', 'speciality'])->withCount('attachments')->find($id);

        if (! $queue) {
            return response()->json(['error' => 'Registro não encontrado'], 404);
        }

        AuditService::record('VIEW', $queue, null, [
            'cliente' => $queue->client?->name,
            'cpf' => $queue->client?->cpf,
            'especialidade' => $queue->speciality?->name,
            'status' => $queue->done ? 'realizado' : 'aguardando',
        ]);

        $queue->position = $this->calculateQueuePosition($queue);

        return response()->json($queue);
    }

    public function showPublicQueue()
    {
        $queues = Queue::with(['client', 'user', 'speciality'])
            ->where('done', 0)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($queues as $queue) {
            $queue->position = $this->calculateQueuePosition($queue);
        }

        $agrupadas = $queues->groupBy(fn ($q) => $q->speciality->name ?? 'Sem Especialidade')
            ->map(function ($fila) {
                return [
                    'comum' => $fila->where('urgency', 0)->values(),
                    'urgencia' => $fila->where('urgency', 1)->values(),
                ];
            })->sortKeys();

        PublicQueueLog::create([
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->header('User-Agent'), 0, 255),
            'host_name' => gethostbyaddr(request()->ip()),
            'referer' => request()->header('referer'),
            'accessed_at' => now(),
        ]);

        return view('queue', [
            'agrupadas' => $agrupadas,
            'data_geracao' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function store(StoreQueueRequest $request)
    {
        $queue = Queue::create($request->validated());
        AuditService::record('CREATE', $queue, null, $queue->toArray());

        $queue->load('client', 'speciality', 'user')->loadCount('attachments');
        $queue->position = $this->calculateQueuePosition($queue);

        return (new QueueListResource($queue))->response()->setStatusCode(201);
    }

    public function update(UpdateQueueRequest $request, $id)
    {
        $queue = Queue::find($id);

        if (! $queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        $old = $queue->toArray();
        $queue->update($request->validated());
        AuditService::record('UPDATE', $queue, $old, $queue->toArray());

        if ($queue->done == true) {
            Queue::where('id_specialities', $queue->id_specialities)
                ->where('urgency', $queue->urgency)
                ->where('done', 0)
                ->update(['updated_at' => now()]);
        }

        $queue->load('client', 'speciality', 'user')->loadCount('attachments');
        $queue->position = $this->calculateQueuePosition($queue);

        return new QueueListResource($queue);
    }

    public function destroy(Request $request, $id)
    {
        if (! $this->canAccessQueue($request)) {
            return response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403);
        }

        $queue = Queue::find($id);

        if (! $queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        AuditService::record('DELETE', $queue, $queue->toArray(), null);
        $queue->delete();

        return response()->json(['message' => 'Registro deletado com sucesso'], 200);
    }

    public function showByUuid($uuid, Request $request)
    {
        $queue = Queue::with(['client', 'user', 'speciality'])
            ->where('uuid', $uuid)
            ->first();

        if (! $queue) {
            return response()->json(['message' => 'Registro não encontrado'], 404);
        }

        $queue->position = $this->calculateQueuePosition($queue);

        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $location = null;

        try {
            $locationResponse = Http::timeout(2)->get("https://ipwho.is/{$ip}");
            if ($locationResponse->ok()) {
                $location = $locationResponse->json();
            }
        } catch (\Exception $e) {
            $location = null;
        }

        QRCodeLog::create([
            'uuid' => $uuid,
            'queue_id' => $queue->id,
            'position' => $queue->position,
            'ip_address' => $ip,
            'host_name' => gethostbyaddr(request()->ip()),
            'user_agent' => substr($userAgent, 0, 255),
            'location' => $location ? json_encode($location) : null,
            'referer' => $request->headers->get('referer'),
            'accessed_at' => now(),
        ]);

        return view('qrcode', [
            'queue' => $queue,
        ]);
    }

    public function storeLocationLog(Request $request)
    {
        $data = $request->validate([
            'uuid' => 'required|uuid',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        QRCodeLog::where('uuid', $data['uuid'])
            ->latest()
            ->first()
            ?->update([
                'location' => json_encode([
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                ]),
            ]);

        return response()->json(['message' => 'Localização salva com sucesso']);
    }

    private function listQuery(array $filters): Builder
    {
        $query = Queue::query()
            ->select('queue.*')
            ->with([
                'client:id,name,mother,cpf,cns,phone',
                'user:id,name',
                'speciality:id,name',
            ])
            ->withCount('attachments')
            ->selectSub(function ($query) {
                $query->from('queue as ahead')
                    ->selectRaw('COUNT(*) + 1')
                    ->whereColumn('ahead.id_specialities', 'queue.id_specialities')
                    ->whereColumn('ahead.urgency', 'queue.urgency')
                    ->where('ahead.done', 0)
                    ->where(function ($query) {
                        $query->whereColumn('ahead.created_at', '<', 'queue.created_at')
                            ->orWhere(function ($query) {
                                $query->whereColumn('ahead.created_at', 'queue.created_at')
                                    ->whereColumn('ahead.id', '<', 'queue.id');
                            });
                    });
            }, 'position');

        if (array_key_exists('done', $filters) && (int) $filters['done'] !== 2) {
            $query->where('done', (int) $filters['done']);
        }

        if (array_key_exists('urgency', $filters) && (int) $filters['urgency'] !== 2) {
            $query->where('urgency', (int) $filters['urgency']);
        }

        if (! empty($filters['speciality_id'])) {
            $query->where('id_specialities', (int) $filters['speciality_id']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.$this->escapeLike($search).'%';
            $digits = preg_replace('/\D+/', '', $search);

            $query->where(function ($query) use ($like, $digits) {
                $query->whereHas('client', function ($query) use ($like, $digits) {
                    $query->where('name', 'like', $like)
                        ->orWhere('mother', 'like', $like);

                    if (strlen((string) $digits) >= 3) {
                        $query->orWhere('cpf', 'like', '%'.$digits.'%')
                            ->orWhere('cns', 'like', '%'.$digits.'%')
                            ->orWhere('phone', 'like', '%'.$digits.'%');
                    }
                })->orWhereHas('speciality', function ($query) use ($like) {
                    $query->where('name', 'like', $like);
                });
            });
        }

        return $query;
    }

    private function calculateQueuePosition(Queue $queue): int
    {
        if ((int) $queue->done === 1) {
            return 0;
        }

        return Queue::where('id_specialities', $queue->id_specialities)
            ->where('urgency', $queue->urgency)
            ->where('done', 0)
            ->where(function ($query) use ($queue) {
                $query->where('created_at', '<', $queue->created_at)
                    ->orWhere(function ($query) use ($queue) {
                        $query->where('created_at', '=', $queue->created_at)
                            ->where('id', '<', $queue->id);
                    });
            })
            ->count() + 1;
    }

    private function canAccessQueue(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/queue');
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
