# 03 — Feature Flags

**Project:** Kurumsal ERP Platform
**Belge:** Feature Flag Sistemi, Dinamik Özellik Yönetimi, Meta-Platform Genişlemesi
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Living Document — mevcut sistem + meta-platform genişleme
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **feature flag** (özellik anahtarı) sistemini tanımlar:

- Mevcut sistem (27 statik flag)
- Çözümleme mantığı (tenant → global → registry)
- Bağımlılık zinciri
- Meta-platform vizyonunda genişleme (statik flag → dinamik özellik)
- Admin UI ve geliştirici deneyimi
- Anti-pattern'ler

**Hedef kitle:** Geliştiriciler, ürün yöneticileri, AI asistanlar.

---

## 2. Feature Flag Nedir?

**Feature flag** = bir özelliğin **açık veya kapalı** olduğunu belirten boolean + metadata. Kodda:

```php
if (Features::enabled('otc.sarfiyyat')) {
    // Bu blok sadece flag açıksa çalışır
}
2.1. Neden Feature Flag?
Senaryo	Flag Olmadan	Flag İle
Yeni özellik deploy etme	Tüm tenant'lar etkilenir	Sadece seçili tenant'lar
Hatalı özellik	Rollback (downtime)	Flag kapat (anlık)
Müşteri A istiyor, B istemiyor	İki kod yolu	Tek kod, iki davranış
A/B test	Ayrı deploy	Tek deploy, iki grup
Tenant planı farkı (Free/Pro)	Kod dallanması	Flag kombinasyonu
2.2. Feature Flag vs Permission
Karıştırılmamalı:

Kavram	Soru	Katman
Feature Flag	"Bu TENANT bu özelliğe sahip mi?"	Tenant (satın alma / tercih)
Permission	"Bu KULLANICI bu özelliği kullanabilir mi?"	User (rol / yetki)
Birlikte çalışırlar:

php
// Önce flag (tenant seviyesi)
if (!Features::enabled('otc.sarfiyyat')) abort(403);

// Sonra permission (kullanıcı seviyesi)
if (!auth()->user()->can('otc.sarfiyyat.edit')) abort(403);

// İkisi de geçtiyse işlem devam
3. Mevcut Sistem — Statik Flags
3.1. Yapı
text
config/features.php          ← Registry (kod, versiyonlu)
    ↓
sys_feature_default          ← Global default (superadmin)
    ↓
sys_tenant_feature           ← Tenant override (tenant admin)
    ↓
FeatureFlagService           ← Çözümleme (resolve)
    ↓
EnsureFeatureEnabled         ← Middleware (route koruması)
    ↓
@feature direktifi           ← Blade (view koruması)
    ↓
Features::enabled()          ← Facade (kod koruması)
3.2. Registry — config/features.php
27 flag tanımlı, 5 gruba ayrılmış:

Grup	Flag sayısı	Örnek
modules	11	fin.enabled, otc.enabled, inv.enabled
otc	7	otc.sarfiyyat, otc.karma_payment, otc.gift_product
ux	4	ux.command_palette, ux.favorites, ux.undo
analytics	3	analytics.abc, analytics.combo, analytics.forecast
security	2	security.2fa, security.ip_whitelist
Her flag'in yapısı:

php
'otc.sarfiyyat' => [
    'label'       => 'Sərfiyyat (Satış Masrafları)',
    'group'       => 'otc',
    'type'        => 'bool',         // bool | int | text | select
    'default'     => true,
    'requires'    => ['otc.enabled'], // bağımlılık
    'order'       => 10,
    'description' => 'Satışa masraf ekle...',
],
3.3. Veritabanı Tabloları
sys_feature_default — Global varsayılan (superadmin yönetir):

sql
CREATE TABLE sys_feature_default (
    feature_key varchar(120) PRIMARY KEY,
    enabled     boolean NOT NULL DEFAULT true,
    config      jsonb,
    created_at  timestamptz,
    updated_at  timestamptz
);
-- RLS: superadmin-only
sys_tenant_feature — Tenant override (tenant admin yönetir):

sql
CREATE TABLE sys_tenant_feature (
    id          uuid PRIMARY KEY,
    tenant_id   uuid NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    feature_key varchar(120) NOT NULL,
    enabled     boolean NOT NULL DEFAULT true,
    config      jsonb,
    updated_by  bigint REFERENCES users(id),
    created_at  timestamptz,
    updated_at  timestamptz,
    UNIQUE(tenant_id, feature_key)
);
-- RLS: tenant-scoped
3.4. Çözümleme Mantığı (Resolution)
Öncelik sırası:

text
1. sys_tenant_feature    (tenant override — varsa)
2. sys_feature_default   (global default — varsa)
3. config/features.php   (registry default — her zaman)
Algoritma (FeatureFlagService::enabled):

php
public function enabled(string $key): bool {
    $def = $this->registry()[$key] ?? null;
    if (!$def) return false;
    
    // 1. Bağımlılık zinciri (recursive)
    foreach ($def['requires'] ?? [] as $parent) {
        if (!$this->enabled($parent)) return false;
    }
    
    // 2. Tenant override
    $override = $this->tenantOverride($key);
    if ($override !== null) return $override;
    
    // 3. Global default
    $global = $this->globalDefault($key);
    if ($global !== null) return $global;
    
    // 4. Registry default
    return (bool) ($def['default'] ?? false);
}
3.5. Bağımlılık Zinciri
Flag'ler birbirine bağlı olabilir:

php
'otc.sarfiyyat.required' => [
    'requires' => ['otc.sarfiyyat'],
    // ...
],
'otc.sarfiyyat' => [
    'requires' => ['otc.enabled'],
    // ...
],
Zincir:

text
otc.enabled (false) 
    → otc.sarfiyyat (otomatik false)
        → otc.sarfiyyat.required (otomatik false)
Kural: Parent flag kapalıysa, child flag'ler otomatik kapalı — DB'de override olsa bile.

3.6. Middleware — Route Koruması
php
// routes/web.php
Route::middleware(['web.auth', 'feature:otc.enabled'])->group(function () {
    Route::get('/otc/sales-orders', SalesOrders::class)->name('otc.sales-orders');
});
Davranış:

Flag açık → route çalışır

Flag kapalı → dashboard'a redirect + flash mesaj

3.7. Blade Direktifi — View Koruması
blade
@feature('otc.sarfiyyat')
    <div class="sarfiyyat-section">
        <!-- Sərfiyyat bölümü -->
    </div>
@endfeature
Davranış:

Flag açık → blok render edilir

Flag kapalı → blok hiç gönderilmez (HTML'de yok)

3.8. Facade — Kod Koruması
php
use App\Support\Features;

public function save() {
    if (Features::enabled('otc.sarfiyyat.required')) {
        $validated = $this->validate(['sarfiyyat' => 'required|array|min:1']);
    }
}
Kural: Flag kontrolü her zaman servis katmanında — UI'da gizlemek yetmez, güvenlik için servis de kontrol eder.

3.9. Admin UI — settings/features
Özellikler:

Kategorize (grup bazlı) toggle listesi

Bağımlılık göstergesi (parent kapalıysa gri)

Type-aware (bool → toggle, int → input)

Override göstergesi ("özel" badge)

Sıfırla butonu (override kaldır)

Anlık kaydetme (Livewire)

Erişim: Tenant admin + superadmin.

4. Feature Flag Türleri
4.1. Bool (Basit Aç/Kapa)
php
'otc.sarfiyyat' => ['type' => 'bool', 'default' => true]
Kullanım: Features::enabled('otc.sarfiyyat')

4.2. Int (Sayısal Değer)
php
'ux.undo.duration_seconds' => [
    'type' => 'int',
    'default' => 60,
    'min' => 10,
    'max' => 600,
]
Kullanım: Features::value('ux.undo.duration_seconds') → 60

4.3. Text (Serbest Metin)
php
'security.ip_whitelist.ips' => [
    'type' => 'text',
    'default' => '',
]
Kullanım: Features::value('security.ip_whitelist.ips') → "1.2.3.4,5.6.7.8"

4.4. Select (Seçenekli)
php
'fin.ledger.primary' => [
    'type' => 'select',
    'options' => ['TDHP', 'IFRS', 'GAAP'],
    'default' => 'TDHP',
]
Kullanım: Features::value('fin.ledger.primary') → "TDHP"

5. Yeni Flag Ekleme — 3 Adım
5.1. Adım 1: Registry'ye Ekle
config/features.php dosyasında ilgili gruba:

php
'inv.barcode.hardware' => [
    'label' => 'Barkod Okuyucu (Donanım)',
    'group' => 'inv',
    'type' => 'bool',
    'default' => true,
    'requires' => ['inv.enabled'],
    'order' => 31,
    'description' => 'USB/Bluetooth barkod okuyucu desteği',
],
5.2. Adım 2: Kodda Kullan
php
// Route koruması
Route::middleware('feature:inv.barcode.hardware')->group(...);

// Servis kontrolü
if (Features::enabled('inv.barcode.hardware')) { ... }

// Blade
@feature('inv.barcode.hardware')
    <input type="text" id="barcode-input">
@endfeature
5.3. Adım 3: Deploy
bash
php artisan config:clear
php artisan optimize:clear
Kural: Migration gerekmez. Yeni flag deploy sonrası otomatik görünür admin UI'da.

6. Flag Kullanım Kuralları
6.1. Ne Zaman Flag Kullan?
✅ Kullan	❌ Kullanma
Modül aç/kapa (fin.enabled)	Buton rengi
Yeni özellik (beta)	Metin değişikliği
Tenant bazlı farklılık	Geçici UI hatası
Plan bazlı (Free/Pro)	Debug kodu
A/B test	Fonksiyon içi hesap
Rollback (hızlı kapat)	
6.2. Flag İsimlendirme
text
<grup>.<alt-grup>.<özellik>[.<alt-özellik>]
Örnek:

otc.enabled — modül

otc.sarfiyyat — özellik

otc.sarfiyyat.required — alt-özellik

ux.command_palette — UX

security.2fa — güvenlik

fin.ledger.primary — config

Kural: Max 4 seviye. snake_case her seviyede.

6.3. Flag Silme
Yasak: Flag'i doğrudan silmek.

Doğru:

Registry'de 'deprecated' => true ekle

Admin UI'da gizle

6 ay bekle (müşteriler etkilenmesin)

Sonra sil

Neden: Tenant'ların override'ları hâlâ DB'de. Ani silme → Features::enabled() çağrıları false döner → beklemedik davranış.

7. Meta-Platform Genişlemesi
Mevcut sistem statik flag'ler — 27 sabit anahtar. Meta-platform vizyonu (Belge 00) çok daha fazlasını gerektirir.

7.1. Mevcut Sınırlama
Sınırlama	Etki
Flag sayısı sabit (27)	Yeni özellik = kod + deploy
Flag kodda tanımlı	Kullanıcı yeni flag yaratamaz
Bağımlılık statik	Runtime'da değişmez
Granularity modül seviyesi	Per-entity, per-field flag yok
Tenant bazlı	Per-layer, per-role flag yok
7.2. Yeni Kavramlar
Meta-platform'da 3 katmanlı özellik yönetimi:

Katman	Ne	Kim tanımlar
L1: Static Flags	Mevcut 27 flag (kod)	Geliştirici
L2: Dynamic Flags	Runtime'da tanımlanan flag'ler	Tenant admin
L3: Structure Flags	Platform structure içindeki özellikler	Structure builder
7.3. Yeni Tablo: sys_feature_registry
Mevcut config/features.php'ye ek olarak DB'de flag tanımı:

sql
CREATE TABLE sys_feature_registry (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NULL,           -- NULL = global, dolu = tenant-özel
    feature_key     varchar(120) NOT NULL,
    label           varchar(255) NOT NULL,
    group_name      varchar(50),
    type            varchar(20) DEFAULT 'bool',
    default_value   jsonb,
    requires        text[],              -- array of feature_key
    config          jsonb,               -- ek metadata
    is_system       boolean DEFAULT false, -- sistem flag'i (silinemez)
    is_active       boolean DEFAULT true,
    created_at      timestamptz,
    updated_at      timestamptz,
    UNIQUE(tenant_id, feature_key)
);
Kurallar:

tenant_id = NULL → global flag (superadmin tanımlar)

tenant_id dolu → tenant-özel flag (tenant admin tanımlar)

is_system = true → silinemez (kod bağımlı)

is_system = false → kullanıcı silebilir

7.4. Çözümleme Mantığı (Yeni)
text
1. sys_tenant_feature    (tenant override)
2. sys_feature_registry  (tenant-özel flag tanımı)
3. sys_feature_registry  (global flag tanımı, tenant_id NULL)
4. sys_feature_default   (global default)
5. config/features.php   (statik registry)
Yeni kod:

php
public function enabled(string $key): bool {
    // 1. Tenant override (varsa kesin)
    $override = $this->tenantOverride($key);
    if ($override !== null) return $override;
    
    // 2. Registry'de tanımlı mı? (önce tenant, sonra global)
    $registry = $this->registryRow($key); // sys_feature_registry
    if ($registry) {
        // Bağımlılık kontrolü
        foreach ($registry->requires ?? [] as $parent) {
            if (!$this->enabled($parent)) return false;
        }
        return (bool) $registry->default_value;
    }
    
    // 3. Statik registry (config/features.php)
    $static = $this->staticRegistry()[$key] ?? null;
    if ($static) {
        // eski mantık...
    }
    
    // 4. Bilinmeyen flag
    return false;
}
7.5. Kullanıcı Flag Yaratma
Tenant admin UI'dan yeni flag tanımlar:

yaml
feature_key: "custom.loyalty_points"
label: "Sadakat Puanı Sistemi"
group_name: "custom"
type: "bool"
default_value: true
requires: ["otc.enabled"]
Etki:

Anında Features::enabled('custom.loyalty_points') çalışır

Admin UI'da görünür

Migration gerekmez

7.6. Structure Flags — L3
Platform structure içindeki özellikler:

yaml
# Structure tanımı
structure:
  features:
    - key: "sarfiyyat"
      default: true
      editable_by_tenant: true    # tenant değiştirebilir mi
    - key: "layers.multi_currency"
      default: true
      editable_by_tenant: false   # sadece structure sahibi değiştirir
Kural: Structure içindeki flag'ler tenant override edilebilir veya edilemez (structure sahibi belirler).

7.7. Permission Entegrasyonu
Flag + permission birlikte:

php
// Sadece "flag açık" + "permission var" ise çalışır
Route::middleware([
    'feature:otc.sarfiyyat',
    'can:otc.sarfiyyat.edit'
])->group(...);
Yeni middleware: feature.and.permission:<flag>,<permission>

7.8. Layer-Aware Flags
Layer bazlı flag:

php
// Sadece "vendor" katmanında geçerli
'otc.vendor_reports' => [
    'requires' => ['otc.enabled'],
    'layer' => 'vendor',
]
Çözümleme: Aktif kullanıcının layer'ı flag'in layer'ıyla uyuşmuyorsa false.

8. Migration Stratejisi — Statik → Dinamik
8.1. Aşama 1: Registry Tablosu (2 hafta)
sql
CREATE TABLE sys_feature_registry (...);
Mevcut 27 flag'i is_system = true ile seed et. Kod aynen çalışır, sadece fallback eklendi.

8.2. Aşama 2: Kullanıcı Flag Yaratma (4 hafta)
Admin UI'da "Yeni Flag" butonu. sys_feature_registry CRUD.

8.3. Aşama 3: Structure Flags (8 hafta)
psf_structure tablosu ile entegrasyon. Structure içindeki flag'ler otomatik registry'ye sync.

8.4. Aşama 4: Layer-Aware (12 hafta)
Layer context ile flag resolve. Cross-tenant flag yönetimi.

9. Feature Flag — Performans
9.1. Cache Stratejisi
text
Request başında:
    ↓
Cache: tenant flag'ler (Redis, 5 dk TTL)
    ↓
Cache miss → DB sorgu → cache
    ↓
Request boyunca in-memory cache
Uygulama:

php
private array $resolvedCache = [];

public function enabled(string $key): bool {
    if (isset($this->resolvedCache[$key])) return $this->resolvedCache[$key];
    
    $result = $this->resolve($key);
    return $this->resolvedCache[$key] = $result;
}
9.2. Query Optimizasyonu
Bir seferde tüm tenant flag'lerini yükle:

php
public function preload(): void {
    $this->tenantCache = SysTenantFeature::where('tenant_id', $this->tenantId)
        ->get()
        ->keyBy('feature_key')
        ->all();
}
Kural: render() öncesi preload() çağır. N+1 önlenir.

9.3. Cache Invalidation
Tenant admin flag değiştirdiğinde:

php
Cache::forget("tenant:{$tenantId}:features");
event(new FeatureFlagChanged($tenantId, $key));
Etki: Sonraki request'te cache miss → DB'den yeniden yüklenir.

10. Test Stratejisi
10.1. Unit Test
php
public function test_flag_resolution_priority(): void {
    // 1. Registry default
    $this->assertTrue(Features::enabled('otc.sarfiyyat'));
    
    // 2. Global default
    SysFeatureDefault::create(['feature_key' => 'otc.sarfiyyat', 'enabled' => false]);
    Features::flush();
    $this->assertFalse(Features::enabled('otc.sarfiyyat'));
    
    // 3. Tenant override
    SysTenantFeature::create([
        'tenant_id' => $tenantId,
        'feature_key' => 'otc.sarfiyyat',
        'enabled' => true,
    ]);
    Features::flush();
    $this->assertTrue(Features::enabled('otc.sarfiyyat'));
}
10.2. Dependency Test
php
public function test_disabled_parent_disables_children(): void {
    Features::set('otc.enabled', false);
    $this->assertFalse(Features::enabled('otc.sarfiyyat'));
    $this->assertFalse(Features::enabled('otc.sarfiyyat.required'));
}
10.3. Middleware Test
php
public function test_route_blocked_when_flag_disabled(): void {
    Features::set('otc.enabled', false);
    
    $this->actingAs($user)
        ->get('/otc/sales-orders')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('feature_disabled', 'otc.enabled');
}
10.4. RLS Test
php
public function test_tenant_cannot_see_other_tenant_flags(): void {
    SysTenantFeature::create([
        'tenant_id' => $tenantA->id,
        'feature_key' => 'otc.sarfiyyat',
        'enabled' => false,
    ]);
    
    $result = TenantContext::runAs($tenantB->id, fn() => SysTenantFeature::all());
    $this->assertCount(0, $result);
}
11. Admin UI — Tasarım
11.1. Mevcut Tasarım
/settings/features — Livewire component.

Bölümler:

📦 Modüller (11 toggle, kart grid)

🛒 Satış İçi Özellikler (7 toggle, katlanır)

⚡ UX (4 toggle)

📊 Analitik (3 toggle)

🔒 Güvenlik (2 toggle)

Özellikler:

Anlık kaydetme (Livewire wire:click)

Bağımlılık göstergesi (parent kapalıysa gri)

Type-aware kontrol

"Özel" badge (override varsa)

Sıfırla butonu

11.2. Meta-Platform UI (Yeni)
Eklenmesi gereken:

Yeni Flag Yarat butonu (tenant admin)

Flag arama (27 → 100+ olunca zorunlu)

Flag import/export (YAML)

Flag history (audit log)

Flag groups (custom gruplar)

Flag usage analytics (hangi flag ne kadar kullanılıyor)

Structure integration (hangi flag hangi structure'a ait)

11.3. Developer UI (Yeni)
/developer/features — developer panel:

Kendi flag'lerini yönet

Hangi tenant hangi flag'i kullanıyor

Flag değişiklik geçmişi

A/B test sonuçları

12. Anti-Patterns
Bu projede yasak olan feature flag hataları:

Flag'i permission olarak kullanmak — İkisi farklı katman

Flag'i debug için kullanmak — debug_mode flag'i değil, env değişkeni

Flag'i config için kullanmak — app.name flag değil, .env

Flag silmek — Deprecation policy zorunlu

Flag default'u true olmayan — Yeni flag açık gelmeli (mevcut davranış korunur)

Nested if (5+ seviye) — Flag kombinasyonu → ayrı flag

Features::enabled() her yerde — Servis katmanında tek kontrol

Flag kontrolü UI'da ama serviste değil — Güvenlik açığı

Flag değişikliğinde cache flush unutmak — Eski değer görünür

Migration'sız flag değişikliği — Her zaman registry güncelle

13. Glossary
Terim	Anlam
Feature flag	Aç/kapa anahtarı
Registry	Flag tanımları (config + DB)
Resolution	Flag değerini belirleme (override → global → default)
Override	Tenant bazlı flag değeri
Dependency	Parent flag'e bağımlılık
Static flag	Kodda tanımlı (27 mevcut)
Dynamic flag	DB'de tanımlı (meta-platform)
Structure flag	Platform structure içindeki flag
Layer-aware	Katmana özel flag
Deprecation	Kullanımdan kaldırma (silme değil)
A/B test	İki grup farklı davranış
14. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — mevcut 27 flag + meta-platform genişleme