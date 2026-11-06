# 04 — Module System

**Project:** Kurumsal ERP Platform
**Belge:** Modül Sistemi, Core Modüller, Meta-Platform Modül Mimarisi
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Living Document — mevcut sistem + genişleme
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **modül sistemini** tanımlar:

- Modül nedir, plugin'den farkı ne
- Mevcut modüller (FIN, INV, OTC, PRC, HRM, MFG, PPM, EAM, CRM, QM, LOG, DMS, SaaS)
- Modül → route → feature flag ilişkisi
- Modül yaşam döngüsü (aktif/pasif)
- Meta-platform vizyonu (modül + plugin ekosistemi)
- Modül oluşturma rehberi

**Hedef kitle:** Geliştiriciler, AI asistanlar, ürün yöneticileri.

---

## 2. Modül Nedir?

**Modül** = belirli bir iş alanına hizmet eden **kod paketi**. İçerir:

- **Modeller** (Eloquent)
- **Servisler** (iş mantığı)
- **Livewire component'leri** (UI)
- **Migration'lar** (schema)
- **Route'lar** (URL)
- **View'lar** (Blade)
- **Feature flag'ler** (aç/kapa)
- **Seeders** (varsayılan veri)

### 2.1. Modül vs Plugin vs Custom Entity

| Kavram | Kim yazar | Nereye kurulur | Güven |
|---|---|---|---|
| **Module** | Biz (platform) | Her tenant | Yüksek (bizim) |
| **Plugin** | Developer / partner | Tenant seçer | Sandbox'ta |
| **Custom Entity** | Tenant admin | Sadece kendi tenant | Metadata |
| **Custom Field** | Tenant admin | Entity'ye alan | Metadata |

**Kural:** Modül **bizim**, plugin **ekosistemin**, custom **kullanıcının**. Üçü de platformda çalışır, ama farklı güven seviyelerinde.

### 2.2. Modül Prensipleri

1. **İzole** — Modül başka modülün iç koduna dokunmaz
2. **Event üzerinden iletişim** — Modüller direkt birbirini çağırmaz
3. **Optional** — Feature flag ile kapatılabilir
4. **Self-contained** — Kendi tabloları, route'ları, ayarları
5. **Migratable** — Kendi migration'ları, ileri/geri
6. **Testable** — Her modülün kendi testi

---

## 3. Mevcut Modüller

### 3.1. Modül Kataloğu

| Kod | Ad | Sorumlu | Ana İşlev |
|---|---|---|---|
| `PLT` | Platform | Tenant, user, feature, audit | Çekirdek |
| `FIN` | Finans | Muhasebe, AR/AP, banka, bütçe | Parasal |
| `INV` | Envanter | Ürün, stok, depo, fiyat | Mal |
| `PRC` | Satın Alma | PO, mal kabul, 3-way match | Tedarik |
| `OTC` | Satış | SO, teklif, dönüşüm | Müşteri |
| `HRM` | İnsan Kaynakları | Personel, puantaj, bordro, masraf | Personel |
| `MFG` | Üretim | BOM, üretim emri, MRP | Üretim |
| `PPM` | Projeler | Proje, görev, zaman | Proje |
| `EAM` | Varlık | Varlık, bakım | Ekipman |
| `CRM` | CRM | Lead, fırsat, aktivite | Müşteri ilişkisi |
| `QM` | Kalite | Muayene, NCR | Kalite |
| `LOG` | Lojistik | Kargo, sevkiyat | Dağıtım |
| `DMS` | Doküman | Doküman, klasör | Arşiv |
| `SaaS` | Abonelik | Plan, tenant abonelik | Ticari |

**Toplam:** 14 modül.

