<?php
namespace Database\Seeders;

use App\Models\SysRole;
use App\Models\SysRoleModule;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultRolesSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['code' => 'admin',    'name' => 'Yönetici',       'modules' => ['PLT','FIN','INV','PRC','OTC','MFG','HRM','CRM','PPM','EAM','QM','LOG']],
            ['code' => 'manager',  'name' => 'Müdür',          'modules' => ['PLT','FIN','INV','PRC','OTC','HRM','PPM','CRM']],
            ['code' => 'accountant','name'=> 'Muhasebeci',      'modules' => ['PLT','FIN','INV']],
            ['code' => 'warehouse','name' => 'Depo Sorumlusu', 'modules' => ['PLT','INV','PRC','LOG']],
            ['code' => 'sales',    'name' => 'Satış Temsilcisi','modules' => ['PLT','OTC','CRM','INV']],
            ['code' => 'user',     'name' => 'Kullanıcı',      'modules' => ['PLT']],
        ];

        Tenant::all()->each(function (Tenant $tenant) use ($defaults) {
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

                foreach ($def['modules'] as $m) {
                    SysRoleModule::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'role_id' => $role->id, 'module_id' => $m],
                        ['id' => (string) Str::uuid()]
                    );
                }
            }
        });

        $this->command->info('Default roller her tenant için oluşturuldu.');
    }
}
