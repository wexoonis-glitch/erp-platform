<?php

namespace App\Services\Platform;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

final class AuditService
{
    private const MAX_STRING_LEN = 1000;

    /**
     * Audit kaydı oluştur.
     * $data: entity, entity_id, action, before_state?, after_state?, tenant_id?, function_id?
     */
    public function record(array $data): AuditLog
    {
        $user = Auth::user();

        return AuditLog::create([
            'tenant_id'    => $data['tenant_id'] ?? null,
            'user_id'      => $data['user_id'] ?? $user?->id,
            'user_email'   => $data['user_email'] ?? $user?->email,
            'function_id'  => $data['function_id'] ?? null,
            'entity'       => $data['entity'],
            'entity_id'    => (string) ($data['entity_id'] ?? ''),
            'action'       => $data['action'],
            'before_state' => $this->trim($data['before_state'] ?? null),
            'after_state'  => $this->trim($data['after_state'] ?? null),
            'ip'           => $data['ip'] ?? (app()->runningInConsole() ? null : Request::ip()),
            'user_agent'   => $data['user_agent'] ?? (app()->runningInConsole() ? null : Request::userAgent()),
            'created_at'   => now(),
        ]);
    }

    private function trim(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $out = [];
        foreach ($data as $k => $v) {
            if (is_string($v) && mb_strlen($v) > self::MAX_STRING_LEN) {
                $v = mb_substr($v, 0, self::MAX_STRING_LEN) . '…[trimmed]';
            }
            $out[$k] = $v;
        }
        return $out;
    }
}
