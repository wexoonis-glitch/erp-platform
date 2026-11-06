# 06 — Layer & Role System

**Project:** Kurumsal ERP Platform
**Belge:** Katman Sistemi, Rol Sistemi, İzin Modeli, Dinamik Organizasyon
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md

---

## 1. Bu Belgenin Amacı

Bu belge, **katman (layer)** ve **rol (role)** sistemlerini derinlemesine tanımlar:

- Katman hiyerarşisi — ağaç, graf, iç içe, matris
- Rol modeli — basit, miras, bileşik, meta
- İzin (permission) modeli — hiyerarşik, koşullu, scope'lu
- Data scope — katman bazlı veri görünürlüğü
- Kullanıcı-rol-katman ilişkisi (membership)
- Restoran örneği — gerçek dünya uygulaması
- Runtime enforcement
- Test stratejisi

**Hedef kitle:** Geliştiriciler, AI asistanlar, ürün mimarları, güvenlik denetçileri.

---

## 2. Katman (Layer) Sistemi — Derinlemesine

### 2.1. Katman Nedir? (Tekrar)

Bir **katman**, organizasyonun **bir seviyesidir**. Her katman:

- Kendi **entity tipi** (şirket, departman, masa, kullanıcı)
- Kendi **rol seti** (kim bu katmanda)
- Kendi **veri görünürlüğü** (ne görür)
- Kendi **izin seti** (ne yapabilir)
- Kendi **UI** (hangi ekranları görür)

### 2.2. Katman Yapısal Tipleri

Her katmanın **yapısal tipi** vardır:

| Tip | Açıklama | Örnek |
|---|---|---|
| **Root** | Kök katman (parent yok) | Platform, Holding |
| **Branch** | Alt katman (birden fazla) | Şirket, Departman |
| **Leaf** | Son katman (alt yok) | Kullanıcı, Masa |
| **Composite** | Alt + kendi entity'si | Bölge (hem kendi hem alt) |
| **Virtual** | Geçici/sanal | Proje, Etkinlik |
| **External** | Dış taraf | Tedarikçi, Müşteri |

### 2.3. Katman Hiyerarşi Modelleri

#### 2.3.1. Ağaç (Tree) — Klasik
Company
├── Department A
│ ├── Team A1
│ │ └── User 1
│ └── Team A2
│ └── User 2
└── Department B
└── Team B1
└── User 3

text

**Kural:** Her katmanın **tek parent**'ı var. En yaygın model.

**Kullanım:** Klasik ERP, franchise, holding.

#### 2.3.2. Graf (Graph) — Çapraz İlişki
Vendor A ───┬─→ Seller 1 ───→ Customer X
│ ↑
│ │
└─→ Seller 2 ───→ Customer X
↑
│
Vendor B ───┬─→ Seller 3 ───→ Customer X
│
└─→ Seller 4

text

**Kural:** Bir katman **birden fazla parent**'a bağlı olabilir (DAG — Directed Acyclic Graph).

**Kullanım:** Marketplace, tedarik zinciri, matrix org.

**Kritik:** Döngü **yasak** (`A → B → A`).

#### 2.3.3. İç İçe (Nested) — Katman İçinde Yapı
Company
└── Department
├── Team
│ └── User
└── [Nested] Project X
├── Task
│ └── Subtask
└── Member (User)

text

**Kural:** Bir katman **kendi içinde** başka bir yapı barındırabilir (alt-proje).

**Kullanım:** Proje yönetimi, karma organizasyon.

#### 2.3.4. Matris (Matrix) — Çok Boyutlu
Kullanıcı A:
Department X (organizational)

Project Y (virtual)

Region Z (geographic)

text

**Kural:** Bir kullanıcı **birden fazla boyutta** katmana ait olabilir.

**Kullanım:** Danışmanlık, matris org.

### 2.4. Katman Meta-Verisi