### 3.2. Modül Bağımlılık Haritası
PLT (her modül PLT'ye bağımlı)
├── FIN
│ ├── INV (stok değeri → muhasebe)
│ ├── PRC (PO → AP invoice)
│ ├── OTC (SO → AR invoice)
│ └── HRM (bordro → yevmiye)
├── INV
│ ├── PRC (mal kabul → stok)
│ ├── OTC (satış → stok çıkışı)
│ ├── MFG (BOM → hammadde)
│ └── LOG (sevkiyat → stok)
├── PRC (PO → mal kabul → stok + AP)
├── OTC (SO → stok + AR)
├── HRM
├── MFG (MFG → stok hareketi)
├── PPM (PPM → masraf merkezi)
├── EAM (EAM → amortisman → FIN)
├── CRM (CRM → OTC lead)
├── QM (QM → mal kabul kontrol)
├── LOG (LOG → OTC sevkiyat)
├── DMS (her modül doküman)
└── SaaS (PLT ile entegre)

text

**Kural:** Modül bağımlılığı **event üzerinden** çalışır. FIN direkt INV'i çağırmaz — INV `stock.moved` event'i yayınlar, FIN dinler.

---

## 4. Modül → Route → Feature Flag İlişkisi

### 4.1. Route Pattern

Her modülün route'ları **prefix + middleware** ile:

```php
// routes/web.php

Route::middleware(['web.auth', 'module.active:FIN', 'feature:fin.enabled'])->group(function () {
    Route::get('/fin/coa', ChartOfAccounts::class)->name('fin.coa');
    Route::get('/fin/journal-entries', JournalEntries::class)->name('fin.journal-entries');
    // ... 25 route
});

Route::middleware(['web.auth', 'module.active:OTC', 'feature:otc.enabled'])->group(function () {
    Route::get('/otc/sales-orders', SalesOrders::class)->name('otc.sales-orders');
    Route::get('/otc/convert-to-invoice', ConvertToInvoice::class)->name('otc.convert-to-invoice');
});
Üç katmanlı koruma:

web.auth — Giriş kontrolü

module.active:XXX — Kullanıcının rolü bu modüle erişebilir mi

feature:xxx.enabled — Tenant bu modülü kullanıyor mu

4.2. Sidebar Filtresi
Sidebar her modülü gösterirken:

php
@if(in_array('FIN', $activeModules) && Features::enabled('fin.enabled'))
    <a href="{{ route('fin.coa') }}">Hesap Planı</a>
@endif
Veya NavigationService içinde merkezi:

php
foreach ($sections as $section) {
    if ($section['module'] === 'FIN' && !Features::enabled('fin.enabled')) continue;
    // ...
}
4.3. Modül Pasifleştirme
Superadmin tenant'ı belirli modülden men edebilir:

php
Features::set('mfg.enabled', false); // tenant için MFG kapalı
Tenant admin kendi kullanmadığı modülü kapatabilir:

php
// /settings/features
Features::set('ppm.enabled', false); // proje yönetimi kullanmıyor
Etki:

Route'lar 404 döner

Sidebar'da görünmez

Arama sonuçlarında yok

API endpoint'leri 403

5. Modül Yaşam Döngüsü
5.1. Aşamalar
text
Kod → Deploy → Route aktif → Tenant açar → Kullanım → Tenant kapatır → Kod korunur
Aşama	Ne
Kod	Modül kodu yazıldı
Deploy	Server'a yüklendi, route'lar aktif
Default OFF/ON	Feature flag default değeri
Tenant açar	Tenant admin kendi panelinden
Kullanım	Gerçek kullanım
Tenant kapatır	Tenant admin panelinden
Kod korunur	Modül silinmez, sadece kapanır
Kural: Modül asla silinmez — sadece feature flag ile kapatılır. Veri korunur.

5.2. Yeni Modül Ekleme
Yeni modül eklemek 5 adım:

Adım 1: Modül kayıt
config/modules.php (yeni dosya):

php
return [
    'payroll_advanced' => [
        'code' => 'PRA',
        'name' => 'Gelişmiş Bordro',
        'depends_on' => ['HRM', 'FIN'],
        'default' => false,
        'version' => '1.0.0',
    ],
];
Adım 2: Feature flag
config/features.php:

php
'pra.enabled' => [
    'label' => 'Gelişmiş Bordro',
    'group' => 'modules',
    'type' => 'bool',
    'default' => false,
    'requires' => ['hrm.enabled', 'fin.enabled'],
    'order' => 65,
],
Adım 3: Modül kodu
text
app/Livewire/Pra/
app/Models/Pra/
app/Services/Pra/
database/migrations/pra_*
resources/views/livewire/pra/
Adım 4: Route
php
Route::middleware(['web.auth', 'module.active:PRA', 'feature:pra.enabled'])->group(function () {
    Route::get('/pra/payroll-advanced', PayrollAdvanced::class)->name('pra.payroll-advanced');
});
Adım 5: Sidebar + Navigation
NavigationService::menu() içine yeni bölüm ekle.

6. Modül İç Yapısı (Standard)
6.1. Klasör Yapısı
text
app/
├── Livewire/{Module}/
│   └── {Feature}.php
├── Models/{Module}/
│   └── {Entity}.php
├── Services/{Module}/
│   └── {Service}Service.php
├── Support/{Module}/
│   └── {Helper}.php
database/migrations/
│   └── {timestamp}_{action}_{module}_{table}.php
resources/views/livewire/{module}/
│   └── {feature}.blade.php
routes/web.php (grup içinde)
6.2. Servis Katmanı Zorunlu
Kural: İş mantığı sadece servis katmanında. Controller/Livewire sadece UI.

php
// ✅ Doğru
class SalesOrders extends Component {
    public function save() {
        app(SalesOrderService::class)->create($data);
    }
}

class SalesOrderService {
    public function create(array $data): OtcSalesOrder {
        return DB::transaction(function () use ($data) {
            $order = OtcSalesOrder::create($data);
            event(new SalesOrderCreated($order));
            return $order;
        });
    }
}

// ❌ Yanlış (Livewire içinde iş mantığı)
class SalesOrders extends Component {
    public function save() {
        $order = OtcSalesOrder::create($this->data);
        foreach ($this->lines as $line) {
            $order->lines()->create($line);
            app(StockService::class)->decrease($line['product_id'], $line['qty']);
            app(InvoiceService::class)->create($order);
        }
    }
}
6.3. Event-Driven
Modüller birbirini event üzerinden tetikler:

php
// OTC modülü
event(new SalesOrderConfirmed($order));

// INV modülü dinler
class DecreaseStockOnSale {
    public function handle(SalesOrderConfirmed $event): void {
        foreach ($event->order->lines as $line) {
            app(StockService::class)->decrease($line->product_id, $line->qty);
        }
    }
}

// FIN modülü dinler
class CreateInvoiceOnSale {
    public function handle(SalesOrderConfirmed $event): void {
        app(InvoiceService::class)->createFromSale($event->order);
    }
}
Kural: Modüller birbirini doğrudan çağırmaz, event yayınlar.

7. Modül Migration Kuralları
7.1. Prefix Zorunlu
php
Schema::create('fin_customer', ...);      // ✅
Schema::create('customer', ...);          // ❌ (prefix yok)
Schema::create('pra_payroll_advanced', ...); // ✅
7.2. RLS Zorunlu
Her tenant-scoped tablo:

php
DB::statement('ALTER TABLE {module}_{table} ENABLE ROW LEVEL SECURITY');
DB::statement('ALTER TABLE {module}_{table} FORCE ROW LEVEL SECURITY');
DB::statement(<<<'SQL'
    CREATE POLICY tenant_isolation ON {module}_{table}
        USING (
            current_setting('app.is_superadmin', true) = 'true'
            OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
        )
        WITH CHECK (
            current_setting('app.is_superadmin', true) = 'true'
            OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
        )
SQL);
7.3. Rollback Zorunlu
Her migration'ın down() metodu ters işlem yapmalı.

7.4. Idempotent
php
if (Schema::hasTable('fin_customer')) return;
8. Meta-Platform Genişlemesi
8.1. Mevcut Sınırlama
Sınırlama	Etki
Modül listesi kod içinde	Yeni modül = deploy
Modül bizim tarafımızdan yazılır	Developer modül ekleyemez
Modül deploy ile eklenir	Tenant yeni modül kuramaz
Modül platformun parçası	Marketplace yok
8.2. Meta-Platform Modül Sistemi
Meta-platform vizyonunda 3 tür "modül" olur:

Tür	Kim yazar	Nasıl eklenir	Güven
Core Module	Biz	Deploy	Tam
Extension Module	Partner (onaylı)	Deploy veya install	Yüksek
Plugin	Developer	Marketplace'ten install	Sandbox
8.3. Modül Registry — Dinamik
config/modules.php yerine sys_module_registry tablosu:

sql
CREATE TABLE sys_module_registry (
    id              uuid PRIMARY KEY,
    code            varchar(20) NOT NULL UNIQUE,
    name            varchar(255) NOT NULL,
    version         varchar(20) NOT NULL,
    provider        varchar(100) DEFAULT 'platform',  -- platform | partner | tenant
    status          varchar(20) DEFAULT 'active',     -- active | beta | deprecated
    depends_on      text[],
    feature_key     varchar(120),
    metadata        jsonb,
    installed_at    timestamptz,
    created_at      timestamptz,
    updated_at      timestamptz
);
Sonuç: Modül runtime'da eklenebilir (deploy sonrası, DB insert).

8.4. Module Auto-Discovery
Laravel ServiceProvider ile:

php
// app/Providers/ModuleServiceProvider.php
public function boot(): void {
    foreach (ModuleRegistry::active() as $module) {
        $routeFile = base_path("modules/{$module->code}/routes.php");
        if (file_exists($routeFile)) {
            Route::middleware(['web.auth', "feature:{$module->feature_key}"])->group($routeFile);
        }
    }
}
Klasör yapısı:

text
modules/
├── FIN/
│   ├── module.json         # metadata
│   ├── routes.php
│   ├── Migrations/
│   ├── Models/
│   ├── Livewire/
│   └── Views/
├── INV/
│   └── ...
└── CustomModuleX/
    └── ...
8.5. Module Install/Uninstall
php
class ModuleInstaller {
    public function install(string $code): void {
        $module = ModuleRegistry::find($code);
        
        // 1. Migration'ları çalıştır
        foreach ($module->migrations() as $migration) {
            $migration->up();
        }
        
        // 2. Feature flag aktif
        Features::set($module->feature_key, true);
        
        // 3. Event listener'ları kaydet
        EventBus::subscribe($module);
        
        // 4. Audit
        AuditLog::log('module.installed', $code);
    }
    
    public function uninstall(string $code): void {
        // Veri korunur, sadece route/UI kapanır
        Features::set($module->feature_key, false);
        AuditLog::log('module.uninstalled', $code);
    }
}
Kural: Uninstall = veri silme değil, sadece flag kapatma.

8.6. Partner Modules
Onaylı partner, kendi modülünü sunar:

yaml
# modules/AcmeTax/module.json
{
    "code": "ACME_TAX",
    "name": "Acme Tax Compliance",
    "version": "1.2.0",
    "provider": "partner",
    "partner_id": "acme-corp",
    "depends_on": ["FIN"],
    "signed": true,
    "signature": "sha256:...",
    "marketplace_url": "https://..."
}
Kurulum:

Superadmin onaylar → status = 'approved'

Tenant admin kendi panelinden seçer → install

Modül sandbox'ta çalışır (yüksek izinlerle)

8.7. Tenant Custom Modules
Tenant kendi modülünü yazabilir (Belge 10 — Plugin System detaylı):

yaml
# modules/TesfirmaWolt/module.json
{
    "code": "TESFIRMA_WOLT",
    "name": "Wolt Integration",
    "version": "1.0.0",
    "provider": "tenant",
    "tenant_id": "tesfirma-tenant-id",
    "depends_on": ["OTC", "LOG"]
}
Kurulum: Tenant admin kendi panelinden. Diğer tenant'lar göremez.

9. Modül İletişimi — Event Bus Preview
9.1. Neden Event Bus?
Modüller birbirini doğrudan çağırmaz:

php
// ❌ Yanlış
class SalesOrderService {
    public function create() {
        $order = OtcSalesOrder::create(...);
        app(StockService::class)->decrease(...);   // INV'e direkt bağımlı
        app(InvoiceService::class)->create(...);   // FIN'e direkt bağımlı
        app(NotificationService::class)->notify(...); // PLT'ye direkt bağımlı
    }
}

// ✅ Doğru
class SalesOrderService {
    public function create() {
        $order = OtcSalesOrder::create(...);
        event(new SalesOrderCreated($order)); // Sadece event yayınla
    }
}
9.2. Event Yayınla/Dinle
php
// OTC modülü yayınlar
event(new SalesOrderCreated($order));

// INV modülü dinler
class DecreaseStock {
    public function handle(SalesOrderCreated $event): void {
        // Stok düş
    }
}

// FIN modülü dinler
class CreateInvoice {
    public function handle(SalesOrderCreated $event): void {
        // Fatura oluştur
    }
}
Kural: Modül bağımlılık zinciri event ile kopar. INV kapalıysa, FIN yine de çalışır (sadece o listener çalışmaz).

Detay: Belge 08 — Event Bus.

10. Modül Test Stratejisi
10.1. Modül Bağımsız Test
Her modül kendi testiyle çalışabilmeli:

php
// tests/Feature/Fin/CustomerTest.php
class CustomerTest extends TestCase {
    public function test_can_create_customer(): void {
        // Tenant context
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        
        $this->actingAs($user);
        
        TenantContext::runAs($tenant->id, function () {
            Livewire::test(Customers::class)
                ->set('code', 'M001')
                ->set('name', 'Customer 1')
                ->call('save')
                ->assertHasNoErrors();
            
            $this->assertDatabaseHas('fin_customer', ['code' => 'M001']);
        });
    }
}
10.2. Modül Entegrasyon Test
İki modül birlikte:

php
// tests/Feature/Integration/SaleToInvoiceTest.php
public function test_sale_order_creates_invoice(): void {
    // OTC + FIN birlikte
    $order = app(SalesOrderService::class)->create($data);
    event(new SalesOrderConfirmed($order));
    
    $this->assertDatabaseHas('fin_ar_invoice', ['sales_order_id' => $order->id]);
}
10.3. Modül Kapalı Test
php
public function test_route_404_when_module_disabled(): void {
    Features::set('otc.enabled', false);
    
    $this->actingAs($user)->get('/otc/sales-orders')->assertRedirect('/dashboard');
}
11. Modül Dokümantasyon
Her modülün kendi README'si olmalı:

text
modules/FIN/
├── README.md
├── module.json
├── routes.php
├── Migrations/
├── Models/
├── Services/
├── Livewire/
└── Views/
README.md içeriği:

markdown
# FIN — Finans Modülü

## Sorumluluk
Muhasebe, AR/AP, banka, bütçe, raporlama.

## Bağımlılıklar
- PLT (zorunlu)
- INV (stok değeri için, opsiyonel)

## Feature Flag
`fin.enabled`

## Tablolar
- `fin_account`, `fin_journal_entry`, `fin_journal_line`
- `fin_customer`, `fin_supplier`
- `fin_ar_invoice`, `fin_ap_invoice`
- ...

## Event'ler
**Yayınlar:**
- `InvoicePosted` — fatura kesildi
- `PaymentReceived` — ödeme alındı

**Dinler:**
- `SalesOrderConfirmed` — AR invoice oluşturur
- `GoodsReceived` — AP invoice oluşturur

## API Endpoints
Detay: `/api/v1/fin/*` (Belge 09)

## Test
`php artisan test --filter=Fin`
12. Anti-Patterns
Bu projede yasak olan modül hataları:

Modül içinde başka modülün modelini çağırmak — Event kullan

Modül içinde başka modülün servisini çağırmak — Event kullan

Prefix'siz tablo — fin_, inv_ zorunlu

RLS'siz tenant-scoped tablo — Migration reddedilir

down() boş — Rollback zorunlu

Modül silmek — Sadece flag kapat

Feature flag kontrolü atlamak — Route + servis zorunlu

İş mantığı Livewire içinde — Servis zorunlu

Global fonksiyon (helper) — Servis/Support class

Modül bağımlılığı statik — Registry + event

13. Glossary
Terim	Anlam
Module	Kod paketi (bizim yazdığımız)
Plugin	Kod paketi (ekosistemin yazdığı)
Core Module	Platformun parçası
Extension Module	Partner modülü
Custom Module	Tenant modülü
Module Registry	Modül kataloğu (config/modules.php veya DB)
Module Auto-Discovery	Runtime'da modül yükleme
Event Bus	Modüller arası async mesajlaşma
Self-contained	Kendi içinde çalışan modül
Migratable	Kendi migration'ı olan
14. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — mevcut 14 modül + meta-platform genişleme