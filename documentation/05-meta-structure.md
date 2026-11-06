# 05 — Meta-Structure Framework

**Project:** Kurumsal ERP Platform
**Belge:** Platform Structure Tanımlama, Katman Sistemi, Dinamik Yapı Motoru
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **en kritik yeniliğini** tanımlar: **kullanıcının kendi platform yapısını tanımlayabilmesi.** İçerir:

- Platform Structure nedir, neden gerekli
- Yapı tanımlama dili (YAML)
- Katman (Layer) modeli — sınırsız
- Rol (Role) modeli — sınırsız
- Veri modeli (Data Scope)
- Yapı versiyonlama
- Yapı builder araçları
- Çalışma zamanı (runtime)
- Yapı marketplace

**Hedef kitle:** Geliştiriciler, AI asistanlar, ürün mimarları, structure builder'lar.

**Kritik:** Bu belge, meta-platformun kalbi. Diğer tüm belgeler (06-13) bunun üzerine kurulur.

---

## 2. Problem — Neden Yapı Tanımlama?

### 2.1. Klasik ERP'nin Dayatması

Her klasik ERP **belirli bir organizasyon modeli** dayatır:

| ERP | Dayattığı Model |
|---|---|
| SAP | Şirket kodu → iş alanı → tesis |
| Odoo | Şirket → şube → depo |
| Logo | Firma → şube → depo |
| NetSuite | Subsidiary → department → location |

**Sorun:** Bu modeller **sabit**. Kullanıcı "bizim yapımız farklı" dediğinde → **özel geliştirme** (6 ay, 100K TL).

### 2.2. Gerçek Dünya Farklı

| Sektör | Organizasyon Modeli |
|---|---|
| **Marketplace** | Vendor → Seller → Customer → Logistics → Payment (6 katman) |
| **Franchise** | Merkez → Bölge → Şube → Çalışan → Müşteri (5 katman) |
| **Holding** | Ana → İştirak → Departman → Ekip → Çalışan (5 katman) |
| **Tedarik Zinciri** | Üretici → T1 → T2 → Distribütör → Perakendeci (5+ katman) |
| **Klinik** | Hastane → Klinik → Poliklinik → Doktor → Hasta (5 katman) |
| **Dernek** | Merkez → Şube → Üye → Gönüllü → Etkinlik (5 katman) |
| **Kooperatif** | Merkez → Ortak → Çiftlik → Üretim (4 katman) |
| **Klasik** | Şirket → Departman → Kullanıcı (3 katman) |

**Her biri farklı.** Hiçbir standart model hepsine uymaz.

### 2.3. Mevcut Çözümler Yetersiz

| Yaklaşım | Neden Yetersiz |
|---|---|
| **Sabit model + custom** | Her müşteri = 6 ay geliştirme |
| **Aşırı esnek model** (matrix org) | Kullanıcı kaybolur, karmaşık |
| **Config + kod** | Hâlâ kod yazmak gerek |
| **Metadata + custom field** | Sadece alan, katman yok |

**Bizim çözüm:** **Platform Structure** — yapıyı kullanıcı tanımlar, platform çalıştırır.

---

## 3. Platform Structure Nedir?

**Platform Structure** = bir organizasyon modelinin **veri olarak tanımı.** İçerir:

- Katmanlar (kaç seviye, ne tür)
- Roller (her katmanda kim)
- Veri modeli (her katman ne görür)
- İş akışları (her katmanda hangi süreçler)
- İzinler (her rol ne yapabilir)
- UI (her katman hangi ekranları görür)
- Feature flags (hangi özellikler açık)

### 3.1. Basit Örnek — Klasik 3 Katman

