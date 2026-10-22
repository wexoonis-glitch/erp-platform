<?php

namespace Database\Seeders;

use App\Models\FinAccount;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FinChartOfAccountsSeeder extends Seeder
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public function run(): void
    {
        // RLS: superadmin bağlamında tüm tenant'ları listele
        $tenants = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => Tenant::where('status', 'active')->get(),
            superadmin: true
        );

        foreach ($tenants as $tenant) {
            // Tenant bağlamında COA var mı?
            $existing = TenantContext::runAs(
                $tenant->id,
                fn() => FinAccount::where('tenant_id', $tenant->id)->count()
            );

            if ($existing > 0) {
                $this->command->info("Tenant {$tenant->code}: COA zaten var ({$existing} hesap), atlanıyor.");
                continue;
            }

            $accounts = [
                ['100', 'Kasa',                              'asset',     true],
                ['101', 'Alınan Çekler',                     'asset',     true],
                ['102', 'Bankalar',                          'asset',     true],
                ['120', 'Alıcılar',                          'asset',     true],
                ['153', 'Ticari Mallar',                     'asset',     true],
                ['191', 'İndirilecek KDV',                   'asset',     true],
                ['320', 'Satıcılar',                         'liability', true],
                ['391', 'Hesaplanan KDV',                    'liability', true],
                ['500', 'Sermaye',                           'equity',    true],
                ['570', 'Geçmiş Yıllar Kârları',             'equity',    true],
                ['600', 'Yurtiçi Satışlar',                  'revenue',   true],
                ['621', 'Satılan Ticari Mallar Maliyeti',    'expense',   true],
                ['770', 'Genel Yönetim Giderleri',           'expense',   true],
            ];

            // Tenant bağlamında hesapları oluştur
            TenantContext::runAs($tenant->id, function () use ($tenant, $accounts) {
                foreach ($accounts as [$code, $name, $type, $postable]) {
                    FinAccount::create([
                        'id'          => (string) Str::uuid(),
                        'tenant_id'   => $tenant->id,
                        'code'        => $code,
                        'name'        => $name,
                        'type'        => $type,
                        'parent_id'   => null,
                        'is_postable' => $postable,
                        'currency'    => 'TRY',
                        'status'      => 'active',
                    ]);
                }
            });

            $this->command->info("Tenant {$tenant->code}: " . count($accounts) . " hesap eklendi.");
        }
    }
}
