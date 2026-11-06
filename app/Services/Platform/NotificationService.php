<?php
namespace App\Services\Platform;
use App\Models\SysNotification;
use App\Models\SysNotificationSetting;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class NotificationService {
    /**
     * Uygulama içi (in-app) bildirim gönderir.
     * Eğer $userId null ise, o tenant'taki tüm admin ve superadminlere gider.
     */
    public function notify(string $type, string $title, string $message, ?string $link = null, ?int $userId = null): void {
        $tenantId = TenantContext::id();
        if (!$tenantId) return;

        $userIds = [];
        if ($userId) {
            $userIds[] = $userId;
        } else {
            // Tenant adminlerini bul (Basitlik için rolü admin veya superadmin olanlar)
            $userIds = User::where('tenant_id', $tenantId)
                ->whereIn('role', ['admin', 'superadmin'])
                ->pluck('id')
                ->toArray();
        }

        foreach ($userIds as $uid) {
            SysNotification::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $uid,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'is_read' => false,
            ]);
        }
    }

    /**
     * E-posta / SMS gibi dış kanallar için (SaaS hatırlatmaları vs.)
     */
    public function send(string $eventKey, string $subject, string $message, ?string $toEmail = null): void {
        $tenantId = TenantContext::id();
        if (!$tenantId) return;

        $settings = SysNotificationSetting::where('tenant_id', $tenantId)->where('event_key', $eventKey)->where('is_active', true)->get();

        foreach ($settings as $setting) {
            $targetEmail = match($setting->recipient_type) {
                'custom_email' => $setting->custom_email,
                'supplier' => $toEmail,
                'customer' => $toEmail,
                default => config('mail.from.address', 'admin@erp.local'),
            };

            if (!$targetEmail) continue;

            if ($setting->channel === 'email') {
                Log::info("BILDIRIM GONDERILDI [{$eventKey}] -> {$targetEmail} : {$subject}");
                // Mail::raw($message, function ($mail) use ($targetEmail, $subject) {
                //     $mail->to($targetEmail)->subject($subject);
                // });
            }
        }
    }
}
