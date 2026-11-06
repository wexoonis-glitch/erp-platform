<?php
namespace App\Console\Commands;

use App\Models\SysRole;
use App\Models\SysRoleModule;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedDefaultRoles extends Command
{
    protected $signature = 'erp:seed-default-roles {--tenant=} {--force}';
    protected $description = 'Her tenant için varsayılan rolleri ve modül izinlerini oluşturur/günceller.';

    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public function handle(): int
    {
        $defaults = [
            ['code' => 'admin',     'name' => 'Yönetici',        'modules' => ['PLT','FIN','INV','PRC','OTC','MFG','HRM','CRM','PPM','EAM','QM','LOG']],
            ['code' => 'manager',   'name' => 'Müdür',           'modules' => ['PLT','FIN','INV','PRC','OTC','HRM','PPM','CRM']],
            ['code' => 'accountant','name' => 'Muhasebeci',      'modules' => ['PLT','FIN','INV']],
            ['code' => 'warehouse', 'name' => 'Depo Sorumlusu',  'modules' => ['PLT','INV','PRC','LOG']],
            ['code' => 'sales',     'name' => 'Satış Temsilcisi','modules' => ['PLT','OTC','CRM','INV']],
            ['code' => 'user',      'name' => 'Kullanıcı',       'modules' => ['PLT']],
        ];

        // RLS bypass: tenant listesini superadmin context'te çek
        $tenants = TenantContext::runAs(
            self::NULL_TENANT,
            function () {
                return $this->option('tenant')
                    ? Tenant::where('id', $this->option('tenant'))->get()
                    : Tenant::orderBy('code')->get();
            },
            superadmin: true
        );

        if ($tenants->isEmpty()) {
            $this->warn('Tenant bulunamadı (RLS veya veri yok).');
            return self::SUCCESS;
        }

        $this->info("İşlenecek tenant sayısı: {$tenants->count()}");

        foreach ($tenants as $tenant) {
            $this->line("→ [{$tenant->code}] {$tenant->name}");

            TenantContext::runAs($tenant->id, function () use ($tenant, $defaults) {
                foreach ($defaults as $def) {
                    $role = SysRole::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'code' => $def['code']],
                        [
                            'id'        => (string) Str::uuid(),
                            'name'      => $def['name'],
                            'is_system' => true,
                            'is_active' => true,
                        ]
                    );

                    $this->syncModules($tenant->id, $role, $def['modules']);
                }

                // Custom roller (bigboss, tes123 gibi) boşsa admin modülleriyle doldur
                $customRoles = SysRole::where('tenant_id', $tenant->id)
                    ->where('is_system', false)
                    ->get();

                $adminModules = $defaults[0]['modules'];
                foreach ($customRoles as $cr) {
                    $currentCount = SysRoleModule::where('tenant_id', $tenant->id)
                        ->where('role_id', $cr->id)->count();
                    if ($currentCount === 0) {
                        $this->syncModules($tenant->id, $cr, $adminModules);
                        $this->line("   (custom rol dolduruldu: {$cr->code} → admin modülleri)");
                    }
                }
            });
        }

        $this->info('✔ Bitti.');
        return self::SUCCESS;
    }

    private function syncModules(string $tenantId, SysRole $role, array $modules): void
    {
        foreach ($modules as $m) {
            SysRoleModule::firstOrCreate(
                ['tenant_id' => $tenantId, 'role_id' => $role->id, 'module_id' => $m],
                ['id' => (string) Str::uuid()]
            );
        }
    }
}
