# 07 — Custom Entity Framework

**Project:** Kurumsal ERP Platform
**Belge:** Kullanıcı Tanımlı Varlıklar, Dinamik Tablo Sistemi, Metadata-Driven Data Model
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md

---

## 1. Bu Belgenin Amacı

Bu belge, kullanıcının **kod yazmadan yeni varlık (tablo)** tanımlayabilmesini sağlayan **Custom Entity Framework**'ü tanımlar:

- Custom Entity nedir, neden gerekli
- Entity tanımlama dili (YAML)
- Alan (field) tipleri
- İlişki (relation) modeli
- Index ve performans
- Validation kuralları
- Form/liste/API otomatik üretimi
- Mevcut Custom Fields ile farkı
- Runtime ve storage stratejisi
- Migration ve şema evrimi

**Hedef kitle:** Geliştiriciler, AI asistanlar, ürün mimarları, tenant admin'ler.

**Kritik:** Bu belge, kullanıcının platformu **kendi ihtiyacına göre şekillendirmesini** sağlayan temel yapı taşıdır.

---

## 2. Problem — Neden Custom Entity?

### 2.1. Gerçek Dünya Sorunu

Her sektörün **kendine özgü varlıkları** vardır:

| Sektör | Özel Varlık |
|---|---|
| **Restoran** | Masa, oturma yeri, menü kategorisi, reçete, porsiyon |
| **Klinik** | Hasta, randevu, reçete, tetkik, muayene |
| **İnşaat** | Şantiye, hakediş, metraj, keşif, imalat kalemi |
| **Tekstil** | Beden, renk, kumaş, model, koleksiyon |
| **Eğitim** | Öğrenci, ders, sınav, not, devamsızlık |
| **Otomotiv** | Araç, plaka, model, servis kaydı, parça |
| **Emlak** | Portföy, mülk, tapu, kira sözleşmesi |
| **Kafe** | Masa, sipariş, ürün varyantı, ekstra |

**Klasik ERP:** Bu varlıklar **yok** → özel geliştirme (6 ay, 100K TL).

**Bizim çözüm:** Kullanıcı **kendi tanımlar.**

### 2.2. Mevcut Custom Fields Yetersiz

Platformda zaten **`sys_custom_field`** var, ama:

| Özellik | Custom Fields | Custom Entity |
|---|---|---|
| **Mevcut varlığa alan** | ✅ | ✅ |
| **Yeni varlık (tablo)** | ❌ | ✅ |
| **İlişki (foreign key)** | ❌ | ✅ |
| **Kendi listesi** | ❌ | ✅ |
| **Kendi formu** | ❌ | ✅ |
| **Kendi API'si** | ❌ | ✅ |
| **Ayrı tablo** | ❌ | ✅ |
| **Index'lenebilir** | ⚠️ Sınırlı | ✅ |

**Fark:** Custom Field → **alan** ekler. Custom Entity → **varlık** (tablo) ekler.

### 2.3. Custom Entity vs Module

| Kavram | Kim | Ne | Nerede |
|---|---|---|---|
| **Module** | Biz | Kod paketi | Her tenant |
| **Plugin** | Developer | Kod paketi | Seçili tenant |
| **Custom Entity** | Tenant admin | Veri modeli | Sadece kendi tenant |
| **Custom Field** | Tenant admin | Alan | Mevcut varlığa |

**Kural:**
- Module = **bizim yazdığımız** özellik
- Custom Entity = **kullanıcının tanımladığı** veri
- İkisi birbirini **tamamlar** (module + custom entity)

### 2.4. Custom Entity Örnek

**Senaryo:** Klinik, "Reçete" varlığı istiyor.

