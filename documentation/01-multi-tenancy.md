# 01 — Multi-Tenancy & RLS

**Project:** Kurumsal ERP Platform
**Belge:** Multi-Tenancy, Row-Level Security, Tenant Isolation
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Living Document — mevcut altyapı + meta-platform genişlemesi
**Depends On:** 00-manifesto.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **çoklu-kiracı (multi-tenant)** mimarisini ve **satır-seviyesi güvenlik (Row-Level Security, RLS)** modelini tanımlar. İçerir:

- Mevcut altyapı (bugün çalışan sistem)
- Meta-platform vizyonu için genişleme (yeni katmanlar)
- Güvenlik garantileri
- Performans notları
- Kod örnekleri

**Hedef kitle:** Geliştiriciler, AI asistanlar, güvenlik denetçileri, yeni ekip üyeleri.

---

## 2. Multi-Tenancy Nedir?

**Tenant** = platformdaki bir **müşteri organizasyonu**. Her tenant:

- Kendi kullanıcılarına sahiptir
- Kendi verilerine sahiptir
- Kendi ayarlarına sahiptir
- Diğer tenant'ları **göremez**

**Multi-tenancy** = tek bir platform örneğinin (instance) **birden fazla tenant'a** hizmet vermesi.

### 2.1. Neden Multi-Tenant?

| Yaklaşım | Avantaj | Dezavantaj |
|---|---|---|
| **Single-tenant** (her müşteriye ayrı sunucu) | Tam izolasyon | Pahalı, bakım zor, ölçeklenmez |
| **Multi-tenant** (tek sunucu, çok müşteri) | Ucuz, bakım kolay, ölçeklenir | İzolasyon kritik |

**Bizim seçim:** Multi-tenant + **DB seviyesinde RLS** ile güçlendirilmiş izolasyon.

### 2.2. İzolasyon Seviyeleri

| Seviye | Yöntem | Bizim |
|---|---|---|
| **Shared DB, Shared Schema** | Her tabloda `tenant_id` + WHERE | ✅ (RLS ile) |
| **Shared DB, Schema-per-Tenant** | Her tenant için ayrı schema | ❌ (karmaşık) |
| **DB-per-Tenant** | Her tenant için ayrı DB | ❌ (pahalı) |
| **Server-per-Tenant** | Her tenant için ayrı sunucu | ❌ (çok pahalı) |

**Bizim model:** Shared DB, Shared Schema + **Row-Level Security** (RLS). Her tabloda `tenant_id` var; PostgreSQL RLS politikası zorunlu kılar.