```yaml
structure:
  key: "classic-3-layer"
  name: "Klasik Şirket Yapısı"
  version: "1.0.0"
  
  layers:
    - key: "company"
      name: "Şirket"
      parent: null
      cardinality: "many"
      
    - key: "department"
      name: "Departman"
      parent: "company"
      cardinality: "many"
      
    - key: "user"
      name: "Kullanıcı"
      parent: "department"
      cardinality: "many"
  
  roles:
    - key: "company_admin"
      layer: "company"
      permissions: ["*"]
    
    - key: "department_manager"
      layer: "department"
      permissions: ["department.*", "user.read"]
    
    - key: "staff"
      layer: "user"
      permissions: ["self.*"]
3.2. Karmaşık Örnek — Marketplace 6 Katman
yaml
structure:
  key: "marketplace-v1"
  name: "Marketplace Ekosistemi"
  version: "2.1.0"
  
  layers:
    - key: "platform_owner"
      name: "Platform Sahibi"
      parent: null
      cardinality: "one"
      
    - key: "vendor"
      name: "Satıcı"
      parent: "platform_owner"
      cardinality: "many"
      
    - key: "seller"
      name: "Bayi"
      parent: "vendor"
      cardinality: "many"
      
    - key: "customer"
      name: "Son Müşteri"
      parent: "seller"
      cardinality: "many"
      
    - key: "logistics_partner"
      name: "Lojistik Partner"
      parent: "platform_owner"
      cardinality: "many"
      
    - key: "payment_provider"
      name: "Ödeme Sağlayıcı"
      parent: "platform_owner"
      cardinality: "many"
  
  roles:
    - key: "platform_admin"
      layer: "platform_owner"
      permissions: ["*"]
    
    - key: "vendor_manager"
      layer: "vendor"
      permissions: ["vendor.*", "seller.*", "product.*"]
    
    - key: "seller_manager"
      layer: "seller"
      permissions: ["seller.*", "product.own", "order.own"]
    
    - key: "customer"
      layer: "customer"
      permissions: ["order.own", "profile.own"]
    
    - key: "logistics_staff"
      layer: "logistics_partner"
      permissions: ["shipment.assigned"]
    
    - key: "payment_staff"
      layer: "payment_provider"
      permissions: ["transaction.read"]
  
  data_scope:
    - layer: "vendor"
      sees: ["seller.*", "product.own_vendor", "order.own_vendor"]
      not_sees: ["customer.pii", "other_vendor.*"]
    
    - layer: "seller"
      sees: ["product.own_seller", "order.own_seller"]
      not_sees: ["vendor.financials", "customer.pii"]
    
    - layer: "customer"
      sees: ["order.own", "profile.own"]
      not_sees: ["vendor.*", "seller.*"]
3.3. Yapı Bileşenleri
Bileşen	Zorunlu	Açıklama
key	✅	Benzersiz tanımlayıcı
name	✅	Görünen isim
version	✅	Semver
layers	✅	Katman listesi (min 1)
roles	✅	Rol listesi (min 1)
data_scope	⚠️	Veri görünürlük (default: tüm tenant)
workflows	❌	İş akışları (opsiyonel)
ui	❌	Ekran düzeni (opsiyonel)
features	❌	Feature flag'ler
metadata	❌	Ek bilgi
4. Katman (Layer) Modeli
4.1. Tanım
Bir katman, organizasyonun bir seviyesi. Her katmanın:

Key — benzersiz tanımlayıcı

Name — görünen isim

Parent — üst katman (null = kök)

Cardinality — alt katman sayısı (one, many, one_or_many)

Data scope — veri görünürlüğü

Roles — bu katmandaki roller

Behaviors — bu katmanın davranışları

4.2. Yapısal Özellikler
Özellik	Açıklama	Örnek
Sınırsız sayı	1 katman ile 100 katman arası	layers: [...]
Ağaç yapısı	Her katmanın bir parent'ı var (kök hariç)	parent: "company"
Graf yapısı	Katmanlar arası çapraz ilişki	relations: [...]
İç içe	Bir katmanın içinde alt yapı olabilir	nested_structure: "..."
Dinamik	Runtime'da katman eklenebilir (versiyonlama)	extensible: true
Conditional	Katman varlığı koşula bağlı	condition: "..."
4.3. Katman Tipleri
Katman tipi tanımlanabilir. Katalog:

Tip	Açıklama	Örnek
organizational	Şirket, departman, ekip	Klasik
geographic	Ülke, bölge, şehir	Bölgesel
functional	Satış, üretim, lojistik	Fonksiyonel
hierarchical	Distribütör, bayi, müşteri	Tedarik
virtual	Proje, geçici ekip	Sanal
external	Tedarikçi, partner	Dış
meta	Başka yapıları içeren	Meta
Kural: Bu liste kapalı değil. Kullanıcı yeni tip tanımlayabilir.

4.4. Katman Davranışları
Her katman şu davranışları tanımlayabilir:

yaml
layer:
  key: "vendor"
  behaviors:
    - create_children: true          # alt katman yaratabilir
    - delete_children: false         # alt katmanı silemez
    - inherit_data: "selective"      # veri mirası
    - report_to_parent: true         # üst katmana raporlar
    - visible_to_public: false       # halka açık mı
    - requires_approval: true        # oluşturma onay gerektirir
4.5. Katman Hiyerarşi Örnekleri
Ağaç (Tree):

text
Company
├── Department A
│   ├── User 1
│   └── User 2
└── Department B
    └── User 3
Graf (Graph):

text
Vendor A ─── Seller 1
    │             │
    ├── Seller 2 ─┤
    │             │
    └── Seller 3 ─┴── Customer X
İç içe (Nested):

text
Company
└── Project Team (virtual layer)
    └── Sub-project
        └── Task
Matrix (Çapraz):

text
User A: Department X + Project Y + Region Z
→ Bir kullanıcı birden fazla katmana ait olabilir (Belge 06).

5. Rol (Role) Modeli
5.1. Tanım
Bir rol, bir katmandaki kullanıcı tipi. Roller katman bazlı tanımlanır.

5.2. Yapısal Özellikler
Özellik	Açıklama
Key	Benzersiz tanımlayıcı
Layer	Hangi katmanda geçerli
Permissions	İzin seti
Inherits	Başka rolden miras
Composed of	Birden fazla rolün bileşimi
Conditional	İzinler koşula bağlı
Meta-role	Başka rolleri tanımlayabilir
5.3. Rol Örnekleri
Basit rol:

yaml
role:
  key: "sales_rep"
  layer: "user"
  permissions:
    - "otc.sales_order.create"
    - "otc.sales_order.read.own"
    - "otc.sales_order.update.own"
Miras alan rol:

yaml
role:
  key: "sales_manager"
  layer: "department"
  inherits: ["sales_rep"]
  permissions:
    - "otc.sales_order.*"    # tüm sales_order işlemleri
    - "user.read"
Bileşik rol:

yaml
role:
  key: "general_manager"
  composed_of: ["sales_manager", "finance_manager", "hr_manager"]
  # Tüm izinlerin birleşimi
Meta-rol:

yaml
role:
  key: "structure_builder"
  layer: "platform_owner"
  can_define:
    - layers
    - roles
    - workflows
    - structures
  permissions:
    - "platform.structure.*"
5.4. İzin Modeli
İzinler hiyerarşik:

text
module.entity.action[.scope]
Kısım	Değer
module	fin, inv, otc, *
entity	customer, invoice, *
action	read, create, update, delete, approve, *
scope	own, department, tenant, *
Örnek:

fin.customer.read — tüm müşterileri oku

otc.sales_order.update.own — sadece kendi siparişlerini güncelle

*.*.* — her şey (superadmin)

5.5. Koşullu İzin
yaml
role:
  key: "senior_approver"
  layer: "department"
  permissions:
    - "otc.sales_order.approve"
  conditions:
    - "sales_order.amount < 100000"
    - "sales_order.customer.risk_score < 5"
Runtime: Kullanıcı sadece koşullar sağlandığında onaylayabilir.

6. Data Scope — Veri Görünürlüğü
6.1. Tanım
Her katman neyi göreceğini tanımlar. Bu, RLS (Belge 01) üzerine application-level bir katman.

6.2. Scope Türleri
Scope	Açıklama
own	Sadece kendi verisi
children	Kendi + alt katmanları
parent	Kendi + üst katmanları
siblings	Aynı katmandaki diğerleri
tenant	Tüm tenant
*	Her şey (superadmin)
6.3. Data Scope Tanımı
yaml
data_scope:
  - layer: "vendor"
    entity: "otc.sales_order"
    see: "own_vendor"           # kendi satıcılarının siparişleri
    not_see: ["customer.pii", "other_vendor.*"]
  
  - layer: "seller"
    entity: "otc.sales_order"
    see: "own_seller"
  
  - layer: "customer"
    entity: "otc.sales_order"
    see: "own"
6.4. Runtime Sorgu
php
// Kullanıcı "seller" katmanında
$orders = OtcSalesOrder::query()
    ->where('seller_id', auth()->user()->seller_id)
    ->get();
Ama: Bu kod hardcoded. Meta-structure'da generic olmalı:

php
// Generic
$orders = DataScope::for(auth()->user())
    ->entity('otc.sales_order')
    ->query()
    ->get();
DataScope sınıfı, kullanıcının katmanına göre otomatik where ekler.

6.5. RLS + Data Scope
İki katman koruma:

RLS (DB seviyesi) — tenant izolasyonu

Data Scope (application seviyesi) — layer izolasyonu

php
// RLS: tenant_id = current_tenant
// DataScope: seller_id = current_user.seller_id

// İkisi birleşir:
// SELECT * FROM otc_sales_order 
// WHERE tenant_id = X 
//   AND seller_id = Y
Kritik: Data Scope, RLS'nin yerine geçmez, üstüne eklenir.

7. Structure Versioning
7.1. Neden Versiyonlama?
Kullanıcı yapıyı değiştirmek istiyor

Ama eski yapıya bağlı tenant'lar var

Ani değişiklik = veri kaybı

Çözüm: Versiyonlama + migration.

7.2. Semver
text
major.minor.patch
Bileşen	Ne zaman artar
major	Breaking change (katman silinir, veri kaybı)
minor	Yeni katman/rol/feature
patch	Bug fix, açıklama
Kural: major değişince, tenant'lar onay verene kadar eski versiyonda kalır.

7.3. Versiyon Tablosu
sql
CREATE TABLE psf_structure_version (
    id              uuid PRIMARY KEY,
    structure_id    uuid NOT NULL,
    version         varchar(20) NOT NULL,
    definition      jsonb NOT NULL,        -- full YAML → JSON
    parent_version  uuid NULL,             -- önceki versiyon
    migration       jsonb NULL,            -- eski → yeni
    is_breaking     boolean DEFAULT false,
    released_at     timestamptz,
    UNIQUE(structure_id, version)
);
7.4. Migration
Yapı değiştiğinde veri göçü tanımlanır:

yaml
migration:
  from: "1.0.0"
  to: "2.0.0"
  steps:
    - action: "rename_layer"
      from: "branch"
      to: "location"
    
    - action: "add_layer"
      key: "region"
      parent: "company"
      position: "before:location"
    
    - action: "map_data"
      table: "tenant_units"
      from_column: "branch_id"
      to_column: "location_id"
Runtime:

Migration test edilir (sandbox'ta)

Onay alındıktan sonra production'da çalışır

Hata olursa rollback

7.5. Tenant Version Pinning
Her tenant bir versiyona bağlı:

sql
ALTER TABLE tenants ADD COLUMN structure_id uuid;
ALTER TABLE tenants ADD COLUMN structure_version_id uuid;
Kural: Tenant, yapı değişse bile eski versiyonda kalır. Ta ki admin "güncelle" tıklayana kadar.

Zorunlu update: Major değişiklik + güvenlik → 72 saat uyarı + zorla.

8. Structure Builder — Yapı Tanımlama Araçları
8.1. Tanımlama Yöntemleri
Yöntem	Kim	Kullanım
YAML	Developer	Full kontrol
JSON	Developer	API
Visual Builder	Tenant admin	Görsel
AI	Herkes	Doğal dil → yapı
Şablon	Herkes	Hazır kopyala
Import	Developer	Başka yerden
8.2. Visual Builder
Bileşenler:

Canvas — sürükle-bırak katmanlar

Layer palette — hazır katman tipleri

Role editor — izin setleri

Data scope editor — veri görünürlük

Preview — canlı önizleme

Validate — yapı geçerliliği

Akış:

text
1. "Yeni Yapı" tıkla
2. Katman ekle (tıkla veya sürükle)
3. Roller tanımla (izinleri tıkla)
4. Data scope ayarla
5. Önizleme
6. Test (sandbox)
7. Yayınla (marketplace veya private)
8.3. AI-Assisted Builder
Kullanıcı: "Franchise zinciri için yapı oluştur: merkez → bölge → şube → çalışan → müşteri"

AI: YAML üretir:

yaml
structure:
  key: "franchise-standard"
  layers: [...]
  roles: [...]
  ...
Kullanıcı: Önizler, düzenler, yayınlar.

8.4. YAML Şablonları
Kütüphane:

text
templates/
├── classic-3-layer.yaml
├── marketplace-6-layer.yaml
├── franchise-5-layer.yaml
├── holding-4-layer.yaml
├── supply-chain-5-layer.yaml
├── clinic-4-layer.yaml
└── cooperative-4-layer.yaml
Kullanım: Kopyala, özelleştir, kaydet.

8.5. Validation
Her yapı validate edilir:

php
class StructureValidator {
    public function validate(array $structure): ValidationResult {
        // 1. Zorunlu alanlar
        // 2. Katman ağacı geçerli mi (döngü yok)
        // 3. Roller geçerli katmanlara bağlı mı
        // 4. İzinler tanımlı mı
        // 5. Data scope çelişkisiz mi
        // 6. Bağımlılık çözümlenebilir mi
        // 7. Versiyon semver geçerli mi
    }
}
Hata örnekleri:

❌ Layer child parent parent — döngü var

❌ Role admin layer nonexistent — tanımsız katman

❌ Permission fin.customer.fly — geçersiz action

❌ Data scope vendor + not_see: vendor.* — çelişki

8.6. Sandbox Testing
Yayınlamadan önce sandbox'ta test:

php
$sandbox = StructureSandbox::create($structureDefinition);

// Test: katman oluşturma
$sandbox->createLayer('vendor', ['name' => 'Vendor A']);
$sandbox->createLayer('seller', ['name' => 'Seller 1', 'parent' => 'vendor-a']);

// Test: rol izinleri
$user = $sandbox->asUser('vendor_manager');
$this->assertTrue($user->can('create_seller'));

// Test: data scope
$user = $sandbox->asUser('seller_manager');
$this->assertFalse($user->can('see_vendor_financials'));

// Test: workflow
$sandbox->runWorkflow('order_approval', $order);
Sandbox: İzole ortam, gerçek veri yok.

9. Runtime — Yapı Çalışma Zamanı
9.1. Request Akışı
text
1. Kullanıcı giriş yapar
    ↓
2. Hangi tenant? → tenant belirle
    ↓
3. Tenant hangi structure kullanıyor? → yapıyı yükle
    ↓
4. Kullanıcı hangi katmanda? → layer belirle
    ↓
5. Kullanıcı hangi rolde? → permission yükle
    ↓
6. Data scope uygula
    ↓
7. UI layout yükle
    ↓
8. Sayfa render
9.2. Middleware
php
// EnsureStructureContext
class EnsureStructureContext {
    public function handle($request, $next) {
        $tenant = TenantContext::get();
        $user = auth()->user();
        
        if (!$tenant || !$user) abort(401);
        
        // Yapıyı yükle
        $structure = Structure::load($tenant->structure_version_id);
        
        // Kullanıcının katman + rolünü belirle
        $membership = SysUserMembership::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->first();
        
        // Context'e ekle
        app()->instance(StructureContext::class, new StructureContext(
            structure: $structure,
            layer: $membership->layer,
            role: $structure->role($membership->role),
            permissions: $structure->permissionsFor($membership->role),
            dataScope: $structure->dataScope($membership->layer),
        ));
        
        return $next($request);
    }
}
9.3. StructureContext
Global context:

php
class StructureContext {
    public function __construct(
        public readonly Structure $structure,
        public readonly Layer $layer,
        public readonly Role $role,
        public readonly array $permissions,
        public readonly DataScope $dataScope,
    ) {}
    
    public function can(string $permission): bool {
        return in_array($permission, $this->permissions) 
            || in_array('*', $this->permissions);
    }
    
    public function canSee(string $entity): bool {
        return $this->dataScope->canSee($entity);
    }
}
9.4. UI Dinamik
Sidebar, yapıya göre dinamik:

php
foreach ($structure->ui->sections as $section) {
    if (!$context->can("view.{$section->key}")) continue;
    echo view('ui.section', compact('section'));
}
Menü sabit değil — yapı tanımlar.

9.5. API Dinamik
API endpoint'leri yapıya göre filtreli:

php
Route::middleware(['structure.can:fin.customer.read'])->group(function () {
    Route::get('/api/v1/fin/customers', ...);
});
Middleware, StructureContext'i kontrol eder.

10. Structure Marketplace
10.1. Yapı Satışı
Structure builder, yapısını marketplace'e koyabilir:

yaml
marketplace:
  listed: true
  price:
    model: "monthly"
    amount: 500
    currency: "TRY"
  categories: ["franchise", "retail"]
  target_audience: "small_chain"
  demo_tenant_id: "demo-xyz"
  trial_days: 14
10.2. Yapı Kategorileri
Sektör: Retail, Manufacturing, Services, Healthcare, Education

Boyut: Solo, SMB, Mid-Market, Enterprise

Model: Marketplace, Franchise, Holding, Coop

Bölge: Global, Regional, Local

10.3. Yapı Rating
Stars (1-5)

Reviews (yorum + ekran görüntüsü)

Usage stats (kaç tenant kullanıyor)

Update frequency (ne sıklıkla güncelleniyor)

10.4. Yapı Versiyonlama — Marketplace
Builder yeni versiyon yayınladığında:

Patch → otomatik

Minor → opsiyonel (tenant seçer)

Major → zorunlu onay + migration

10.5. Yapı Komisyonu
Platform %20-30 komisyon

Builder %70-80

Aylık ödeme

Stripe Connect / iyzico Marketplace

11. Mevcut Sistemden Geçiş
11.1. Mevcut Durum
Bugün her tenant implicit olarak klasik 3 katman kullanıyor:

Platform (biz)

Tenant

User

Veri: tenants, users, ilişkili tablolar.

11.2. Geçiş Stratejisi
Aşama 1: Backward compat (3-4 hafta)

Yeni tablolar (psf_*) ekle

Mevcut tenant'lar için classic-3-layer yapısı otomatik oluştur

Eski davranış korunur

Aşama 2: Optional adoption (2-3 ay)

Yeni tenant'lar yapı seçebilir

Eski tenant'lar ister klasik kalır, ister yeni yapıya geçer

Feature flag: meta_structure.enabled

Aşama 3: Full adoption (6 ay)

Tüm tenant'lar yeni sistem

Eski (implicit) kaldırılır

Migration job ile geçiş

11.3. Veri Migration
php
class MigrateToMetaStructure implements ShouldQueue {
    public function handle(): void {
        $tenant = Tenant::find($this->tenantId);
        
        // 1. Klasik yapıyı oluştur
        $structure = Structure::create([
            'key' => 'classic-3-layer',
            'tenant_id' => $tenant->id,
            // ...
        ]);
        
        // 2. Mevcut kullanıcıları katmanlara ata
        foreach ($tenant->users as $user) {
            SysUserMembership::create([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'layer' => $user->role === 'admin' ? 'company' : 'user',
                'role' => $user->role,
            ]);
        }
        
        // 3. Tenant'ı yeni yapıya bağla
        $tenant->update([
            'structure_id' => $structure->id,
            'structure_version_id' => $structure->currentVersion->id,
        ]);
    }
}
12. Test Stratejisi
12.1. Structure Validator Testleri
php
public function test_rejects_cyclic_layers(): void {
    $this->expectException(InvalidStructureException::class);
    
    StructureValidator::validate([
        'layers' => [
            ['key' => 'a', 'parent' => 'b'],
            ['key' => 'b', 'parent' => 'a'],
        ],
    ]);
}

public function test_rejects_undefined_role_layer(): void {
    $this->expectException(InvalidStructureException::class);
    
    StructureValidator::validate([
        'layers' => [['key' => 'company']],
        'roles' => [['key' => 'admin', 'layer' => 'nonexistent']],
    ]);
}
12.2. Runtime Testleri
php
public function test_vendor_cannot_see_other_vendor_orders(): void {
    $marketplace = Structure::factory()->marketplace();
    $tenant = Tenant::factory()->withStructure($marketplace)->create();
    
    $vendorA = $tenant->createLayer('vendor', ['name' => 'Vendor A']);
    $vendorB = $tenant->createLayer('vendor', ['name' => 'Vendor B']);
    
    $userA = $tenant->createUserInLayer($vendorA, 'vendor_manager');
    
    // Kullanıcı A, B'nin siparişini göremez
    $this->actingAs($userA);
    $orders = OtcSalesOrder::where('vendor_id', $vendorB->id)->get();
    $this->assertCount(0, $orders);
}
12.3. Migration Testleri
php
public function test_structure_migration_preserves_data(): void {
    $v1 = Structure::factory()->v1()->create();
    $v2 = Structure::factory()->v2()->create(['parent_version_id' => $v1->id]);
    
    $tenant = Tenant::factory()->withStructure($v1)->create();
    $data = $tenant->createTestData();
    
    // Migration çalıştır
    $migration = StructureMigration::create($v1, $v2);
    $migration->execute($tenant);
    
    // Veri korundu mu?
    $this->assertSame($data->count(), $tenant->refresh()->data()->count());
}
12.4. Sandbox Testleri
php
public function test_sandbox_can_test_structure_safely(): void {
    $sandbox = StructureSandbox::create($definition);
    
    // Gerçek veri etkilenmez
    $sandbox->createLayer('vendor', ['name' => 'Test']);
    
    // Sandbox içinde var
    $this->assertCount(1, $sandbox->layers());
    
    // Gerçek DB'de yok
    $this->assertCount(0, SysTenantStructure::where('key', 'vendor')->get());
}
13. Performans
13.1. Cache Stratejisi
text
1. Structure definition → Redis (5 dk TTL)
2. Layer tree → in-memory (request boyunca)
3. Permissions → session (login'de yükle)
4. Data scope → in-memory (request boyunca)
13.2. Index Stratejisi
sql
-- psf_structure
CREATE INDEX idx_psf_structure_key ON psf_structure(key);
CREATE INDEX idx_psf_structure_tenant ON psf_structure(tenant_id);

-- psf_layer
CREATE INDEX idx_psf_layer_structure ON psf_layer(structure_id);
CREATE INDEX idx_psf_layer_parent ON psf_layer(parent_layer_id);

-- sys_user_membership
CREATE INDEX idx_membership_user ON sys_user_membership(user_id);
CREATE INDEX idx_membership_tenant ON sys_user_membership(tenant_id);
CREATE INDEX idx_membership_layer ON sys_user_membership(layer_id);
13.3. Query Optimizasyonu
Data scope uygulanırken JOIN yerine subquery:

php
// ✅ Hızlı
$orders = OtcSalesOrder::whereIn('seller_id', 
    SysUserMembership::where('user_id', $userId)
        ->pluck('layer_entity_id')
)->get();

// ❌ Yavaş
$orders = OtcSalesOrder::whereHas('seller', function($q) use ($userId) {
    $q->whereHas('memberships', function($q2) use ($userId) {
        $q2->where('user_id', $userId);
    });
})->get();
14. Anti-Patterns
Bu projede yasak olan structure hataları:

Hardcoded layer — Her katman tanımlanabilir olmalı

Hardcoded role — Her rol tanımlanabilir olmalı

Sınırsız derinlik (pratikte) — Max 20 katman önerilir (UI için)

Döngüsel parent — Validator reddetmeli

Versioning'siz değişiklik — Yapı değişikliği = yeni versiyon

Migration'sız veri dönüşümü — Her veri değişikliği migrate edilmeli

RLS'siz data scope — İki katman birlikte çalışmalı

Sandbox'sız publish — Yayın öncesi test zorunlu

Breaking change onaysız — Major = tenant onayı

Structure delete (kullanımda) — Sadece deprecate

15. Roadmap
15.1. Aşama 1: Foundation (6-8 hafta)
psf_* tabloları

StructureValidator

StructureLoader

StructureContext

Backward compat (classic-3-layer)

15.2. Aşama 2: Builder (8-10 hafta)
YAML parser

Visual builder UI

Sandbox test

Version management

15.3. Aşama 3: Runtime (6-8 hafta)
Dynamic UI

Dynamic API

Data scope enforcement

Permission system

15.4. Aşama 4: Marketplace (8-10 hafta)
Structure catalog

Install flow

Payment/commission

Rating/review

15.5. Aşama 5: AI (4-6 hafta)
AI-assisted builder

Natural language → structure

Auto-validation

Toplam: 32-42 hafta (8-10 ay).

16. Glossary
Terim	Anlam
Platform Structure	Yapı tanımı (YAML)
Layer	Katman (organizasyon seviyesi)
Role	Rol (katmandaki kullanıcı tipi)
Data Scope	Veri görünürlüğü kuralı
Structure Builder	Yapı tanımlama aracı
Sandbox	Test ortamı
Version	Yapı sürümü
Migration	Eski → yeni veri göçü
Template	Hazır yapı şablonu
Marketplace	Yapı satış platformu
Visual Builder	Görsel tanımlama
AI-Assisted	AI yardımlı tanımlama
17. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — meta-structure framework tasarımı