```yaml
entity:
  key: "recipe"
  name: "Reçete"
  module: "custom"
  
  fields:
    - { key: "number", type: "string", required: true, unique: true }
    - { key: "patient_id", type: "relation", target: "patient" }
    - { key: "doctor_id", type: "relation", target: "doctor" }
    - { key: "date", type: "date", required: true }
    - { key: "diagnosis", type: "text" }
    - { key: "medications", type: "jsonb" }
    - { key: "notes", type: "text" }
  
  relations:
    - { type: "belongs_to", target: "patient" }
    - { type: "belongs_to", target: "doctor" }
Sonuç:

Otomatik tablo: custom_recipe

Otomatik form: /custom/recipe/new

Otomatik liste: /custom/recipe

Otomatik API: GET/POST/PUT/DELETE /api/custom/recipe

Otomatik yetki: custom.recipe.read/create/update/delete

Otomatik audit: her işlem loglanır

Kod yazılmadı. Sadece YAML.

3. Custom Entity Nedir?
Custom Entity = kullanıcı tanımlı varlık (tablo). İçerir:

Alanlar (fields) — kolonlar

İlişkiler (relations) — foreign key

Doğrulama (validations) — kurallar

Index'ler — performans

Metadata — UI, izin, davranış

3.1. Kapsam
Özellik	Desteklenir
Yeni tablo	✅
Kolon ekleme	✅
Kolon tipi değiştirme	⚠️ Sınırlı
Kolon silme	✅ (soft)
Foreign key	✅
Composite key	✅
Unique constraint	✅
Index	✅
Check constraint	✅
Trigger	❌ (güvenlik)
Stored procedure	❌ (güvenlik)
View	✅ (read-only)
3.2. Kısıtlar
Güvenlik gereği yasak:

❌ Raw SQL

❌ Stored procedure

❌ Trigger

❌ DROP TABLE (sadece soft delete)

❌ Superadmin tablolarına erişim

❌ Başka tenant'ın verisi

Kural: Custom Entity, kendi kum havuzunda çalışır. Ana sisteme müdahale edemez.

4. Entity Tanımlama Dili (YAML)
4.1. Tam Yapı
yaml
entity:
  # ═══════════════ ZORUNLU ═══════════════
  key: "recipe"                          # benzersiz, snake_case
  name: "Reçete"                         # görünen
  module: "custom"                       # modül kodu
  
  # ═══════════════ ALANLAR ═══════════════
  fields:
    - key: "number"
      name: "Reçete No"
      type: "string"
      required: true
      unique: true
      max_length: 50
    
    - key: "patient_id"
      name: "Hasta"
      type: "relation"
      target: "patient"                  # başka entity
      required: true
    
    - key: "date"
      name: "Tarih"
      type: "date"
      required: true
      default: "now()"
    
    - key: "diagnosis"
      name: "Teşhis"
      type: "text"
      max_length: 2000
    
    - key: "medications"
      name: "İlaçlar"
      type: "jsonb"                      # esnek array
    
    - key: "status"
      name: "Durum"
      type: "enum"
      options: ["active", "cancelled", "completed"]
      default: "active"
  
  # ═══════════════ İLİŞKİLER ═══════════════
  relations:
    - key: "patient"
      type: "belongs_to"
      target: "patient"
      from: "patient_id"
      to: "id"
      on_delete: "restrict"
    
    - key: "doctor"
      type: "belongs_to"
      target: "doctor"
      from: "doctor_id"
      to: "id"
      on_delete: "nullify"
  
  # ═══════════════ INDEX'LER ═══════════════
  indexes:
    - name: "idx_recipe_tenant_date"
      columns: ["tenant_id", "date"]
      type: "btree"
    
    - name: "idx_recipe_patient"
      columns: ["tenant_id", "patient_id"]
      type: "btree"
    
    - name: "idx_recipe_status"
      columns: ["tenant_id", "status"]
      where: "status = 'active'"          # partial index
      type: "btree"
  
  # ═══════════════ UI ═══════════════
  ui:
    list:
      columns: ["number", "patient_id", "date", "status"]
      default_sort: "date DESC"
      per_page: 25
      filters: ["status", "date_range", "patient_id"]
    
    form:
      sections:
        - title: "Temel Bilgiler"
          fields: ["number", "patient_id", "doctor_id", "date"]
        - title: "Detaylar"
          fields: ["diagnosis", "medications", "notes"]
    
    detail:
      sections:
        - title: "Reçete"
          fields: ["number", "patient_id", "date", "status"]
        - title: "İçerik"
          fields: ["diagnosis", "medications"]
  
  # ═══════════════ İZİNLER ═══════════════
  permissions:
    - "custom.recipe.read"
    - "custom.recipe.create"
    - "custom.recipe.update"
    - "custom.recipe.delete"
    - "custom.recipe.export"
  
  # ═══════════════ DAVRANIŞ ═══════════════
  behaviors:
    soft_delete: true
    audit_log: true
    auto_code:                          # otomatik kod üretimi
      field: "number"
      pattern: "REC-{YYYY}-{NNNN}"
    
    workflow: true                      # workflow desteği
    api: true                           # API endpoint
    webhook: true                       # dış sistem event
4.2. Minimal Örnek
yaml
entity:
  key: "recipe"
  name: "Reçete"
  module: "custom"
  fields:
    - { key: "number", type: "string", required: true, unique: true }
    - { key: "patient_id", type: "relation", target: "patient" }
    - { key: "date", type: "date" }
Bu 8 satır ile:

Tablo: custom_recipe

Form: /custom/recipe/new

Liste: /custom/recipe

API: POST /api/custom/recipe

5. Alan (Field) Tipleri
5.1. Desteklenen Tipler
Tip	SQL Karşılık	Kullanım
string	varchar(N)	Kısa metin
text	text	Uzun metin
integer	bigint	Tam sayı
decimal	numeric(p,s)	Ondalık
boolean	boolean	Doğru/yanlış
date	date	Tarih
time	time	Saat
datetime	timestamptz	Tarih + saat
json	jsonb	Esnek veri
uuid	uuid	UUID
email	varchar(255)	Email (validasyonlu)
phone	varchar(30)	Telefon
url	varchar(500)	URL
enum	varchar(50)	Sabit liste
relation	uuid	Foreign key
file	varchar(500)	Dosya yolu
image	varchar(500)	Resim yolu
money	numeric(18,4)	Para
percent	numeric(5,2)	Yüzde
color	varchar(7)	Hex renk
rich_text	text	HTML içerik
markdown	text	Markdown
geolocation	jsonb	Lat/lng
array	jsonb	Dizi
5.2. Alan Tanımı
yaml
field:
  key: "sales_price"                    # zorunlu
  name: "Satış Fiyatı"                  # görünen
  type: "decimal"                       # zorunlu
  
  # Opsiyonel özellikler
  required: false                       # zorunlu mu
  unique: false                         # benzersiz mi
  default: 0                            # varsayılan
  min: 0                                # minimum
  max: 999999.99                        # maximum
  precision: 18                         # decimal precision
  scale: 4                              # decimal scale
  max_length: 255                       # string max
  
  # Validasyon
  validations:
    - "numeric"
    - "min:0"
  
  # UI
  placeholder: "0.00"
  help_text: "KDV hariç"
  readonly: false
  hidden: false
5.3. Enum
yaml
field:
  key: "status"
  type: "enum"
  options:
    - { value: "active", label: "Aktif", color: "green" }
    - { value: "inactive", label: "Pasif", color: "gray" }
    - { value: "draft", label: "Taslak", color: "yellow" }
  default: "draft"
5.4. Relation
yaml
field:
  key: "customer_id"
  type: "relation"
  target: "fin_customer"                # başka entity (custom veya platform)
  display_field: "name"                 # dropdown'da gösterilecek alan
  multiple: false                       # many-to-many mi
  required: true
5.5. JSON / Array
yaml
field:
  key: "medications"
  type: "json"
  schema:                               # JSON şeması
    type: "array"
    items:
      type: "object"
      properties:
        name: { type: "string" }
        dose: { type: "string" }
        frequency: { type: "string" }
5.6. Hesaplanan Alan (Computed)
yaml
field:
  key: "total"
  type: "decimal"
  computed: "quantity * unit_price"    # runtime hesaplanır
  stored: true                          # DB'de saklanır mı
Kural: Computed field read-only.

6. İlişki (Relation) Modeli
6.1. İlişki Tipleri
Tip	Açıklama	Örnek
belongs_to	N:1 (bu entity başka entity'ye ait)	Reçete → Hasta
has_many	1:N (bu entity birden fazla)	Hasta → Reçeteler
has_one	1:1	Kullanıcı → Profil
many_to_many	N:N	Ürün ↔ Kategori
has_many_through	N:N (pivot ile)	Sipariş → Ürün (sipariş satırı üzerinden)
6.2. Belongs_to Tanımı
yaml
relations:
  - key: "patient"
    type: "belongs_to"
    target: "patient"
    from: "patient_id"                  # bu entity'deki kolon
    to: "id"                            # hedef entity'deki kolon
    on_delete: "restrict"               # restrict | cascade | nullify | set_default
    on_update: "cascade"
6.3. Has_many Tanımı
yaml
relations:
  - key: "recipes"
    type: "has_many"
    target: "recipe"
    from: "id"
    to: "patient_id"
    cascade_delete: false
6.4. Many_to_many Tanımı
yaml
relations:
  - key: "categories"
    type: "many_to_many"
    target: "category"
    pivot_table: "custom_product_category"
    pivot_from: "product_id"
    pivot_to: "category_id"
6.5. Cross-Entity (Platform)
Custom entity, platform entity'lerine bağlanabilir:

yaml
relations:
  - key: "customer"
    type: "belongs_to"
    target: "fin_customer"              # platform entity
    from: "customer_id"
    to: "id"
    on_delete: "restrict"
Kritik: Cross-entity ilişki read için OK, write için kısıtlı (güvenlik).

6.6. İlişki Doğrulama
Validator kontrolleri:

✅ Target entity var mı

✅ Target kolon var mı

✅ On delete davranışı geçerli mi

✅ Döngüsel bağımlılık yok mu

✅ Cross-tenant izolasyon (target tenant-scoped mu)

7. Storage Stratejisi
7.1. İki Yaklaşım
Yaklaşım	Avantaj	Dezavantaj
EAV (Entity-Attribute-Value)	Tek tablo, esnek	Yavaş, index'lenemez
DDL (gerçek tablo)	Hızlı, index'lenir	Migration gerekir
JSONB	Esnek + index'lenir	Sorgu karmaşık
Bizim seçim: Hibrit — DDL (gerçek tablo) + JSONB (esnek alanlar için).

7.2. Hibrit Model
text
┌─────────────────────────────────────────────────────────┐
│  meta_entity                                            │
│  ─ key, name, tenant_id                                 │
│  ─ Alan tanımları (JSONB)                               │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│  custom_<entity_key>                                    │
│  ─ id, tenant_id                                        │
│  ─ Standart kolonlar (typed)                            │
│  ─ data JSONB (flex alanlar)                            │
└─────────────────────────────────────────────────────────┘
Kural:

Sık sorgulanan alanlar → typed kolon

Nadiren sorgulanan alanlar → JSONB

Bilinmeyen alanlar → JSONB

7.3. Tablo Yapısı
sql
CREATE TABLE custom_recipe (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    
    -- Standart kolonlar
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    deleted_at      timestamptz NULL,
    created_by      bigint NULL REFERENCES users(id),
    updated_by      bigint NULL REFERENCES users(id),
    
    -- Typed kolonlar (field.key + field.type'a göre)
    number          varchar(50) NOT NULL,
    patient_id      uuid NOT NULL,
    doctor_id       uuid NULL,
    date            date NOT NULL,
    status          varchar(50) DEFAULT 'active',
    
    -- Flex kolonlar (JSONB)
    data            jsonb NOT NULL DEFAULT '{}',
    
    -- Unique constraint
    UNIQUE (tenant_id, number)
);

-- Index'ler
CREATE INDEX idx_recipe_tenant ON custom_recipe(tenant_id);
CREATE INDEX idx_recipe_tenant_date ON custom_recipe(tenant_id, date DESC);
CREATE INDEX idx_recipe_tenant_patient ON custom_recipe(tenant_id, patient_id);
CREATE INDEX idx_recipe_status ON custom_recipe(tenant_id, status) WHERE deleted_at IS NULL;

-- JSONB index
CREATE INDEX idx_recipe_data_gin ON custom_recipe USING GIN(data);

-- RLS
ALTER TABLE custom_recipe ENABLE ROW LEVEL SECURITY;
ALTER TABLE custom_recipe FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON custom_recipe
    USING (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    )
    WITH CHECK (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    );
7.4. Alan Tipi Mapping
Field Type	SQL Column Type
string	varchar(max_length)
text	text
integer	bigint
decimal	numeric(precision, scale)
boolean	boolean
date	date
datetime	timestamptz
enum	varchar(50)
relation	uuid
json	jsonb
email	varchar(255)
phone	varchar(30)
money	numeric(18, 4)
Kural: json, array, geolocation → JSONB data kolonuna. Diğerleri typed kolon.

7.5. Tablo Adlandırma
text
custom_<entity_key>
Örnek:

custom_recipe

custom_patient

custom_vehicle

custom_order_item

Kısıt:

Max 63 karakter (PostgreSQL limit)

snake_case zorunlu

Sadece a-z, 0-9, _

7.6. Metadata Tablosu
sql
CREATE TABLE meta_entity (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NULL REFERENCES tenants(id) ON DELETE CASCADE,  -- NULL = global
    key             varchar(63) NOT NULL,
    name            varchar(255) NOT NULL,
    module          varchar(50) DEFAULT 'custom',
    definition      jsonb NOT NULL,       -- full YAML → JSON
    version         integer DEFAULT 1,
    status          varchar(20) DEFAULT 'active',
    created_by      bigint REFERENCES users(id),
    created_at      timestamptz,
    updated_at      timestamptz,
    UNIQUE(tenant_id, key)
);

CREATE INDEX idx_meta_entity_tenant ON meta_entity(tenant_id);
CREATE INDEX idx_meta_entity_key ON meta_entity(key);
7.7. Şema Versiyonlama
sql
CREATE TABLE meta_entity_version (
    id              uuid PRIMARY KEY,
    entity_id       uuid NOT NULL REFERENCES meta_entity(id) ON DELETE CASCADE,
    version         integer NOT NULL,
    definition      jsonb NOT NULL,       -- o versiyonun tam tanımı
    changes         jsonb,                -- ne değişti
    is_breaking     boolean DEFAULT false,
    migrated_at     timestamptz,
    created_at      timestamptz,
    UNIQUE(entity_id, version)
);
Kural: Her entity değişikliği = yeni versiyon. Eski versiyon veri korunur.

8. Validation Kuralları
8.1. Zorunlu Alan Kontrolü
yaml
validations:
  - "required"
Etki: Form'da zorunlu işaret, API'de 422 hata.

8.2. Tip Kontrolü
yaml
validations:
  - "integer"
  - "numeric"
  - "email"
  - "url"
  - "date"
8.3. Aralık Kontrolü
yaml
validations:
  - "min:0"
  - "max:100"
  - "between:0,100"
  - "min_length:3"
  - "max_length:255"
8.4. Pattern
yaml
validations:
  - "regex:/^[A-Z]{2}-\d{4}$/"
8.5. Custom Validation
yaml
validations:
  - "unique:custom_recipe,number"
  - "exists:fin_customer,id"
  - "in:active,inactive,draft"
8.6. Conditional Validation
yaml
validations:
  - when: "status == 'cancelled'"
    rules: ["cancel_reason:required"]
9. Otomatik Üretim — Form, Liste, API
9.1. Liste Sayfası
Otomatik üretilen: /custom/recipe

text
┌──────────────────────────────────────────────────────┐
│  Reçeteler                              [+ Yeni]     │
├──────────────────────────────────────────────────────┤
│  🔍 Ara... [Filtreler ▾]                             │
├──────────────────────────────────────────────────────┤
│  □ | Reçete No | Hasta | Tarih | Durum | İşlemler    │
│  ─────────────────────────────────────────────────── │
│  □ | REC-001  | Ali   | ...   | Aktif | [👁][✏][🗑]  │
│  □ | REC-002  | Ayşe  | ...   | Aktif | [👁][✏][🗑]  │
│                                                       │
│  [Sayfa 1/5]  [<] [1] [2] [3] [>]                    │
└──────────────────────────────────────────────────────┘
Özellikler:

Kolon seçimi (kullanıcı özelleştirebilir)

Sıralama

Filtreleme

Arama

Pagination

Toplu işlem

Export (CSV, Excel, PDF)

9.2. Form Sayfası
Otomatik üretilen: /custom/recipe/new

text
┌──────────────────────────────────────────────────────┐
│  Yeni Reçete                                          │
├──────────────────────────────────────────────────────┤
│  ── Temel Bilgiler ─────────────────────             │
│  Reçete No: [____________]                            │
│  Hasta:     [Dropdown ▾  ]                            │
│  Doktor:    [Dropdown ▾  ]                            │
│  Tarih:     [📅 Date      ]                            │
│                                                       │
│  ── Detaylar ──────────────────────────              │
│  Teşhis:    [Textarea    ]                            │
│  İlaçlar:   [JSON editor ]                            │
│  Notlar:    [Textarea    ]                            │
│                                                       │
│  [İptal] [Kaydet]                                     │
└──────────────────────────────────────────────────────┘
Özellikler:

Alan tiplerine göre otomatik kontrol

Validasyon

Conditional field (koşullu gösterim)

Autosave (opsiyonel)

Draft (opsiyonel)

9.3. Detail Sayfası
Otomatik üretilen: /custom/recipe/{id}

text
┌──────────────────────────────────────────────────────┐
│  Reçete REC-001                        [✏ Düzenle]  │
├──────────────────────────────────────────────────────┤
│  Hasta: Ali Yılmaz                                    │
│  Tarih: 06.11.2026                                    │
│  Durum: Aktif                                         │
│                                                       │
│  Teşhis: ...                                          │
│  İlaçlar: [...]                                       │
└──────────────────────────────────────────────────────┘
9.4. API Endpoint
Otomatik üretilen:

text
GET    /api/v1/custom/recipe              Liste
GET    /api/v1/custom/recipe/{id}         Detay
POST   /api/v1/custom/recipe              Oluştur
PUT    /api/v1/custom/recipe/{id}         Güncelle
DELETE /api/v1/custom/recipe/{id}         Sil
GET    /api/v1/custom/recipe/export       Dışa aktar
POST   /api/v1/custom/recipe/import       İçe aktar
Örnek istek:

http
POST /api/v1/custom/recipe
Authorization: Bearer <token>
Content-Type: application/json

{
    "number": "REC-001",
    "patient_id": "uuid-...",
    "doctor_id": "uuid-...",
    "date": "2026-11-06",
    "diagnosis": "Soğuk algınlığı",
    "medications": [
        { "name": "Paracetamol", "dose": "500mg", "frequency": "3x1" }
    ]
}
Yanıt:

json
{
    "id": "uuid-...",
    "number": "REC-001",
    "patient": { "id": "uuid-...", "name": "Ali Yılmaz" },
    "doctor": { "id": "uuid-...", "name": "Dr. Ayşe" },
    "date": "2026-11-06",
    "diagnosis": "Soğuk algınlığı",
    "medications": [...],
    "status": "active",
    "created_at": "2026-11-06T10:30:00Z"
}
9.5. İzin Kontrolü
Her CRUD için otomatik izin:

text
custom.recipe.read
custom.recipe.create
custom.recipe.update
custom.recipe.delete
custom.recipe.export
custom.recipe.import
Kullanıcı rolüne göre atanabilir (Belge 06).

9.6. Audit Log
Her işlem otomatik loglanır:

json
{
    "action": "create",
    "entity": "custom_recipe",
    "entity_id": "uuid-...",
    "user_id": 42,
    "tenant_id": "uuid-...",
    "changes": { "number": "REC-001", ... },
    "created_at": "2026-11-06T10:30:00Z"
}
10. Mevcut Custom Fields'ten Farkı
10.1. Karşılaştırma
Özellik	Custom Field	Custom Entity
Alan ekleme	✅	✅
Yeni tablo	❌	✅
Kendi listesi	❌	✅
Kendi formu	❌	✅
Kendi API'si	❌	✅
İlişki (FK)	❌	✅
Cross-entity	❌	✅
Kendi izinleri	⚠️	✅
Kendi audit'i	⚠️	✅
Kendi export'u	⚠️	✅
Kendi workflow'u	❌	✅
Migration	⚠️	✅
Version	⚠️	✅
10.2. Ne Zaman Hangisi?
Senaryo	Custom Field	Custom Entity
Müşteriye "segment" alanı	✅	❌
Ürüne "marka" alanı	✅	❌
Yeni "Reçete" varlığı	❌	✅
Yeni "Araç" varlığı	❌	✅
Siparişe "kargo takip" alanı	✅	❌
Yeni "Servis Kaydı" varlığı	❌	✅
Kural:

Mevcut varlığa alan → Custom Field

Yeni varlık (tablo) → Custom Entity

10.3. Birlikte Kullanım
Custom Entity'ye de custom field eklenebilir:

yaml
entity:
  key: "recipe"
  custom_fields_enabled: true           # custom field eklenebilir
11. İleri Özellikler
11.1. Computed Fields
yaml
field:
  key: "total"
  type: "decimal"
  computed: "qty * price"
  stored: true                          # DB'de sakla
  refresh: "on_update"                  # ne zaman hesapla
Uygulama: PostgreSQL generated column veya trigger (kontrollü).

11.2. Auto-Code
yaml
behaviors:
  auto_code:
    field: "number"
    pattern: "REC-{YYYY}-{NNNN}"
    sequence_reset: "yearly"
Sonuç: REC-2026-0001, REC-2026-0002...

11.3. Workflow Entegrasyonu
yaml
behaviors:
  workflow: true
Etki:

Entity için workflow tanımlanabilir

Durum geçişleri (Belge 11)

Onay akışı

Otomasyon

11.4. Webhook
yaml
behaviors:
  webhook: true
  events: ["created", "updated", "deleted"]
Etki: Her işlemde dış sistemlere HTTP POST.

11.5. File / Image
yaml
field:
  key: "avatar"
  type: "image"
  max_size: 5MB
  allowed_types: ["jpg", "png", "webp"]
  storage: "s3"
Uygulama: S3/R2 storage, signed URL.

11.6. Relational Computed
yaml
field:
  key: "invoice_count"
  type: "integer"
  computed_relation:
    type: "count"
    target: "fin_ar_invoice"
    foreign_key: "patient_id"
Sonuç: patient.invoice_count → ilişkili fatura sayısı.

11.7. Attachment (Dosya)
yaml
field:
  key: "attachments"
  type: "file"
  multiple: true
  max_files: 10
12. Runtime
12.1. Entity Load
Uygulama başladığında:

php
foreach (MetaEntity::where('status', 'active')->get() as $meta) {
    DynamicModel::register($meta);
}
Etki: Her custom entity bir dinamik Eloquent model olur.

12.2. Dynamic Model
php
class DynamicModel {
    public static function register(MetaEntity $meta): void {
        $className = 'Custom_' . Str::studly($meta->key);
        
        if (class_exists($className)) return;
        
        eval("
            class {$className} extends \\App\\Models\\CustomEntityModel {}
        ");
        
        $className::setMeta($meta);
    }
}
Kullanım:

php
$recipes = \Custom_Recipe::where('tenant_id', $tenantId)
    ->where('date', '>=', '2026-01-01')
    ->get();
Kural: Dynamic model standart Eloquent API'si sunar.

12.3. Migration Runtime
Entity oluşturulunca:

php
class EntityMigrator {
    public function migrate(MetaEntity $meta): void {
        // 1. Tablo oluştur (varsa update)
        Schema::create("custom_{$meta->key}", function (Blueprint $t) use ($meta) {
            $t->uuid('id')->primary();
            $t->uuid('tenant_id');
            $t->timestampsTz();
            $t->softDeletesTz();
            
            foreach ($meta->fields as $field) {
                $this->addColumn($t, $field);
            }
            
            $t->jsonb('data')->default('{}');
        });
        
        // 2. Index'ler
        foreach ($meta->indexes as $index) {
            $this->addIndex($meta->key, $index);
        }
        
        // 3. RLS
        $this->enableRls($meta->key);
        
        // 4. Model register
        DynamicModel::register($meta);
    }
}
12.4. Schema Evolution
Yeni alan ekleme:

yaml
# v1 → v2
fields:
  - { key: "new_field", type: "string" }  # yeni eklendi
Migration:

sql
ALTER TABLE custom_recipe ADD COLUMN new_field varchar(255) NULL;
Etki: Mevcut veri etkilenmez (nullable).

Alan tipi değiştirme:

yaml
# v1: string
# v2: integer
Migration:

sql
-- Yeni kolon
ALTER TABLE custom_recipe ADD COLUMN new_field_int bigint NULL;

-- Data migration
UPDATE custom_recipe SET new_field_int = CAST(new_field AS bigint) WHERE new_field ~ '^\d+$';

-- Eski kolonu sil (soft)
ALTER TABLE custom_recipe RENAME COLUMN new_field TO old_new_field;
ALTER TABLE custom_recipe RENAME COLUMN new_field_int TO new_field;
Kural: Breaking change → version bump.

13. Performans
13.1. Query Optimizasyonu
php
// ✅ Typed kolon (hızlı)
Custom_Recipe::where('tenant_id', $tid)
    ->where('date', '>=', '2026-01-01')
    ->get();

// ⚠️ JSONB (GIN index gerekli)
Custom_Recipe::where('tenant_id', $tid)
    ->where('data->status', 'active')
    ->get();
Kural: Sık sorgulanan alanlar typed kolon olmalı.

13.2. Index Öncelikleri
yaml
indexes:
  # Zorunlu (her entity)
  - { columns: ["tenant_id"] }
  - { columns: ["tenant_id", "created_at"] }
  
  # Alan bazlı
  - { columns: ["tenant_id", "date"] }
  - { columns: ["tenant_id", "status"] }
  
  # Unique
  - { columns: ["tenant_id", "number"], unique: true }
  
  # JSONB (gerektiğinde)
  - { columns: ["data"], type: "gin" }
13.3. Partitioning (İleride)
Büyük custom entity (>10M satır):

sql
CREATE TABLE custom_recipe (
    ...
) PARTITION BY RANGE (created_at);
Kural: 10M+ satır = partition düşün.

14. Güvenlik
14.1. İzolasyon
✅ RLS zorunlu (her custom entity)

✅ Tenant izolasyonu (uygulama + DB)

✅ Cross-tenant erişim yasak

✅ Platform entity'lerine write yasak (read-only cross-entity)

14.2. Sandbox
❌ Raw SQL yasak

❌ Stored procedure yasak

❌ Trigger yasak

❌ Dynamic SQL yasak

✅ Sadece API üzerinden CRUD

14.3. Rate Limit
Custom entity API için rate limit zorunlu

Tenant başına: 1000 istek/dakika (varsayılan)

Büyük tenant: plan bazlı

14.4. Audit
Her CRUD işlemi log'lanır

Kim, ne zaman, ne değiştirdi

7 yıl saklama (yasal)

14.5. GDPR / KVKK
Kişisel veri içeren entity'ler işaretlenir

"Right to be forgotten" → hard delete (soft delete yerine)

Veri export → kullanıcı talep edebilir

15. Test Stratejisi
15.1. Entity Tanım Testi
php
public function test_valid_entity_definition(): void {
    $definition = [
        'key' => 'recipe',
        'name' => 'Reçete',
        'fields' => [
            ['key' => 'number', 'type' => 'string', 'required' => true],
            ['key' => 'date', 'type' => 'date'],
        ],
    ];
    
    $result = EntityValidator::validate($definition);
    $this->assertTrue($result->isValid());
}

public function test_rejects_invalid_field_type(): void {
    $this->expectException(InvalidEntityException::class);
    
    EntityValidator::validate([
        'key' => 'test',
        'fields' => [['key' => 'x', 'type' => 'invalid_type']],
    ]);
}
15.2. Migration Testi
php
public function test_entity_creates_table(): void {
    $meta = MetaEntity::factory()->create([
        'key' => 'recipe',
        'tenant_id' => $tenantId,
        'definition' => [...],
    ]);
    
    app(EntityMigrator::class)->migrate($meta);
    
    $this->assertTrue(Schema::hasTable('custom_recipe'));
    $this->assertTrue(Schema::hasColumn('custom_recipe', 'number'));
    $this->assertTrue(Schema::hasColumn('custom_recipe', 'date'));
}
15.3. CRUD Testi
php
public function test_custom_entity_crud(): void {
    $meta = MetaEntity::factory()->create(['key' => 'recipe', ...]);
    app(EntityMigrator::class)->migrate($meta);
    
    $recipe = Custom_Recipe::create([
        'tenant_id' => $tenantId,
        'number' => 'REC-001',
        'date' => '2026-11-06',
    ]);
    
    $this->assertDatabaseHas('custom_recipe', ['number' => 'REC-001']);
    
    $recipe->update(['number' => 'REC-002']);
    $this->assertEquals('REC-002', $recipe->fresh()->number);
    
    $recipe->delete();
    $this->assertSoftDeleted('custom_recipe', ['id' => $recipe->id]);
}
15.4. RLS Testi
php
public function test_custom_entity_rls(): void {
    $meta = MetaEntity::factory()->create(['key' => 'recipe', ...]);
    app(EntityMigrator::class)->migrate($meta);
    
    $recipeA = TenantContext::runAs($tenantA->id, fn() => 
        Custom_Recipe::create(['number' => 'A-001', ...])
    );
    $recipeB = TenantContext::runAs($tenantB->id, fn() => 
        Custom_Recipe::create(['number' => 'B-001', ...])
    );
    
    $result = TenantContext::runAs($tenantA->id, fn() => Custom_Recipe::all());
    $this->assertCount(1, $result);
    $this->assertEquals('A-001', $result->first()->number);
}
15.5. API Testi
php
public function test_custom_entity_api(): void {
    $meta = MetaEntity::factory()->create(['key' => 'recipe', ...]);
    app(EntityMigrator::class)->migrate($meta);
    
    $this->actingAs($user)
        ->postJson('/api/v1/custom/recipe', [
            'number' => 'REC-001',
            'date' => '2026-11-06',
        ])
        ->assertStatus(201)
        ->assertJsonPath('number', 'REC-001');
}
16. Restoran Örneği — Masa Custom Entity
Kullanıcının sorusuna cevap: masa, oturma yeri custom entity olarak tanımlanabilir.

16.1. Masa Entity
yaml
entity:
  key: "restaurant_table"
  name: "Restoran Masası"
  module: "custom"
  
  fields:
    - { key: "table_number", type: "string", required: true, unique: true }
    - { key: "branch_id", type: "relation", target: "branch", required: true }
    - { key: "zone_id", type: "relation", target: "zone", required: true }
    - { key: "capacity", type: "integer", required: true }
    - { key: "shape", type: "enum", options: ["round", "square", "rectangle"] }
    - { key: "position_x", type: "decimal", precision: 10, scale: 2 }
    - { key: "position_y", type: "decimal", precision: 10, scale: 2 }
    - { key: "width", type: "decimal", precision: 10, scale: 2 }
    - { key: "height", type: "decimal", precision: 10, scale: 2 }
    - { key: "rotation", type: "integer", default: 0 }
    - { key: "status", type: "enum", options: ["free", "occupied", "reserved", "cleaning"], default: "free" }
    - { key: "current_order_id", type: "uuid" }
  
  ui:
    custom_views:
      - key: "floor_map"
        name: "Masa Haritası"
        type: "canvas"
        columns: ["position_x", "position_y", "width", "height", "rotation", "shape"]
  
  behaviors:
    audit_log: true
    workflow: false
16.2. Oturma Yeri Entity
yaml
entity:
  key: "restaurant_seat"
  name: "Oturma Yeri"
  module: "custom"
  
  fields:
    - { key: "table_id", type: "relation", target: "restaurant_table", required: true }
    - { key: "seat_number", type: "string", required: true }
    - { key: "position", type: "enum", options: ["north", "south", "east", "west"] }
    - { key: "x_offset", type: "decimal", precision: 10, scale: 2 }
    - { key: "y_offset", type: "decimal", precision: 10, scale: 2 }
  
  indexes:
    - { columns: ["tenant_id", "table_id"] }
  
  behaviors:
    audit_log: true
Sonuç:

Tablo: custom_restaurant_table, custom_restaurant_seat

Form: /custom/restaurant_table/new

Masa haritası (custom view ile canvas)

API: /api/v1/custom/restaurant_table

Cevap: EVET — masa, oturma yeri, pozisyon, şekil hepsi custom entity ile kaydedilebilir.

17. Anti-Patterns
Bu projede yasak olan custom entity hataları:

Saf EAV (Entity-Attribute-Value) — Performans kabusu

Trigger / stored procedure — Güvenlik riski

Raw SQL — Injection riski

DROP TABLE (gerçek) — Veri kaybı

Entity silme (kullanımda) — Sadece deprecate

Version'sız değişiklik — Her değişiklik = yeni versiyon

Index'siz büyük tablo — Performans

Unique'siz kod alanı — Duplicate riski

Cross-tenant FK — İzolasyon ihlali

Custom field + custom entity karışıklığı — Doğru araç seç

18. Roadmap
18.1. Aşama 1: Foundation (6 hafta)
meta_entity, meta_entity_version tabloları

YAML parser

EntityValidator

EntityMigrator

DynamicModel

RLS otomatik

18.2. Aşama 2: CRUD UI (8 hafta)
Liste sayfası (otomatik)

Form sayfası (otomatik)

Detail sayfası (otomatik)

Export/import

18.3. Aşama 3: API (4 hafta)
REST endpoint (otomatik)

Swagger docs (otomatik)

Rate limit

Auth

18.4. Aşama 4: Advanced (8 hafta)
Computed fields

Auto-code

Workflow entegrasyonu

Webhook

File/image

Canvas view (masa haritası gibi)

18.5. Aşama 5: Tools (4 hafta)
Visual entity builder

AI-assisted builder

Migration tools

Import/export

Toplam: 30 hafta (7-8 ay).

19. Glossary
Terim	Anlam
Custom Entity	Kullanıcı tanımlı varlık (tablo)
Meta Entity	Entity tanımı (metadata)
Field	Alan (kolon)
Relation	İlişki (foreign key)
Dynamic Model	Runtime oluşturulan Eloquent model
EntityMigrator	Entity → tablo dönüşümü
EntityValidator	Tanım doğrulayıcı
Typed Column	Tip belirli kolon
JSONB Column	Esnek JSON kolonu
Computed Field	Hesaplanan alan
Auto-Code	Otomatik kod üretimi
EAV	Entity-Attribute-Value (kullanmıyoruz)
20. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — custom entity framework tasarımı