**Neden bu model:**
- Tek migration tüm tenant'ları etkiler (kolay bakım)
- Tek backup/restore tüm tenant'lar (kolay operasyon)
- Ölçeklenir (binlerce tenant tek DB'de)
- **RLS sayesinde** "uygulama unutmuş" olsa bile veri sızmaz

---

## 3. Mevcut Altyapı — TenantContext

### 3.1. Nedir?

`App\Support\TenantContext`, **aktif tenant'ı** tutan static bir servistir. Tüm sorgular bu context üzerinden çalışır.

### 3.2. API

```php
namespace App\Support;

final class TenantContext {
    // Aktif tenant ID'sini döner (yoksa null)
    public static function id(): ?string;
    
    // Belirli bir tenant olarak kod çalıştırır
    public static function runAs(string $tenantId, \Closure $callback, bool $asSuperadmin = false): mixed;
    
    // Superadmin modunda mı?
    public static function isSuperadmin(): bool;
    
    // Context'i sıfırla
    public static function reset(): void;
}
3.3. Kullanım Örneği
php
// Tenant context içinde çalıştır
TenantContext::runAs($tenantId, function () {
    // Bu blok içinde yapılan tüm sorgular
    // otomatik olarak sadece $tenantId'ye ait veriyi görür
    $customers = FinCustomer::all(); // sadece bu tenant'ın müşterileri
    return $customers;
});
Kritik: TenantContext::runAs() çağrılmadan yapılan sorgular hiçbir tenant verisi görmez (boş sonuç döner) veya App\Support\TenantContext middleware tarafından zorunlu kılınır.

3.4. Superadmin Bypass
Superadmin, app.is_superadmin = 'true' PostgreSQL değişkenini set ederek tüm tenant'ların verisini görebilir.

php
// Superadmin tüm tenant'ları görür
TenantContext::runAs($NULL_TENANT, function () {
    return Tenant::all(); // tüm tenant'lar
}, asSuperadmin: true);
Kritik güvenlik kuralı: is_superadmin = true sadece superadmin rolündeki kullanıcı için set edilir. Tenant admin bile bu flag'i açamaz.

3.5. NULL_TENANT Sabiti
php
private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
Superadmin'in tenant'sız işlemleri için kullanılır. Örneğin:

Tenant listesi

SaaS plan yönetimi

Cross-tenant raporlar

Not: Superadmin'in kendisi de bir tenant'a bağlı değildir (users.tenant_id = null).

4. Row-Level Security (RLS)
4.1. Nedir?
PostgreSQL'in satır seviyesinde güvenlik özelliğidir. Her tablo için politika (policy) tanımlanır; kullanıcının hangi satırları görebileceğini DB motoru zorunlu kılar.

Kritik: Bu uygulama seviyesinde değil, veritabanı seviyesinde bir güvenliktir. Uygulamada bug olsa bile RLS veriyi korur.

4.2. Standart RLS Politikası
Her tenant-scoped tabloda zorunlu politika:

sql
ALTER TABLE <tablo_adi> ENABLE ROW LEVEL SECURITY;
ALTER TABLE <tablo_adi> FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON <tablo_adi>
    USING (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    )
    WITH CHECK (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    );
4.3. Politika Ne Yapıyor?
Kısım	Anlamı
ENABLE ROW LEVEL SECURITY	RLS'yi açar
FORCE ROW LEVEL SECURITY	Tablo sahibi (owner) bile RLS'ye uyar
USING (...)	SELECT/UPDATE/DELETE'te hangi satırları görebilir
WITH CHECK (...)	INSERT/UPDATE'te hangi satırları yazabilir
current_setting('app.is_superadmin', true)	Superadmin bypass flag'i
current_setting('app.tenant_id', true)	Aktif tenant ID'si
NULLIF(..., '')::uuid	Boş string'i null'a çevir (güvenli cast)
4.4. Uygulama Tarafı
Middleware (web.auth) her request'te şu SQL'i çalıştırır:

php
// Tenant kullanıcısı için
DB::statement("SELECT set_config('app.tenant_id', ?, false)", [$tenantId]);
DB::statement("SELECT set_config('app.is_superadmin', 'false', false)");

// Superadmin için
DB::statement("SELECT set_config('app.tenant_id', '', false)");
DB::statement("SELECT set_config('app.is_superadmin', 'true', false)");
Not: set_config(..., false) → session seviyesinde (connection süresince geçerli). true → transaction seviyesinde (sadece o transaction).

4.5. Hangi Tablolar RLS'ye Tabi?
Tenant-scoped tüm tablolar. Örnek liste:

users (tenant_id dolu olanlar)

fin_customer, fin_supplier, fin_account, fin_journal_entry, fin_ar_invoice, fin_ap_invoice, fin_bank_account

inv_product, inv_warehouse, inv_stock_movement, inv_balance

otc_sales_order, prc_purchase_order, prc_goods_receipt

hrm_employee, hrm_attendance, hrm_payroll, hrm_expense_claim

mfg_bom, mfg_production_order

ppm_project, eam_asset, crm_lead, crm_opportunity

qm_inspection_plan, qm_ncr

log_carrier, log_shipment

dms_document

sys_role, sys_feature_flag, sys_tenant_feature, sys_notification, sys_import_log, sys_user_nav_item, sys_custom_field, sys_custom_field_value

fin_sale_expense, fin_sale_expense_default, sys_tenant_setting

4.6. Hangi Tablolar RLS DIŞINDA?
Platform core tabloları (tenant kavramı yok):

tenants (kendisi tenant tanımı)

migrations (framework)

failed_jobs, job_batches, cache, sessions (framework)

sys_feature_default (global default, superadmin-only)

sys_saas_plan (superadmin-only)

sys_audit_log — özel (audit için tüm tenant'lar yazabilir ama sadece superadmin ve ilgili tenant admin okur — ayrı politika)

4.7. RLS Best Practice
Yeni tablo = yeni RLS — Migration'da zorunlu

FORCE ROW LEVEL SECURITY — Owner bile bypass edemez

WITH CHECK — INSERT/UPDATE de korunur (sadece SELECT değil)

tenant_id NOT NULL + FK — Referans bütünlüğü

Index tenant_id — Performans (her sorguda filtrelenir)

5. Meta-Platform Genişlemesi — Bugün Olmayan Ama Gereken
Meta-platform vizyonu (Belge 00) çok katmanlı, çok yapılı, çok rollü bir dünya tanımlıyor. Mevcut multi-tenancy 2 katmanlı: platform ↔ tenant. Yeni vizyon N katmanlı olmalı.

5.1. Mevcut Sınırlama
Bugün:

text
Platform (biz) → Tenant (müşteri) → User (kullanıcı)
Sorun:

Bir tenant'ın kendi içinde alt katmanları olamaz (holding → iştirak)

Bir kullanıcı birden fazla tenant'a bağlı olamaz (danışman, developer)

Cross-tenant işbirliği yok (marketplace, tedarik zinciri)

Nested tenancy yok (tenant içinde tenant)

5.2. Yeni Model — N-Katmanlı Tenancy
text
Platform (biz)
  └── Tenant (müşteri organizasyonu)
       ├── Sub-Tenant (alt şirket, şube, departman)  ← YENİ
       │    └── Sub-Sub-Tenant (daha alt katman)     ← YENİ
       └── User
            └── Membership (birden fazla tenant'a üyelik) ← YENİ
5.3. Yeni Kavramlar
Kavram	Açıklama
Tenant Tree	Tenant'lar ağaç yapısında (holding → iştirak → şube)
Membership	Bir kullanıcının bir tenant'a üyeliği (rol + izin)
Cross-Tenant Access	Kullanıcının birden fazla tenant'a erişimi (danışman, developer)
Delegated Access	Bir tenant'ın başka bir tenant'a geçici erişim vermesi
Data Sharing	Tenant'lar arası kontrollü veri paylaşımı (tedarik zinciri)
5.4. Schema Değişiklikleri
tenants tablosu:

sql
ALTER TABLE tenants ADD COLUMN parent_tenant_id uuid NULL REFERENCES tenants(id);
ALTER TABLE tenants ADD COLUMN depth int DEFAULT 0;
ALTER TABLE tenants ADD COLUMN path text;  -- materialized path: /root/child1/child2/
ALTER TABLE tenants ADD COLUMN tenant_type varchar(50) DEFAULT 'organization';
-- tenant_type: organization | branch | department | project | virtual | marketplace_vendor
Yeni tablo: sys_user_membership

sql
CREATE TABLE sys_user_membership (
    id uuid PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id),
    tenant_id uuid NOT NULL REFERENCES tenants(id),
    role varchar(50) NOT NULL,
    scope varchar(50) DEFAULT 'full',  -- full | branch | department
    status varchar(20) DEFAULT 'active',
    is_primary boolean DEFAULT false,
    valid_from timestampTz,
    valid_until timestampTz,
    created_at timestampTz,
    updated_at timestampTz,
    UNIQUE(user_id, tenant_id, role)
);
Yeni tablo: sys_tenant_delegation (bir tenant'ın başka bir tenant'a geçici erişim)

sql
CREATE TABLE sys_tenant_delegation (
    id uuid PRIMARY KEY,
    from_tenant_id uuid NOT NULL REFERENCES tenants(id),
    to_tenant_id uuid NOT NULL REFERENCES tenants(id),
    scope text NOT NULL,  -- JSON: { entities: ['fin_customer'], actions: ['read'] }
    valid_from timestampTimestamptz,
    valid_until timestampTimestamptz,
    granted_by bigint REFERENCES users(id),
    status varchar(20) DEFAULT 'active'
);
5.5. RLS Politikası — Nested Tenancy
Eski politika:

sql
USING (tenant_id = current_setting('app.tenant_id')::uuid)
Yeni politika:

sql
USING (
    -- Superadmin
    current_setting('app.is_superadmin', true) = 'true'
    -- Kendi tenant
    OR tenant_id = current_setting('app.tenant_id')::uuid
    -- Parent tenant: tüm alt tenant'ları görür
    OR tenant_id IN (
        SELECT id FROM tenants
        WHERE path LIKE current_setting('app.tenant_path') || '%'
    )
    -- Cross-tenant delegasyon
    OR tenant_id IN (
        SELECT from_tenant_id FROM sys_tenant_delegation
        WHERE to_tenant_id = current_setting('app.tenant_id')::uuid
        AND status = 'active'
        AND now() BETWEEN valid_from AND valid_until
    )
)
Kritik: app.tenant_path = aktif tenant'ın materialized path'i (/root/holding/branch1/). Parent tenant tüm altındakileri görür.

5.6. Context Değişikliği
TenantContext yeni API'ler:

php
interface TenantContext {
    // Aktif tenant
    public static function id(): ?string;
    
    // Aktif tenant'ın path'i
    public static function path(): ?string;
    
    // Bir tenant'a erişim var mı?
    public static function canAccess(string $tenantId): bool;
    
    // Belirli tenant olarak çalıştır
    public static function runAs(string $tenantId, \Closure $callback): mixed;
    
    // Bir kullanıcının tüm erişebildiği tenant'lar
    public static function accessibleTenants(int $userId): array;
    
    // Superadmin modu
    public static function runAsSuperadmin(\Closure $callback): mixed;
}
6. Independent Developer Erişimi (Yeni)
Belge 00'da tanımlanan Independent Developer rolü, multi-tenancy'de özel bir yer tutar.

6.1. Senaryo
Developer Ali, Tesfirma için Wolt plugin'i yazıyor. Ali:

Platform üyesi (kendi developer hesabı)

Tesfirma tenant'ına geçici üyelik (plugin geliştirmek için)

Aynı zamanda 5 başka tenant'a da üye (diğer müşterileri)

6.2. Membership Modeli
sql
-- sys_user_membership
INSERT INTO sys_user_membership (user_id, tenant_id, role, valid_until) VALUES
    (ali_id, tesfirma_tenant_id, 'developer', '2026-12-31'),
    (ali_id, customer2_tenant_id, 'developer', '2026-06-30'),
    (ali_id, customer3_tenant_id, 'developer', NULL); -- süresiz
6.3. RLS Değişikliği
Developer'ın göreceği veriler:

Kendi yazdığı plugin'ler

Müşterinin plugin konfigürasyonu

Müşterinin ticari verileri DEĞİL (fin_customer, otc_sales_order vs.)

Çözüm: Scope bazlı RLS

sql
-- plugin_installation tablosunda RLS
USING (
    tenant_id = current_setting('app.tenant_id')::uuid
    AND (
        -- Tenant admin her şeyi görür
        current_setting('app.user_role') IN ('admin', 'superadmin')
        -- Developer sadece kendi plugin'lerini görür
        OR (
            current_setting('app.user_role') = 'developer'
            AND plugin.author_id = current_setting('app.user_id')::bigint
        )
    )
)
6.4. Developer Dashboard
Developer kendi panelinde:

Müşterilerim — hangi tenant'larda aktif

Plugin'lerim — hangi tenant'da hangi versiyon

Gelir — aylık, komisyon kesintili

Hatalar — müşterilerden gelen bug raporları

Analytics — kurulum sayısı, aktif kullanıcı

Kritik: Developer müşterinin ticari verilerine erişemez. Sadece kendi plugin'inin tenant'taki durumunu görür.

7. Güvenlik Garantileri
Aşağıdaki garantiler mimari düzeyde sağlanır, uygulama bug'larına bağlı değildir:

7.1. Tenant İzolasyonu
✅ Bir tenant diğerinin verisini göremez (RLS).
✅ Bir tenant diğerinin verisini değiştiremez (RLS WITH CHECK).
✅ Bir tenant diğerinin verisini silemez (RLS).
✅ Superadmin bypass audit'lenir (her bypass log'a yazılır).

7.2. Cross-Tenant Delegasyon
✅ Delegasyon süreli (valid_from, valid_until).
✅ Delegasyon scope'lu (sadece belirli entity, belirli action).
✅ Delegasyon audit'lenir (kim, kime, ne zaman, ne için).
✅ Delegasyon iptal edilebilir (status = revoked).

7.3. Developer İzolasyonu
✅ Developer müşterinin ticari verisine erişemez.
✅ Developer sadece kendi plugin'inin tenant'taki durumunu görür.
✅ Developer'ın tenant'a erişimi süreli (valid_until).

7.4. Backup & Restore
Tek DB backup tüm tenant'ları kapsar

Tek tenant restore mümkün değil (tüm DB restore gerekir — bu bir sınırlama)

İleride: logical backup ile tenant-bazlı restore (Belge 19'da)

8. Performans Notları
8.1. Index Stratejisi
Her tenant-scoped tabloda zorunlu index:

sql
CREATE INDEX idx_<tablo>_tenant ON <tablo>(tenant_id);
-- veya composite
CREATE INDEX idx_<tablo>_tenant_created ON <tablo>(tenant_id, created_at DESC);
Neden: RLS her sorguya WHERE tenant_id = ? ekler. Index yoksa full scan → yavaş.

8.2. Connection Pooling
PostgreSQL set_config connection başına çalışır. Connection pooling (PgBouncer) kullanılıyorsa:

Transaction mode → her transaction başında set_config tekrar çalıştırılmalı

Session mode → sorun yok

Bizim seçim: Session mode (PgBouncer yok, direkt PostgreSQL).

8.3. RLS Overhead
RLS policy her sorguya ek WHERE ekler. Performans etkisi:

Basit sorgular: ~%5-10 overhead

Karmaşık join'ler: ~%15-20 overhead

Kabul edilebilir (güvenlik karşılığı)

Optimizasyon: Materialized view'lar RLS'yi bypass eder (dikkat!). RLS gereken view'lar için WITH (security_barrier = true) kullan.

8.4. Superadmin Bypass Performansı
Superadmin modunda RLS devre dışı (çünkü is_superadmin = true). Bu, tüm tabloları tarar → yavaş.

Çözüm:

Superadmin sorguları nadir olmalı

Cross-tenant raporlar ayrı tablo/view ile yapılmalı (her tenant'ın verisi ayrı satır)

Superadmin paneli lazy load kullanmalı

9. Migration Pattern
Her yeni tenant-scoped tablo için standart migration pattern:

php
Schema::create('fin_example', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');                        // ZORUNLU
    // ... diğer kolonlar
    
    $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
    $table->index('tenant_id');                        // ZORUNLU
});

// RLS — ZORUNLU
DB::statement('ALTER TABLE fin_example ENABLE ROW LEVEL SECURITY');
DB::statement('ALTER TABLE fin_example FORCE ROW LEVEL SECURITY');
DB::statement(<<<'SQL'
    CREATE POLICY tenant_isolation ON fin_example
        USING (
            current_setting('app.is_superadmin', true) = 'true'
            OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
        )
        WITH CHECK (
            current_setting('app.is_superadmin', true) = 'true'
            OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
        )
SQL);
Kural: Tenant-scoped tablo = tenant_id + RLS + index. İstisna yok.

10. Test Stratejisi
10.1. Birim Testleri
Her tenant-scoped işlem için:

php
public function test_tenant_a_cannot_see_tenant_b_data(): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    
    $customerA = TenantContext::runAs($tenantA->id, fn() => 
        FinCustomer::create(['code' => 'A1', 'name' => 'Customer A'])
    );
    $customerB = TenantContext::runAs($tenantB->id, fn() => 
        FinCustomer::create(['code' => 'B1', 'name' => 'Customer B'])
    );
    
    // Tenant A sadece kendi müşterisini görür
    $result = TenantContext::runAs($tenantA->id, fn() => FinCustomer::all());
    $this->assertCount(1, $result);
    $this->assertEquals('A1', $result->first()->code);
}
10.2. RLS Testleri
php
public function test_rls_blocks_direct_sql_without_tenant_context(): void {
    $this->expectException(\Illuminate\Database\QueryException::class);
    
    // Tenant context yok → RLS engeller
    DB::table('fin_customer')->insert(['id' => uuid(), 'tenant_id' => uuid(), ...]);
}
10.3. Superadmin Bypass Testi
php
public function test_superadmin_can_see_all_tenants(): void {
    Tenant::factory()->count(3)->create();
    
    $result = TenantContext::runAsSuperadmin(fn() => FinCustomer::all());
    // 3 tenant'ın tüm müşterileri görünür
}
11. Operasyon Notları
11.1. Backup
Full DB backup — tüm tenant'lar

Frequency — günlük (min), saatlik (ideal)

Retention — 30 gün (min), 1 yıl (ideal)

Test — restore test edilmeden backup sayılmaz

11.2. Restore
Tam DB restore — kesinti gerektirir

Point-in-time recovery — PostgreSQL WAL ile mümkün

Tenant-bazlı restore — ileride (Belge 19)

11.3. Migration
Tüm tenant'lar aynı anda etkilenir

Downtime — büyük tablolar için (CREATE INDEX CONCURRENTLY)

Rollback — php artisan migrate:rollback (dikkat: veri kaybı)

11.4. Monitoring
Her tenant için sorgu sayısı, yanıt süresi, hata oranı izlenmeli

Yavaş tenant'lar ayrı ölçeklenmeli (ileride sharding)

12. Meta-Platform Yol Haritası
Aşama	Multi-Tenancy Gereksinimi
Şimdi	Shared DB + RLS + tenant_id
6 ay	Membership tablosu + cross-tenant delegasyon
12 ay	Nested tenancy (parent/child) + tenant tree
18 ay	Developer multi-membership + scope RLS
24 ay	Marketplace tenant'ları (vendor/seller/customer)
36 ay	Sharding (büyük tenant'lar ayrı DB)
Her aşama bir öncekini bozmaz — üstüne eklenir.

13. Anti-Patterns
Bu projede yasak olan multi-tenancy hataları:

WHERE tenant_id = ? yazmayı unutmak — RLS zaten zorunlu, ama uygulama tarafında da yazılmalı (defense in depth)

FORCE ROW LEVEL SECURITY olmadan tablo — owner bypass edebilir, tehlikeli

WITH CHECK olmadan sadece USING — INSERT/UPDATE korunmaz

Cross-tenant sorgu için superadmin açmak — gereksiz yere bypass

Developer'a sınırsız erişim — scope zorunlu

tenant_id nullable — referans bütünlüğü bozulur

RLS'siz yeni tablo — migration code review'da reddedilir

Superadmin bypass audit'siz — log zorunlu

14. Glossary
Terim	Anlam
Tenant	Müşteri organizasyonu
RLS	Row-Level Security (PostgreSQL)
TenantContext	Aktif tenant tutan servis
NULL_TENANT	Sıfır UUID, superadmin tenant'sız işlemler
Superadmin Bypass	app.is_superadmin = true ile RLS devre dışı
Tenant Tree	Tenant'ların ağaç yapısı (parent/child)
Membership	Kullanıcı ↔ Tenant üyeliği
Cross-Tenant Delegation	Tenant'lar arası süreli erişim
Materialized Path	Tenant hiyerarşisi için /root/child/grandchild/ yolu
15. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — mevcut altyapı + meta-platform genişleme