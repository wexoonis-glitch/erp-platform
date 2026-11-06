# 09 — API-First

**Project:** Kurumsal ERP Platform
**Belge:** API Architecture, REST, Generic Entity API, GraphQL, Authentication
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — kısmen mevcut (web routes var, API yok)
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **API mimarisini** tanımlar:

- API-First prensibi (neden API birinci sınıf)
- 3 katmanlı API stratejisi (REST + Generic + GraphQL)
- REST design (core modüller)
- Generic Entity API (custom entity'ler)
- GraphQL (kompleks sorgular)
- Authentication (Sanctum, OAuth2, API key)
- Authorization (scope, permission)
- Versioning, rate limiting, pagination
- Error handling, response format
- Documentation (OpenAPI, Swagger)
- SDK (PHP, JS, Python)
- Testing, security, performance

**Hedef kitle:** Geliştiriciler, AI asistanlar, entegrasyon geliştiricileri, mobil geliştiriciler.

**Kritik:** API, platformun **asıl arayüzü**. Web UI bir client, mobil bir client, dış sistem bir client — hepsi aynı API'yi kullanır.

---

## 2. API-First Prensibi

### 2.1. Nedir?

**API-First** = **önce API tasarla, sonra UI'ı üstüne yap.** Tersi değil.

**Yanlış:**
Web UI yaz (Livewire)

Sonra "API'ye de ihtiyaç var" de

UI'daki iş mantığını API'ye kopyala

İki kod yolu → tutarsızlık

text

**Doğru:**
Service katmanı yaz (iş mantığı)

REST/GraphQL/Generic endpoint'leri bu servise bağla

Livewire component'leri de aynı servise bağla

Tek kaynak (service), çoklu arayüz (API + UI)

text

### 2.2. API-First Prensipleri

| Prensip | Açıklama |
|---|---|
| **Tek servis** | Hem UI hem API aynı service'i çağırır |
| **Versioning** | Her breaking change = yeni versiyon |
| **Idempotency** | Aynı istek 2 kez → aynı sonuç |
| **Standardization** | REST → HTTP standartları |
| **Documentation** | Her endpoint OpenAPI'de |
| **Testability** | Her endpoint test edilir |
| **Security** | Auth her istekte |
| **Rate limit** | DDoS koruması |

### 2.3. Servis Katmanı (Tek Kaynak)

```php
// app/Services/Otc/SalesOrderService.php
class SalesOrderService {
    public function create(array $data): OtcSalesOrder {
        return DB::transaction(function () use ($data) {
            $order = OtcSalesOrder::create($data);
            event(new SalesOrderCreated($order));
            return $order;
        });
    }
}

// Web (Livewire)
class SalesOrders extends Component {
    public function save() {
        $order = app(SalesOrderService::class)->create($this->data);
    }
}

// REST API
class SalesOrderController {
    public function store(Request $request) {
        $order = app(SalesOrderService::class)->create($request->validated());
        return new SalesOrderResource($order);
    }
}

// GraphQL
class SalesOrderMutation {
    public function create($_, array $args) {
        return app(SalesOrderService::class)->create($args);
    }
}
Kural: İş mantığı sadece service'te. Controller, Livewire, GraphQL resolver sadece adapter.

3. API Stratejisi — 3 Katman
3.1. Katman 1: REST (Core Modüller)
Ne için: Bilinen entity'ler (FIN, OTC, INV, HRM, PRC, MFG...)

Örnek:

text
GET    /api/v1/otc/sales-orders
GET    /api/v1/otc/sales-orders/{id}
POST   /api/v1/otc/sales-orders
PUT    /api/v1/otc/sales-orders/{id}
DELETE /api/v1/otc/sales-orders/{id}

GET    /api/v1/fin/customers
POST   /api/v1/fin/customers
PUT    /api/v1/fin/customers/{id}
DELETE /api/v1/fin/customers/{id}
Avantaj:

✅ Standart HTTP metodolojisi

✅ Cache-friendly (GET)

✅ Dokümantasyon kolay (OpenAPI)

✅ SDK kolay

✅ Rate limit granular

3.2. Katman 2: Generic Entity API (Custom)
Ne için: Kullanıcı tanımlı entity'ler (recipe, table, seat, vehicle...)

Örnek:

text
GET    /api/v1/entity/{entity_key}              → list
GET    /api/v1/entity/{entity_key}/{id}         → detail
POST   /api/v1/entity/{entity_key}              → create
PUT    /api/v1/entity/{entity_key}/{id}         → update
DELETE /api/v1/entity/{entity_key}/{id}         → delete

Örnek:
GET    /api/v1/entity/recipe
POST   /api/v1/entity/recipe
GET    /api/v1/entity/restaurant_table
POST   /api/v1/entity/restaurant_table
Avantaj:

✅ 100 custom entity = 5 endpoint (500 değil)

✅ Tek kod, tek test

✅ Tek middleware, tek auth, tek rate limit

✅ Yeni entity → otomatik API hazır

✅ Bakım kolay

Dezavantaj:

⚠️ Cache sınırlı (entity_key + tenant bazlı)

⚠️ Dokümantasyon dinamik (OpenAPI generated)

⚠️ SDK generic

3.3. Katman 3: GraphQL (Opsiyonel, Kompleks)
Ne için: Dashboard, rapor, mobil selective fetch, AI sorguları

Örnek:

graphql
query {
  dashboard {
    kpis {
      revenueThisMonth
      openOrders
      lowStockCount
    }
    recentOrders(limit: 5) {
      number
      customer { name }
      total
    }
  }
}
Tek endpoint: POST /api/graphql

Avantaj:

✅ Tek istek, çoklu fetch (network tasarrufu)

✅ Over-fetching yok (mobil için kritik)

✅ Tipli (schema)

✅ Introspection (kendi kendini dokümante)

✅ AI/LLM için ideal (doğal dil → GraphQL)

Dezavantaj:

❌ Cache zorlu (POST)

❌ N+1 riski (DataLoader zorunlu)

❌ Öğrenme eğrisi

❌ Rate limit kaba (her istek farklı ağırlık)

Karar: GraphQL opsiyonel, Faz 4'te (12-18 ay).

3.4. Neden Bu Hibrit?
Katman	Kapsam	Endpoint Sayısı
REST (core)	14 modül	~80 endpoint
Generic (custom)	Sınırsız entity	5 endpoint
GraphQL	Kompleks	1 endpoint
Toplam: ~86 endpoint (1000+ değil).

Karşılaştırma:

Yaklaşım	Endpoint	Bakım	Cache	Esneklik
Saf REST	500+	Zor	✅	⚠️
Saf GraphQL	1	Kolay	❌	✅
Hibrit (biz)	86	Kolay	✅	✅
4. REST Design — Core Modüller
4.1. URL Yapısı
text
/api/{version}/{module}/{resource}
Kısım	Örnek
api	Sabit
version	v1, v2
module	otc, fin, inv, hrm
resource	sales-orders, customers, products
Örnek:

text
GET /api/v1/otc/sales-orders
GET /api/v1/fin/customers
GET /api/v1/inv/products
4.2. HTTP Metodları
Metod	Kullanım	Idempotent
GET	Okuma	✅
POST	Oluşturma	❌
PUT	Tam güncelleme	✅
PATCH	Kısmi güncelleme	⚠️
DELETE	Silme	✅
Kural: POST sadece oluşturma için. Sorgu için POST yasak.

4.3. Standart CRUD Endpoint'leri
text
GET    /api/v1/otc/sales-orders              → list
GET    /api/v1/otc/sales-orders/{id}         → detail
POST   /api/v1/otc/sales-orders              → create
PUT    /api/v1/otc/sales-orders/{id}         → update (full)
PATCH  /api/v1/otc/sales-orders/{id}         → update (partial)
DELETE /api/v1/otc/sales-orders/{id}         → delete (soft)
4.4. Nested Resource
text
GET    /api/v1/otc/sales-orders/{id}/lines          → order lines
POST   /api/v1/otc/sales-orders/{id}/lines          → add line
GET    /api/v1/otc/sales-orders/{id}/lines/{lineId} → specific line
DELETE /api/v1/otc/sales-orders/{id}/lines/{lineId} → remove line
Kural: Max 2 seviye nested. Daha derin → ayrı endpoint.

4.5. Action Endpoint'leri
Durum değiştiren özel işlemler (CRUD dışı):

text
POST /api/v1/otc/sales-orders/{id}/confirm        → onayla
POST /api/v1/otc/sales-orders/{id}/ship           → sevk et
POST /api/v1/otc/sales-orders/{id}/cancel         → iptal
POST /api/v1/fin/ar-invoices/{id}/post            → muhasebeleştir
POST /api/v1/fin/ar-invoices/{id}/void            → iptal
Kural: Action endpoint'leri POST (durum değişir), path'te fiil.

4.6. Bulk Endpoint'leri
Toplu işlem:

text
POST /api/v1/otc/sales-orders/bulk-create       → toplu oluştur
PUT  /api/v1/otc/sales-orders/bulk-update       → toplu güncelle
DELETE /api/v1/otc/sales-orders/bulk-delete     → toplu sil
Body:

json
{
    "items": [
        { "customer_id": "...", "date": "2026-11-06", ... },
        { "customer_id": "...", "date": "2026-11-06", ... }
    ]
}
Response:

json
{
    "created": 2,
    "failed": 0,
    "results": [
        { "id": "...", "status": "created" },
        { "id": "...", "status": "created" }
    ]
}
4.7. Import/Export Endpoint'leri
text
POST /api/v1/{module}/{resource}/import     → CSV/Excel import
GET  /api/v1/{module}/{resource}/export     → CSV/Excel export
GET  /api/v1/{module}/{resource}/template   → boş şablon
4.8. Naming Convention
Kural	Örnek
Kaynak isimleri çoğul	customers, sales-orders
Kebab-case	sales-orders (camelCase değil)
Fiil yok	/customers (getCustomers değil)
Aksiyonlar fiil	/confirm, /post
Nested = path	/orders/{id}/lines
5. Generic Entity API — Custom Entity'ler
5.1. Neden Generic?
Problem: 100 custom entity × 5 endpoint = 500 endpoint.

Çözüm: Tek controller, entity_key parametresi.

5.2. Endpoint'ler
text
GET    /api/v1/entity/{entity_key}              → list
GET    /api/v1/entity/{entity_key}/{id}         → detail
POST   /api/v1/entity/{entity_key}              → create
PUT    /api/v1/entity/{entity_key}/{id}         → update
DELETE /api/v1/entity/{entity_key}/{id}         → delete
Örnek:

text
GET    /api/v1/entity/recipe               → tüm reçeteler
POST   /api/v1/entity/recipe               → yeni reçete
GET    /api/v1/entity/restaurant_table     → tüm masalar
POST   /api/v1/entity/restaurant_table     → yeni masa
5.3. Entity Key Resolution
php
class GenericEntityController {
    public function list(string $entityKey, Request $request) {
        // 1. Entity meta'sını al
        $meta = MetaEntity::forTenant(TenantContext::id())
            ->where('key', $entityKey)
            ->where('status', 'active')
            ->firstOrFail();
        
        // 2. Feature flag kontrolü
        if (!Features::enabled("custom.{$entityKey}.enabled")) {
            abort(403, 'Entity disabled');
        }
        
        // 3. Permission kontrolü
        $this->authorize("custom.{$entityKey}.read");
        
        // 4. Dynamic model
        $model = DynamicModel::for($meta);
        
        // 5. Query (RLS + data scope otomatik)
        $query = $model::query();
        
        // 6. Filter, sort, paginate
        $query = $this->applyListParams($query, $request, $meta);
        
        // 7. Response
        return EntityResource::collection($query->paginate());
    }
}
5.4. Filter, Sort, Pagination
List endpoint parametreleri:

Parametre	Örnek	Açıklama
filter[key]	filter[status]=active	Filtre
filter[key][op]	filter[date][gte]=2026-01-01	Operatörlü filtre
sort	sort=-created_at	Sıralama (- = desc)
page	page=2	Sayfa
per_page	per_page=50	Sayfa boyutu
search	search=ali	Arama
include	include=patient,doctor	İlişki yükle
fields	fields=id,number,date	Alan seçimi
Operatörler:

Operatör	Anlam	Örnek
eq	Eşit	filter[status][eq]=active
ne	Eşit değil	filter[status][ne]=deleted
gt	Büyük	filter[total][gt]=1000
gte	Büyük/eşit	filter[date][gte]=2026-01-01
lt	Küçük	filter[total][lt]=1000
lte	Küçük/eşit	filter[date][lte]=2026-12-31
in	İçinde	filter[status][in]=active,draft
nin	İçinde değil	filter[status][nin]=deleted
like	LIKE	filter[name][like]=%ali%
null	NULL	filter[deleted_at][null]=true
Örnek URL:

text
GET /api/v1/entity/recipe?filter[status]=active&filter[date][gte]=2026-01-01&sort=-created_at&per_page=25&page=1
5.5. Custom Entity Response
json
{
    "data": [
        {
            "id": "uuid-1",
            "number": "REC-001",
            "date": "2026-11-06",
            "status": "active",
            "patient": {
                "id": "uuid-p1",
                "name": "Ali Yılmaz"
            },
            "data": {
                "diagnosis": "Soğuk algınlığı",
                "medications": [...]
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 25,
        "total": 143,
        "last_page": 6
    },
    "links": {
        "first": "...",
        "prev": null,
        "next": "...",
        "last": "..."
    }
}
5.6. Custom Action
Custom entity'ye özel işlem tanımlanabilir:

yaml
entity:
  key: "recipe"
  actions:
    - key: "approve"
      label: "Onayla"
      permission: "custom.recipe.approve"
      handler: "RecipeApprovalService"
Endpoint:

text
POST /api/v1/entity/recipe/{id}/approve
6. GraphQL (Opsiyonel)
6.1. Ne Zaman?
Dashboard (10+ tablo join, tek istek)

Mobil (selective fetch)

AI/LLM (doğal dil → GraphQL)

3rd party (esnek sorgu)

6.2. Endpoint
text
POST /api/graphql
6.3. Schema Örneği
graphql
type Query {
    dashboard: Dashboard!
    salesOrders(filter: SalesOrderFilter, first: Int, after: String): SalesOrderConnection
    customer(id: ID!): Customer
    customEntity(key: String!, id: ID!): CustomEntity
}

type Dashboard {
    kpis: KPIs!
    recentOrders(limit: Int): [SalesOrder!]!
    topProducts(limit: Int): [Product!]!
}

type KPIs {
    revenueThisMonth: Decimal!
    openOrders: Int!
    lowStockCount: Int!
    pendingApprovals: Int!
}

type SalesOrder {
    id: ID!
    number: String!
    customer: Customer!
    total: Decimal!
    status: String!
    items: [SalesOrderLine!]!
}
6.4. Query Örneği
graphql
query MyDashboard {
    dashboard {
        kpis {
            revenueThisMonth
            openOrders
        }
        recentOrders(limit: 5) {
            number
            customer { name }
            total
        }
    }
}
Response: Tek istek, tek round-trip. Mobil için kritik.

6.5. DataLoader (N+1 Önleme)
php
class CustomerDataLoader extends DataLoader {
    public function loadMany(array $ids): array {
        return FinCustomer::whereIn('id', $ids)->get()->keyBy('id');
    }
}
Kural: GraphQL'de her relation için DataLoader zorunlu. Aksi halde N+1 patlar.

6.6. GraphQL Security
Risk	Korumа
Query depth attack	Max depth 5
Query complexity	Max complexity 1000
Introspection in prod	Kapalı
N+1	DataLoader
Rate limit	Complexity-based
7. Authentication
7.1. 3 Yöntem
Yöntem	Kullanım	Lifetime
Sanctum (Bearer token)	1st party (web, mobil)	7 gün (refresh)
OAuth2	3rd party integrations	1 saat (refresh)
API Key	Server-to-server	Süresiz (rotate)
7.2. Sanctum (Varsayılan)
Login:

http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "...",
    "device_name": "iPhone 15"
}
Response:

json
{
    "token": "1|abc123...",
    "expires_at": "2026-11-13T10:30:00Z",
    "user": {
        "id": 42,
        "name": "Ali",
        "tenant_id": "uuid-...",
        "role": "admin"
    }
}
Kullanım:

http
GET /api/v1/otc/sales-orders
Authorization: Bearer 1|abc123...
7.3. OAuth2 (3rd Party)
Flow: Authorization Code

text
1. App → Redirect to /oauth/authorize?client_id=X&redirect_uri=Y&scope=...
2. User → Login + Consent
3. App → Code received
4. App → POST /oauth/token (code + client_secret)
5. App → Access token + refresh token
Scope'lar:

text
fin.customer.read
fin.customer.write
otc.sales_order.read
7.4. API Key (Server-to-Server)
Kullanım:

http
POST /api/v1/integration/wolt/orders
X-API-Key: sk_live_abc123...
Rotation: Her 90 gün.

7.5. Rate Limit (Auth Bazlı)
Auth Tipi	Limit
Sanctum	1000 istek/dakika
OAuth2	500 istek/dakika
API Key	5000 istek/dakika
8. Authorization
8.1. Layered Authorization
Her API isteği 3 katman kontrol:

text
1. Authentication   → Token geçerli mi?
2. Tenant Isolation → RLS (Belge 01)
3. Permission       → Kullanıcı bu action'a yetkili mi?
4. Data Scope       → Kullanıcı bu veriyi görebilir mi?
8.2. Permission Check
php
class SalesOrderController {
    public function store(Request $request) {
        $this->authorize('otc.sales_order.create');
        
        $order = app(SalesOrderService::class)->create($request->validated());
        
        return new SalesOrderResource($order);
    }
}
authorize → StructureContext + permission resolver (Belge 06).

8.3. Data Scope Check
php
// Service katmanında otomatik
class SalesOrderService {
    public function list() {
        return OtcSalesOrder::query()  // ← DataScope global scope
            ->paginate();
    }
}
Kural: Data scope servis katmanında, controller'da değil.

8.4. Scope (OAuth2)
http
POST /oauth/authorize
    ?client_id=X
    &scope=fin.customer.read fin.customer.write
Kullanıcı onaylarsa: Token sadece bu scope'larla çalışır.

Middleware:

php
Route::middleware('scope:fin.customer.read')->get('/api/v1/fin/customers', ...);
9. Versioning
9.1. URL Versioning
text
/api/v1/otc/sales-orders
/api/v2/otc/sales-orders
Avantaj: Açık, cache-friendly, debug kolay.

9.2. Ne Zaman Yeni Versiyon?
Değişiklik	Yeni Versiyon
Field ekleme	❌ (backward compatible)
Field kaldırma	✅
Field tipi değişimi	✅
Endpoint kaldırma	✅
Response yapısı değişimi	✅
URL yapısı değişimi	✅
Kural: Backward compatibility kutsal. Breaking change = yeni versiyon.

9.3. Deprecation Policy
text
1. v2 yayınla (v1 aktif)
2. v1'e "Sunset" header ekle
3. 12 ay uyarı
4. v1 kapat
Sunset header:

http
HTTP/1.1 200 OK
Sunset: Sat, 06 Nov 2027 00:00:00 GMT
Deprecation: true
Link: </api/v2/...>; rel="successor-version"
9.4. Versiyonlar Arası Geçiş
php
// v1 ve v2 aynı servisi kullanır, sadece response farklı
Route::prefix('v1')->group(function () {
    Route::get('/customers', [V1CustomerController::class, 'index']);
});

Route::prefix('v2')->group(function () {
    Route::get('/customers', [V2CustomerController::class, 'index']);
});

// İkisi de aynı service
class V1CustomerController {
    public function index() {
        return V1CustomerResource::collection(app(CustomerService::class)->list());
    }
}

class V2CustomerController {
    public function index() {
        return V2CustomerResource::collection(app(CustomerService::class)->list());
    }
}
Kural: Service değişmez, sadece Resource (response) değişir.

10. Rate Limiting
10.1. Limit Türleri
Limit	Kapsam	Örnek
Global	Tüm istekler	10.000/dk (platform)
Per-tenant	Tenant başına	1.000/dk
Per-user	Kullanıcı başına	100/dk
Per-endpoint	Endpoint başına	10/dk (login)
10.2. Header'lar
http
HTTP/1.1 200 OK
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 998
X-RateLimit-Reset: 1699281600
10.3. 429 Response
http
HTTP/1.1 429 Too Many Requests
Retry-After: 60
Content-Type: application/json

{
    "error": {
        "code": "rate_limit_exceeded",
        "message": "Rate limit exceeded. Try again in 60 seconds.",
        "retry_after": 60
    }
}
10.4. Plan Bazlı Limit
Plan	Limit
Free	100/dk
Pro	1.000/dk
Enterprise	10.000/dk
Custom	Negotiated
11. Pagination
11.1. Offset Pagination (Varsayılan)
http
GET /api/v1/fin/customers?page=2&per_page=25
Response:

json
{
    "data": [...],
    "meta": {
        "current_page": 2,
        "per_page": 25,
        "total": 143,
        "last_page": 6,
        "from": 26,
        "to": 50
    },
    "links": {
        "first": "/api/v1/fin/customers?page=1",
        "prev": "/api/v1/fin/customers?page=1",
        "next": "/api/v1/fin/customers?page=3",
        "last": "/api/v1/fin/customers?page=6"
    }
}
Kullanım: Küçük/orta tablolar.

11.2. Cursor Pagination (Büyük)
http
GET /api/v1/fin/journal-entries?cursor=eyJpZCI6MTIzfQ&per_page=100
Response:

json
{
    "data": [...],
    "meta": {
        "per_page": 100,
        "next_cursor": "eyJpZCI6MjIzfQ",
        "prev_cursor": null,
        "has_more": true
    }
}
Kullanım: Büyük tablolar (100K+ satır).

Kural: Cursor pagination stable — yeni kayıt eklense bile sayfa kaymaz.

11.3. Seçim
Tablo Boyutu	Pagination
< 10K	Offset
10K - 1M	Offset (index'li)
> 1M	Cursor
12. Response Format
12.1. Başarı Response
json
{
    "data": {
        "id": "uuid-...",
        "number": "SO-001",
        "total": 5000.00,
        "customer": { "id": "...", "name": "Acme" }
    }
}
Collection:

json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 25,
        "total": 143
    },
    "links": {...}
}
12.2. Error Response
Format (RFC 7807 — Problem Details):

json
{
    "type": "https://api.example.com/errors/validation-failed",
    "title": "Validation Failed",
    "status": 422,
    "detail": "The given data was invalid.",
    "instance": "/api/v1/otc/sales-orders",
    "errors": {
        "customer_id": ["The customer id field is required."],
        "items": ["At least one item is required."],
        "items.0.qty": ["Quantity must be greater than 0."]
    }
}
HTTP Status Kodları:

Kod	Anlam
200	OK
201	Created
204	No Content (delete)
400	Bad Request
401	Unauthorized
403	Forbidden
404	Not Found
409	Conflict (duplicate)
422	Validation Error
429	Rate Limit
500	Server Error
12.3. Partial Success (Bulk)
json
{
    "data": [
        { "index": 0, "status": "created", "id": "uuid-1" },
        { "index": 1, "status": "failed", "errors": { "code": ["Duplicate"] } }
    ],
    "meta": {
        "total": 2,
        "success": 1,
        "failed": 1
    }
}
HTTP: 207 Multi-Status.

13. Filtering, Sorting, Searching
13.1. Filter
text
?filter[status]=active
?filter[status][in]=active,draft
?filter[date][gte]=2026-01-01
?filter[total][between]=1000,5000
?filter[name][like]=%ali%
?filter[deleted_at][null]=true
13.2. Sort
text
?sort=created_at           (asc)
?sort=-created_at          (desc)
?sort=name,-created_at     (multiple)
13.3. Search
text
?search=ali
?search_fields=name,email  (hangi alanlarda)
Uygulama: PostgreSQL full-text search veya LIKE.

13.4. Include (Eager Load)
text
?include=customer,items.product
Kural: Max 3 seviye. customer.address.city OK, a.b.c.d reddedilir.

13.5. Fields (Selective)
text
?fields=id,number,total
Kural: id her zaman döner.

14. Documentation (OpenAPI)
14.1. OpenAPI 3.1
Her endpoint OpenAPI spec'te tanımlı:

yaml
openapi: 3.1.0
info:
  title: Kurumsal ERP API
  version: 1.0.0
paths:
  /api/v1/otc/sales-orders:
    get:
      summary: List sales orders
      parameters:
        - in: query
          name: filter[status]
          schema: { type: string }
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SalesOrderCollection'
14.2. Swagger UI
URL: /api/docs

Görünüm: Interaktif, deneyebilirsin.

14.3. Custom Entity OpenAPI
Custom entity'ler için dinamik OpenAPI:

bash
GET /api/v1/docs/entity/{entity_key}
Response: O entity'nin schema'sı.

14.4. Postman Collection
/api/postman.json — tüm endpoint'ler Postman'de.

15. SDK
15.1. PHP SDK
php
use Erp\SDK\ErpClient;

$client = new ErpClient('https://api.example.com', 'token');

// REST
$orders = $client->otc()->salesOrders()->list(['filter' => ['status' => 'active']]);
$order = $client->otc()->salesOrders()->create($data);

// Custom entity
$recipes = $client->entity('recipe')->list();
$recipe = $client->entity('recipe')->create($data);

// GraphQL
$result = $client->graphql()->query('query {...}');
15.2. JS SDK
javascript
import { ErpClient } from '@erp/sdk';

const client = new ErpClient('https://api.example.com', 'token');

const orders = await client.otc.salesOrders.list({ filter: { status: 'active' }});
const recipe = await client.entity('recipe').create(data);
15.3. Python SDK
python
from erp_sdk import ErpClient

client = ErpClient('https://api.example.com', 'token')
orders = client.otc.sales_orders.list(filter={'status': 'active'})
16. Testing
16.1. Automated Test
php
public function test_list_sales_orders(): void {
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/otc/sales-orders')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'number', 'total']],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
}

public function test_create_validates_required_fields(): void {
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/otc/sales-orders', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.customer_id.0', 'The customer id field is required.');
}
16.2. Contract Test
OpenAPI spec ile response uyumlu mu?

php
public function test_api_matches_openapi(): void {
    $response = $this->getJson('/api/v1/otc/sales-orders');
    
    $spec = OpenApiValidator::load('/api/docs');
    $spec->validate('/api/v1/otc/sales-orders', 'get', $response->json());
}
16.3. Load Test
bash
k6 run loadtest.js
Hedef: P95 < 200ms, 1000 RPS.

17. Security
17.1. HTTPS Zorunlu
HTTP → 301 HTTPS.

17.2. CORS
php
config/cors.php:
'paths' => ['api/*'],
'allowed_origins' => ['*.myapp.com'],  // tenant bazlı
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'max_age' => 86400,
Kural: * origin sadece development'ta. Production'da whitelist.

17.3. Input Validation
Her endpoint FormRequest:

php
class CreateSalesOrderRequest extends FormRequest {
    public function rules(): array {
        return [
            'customer_id' => 'required|uuid|exists:fin_customer,id',
            'date' => 'required|date|before_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:inv_product,id',
            'items.*.qty' => 'required|numeric|min:0.01',
        ];
    }
}
17.4. SQL Injection
Kural: Raw SQL yasak. Eloquent/Query Builder.

17.5. IDOR (Insecure Direct Object Reference)
text
GET /api/v1/otc/sales-orders/123
Koruma: RLS + DataScope. Kullanıcı sadece kendi siparişini görür.

17.6. Mass Assignment
php
// Model'de
protected $fillable = ['customer_id', 'date', 'items'];
Kural: $guarded = [] yasak.

17.7. Sensitive Data
Kural: PII response'da masked (Belge 08).

json
{
    "customer": {
        "name": "Ali Yılmaz",
        "email": "a***@example.com",
        "tax_no": "***4567"
    }
}
17.8. Audit
Her API isteği audit log:

json
{
    "user_id": 42,
    "tenant_id": "...",
    "method": "POST",
    "path": "/api/v1/otc/sales-orders",
    "ip": "192.168.1.1",
    "user_agent": "...",
    "status": 201,
    "duration_ms": 234
}
18. Performance
18.1. Caching
Endpoint	Cache
GET /customers	60 sn
GET /products	5 dk
GET /dashboard	30 sn
POST/PUT/DELETE	Cache invalidate
Header:

http
Cache-Control: public, max-age=60, s-maxage=60
ETag: "abc123"
18.2. ETag / If-None-Match
http
GET /api/v1/inv/products
If-None-Match: "abc123"

HTTP/1.1 304 Not Modified
ETag: "abc123"
Kazanç: Network tasarrufu, %70 cache hit.

18.3. Gzip / Brotli
Tüm API response sıkıştırılmış (nginx).

18.4. Database Query Optimizasyonu
Index zorunlu (Belge 02)

Eager load (Belge 02)

Cursor pagination (büyük tablolar)

18.5. Timeout
İstek Tipi	Timeout
GET (list)	5 sn
GET (detail)	3 sn
POST (create)	10 sn
Bulk	60 sn
Export	300 sn
Kural: Timeout = connection close + 504.

19. Anti-Patterns
Bu projede yasak olan API hataları:

POST ile sorgu — GET kullan

Fiil URL'de — /getCustomers ❌

Tek endpoint her şey — REST + Generic + GraphQL hibrit

Versioning'siz breaking change — Yeni versiyon zorunlu

Auth'suz endpoint — Her endpoint auth (public olanlar hariç)

Input validation'sız — FormRequest zorunlu

Raw SQL — Eloquent/Query Builder

PII filtresiz — Mask zorunlu

Rate limit'siz — Her endpoint limit

Dokümantasyon'suz endpoint — OpenAPI zorunlu

Test edilmemiş endpoint — CI'da test zorunlu

N+1 — Eager load/DataLoader

Mass assignment — $fillable zorunlu

* CORS origin (prod) — Whitelist

Stack trace prod'da — RFC 7807 error format

20. Roadmap
Aşama	Süre	Kapsam
1. Foundation	6 hafta	Sanctum auth, REST core (FIN, INV), OpenAPI, test
2. Core Modules	8 hafta	REST (OTC, PRC, HRM, MFG, ...) tam
3. Generic Entity	4 hafta	Custom entity API
4. Advanced	6 hafta	Filtering, sorting, bulk, export
5. GraphQL	6 hafta	Opsiyonel GraphQL
6. SDK	4 hafta	PHP, JS, Python
7. Marketplace API	6 hafta	3rd party apps
Toplam: 40 hafta (~10 ay).

21. Glossary
Terim	Anlam
API-First	Önce API, sonra UI
REST	Representational State Transfer
Generic API	Tek endpoint, entity_key parametreli
GraphQL	Query language for APIs
Sanctum	Laravel token auth
OAuth2	Authorization framework
API Key	Server-to-server auth
Scope	OAuth2 izin
Rate limit	İstek sınırı
Cursor pagination	Stable pagination
ETag	Cache validator
RFC 7807	Problem Details error format
OpenAPI	API specification
SDK	Software Development Kit
DataLoader	GraphQL N+1 önleme
Breaking change	Geriye uyumsuz değişiklik
Idempotency	Aynı istek = aynı sonuç
IDOR	Insecure Direct Object Reference
22. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — hibrit API stratejisi, plugin audit notu