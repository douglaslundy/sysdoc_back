<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    private static array $sensitive = ['password', 'remember_token', 'token'];

    public static function record(
        string $action,
        ?Model $model = null,
        ?array $old = null,
        ?array $new = null
    ): void {
        try {
            $user = Auth::user();

            AuditLog::create([
                'user_id'    => $user?->id,
                'user_name'  => $user?->name ?? 'Sistema',
                'action'     => $action,
                'model_type' => $model ? class_basename($model) : null,
                'model_id'   => $model?->getKey(),
                'endpoint'   => request()->path(),
                'method'     => request()->method(),
                'ip_address' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
                'old_values' => $old ? self::sanitize($old) : null,
                'new_values' => $new ? self::sanitize($new) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Silencioso — auditoria não pode quebrar a aplicação
        }
    }

    private static function sanitize(array $data): array
    {
        return array_diff_key($data, array_flip(self::$sensitive));
    }
}