```yaml
layer:
  # Zorunlu
  key: "vendor"                 # benzersiz
  name: "Satıcı"                # görünen
  parent: "platform_owner"      # üst katman
  cardinality: "many"           # alt katman sayısı
  
  # Opsiyonel
  type: "organizational"        # root | branch | leaf | composite | virtual | external
  structure_type: "tree"        # tree | graph | nested | matrix
  
  # Davranışlar
  behaviors:
    create_children: true       # alt katman yaratabilir
    delete_children: false      # alt katmanı silemez
    inherit_data: "selective"   # veri mirası (full | selective | none)
    report_to_parent: true      # üst katmana raporlar
    visible_to_public: false    # halka açık mı
    requires_approval: true     # oluşturma onay gerektirir
    
  # İlişkiler
  relations:
    - type: "belongs_to"
      target: "platform_owner"
      cascade: true
    - type: "has_many"
      target: "seller"
      cascade: false
    - type: "references"
      target: "logistics_partner"
      optional: true
  
  # Metadata
  metadata:
    icon: "store"
    color: "#4f46e5"
    order: 20
2.5. Katman Örnekleri
Örnek A: Klasik Şirket
yaml
layers:
  - key: "company"
    type: "root"
    structure_type: "tree"
    
  - key: "department"
    parent: "company"
    type: "branch"
    
  - key: "user"
    parent: "department"
    type: "leaf"
Örnek B: Marketplace
yaml
layers:
  - key: "platform_owner"
    type: "root"
    structure_type: "graph"
    
  - key: "vendor"
    parent: "platform_owner"
    type: "branch"
    cardinality: "many"
    
  - key: "seller"
    parent: "vendor"
    type: "branch"
    cardinality: "many"
    
  - key: "customer"
    parent: "seller"
    type: "leaf"
    
  - key: "logistics_partner"
    parent: "platform_owner"
    type: "external"
    
  - key: "payment_provider"
    parent: "platform_owner"
    type: "external"
Örnek C: Restoran Zinciri (Kullanıcının Sorusu)
yaml
structure:
  key: "restaurant-chain"
  name: "Restoran Zinciri"
  version: "1.0.0"
  
  layers:
    # Organizasyonel katmanlar
    - key: "brand"                    # Marka (KFC, McDonald's)
      type: "root"
      structure_type: "tree"
      
    - key: "branch"                   # Şube (her lokasyon)
      parent: "brand"
      type: "branch"
      cardinality: "many"
      
    - key: "staff"                    # Personel
      parent: "branch"
      type: "leaf"
      cardinality: "many"
    
    # Fiziksel katmanlar (restoran özel)
    - key: "floor"                    # Kat (zemin, 1. kat)
      parent: "branch"
      type: "branch"
      cardinality: "many"
      
    - key: "zone"                     # Bölge (sigara, sigarasız, teras, bar)
      parent: "floor"
      type: "branch"
      cardinality: "many"
      
    - key: "table"                    # Masa
      parent: "zone"
      type: "branch"
      cardinality: "many"
      metadata:
        columns:
          - key: "table_number"
            type: "string"
            required: true
          - key: "capacity"
            type: "integer"
            required: true
          - key: "shape"              # round, square, rectangle
            type: "enum"
          - key: "position_x"         # görsel pozisyon
            type: "decimal"
          - key: "position_y"
            type: "decimal"
          - key: "rotation"           # derece
            type: "integer"
          - key: "status"             # free, occupied, reserved, cleaning
            type: "enum"
      
    - key: "seat"                     # Oturma yeri
      parent: "table"
      type: "leaf"
      cardinality: "many"
      metadata:
        columns:
          - key: "seat_number"
            type: "string"
            required: true
          - key: "position"           # kuzey, güney, doğu, batı
            type: "enum"
  
  # Restoran özel roller
  roles:
    - key: "brand_owner"
      layer: "brand"
      permissions: ["*"]
    
    - key: "branch_manager"
      layer: "branch"
      permissions: ["branch.*", "staff.*", "floor.*", "table.*", "order.*"]
    
    - key: "waiter"
      layer: "staff"
      permissions: ["order.own", "table.assign.own"]
    
    - key: "chef"
      layer: "staff"
      permissions: ["kitchen.order.read", "kitchen.order.update"]
    
    - key: "cashier"
      layer: "staff"
      permissions: ["sale.create", "sale.void.own", "payment.*"]
    
    - key: "customer"
      layer: "seat"
      permissions: ["menu.read", "order.own", "feedback.own"]
Cevap: Yukarıdaki tanım gereği — Evet, masa, oturma yeri, pozisyon, kat, bölge hepsi kaydedilebilir. Bu bir katman + metadata meselesi.

3. Rol (Role) Sistemi — Derinlemesine
3.1. Rol Nedir? (Tekrar)
Bir rol, bir katmandaki kullanıcı tipidir. Rol belirli bir katmana bağlıdır ve izin setini taşır.

3.2. Rol Hiyerarşisi
3.2.1. Basit Rol
yaml
role:
  key: "waiter"
  layer: "staff"
  permissions:
    - "order.create"
    - "order.read.own"
3.2.2. Miras Alan Rol
yaml
role:
  key: "senior_waiter"
  layer: "staff"
  inherits: ["waiter"]
  permissions:
    - "order.void.own"              # ek izin
    - "discount.apply.own"          # ek izin
Kural: Miras alınan izinler + yeni izinler = toplam.

3.2.3. Bileşik Rol
yaml
role:
  key: "shift_supervisor"
  layer: "staff"
  composed_of:
    - "senior_waiter"
    - "cashier"
    - "kitchen_staff"
  # Tüm izinlerin birleşimi
Kural: Bileşik rol = tüm alt rollerin izin birleşimi.

3.2.4. Meta-Rol
yaml
role:
  key: "structure_builder"
  layer: "platform_owner"
  can_define:
    - layers
    - roles
    - workflows
    - structures
    - features
  permissions:
    - "platform.structure.*"
    - "platform.role.*"
Kural: Meta-rol, başka roller tanımlayabilir.

3.2.5. Koşullu Rol
yaml
role:
  key: "senior_approver"
  layer: "branch"
  permissions:
    - "otc.sales_order.approve"
  conditions:
    - "sales_order.amount < 100000"
    - "sales_order.customer.risk_score < 5"
    - "now().hour >= 8 && now().hour < 18"     # iş saati
Runtime: Kullanıcı sadece koşullar sağlandığında izin kullanır.

3.3. Rol Katman İlişkisi
Bir kullanıcı birden fazla katmanda rol alabilir:

yaml
memberships:
  - user: "ali"
    tenant: "tesfirma"
    layer: "staff"
    role: "waiter"
    scope: "branch-1"              # sadece şube 1
    
  - user: "ali"
    tenant: "tesfirma"
    layer: "staff"
    role: "shift_supervisor"
    scope: "branch-2"              # şube 2'de supervisor
Kural: Bir kullanıcı bir tenant'ta birden fazla üyelik alabilir (farklı katman/rol/scope).

3.4. Rol İzin Modeli
3.4.1. İzin Formatı
text
<module>.<entity>.<action>[.<scope>]
Kısım	Açıklama	Örnek
module	Modül kodu	fin, otc, inv, *
entity	Varlık	customer, order, *
action	İşlem	read, create, update, delete, approve, *
scope	Kapsam	own, department, tenant, *
3.4.2. İzin Örnekleri
İzin	Anlam
fin.customer.read	Tüm müşterileri oku
otc.order.create	Sipariş oluştur
otc.order.update.own	Sadece kendi siparişini güncelle
otc.order.delete.*	Herkesin siparişini sil (yönetici)
*.*.*	Her şey (superadmin)
3.4.3. Wildcard Kuralları
Pattern	Eşleşir
fin.*	fin.customer.read, fin.invoice.create, ...
*.customer.read	fin.customer.read, crm.customer.read, ...
fin.customer.*	fin.customer.read, fin.customer.create, ...
*.*.*	Her şey
3.5. Koşullu İzin (Conditional Permission)
3.5.1. Koşul Türleri
Tip	Örnek
Karşılaştırma	amount < 100000
Zaman	now.hour >= 9 && now.hour < 18
Kullanıcı	user.role == 'manager'
Entity	entity.status == 'draft'
İlişki	order.customer.id == user.customer_id
Kombinasyon	(a || b) && c
3.5.2. Koşul Tanımı
yaml
role:
  key: "discount_approver"
  layer: "branch"
  permissions:
    - "otc.order.discount.approve"
  conditions:
    - when: "order.discount_percent <= 30"
      allow: true
    - when: "order.discount_percent > 30"
      allow: false
    - when: "user.role == 'branch_manager'"
      allow: true
      override: true
3.6. İzin Çözümleme Algoritması
text
1. Kullanıcının tüm rollerini al (membership)
2. Her rolün izin setini al
3. Miras ve bileşim varsa recursive çöz
4. Birleşim (union) al
5. Koşulları değerlendir
6. Sonuç: izin seti
php
class PermissionResolver {
    public function resolve(User $user, string $permission): bool {
        // 1. Tüm roller
        $roles = $user->memberships()
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->get()
            ->map(fn($m) => $m->role);
        
        // 2. Her rol için kontrol
        foreach ($roles as $role) {
            if ($this->roleAllows($role, $permission)) return true;
        }
        
        return false;
    }
    
    private function roleAllows(Role $role, string $permission): bool {
        // a) Direct match
        if ($this->matches($role->permissions, $permission)) {
            if ($this->conditionsMet($role, $permission)) return true;
        }
        
        // b) Inherited
        foreach ($role->inherits as $parentRole) {
            if ($this->roleAllows($parentRole, $permission)) return true;
        }
        
        // c) Composed
        foreach ($role->composed_of as $componentRole) {
            if ($this->roleAllows($componentRole, $permission)) return true;
        }
        
        return false;
    }
}
4. Data Scope — Katman Bazlı Veri Görünürlüğü
4.1. Tanım
Data Scope = kullanıcının hangi katmanlardaki hangi veriyi görebileceği. Bu, RLS (tenant) ve permission (rol) üzerine üçüncü katman.

4.2. Scope Seviyeleri
Seviye	Anlam
own	Sadece kendi oluşturduğu
assigned	Kullanıcıya atanan
layer	Aynı katman
children	Alt katmanlar
parent	Üst katmanlar
tenant	Tüm tenant
global	Tüm tenant'lar (superadmin)
4.3. Data Scope Tanımı
yaml
data_scope:
  - layer: "vendor"
    entity: "otc.sales_order"
    rules:
      - when: "order.vendor_id == user.vendor_id"
        access: "full"              # gör + değiştir
      - when: "order.status == 'draft'"
        access: "read"
      - default: "deny"
  
  - layer: "seller"
    entity: "otc.sales_order"
    rules:
      - when: "order.seller_id == user.seller_id"
        access: "full"
      - when: "order.parent_order_id IN user.own_orders"
        access: "read"
  
  - layer: "customer"
    entity: "otc.sales_order"
    rules:
      - when: "order.customer_id == user.id"
        access: "read"
4.4. Data Scope Runtime
php
class DataScopeEnforcer {
    public function apply(QueryBuilder $query, string $entity, User $user): QueryBuilder {
        $context = app(StructureContext::class);
        $scope = $context->dataScope($entity);
        
        if (!$scope) return $query; // kısıtlama yok
        
        return $query->where(function ($q) use ($scope, $user, $context) {
            foreach ($scope->rules as $rule) {
                $q->orWhere(function ($subQ) use ($rule, $user, $context) {
                    $this->applyRule($subQ, $rule, $user, $context);
                });
            }
        });
    }
}
4.5. Scope Çözümleme Sırası
Kullanıcı veri gördüğünde 3 katman birlikte çalışır:

text
1. RLS (DB)          → tenant_id = current_tenant
    AND
2. Permission (app)  → user.has('otc.order.read')
    AND
3. Data Scope (app)  → order.seller_id = user.seller_id
Kural: Bir katman deny derse, diğerleri allow dese bile → deny.

Örnek:

Katman	Sonuç
RLS: tenant A ✅	Geçer
Permission: otc.order.read ✅	Geçer
Data Scope: seller_id = X ✅	Geçer
Toplam	Görür
Katman	Sonuç
RLS: tenant A ✅	Geçer
Permission: otc.order.read ✅	Geçer
Data Scope: seller_id = Y (başka satıcı) ❌	Reddeder
Toplam	Göremez
5. Membership — Kullanıcı-Rol-Katman İlişkisi
5.1. Neden Membership Tablosu?
Mevcut sistemde users.tenant_id + users.role var. Ama meta-platform'da kullanıcı birden fazla katmana/role/tenant'a ait olabilir.

5.2. Membership Tablosu
sql
CREATE TABLE sys_user_membership (
    id              uuid PRIMARY KEY,
    user_id         bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tenant_id       uuid NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    layer_id        uuid NOT NULL REFERENCES psf_layer_instance(id),
    role_id         uuid NOT NULL REFERENCES psf_role(id),
    scope           varchar(50) DEFAULT 'full',        -- full | branch | department
    is_primary      boolean DEFAULT false,
    status          varchar(20) DEFAULT 'active',      -- active | suspended | revoked
    valid_from      timestamptz DEFAULT now(),
    valid_until     timestamptz NULL,
    metadata        jsonb,
    created_at      timestamptz,
    updated_at      timestamptz,
    UNIQUE(user_id, tenant_id, layer_id, role_id)
);

CREATE INDEX idx_membership_user ON sys_user_membership(user_id);
CREATE INDEX idx_membership_tenant ON sys_user_membership(tenant_id);
CREATE INDEX idx_membership_layer ON sys_user_membership(layer_id);
CREATE INDEX idx_membership_role ON sys_user_membership(role_id);
5.3. Katman Instance
Not: psf_layer tanım (template), psf_layer_instance gerçek örnek (runtime).

sql
CREATE TABLE psf_layer_instance (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NOT NULL REFERENCES tenants(id),
    structure_id    uuid NOT NULL REFERENCES psf_structure(id),
    layer_key       varchar(50) NOT NULL,               -- "vendor", "seller", ...
    parent_id       uuid NULL REFERENCES psf_layer_instance(id),
    code            varchar(50),                        -- "V-001", "S-042"
    name            varchar(255),
    path            text,                               -- "/vendor-1/seller-3/"
    depth           int DEFAULT 0,
    data            jsonb DEFAULT '{}',                 -- metadata (adres, telefon, ...)
    status          varchar(20) DEFAULT 'active',
    created_at      timestamptz,
    updated_at      timestamptz
);

CREATE INDEX idx_layer_instance_tenant ON psf_layer_instance(tenant_id);
CREATE INDEX idx_layer_instance_parent ON psf_layer_instance(parent_id);
CREATE INDEX idx_layer_instance_path ON psf_layer_instance(path);  -- prefix query
Örnek:

sql
-- Vendor A instance
INSERT INTO psf_layer_instance (id, tenant_id, layer_key, parent_id, code, name, path)
VALUES (uuid('v1'), tenant, 'vendor', platform_root_id, 'V-001', 'Vendor A', '/v1/');

-- Seller 1 (Vendor A'ya bağlı)
INSERT INTO psf_layer_instance (id, tenant_id, layer_key, parent_id, code, name, path)
VALUES (uuid('s1'), tenant, 'seller', uuid('v1'), 'S-001', 'Seller 1', '/v1/s1/');

-- Seller 2 (Vendor A'ya bağlı)
INSERT INTO psf_layer_instance (id, tenant_id, layer_key, parent_id, code, name, path)
VALUES (uuid('s2'), tenant, 'seller', uuid('v1'), 'S-002', 'Seller 2', '/v1/s2/');
5.4. Kullanıcı Erişim Sorgusu
"Kullanıcı Ali hangi tenant'lara/kurumlara erişebilir?"

php
$access = SysUserMembership::where('user_id', $userId)
    ->where('status', 'active')
    ->where('valid_from', '<=', now())
    ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
    ->with(['tenant', 'layer', 'role'])
    ->get();
5.5. Membership Yönetimi
Tenant admin:

Kullanıcı davet et (email)

Rol/katman seç

Scope belirle

Süre belirle (opsiyonel)

Developer:

Tenant'a geçici üye ol (süreli)

Sadece kendi plugin'inin olduğu katmana

Superadmin:

Herhangi bir tenant'a herhangi bir rol

Audit zorunlu

6. Restoran Örneği — Tam Uygulama
Kullanıcının sorusu: "Restoran için platform veya plugin hazırlandı, masa, oturma yeri, KFC/McDonald's tarzı sale point + kitchen ekranı — mümkün mü?"

Cevap: EVET. Aşağıda adım adım:

6.1. Structure Tanımı
yaml
structure:
  key: "restaurant-chain"
  name: "Restoran Zinciri"
  version: "1.0.0"
  
  layers:
    - key: "brand"
      type: "root"
      cardinality: "one"
      metadata:
        entity: "restaurant_brand"
        columns:
          - { key: "name", type: "string" }
          - { key: "logo_url", type: "string" }
          - { key: "franchise_fee", type: "decimal" }
    
    - key: "branch"
      parent: "brand"
      cardinality: "many"
      metadata:
        entity: "restaurant_branch"
        columns:
          - { key: "name", type: "string" }
          - { key: "address", type: "text" }
          - { key: "phone", type: "string" }
          - { key: "open_hours", type: "jsonb" }
          - { key: "manager_id", type: "user_ref" }
    
    - key: "floor"
      parent: "branch"
      cardinality: "many"
      metadata:
        entity: "restaurant_floor"
        columns:
          - { key: "name", type: "string" }        # "Zemin", "1. Kat", "Teras"
          - { key: "level", type: "integer" }
          - { key: "layout_image", type: "string" }
    
    - key: "zone"
      parent: "floor"
      cardinality: "many"
      metadata:
        entity: "restaurant_zone"
        columns:
          - { key: "name", type: "string" }        # "Sigara", "Sigarasız", "Bar", "Teras"
          - { key: "smoking", type: "boolean" }
          - { key: "capacity", type: "integer" }
    
    - key: "table"
      parent: "zone"
      cardinality: "many"
      metadata:
        entity: "restaurant_table"
        columns:
          - { key: "table_number", type: "string", required: true }
          - { key: "capacity", type: "integer" }
          - { key: "shape", type: "enum", options: ["round","square","rectangle"] }
          - { key: "position_x", type: "decimal" }     # canvas x
          - { key: "position_y", type: "decimal" }     # canvas y
          - { key: "width", type: "decimal" }
          - { key: "height", type: "decimal" }
          - { key: "rotation", type: "integer" }
          - { key: "status", type: "enum", options: ["free","occupied","reserved","cleaning"], default: "free" }
          - { key: "current_order_id", type: "uuid", nullable: true }
    
    - key: "seat"
      parent: "table"
      cardinality: "many"
      metadata:
        entity: "restaurant_seat"
        columns:
          - { key: "seat_number", type: "string", required: true }
          - { key: "position", type: "enum", options: ["north","south","east","west"] }
          - { key: "x_offset", type: "decimal" }       # masa merkezine göre
          - { key: "y_offset", type: "decimal" }
    
    - key: "staff"
      parent: "branch"
      cardinality: "many"
      metadata:
        entity: "restaurant_staff"
        columns:
          - { key: "user_id", type: "user_ref" }
          - { key: "position", type: "enum", options: ["waiter","chef","cashier","manager"] }
          - { key: "shift", type: "jsonb" }
6.2. Sale Point Ekranı
Nasıl çalışır:

Kasiyer giriş yapar → kendi branch'ini görür

Table Map ekranı: tüm masalar görsel olarak

Masa tıkla → o masanın siparişleri

Sipariş ekle → mutfak ekranına gider

Uygulama:

Custom Entity: restaurant_table (layer metadata)

Plugin: Restaurant POS (sale point ekranı)

Plugin: Table Management (görsel masa haritası)

UI Builder: Masa haritası layout

6.3. Kitchen Display System (KDS)
Nasıl çalışır:

Mutfak personeli giriş yapar → KDS ekranı

Yeni siparişler otomatik düşer (event)

Sipariş hazır → "Hazır" tıkla → garson ekranına gider

Uygulama:

Plugin: Kitchen Display

Event: order.placed → KDS dinler

Event: order.ready → garson dinler

UI: Özel layout (kanban style)

6.4. Masa → Sipariş Akışı
text
1. Müşteri masaya oturur
2. Garson (staff, role: waiter) → masayı açar
3. Sipariş alır → Table Order plugin → order.create
4. Event: order.placed → Kitchen Display
5. Mutfak hazırlar → order.ready → Garson
6. Garson servis eder → order.served
7. Müşteri ödeme yapar → cashier → payment.received
8. Event: payment.received → table.free
9. Masa hazır (status = cleaning, sonra free)
Tamamı plugin + event + custom entity ile.

6.5. KFC/McDonald's Tarzı — Counter Service
Fark: Masa yok, sipariş sayacı var.

Yapı:

yaml
layers:
  - key: "counter"              # Sipariş sayacı
    parent: "branch"
    cardinality: "many"
    metadata:
      columns:
        - { key: "counter_number", type: "string" }
        - { key: "type", type: "enum", options: ["cashier","self_service","drive_thru"] }
  
  - key: "order_queue"          # Sipariş kuyruğu (hazırlık için)
    parent: "branch"
    metadata:
      columns:
        - { key: "queue_number", type: "string" }      # "A-042"
        - { key: "status", type: "enum", options: ["waiting","preparing","ready"] }
Akış:

text
1. Müşteri self-service kiosk → sipariş
2. Ödeme → order.placed + queue.created
3. KDS → hazırla → order.ready
4. Sipariş ekranı → "A-042 hazır" göster
5. Müşteri teslim alır
6.6. Cevaplar — Net
Soru	Cevap	Neden
Restoranın mevcut yapısı kaydedilebilir mi?	✅ Evet	Layer + metadata
Masaların yerleri kaydedilebilir mi?	✅ Evet	position_x/y metadata
Masaların numaraları kaydedilebilir mi?	✅ Evet	table_number metadata
Oturma yerlerinin numaraları kaydedilebilir mi?	✅ Evet	seat layer
Masadan gelen sipariş satış ekranına düşer mi?	✅ Evet	Event + plugin
KFC/McDonald's gibi sale point ekranı?	✅ Evet	Custom plugin + UI
KFC/McDonald's gibi kitchen ekranı?	✅ Evet	Custom plugin + event
Hepsi bu framework ile yapılabilir.

7. Runtime Enforcement
7.1. Request Akışı — Detaylı
text
Request gelir
    ↓
1. Middleware: web.auth            (kim?)
    ↓
2. Middleware: tenant.context      (hangi tenant?)
    ↓
3. Middleware: structure.context   (hangi yapı?)
    ↓
4. Middleware: membership.resolve  (hangi katman + rol?)
    ↓
5. Middleware: permission.check    (izin var mı?)
    ↓
6. Controller / Livewire
    ↓
7. Service katmanı                 (iş mantığı)
    ↓
8. Query (RLS + data scope)
    ↓
9. Response
7.2. StructureContext
php
class StructureContext {
    public function __construct(
        public readonly Structure $structure,
        public readonly LayerInstance $layer,
        public readonly Role $role,
        public readonly PermissionSet $permissions,
        public readonly DataScope $dataScope,
    ) {}
    
    public function can(string $permission): bool {
        return $this->permissions->has($permission);
    }
    
    public function canIn(string $permission, string $scope): bool {
        return $this->permissions->hasWithScope($permission, $scope);
    }
    
    public function dataScopeFor(string $entity): ?ScopeRule {
        return $this->dataScope->for($entity);
    }
}
7.3. PermissionCheck Middleware
php
class CheckPermission {
    public function handle($request, Closure $next, string $permission) {
        $context = app(StructureContext::class);
        
        if (!$context->can($permission)) {
            abort(403, "Yetkisiz: {$permission}");
        }
        
        return $next($request);
    }
}
7.4. Data Scope Uygulama
php
// Global scope (Eloquent)
class ScopedByDataScope implements Scope {
    public function apply(Builder $builder, Model $model): void {
        $context = app(StructureContext::class);
        if (!$context) return;
        
        $scope = $context->dataScopeFor($model->getTable());
        if (!$scope) return;
        
        $builder->where(function ($q) use ($scope, $context) {
            $scope->applyTo($q, $context);
        });
    }
}

// Model
class OtcSalesOrder extends Model {
    protected static function booted(): void {
        static::addGlobalScope(new ScopedByDataScope());
    }
}
Kural: Global scope, her sorguya otomatik uygulanır. Uygulama unutamaz.

8. Membership Yönetimi — Pratik
8.1. Yeni Kullanıcı Ekleme
Tenant admin:

/settings/memberships → "Kullanıcı Davet Et"

Email + katman seç + rol seç

Davet gönderilir

Kullanıcı kabul eder → membership oluşur

8.2. Toplu Kullanıcı Ekleme
CSV import:

text
email,layer_code,role_code,scope
ali@tesfirma.com,branch-1,waiter,full
ayse@tesfirma.com,branch-1,cashier,full
mehmet@tesfirma.com,branch-2,branch_manager,full
8.3. Developer Erişimi
Tenant admin → Developer davet:

Developer email + süre (30 gün)

Sadece developer rolü (kısıtlı)

Developer sadece kendi plugin kapsamında erişim

Süre dolunca otomatik revoke

8.4. Membership İptali
php
SysUserMembership::where('user_id', $userId)
    ->where('tenant_id', $tenantId)
    ->update(['status' => 'revoked', 'valid_until' => now()]);
Etki: Sonraki request'te kullanıcı erişim kaybeder.

9. Test Stratejisi
9.1. Layer Testleri
php
public function test_layer_can_have_multiple_children(): void {
    $structure = Structure::factory()->marketplace()->create();
    $vendor = LayerInstance::create(['structure_id' => $structure->id, 'layer_key' => 'vendor', 'name' => 'V1']);
    
    $seller1 = LayerInstance::create(['parent_id' => $vendor->id, 'layer_key' => 'seller', 'name' => 'S1']);
    $seller2 = LayerInstance::create(['parent_id' => $vendor->id, 'layer_key' => 'seller', 'name' => 'S2']);
    
    $this->assertCount(2, $vendor->children);
}
9.2. Role Testleri
php
public function test_role_inheritance_works(): void {
    $role = Role::factory()
        ->withInheritance(['base_role'])
        ->withPermissions(['extra.permission'])
        ->create();
    
    $context = new StructureContext(...);
    $this->assertTrue($context->can('base.permission'));
    $this->assertTrue($context->can('extra.permission'));
}
9.3. Data Scope Testleri
php
public function test_vendor_cannot_see_other_vendor_data(): void {
    $structure = Structure::factory()->marketplace()->create();
    $vendorA = LayerInstance::factory()->for($structure)->vendor('A')->create();
    $vendorB = LayerInstance::factory()->for($structure)->vendor('B')->create();
    
    $orderA = OtcSalesOrder::factory()->for($vendorA)->create();
    $orderB = OtcSalesOrder::factory()->for($vendorB)->create();
    
    // Kullanıcı A
    $userA = User::factory()->create();
    SysUserMembership::factory()
        ->for($userA, $vendorA, 'vendor_manager')
        ->create();
    
    $this->actingAs($userA);
    
    // A kendi siparişini görür, B'nin siparişini görmez
    $context = app(StructureContext::class)->forUser($userA);
    app()->instance(StructureContext::class, $context);
    
    $orders = OtcSalesOrder::all();
    $this->assertCount(1, $orders);
    $this->assertEquals($orderA->id, $orders->first()->id);
}
9.4. Restoran Testleri
php
public function test_table_order_flows_to_kitchen(): void {
    // Restoran yapısı
    $structure = Structure::factory()->restaurant()->create();
    $branch = LayerInstance::factory()->for($structure)->layer('branch')->create();
    $table = LayerInstance::factory()->for($branch)->layer('table')->create(['table_number' => 'T-01']);
    
    // Waiter
    $waiter = User::factory()->create();
    SysUserMembership::factory()->for($waiter, $branch, 'waiter')->create();
    
    // Sipariş oluştur
    $this->actingAs($waiter);
    $order = app(OrderService::class)->createForTable($table, [
        ['product_id' => $product->id, 'qty' => 2],
    ]);
    
    // Event yayınlandı mı?
    Event::assertDispatched(OrderPlaced::class);
    
    // Kitchen Display dinledi mi?
    $this->assertDatabaseHas('kds_tickets', ['order_id' => $order->id]);
}
10. Performans
10.1. Cache
Veri	Cache	TTL
Structure definition	Redis	5 dk
Layer tree	Redis	5 dk
User memberships	Redis	1 dk
Role permissions	Redis	5 dk
Data scope rules	In-memory	Request
10.2. Query Optimizasyonu
Membership resolve:

php
// ✅ Tek sorgu
$user->load('memberships.role', 'memberships.layer');
Layer tree:

php
// ✅ Materialized path + LIKE
$children = LayerInstance::where('path', 'LIKE', "{$parent->path}%")->get();
10.3. Index Öncelikleri
sql
-- Membership
CREATE INDEX idx_membership_user_active ON sys_user_membership(user_id, status) WHERE status = 'active';
CREATE INDEX idx_membership_expiry ON sys_user_membership(valid_until) WHERE valid_until IS NOT NULL;

-- Layer instance
CREATE INDEX idx_layer_path ON psf_layer_instance(path text_pattern_ops);
CREATE INDEX idx_layer_tenant_key ON psf_layer_instance(tenant_id, layer_key);
11. Anti-Patterns
Bu projede yasak olan katman/rol hataları:

Hardcoded role — Her rol tanımlanabilir olmalı

Hardcoded permission — Her izin tanımlanabilir olmalı

Sabit katman sayısı — Sınırsız olmalı (pratikte 20 önerilir UI için)

Döngüsel layer — Validator reddeder

Cycle in role inheritance — A → B → A yasak

Data scope atlanması — Global scope zorunlu

Membership süresiz — Opsiyonel ama default 1 yıl önerilir

Permission cached too long — 5 dk max

Superadmin her şey — Bypass audit'lenmeli

Delete role (kullanımda) — Sadece deprecate

12. Roadmap
Aşama	Süre	İçerik
1. Foundation	4 hafta	psf_layer, psf_role, sys_user_membership tabloları
2. Runtime	4 hafta	StructureContext, permission resolver, data scope enforcer
3. Membership	3 hafta	UI, invitation, bulk import
4. Restoran Pilot	4 hafta	Restaurant structure template + 2 plugin (POS, KDS)
5. Diğer Sektörler	Sürekli	Franchise, marketplace, holding, clinic...
Toplam: 15 hafta temel + sürekli genişleme.

13. Glossary
Terim	Anlam
Layer	Organizasyon seviyesi
Layer Instance	Gerçek katman kaydı (runtime)
Role	Katmandaki kullanıcı tipi
Permission	İzin (module.entity.action.scope)
Data Scope	Katman bazlı veri görünürlüğü
Membership	Kullanıcı ↔ katman ↔ rol üyeliği
StructureContext	Runtime aktif yapı
PermissionResolver	İzin çözümleyici
Conditional Permission	Koşullu izin
Meta-Role	Başka roller tanımlayan rol
14. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — layer, role, membership, restoran örneği