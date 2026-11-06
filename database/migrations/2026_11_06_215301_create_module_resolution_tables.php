<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        // 1. sys_module_registry — mevcut modüllerin kataloğu
        // ═══════════════════════════════════════════════════════
        Schema::create('sys_module_registry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 60)->unique();           // 'pulse', 'fin', 'otc'
            $table->string('name', 255);
            $table->string('version', 20)->default('1.0.0');
            $table->string('provider', 20)->default('platform');  // platform|partner|tenant
            $table->string('status', 20)->default('active');      // active|beta|deprecated|disabled
            $table->jsonb('manifest')->nullable();          // routes, migrations, providers, icon, ...
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        // ═══════════════════════════════════════════════════════
        // 2. sys_module_policy — N-katmanlı, koşullu, öncelikli
        // ═══════════════════════════════════════════════════════
        Schema::create('sys_module_policy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();          // NULL = tüm tenantlar
            $table->string('scope_type', 40);               // platform|structure|tenant|user|role|group|runtime|custom-*
            $table->uuid('scope_id')->nullable();           // ilgili varlığın ID'si
            $table->jsonb('modules');                       // ['*'] veya ['fin','otc'] veya ['!fin','+pulse']
            $table->string('mode', 20)->default('override');// override|merge|patch
            $table->integer('priority')->default(50);       // yüksek = önce uygulanır
            $table->jsonb('conditions')->nullable();        // {"plan":"pro","region":"tr",...}
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['scope_type', 'scope_id']);
            $table->index('priority');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // RLS — sys_module_policy tenant-scoped
        DB::statement('ALTER TABLE sys_module_policy ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sys_module_policy FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON sys_module_policy
                USING (
                    current_setting('app.is_superadmin', true) = 'true'
                    OR tenant_id IS NULL
                    OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                )
                WITH CHECK (
                    current_setting('app.is_superadmin', true) = 'true'
                    OR tenant_id IS NULL
                    OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                )
        SQL);

        // sys_module_registry: platform-geneli, RLS gerekmez
        // (herkes okur, sadece superadmin yazar → uygulama katmanı)

        // ═══════════════════════════════════════════════════════
        // 3. Seed — mevcut 14 modülü registry'ye ekle
        // ═══════════════════════════════════════════════════════
        $mods = [
            ['pulse',    'Pulse Monitoring', 100],
            ['fin',      'Finans',           90],
            ['inv',      'Envanter',         90],
            ['otc',      'Satış',            90],
            ['prc',      'Satın Alma',       90],
            ['mfg',      'Üretim',           80],
            ['hrm',      'İnsan Kaynakları', 80],
            ['crm',      'CRM',              80],
            ['ppm',      'Projeler',         70],
            ['eam',      'Varlık Yönetimi',  70],
            ['qm',       'Kalite',           70],
            ['log',      'Lojistik',         70],
            ['dms',      'Dokümanlar',       60],
            ['saas',     'SaaS',             60],
        ];

        foreach ($mods as [$key, $name, $order]) {
            DB::table('sys_module_registry')->insert([
                'id'         => (string) \Illuminate\Support\Str::uuid(),
                'key'        => $key,
                'name'       => $name,
                'version'    => '1.0.0',
                'provider'   => 'platform',
                'status'     => 'active',
                'manifest'   => json_encode(['legacy' => true]),
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ═══════════════════════════════════════════════════════
        // 4. Default policy — platform katmanı: tüm modüller açık
        // ═══════════════════════════════════════════════════════
        DB::table('sys_module_policy')->insert([
            'id'         => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'  => null,
            'scope_type' => 'platform',
            'scope_id'   => null,
            'modules'    => json_encode(['*']),
            'mode'       => 'override',
            'priority'   => 100,
            'conditions' => null,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_module_policy');
        Schema::dropIfExists('sys_module_registry');
    }
};
