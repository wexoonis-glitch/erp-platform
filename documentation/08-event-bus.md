# 08 — Event Bus

**Project:** Kurumsal ERP Platform
**Belge:** Event-Driven Architecture, Event Bus, Event Store, Cross-Tenant Audit
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — kısmen mevcut (event'ler var, bus yok)
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **event-driven architecture**'ını ve **event bus** sistemini tanımlar:

- Event nedir, neden event-driven
- Event tipleri (domain, integration, system)
- Event yapısı ve metadata
- Publishing (sync, async, outbox)
- Subscribers (listeners, plugins, webhooks, AI)
- Event store (persistence, replay)
- **Cross-tenant visibility** (owner audit)
- Privacy ve compliance filtreleri
- Performance ve scalability
- Mevcut sistemle geçiş

**Hedef kitle:** Geliştiriciler, AI asistanlar, güvenlik denetçileri, ürün mimarları.

**Kritik:** Bu belge, modüller arası **tek iletişim kanalını** tanımlar. Event bus olmadan modüller birbirine **sıkı bağlı** olur → meta-platform imkansız.

---

## 2. Neden Event-Driven?

### 2.1. Klasik Yaklaşım (Yanlış)

```php
// SalesOrderService - her şey burada
class SalesOrderService {
    public function create($data) {
        $order = OtcSalesOrder::create($data);
        
        // Stok düş
        app(StockService::class)->decrease($order->items);
        
        // Fatura oluştur
        app(InvoiceService::class)->create($order);
        
        // Bildirim gönder
        app(NotificationService::class)->notify('order_created', $order);
        
        // Muhasebe kaydı
        app(JournalService::class)->post($order);
        
        // Kargo oluştur
        app(ShipmentService::class)->create($order);
        
        // Wolt'a gönder
        app(WoltIntegration::class)->sync($order);
        
        return $order;
    }
}
Sorunlar:

Sorun	Etki
Sıkı bağlılık	Her modül birbirine bağımlı
Test edilemez	6 servis mock'lanmalı
Yavaş	6 servis sıralı çalışır (2-3 sn)
Kırılgan	Wolt çökse → tüm satış çöker
Tekrar edilemez	Hata olursa geri alma imkansız
Genişletilemez	Yeni entegrasyon = SalesOrderService değişir
2.2. Event-Driven (Doğru)
php
class SalesOrderService {
    public function create($data) {
        return DB::transaction(function () use ($data) {
            $order = OtcSalesOrder::create($data);
            
            // Sadece event yayınla
            event(new SalesOrderCreated($order));
            
            return $order;
        });
    }
}
Faydalar:

Fayda	Etki
Loose coupling	Modüller birbirini bilmez
Testable	Event mock'lanır
Hızlı	Ana işlem 200ms, gerisi async
Resilient	Wolt çökse, satış devam eder
Replayable	Event'ler saklanır, yeniden çalıştırılır
Extensible	Yeni listener = SalesOrderService değişmez
2.3. Kim Ne Yapar?
text
SalesOrderService
    ↓ yayınlar
SalesOrderCreated event
    ↓ dinler
    ├── StockService         (stok düş)
    ├── InvoiceService       (fatura oluştur)
    ├── NotificationService  (bildirim)
    ├── JournalService       (muhasebe)
    ├── ShipmentService      (kargo)
    ├── WoltIntegration      (dış sync)
    └── AI AnomalyDetector   (anomali)
Kural: Yayıncı kimin dinlediğini bilmez. Dinleyici kimin yayınladığını bilmez. Aralarında sadece event var.

3. Event Nedir?
3.1. Tanım
Bir event, sistemde gerçekleşmiş bir olaydır (geçmiş zaman). İçerir:

Ne oldu (event type)

Ne zaman (timestamp)

Kim yaptı (actor)

Hangi tenant (tenant_id)

Hangi entity (aggregate)

Detaylar (payload)

3.2. Event vs Command
Kavram	Anlam	Örnek
Event	"Ne oldu" (geçmiş)	SalesOrderCreated
Command	"Ne yapılmalı" (istek)	CreateSalesOrder
Kural: Event geçmiş zaman — değiştirilemez. Command emir — reddedilebilir.

Bizde sadece event var (command queue kullanmıyoruz, senkron API).

3.3. Event Yapısı
php
abstract class DomainEvent {
    public string $eventId;              // UUID
    public string $eventType;            // "otc.sales_order.created"
    public string $aggregateType;        // "otc_sales_order"
    public string $aggregateId;          // UUID
    public ?string $tenantId;            // UUID
    public ?int $userId;                 // bigint
    public ?string $layerId;             // UUID (Belge 06)
    public array $payload;               // Event özel veri
    public array $metadata;              // Ek bilgi
    public \DateTimeImmutable $occurredAt;
    public int $version;                 // Event schema version
    public string $visibility;           // tenant_only | owner_metadata | owner_full
}
Örnek:

json
{
    "eventId": "550e8400-e29b-41d4-a716-446655440000",
    "eventType": "otc.sales_order.created",
    "aggregateType": "otc_sales_order",
    "aggregateId": "660f9511-f39c-52e5-b827-557766551111",
    "tenantId": "770a0622-a40d-63f6-c938-668877662222",
    "userId": 42,
    "layerId": "880b1733-b51e-74a7-da49-779988773333",
    "payload": {
        "order_number": "SO-2026-001234",
        "customer_id": "990c2844-c62f-85b8-eb5a-88aa99884444",
        "grand_total": 5000.00,
        "currency": "TRY",
        "items": [
            { "product_id": "...", "qty": 2, "price": 2500.00 }
        ]
    },
    "metadata": {
        "ip": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "request_id": "req-abc-123"
    },
    "occurredAt": "2026-11-06T10:30:00Z",
    "version": 1,
    "visibility": "owner_metadata"
}
4. Event Tipleri
4.1. Domain Events
Modül içi iş olayları. Örnek:

Event	Modül	Açıklama
fin.customer.created	FIN	Müşteri oluşturuldu
fin.ar_invoice.posted	FIN	Fatura kesildi
fin.payment.received	FIN	Ödeme alındı
inv.product.created	INV	Ürün oluşturuldu
inv.stock.moved	INV	Stok hareketi
inv.stock.below_minimum	INV	Minimum altına düştü
otc.sales_order.created	OTC	Satış oluşturuldu
otc.sales_order.confirmed	OTC	Satış onaylandı
otc.sales_order.shipped	OTC	Sevk edildi
hrm.employee.hired	HRM	Personel işe alındı
hrm.payroll.processed	HRM	Bordro işlendi
4.2. Integration Events
Dış sistemlerle ilgili olaylar. Örnek:

Event	Kaynak	Açıklama
integration.wolt.order_received	Wolt	Dış sipariş alındı
integration.bank.transaction_synced	Banka	Banka hareketi çekildi
integration.einvoice.sent	GİB	e-Fatura gönderildi
integration.einvoice.accepted	GİB	e-Fatura kabul edildi
integration.cargo.shipment_created	Kargo	Sevkiyat oluşturuldu
integration.stripe.payment_captured	Stripe	Ödeme alındı
Kural: Integration event'ler domain event tetikler.

text
integration.wolt.order_received
    ↓ (WoltIntegrationService)
otc.sales_order.created
    ↓ (InventoryService)
inv.stock.moved
4.3. System Events
Platform altyapısı olayları. Örnek:

Event	Açıklama
system.tenant.created	Yeni tenant
system.user.login	Kullanıcı giriş yaptı
system.user.logout	Çıkış
system.feature.toggled	Feature flag değişti
system.structure.published	Platform structure yayınlandı
system.plugin.installed	Plugin kuruldu
system.error.occurred	Hata oluştu
system.backup.completed	Backup tamamlandı
4.4. Meta Events
Owner (biz) için özel olaylar. Örnek:

Event	Açıklama
meta.tenant.active	Tenant aktif
meta.tenant.inactive_30d	30 gün kullanılmadı
meta.usage.spike	Kullanım artışı
meta.error.rate_high	Hata oranı yüksek
meta.security.suspicious_activity	Şüpheli aktivite
Kullanım: Owner dashboard'da görünür.

5. Event Naming Convention
5.1. Format
text
<domain>.<aggregate>.<verb_past>
Kısım	Örnek	Açıklama
domain	otc	Modül kodu
aggregate	sales_order	Varlık
verb_past	created	Geçmiş zaman fiil
5.2. Örnekler
✅ Doğru	❌ Yanlış
otc.sales_order.created	SalesOrderCreated
fin.ar_invoice.posted	invoice_posted
inv.stock.decreased	stock_move
hrm.payroll.processed	payroll_process
Kural: snake_case + geçmiş zaman.

6. Publishing — Event Yayınlama
6.1. 3 Publishing Model
Model	Ne Zaman	Risk
Sync	Anında çalışır	Yavaş, kırılgan
Async	Kuyruğa atar	Hızlı, eventual consistency
Outbox	DB'ye yaz + kuyruk	Güvenli, transactional
Bizim seçim: Outbox pattern (en güvenli).

6.2. Outbox Pattern
text
┌─────────────────────────────────────────────┐
│  DB Transaction                             │
│  1. Business data yaz (order)               │
│  2. Event'i event_outbox'a yaz              │
│  3. COMMIT                                  │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Background Worker                          │
│  1. event_outbox oku (yayınlanmamış)        │
│  2. Subscriber'lara gönder                  │
│  3. published = true                        │
└─────────────────────────────────────────────┘
Neden outbox?

Senaryo	Outbox Yok	Outbox Var
DB commit OK, event publish FAIL	Event kaybolur	Event outbox'ta (retry)
Event publish OK, DB commit FAIL	Event yanlış	Outbox atomik
Sunucu çöküyor	Event kaybolur	Outbox kalır
6.3. Event Publishing Kodu
php
// Servis katmanı
class SalesOrderService {
    public function create(array $data): OtcSalesOrder {
        return DB::transaction(function () use ($data) {
            $order = OtcSalesOrder::create($data);
            
            // Event'i outbox'a yaz (aynı transaction)
            event(new SalesOrderCreated($order));
            
            return $order;
        });
    }
}

// Laravel event listener → outbox
class OutboxSubscriber {
    public function handle($event): void {
        EventOutbox::create([
            'id' => Str::uuid(),
            'event_type' => get_class($event),
            'payload' => serialize($event),
            'tenant_id' => TenantContext::id(),
            'status' => 'pending',
            'created_at' => now(),
        ]);
    }
}
6.4. Outbox Worker
php
class ProcessOutbox implements ShouldQueue {
    public function handle(): void {
        $events = EventOutbox::where('status', 'pending')
            ->orderBy('created_at')
            ->limit(100)
            ->lockForUpdate()
            ->get();
        
        foreach ($events as $event) {
            try {
                $deserialized = unserialize($event->payload);
                
                // Bus'a gönder
                EventBus::dispatch($deserialized);
                
                $event->update(['status' => 'published', 'published_at' => now()]);
            } catch (\Throwable $e) {
                $event->increment('retry_count');
                
                if ($event->retry_count > 5) {
                    $event->update(['status' => 'dead_letter']);
                    Log::error("Event failed: {$event->id}", ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
Cron: Her 5 saniyede çalışır.

6.5. Sync vs Async Kararı
Event	Model	Neden
sales_order.created	Async	Hızlı cevap gerek
stock.decreased	Async	Eventual consistency OK
invoice.posted	Async	Bildirim sonra
payment.received	Sync	Anında stok güncelle
user.login	Sync	Audit anında
Kural: Kritik olanlar sync, gerisi async.

7. Subscribers — Event Dinleyiciler
7.1. Subscriber Tipleri
Tip	Kim	Nerede
Listener	Core modül	Kod içinde
Plugin	Developer	Sandbox'ta
Webhook	Dış sistem	HTTP POST
AI	AI service	Async
Custom	Tenant admin	Workflow/automation
7.2. Listener
php
// app/Listeners/Inv/DecreaseStockOnSale.php
class DecreaseStockOnSale {
    public function handle(SalesOrderCreated $event): void {
        foreach ($event->payload['items'] as $item) {
            app(StockService::class)->decrease(
                productId: $item['product_id'],
                qty: $item['qty'],
                reference: "order:{$event->aggregateId}",
            );
        }
    }
}

// app/Providers/EventServiceProvider.php
protected $listen = [
    SalesOrderCreated::class => [
        DecreaseStockOnSale::class,
        CreateInvoiceOnSale::class,
        NotifyOnSale::class,
    ],
];
7.3. Plugin Subscriber
Plugin, event bus'a kendi listener'ını kaydeder:

yaml
# plugin.json
{
    "subscribes": [
        { "event": "otc.sales_order.created", "handler": "WoltSync" },
        { "event": "inv.stock.below_minimum", "handler": "WoltAlert" }
    ]
}
Sandbox'ta çalışır, izole.

7.4. Webhook Subscriber
php
class WebhookSubscriber {
    public function handle($event): void {
        $webhooks = SysWebhook::where('tenant_id', $event->tenantId)
            ->whereJsonContains('event_types', $event->eventType)
            ->where('status', 'active')
            ->get();
        
        foreach ($webhooks as $webhook) {
            dispatch(new DeliverWebhook($webhook, $event));
        }
    }
}

class DeliverWebhook implements ShouldQueue {
    public function handle(): void {
        // HTTP POST to webhook.url
        // Signed payload (HMAC)
        // Retry on failure (exponential backoff)
    }
}
Webhook kaydı:

sql
CREATE TABLE sys_webhook (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NOT NULL,
    url             varchar(500) NOT NULL,
    event_types     text[] NOT NULL,
    secret          varchar(255) NOT NULL,           -- HMAC key
    status          varchar(20) DEFAULT 'active',
    retry_count     int DEFAULT 3,
    created_at      timestamptz,
    updated_at      timestamptz
);
7.5. AI Subscriber
php
class AnomalyDetector {
    public function handle($event): void {
        // Feature extraction
        $features = $this->extract($event);
        
        // Model prediction
        $score = app(AIService::class)->predict('anomaly', $features);
        
        if ($score > 0.8) {
            event(new AnomalyDetected($event, $score));
        }
    }
}
7.6. Custom Automation Subscriber
Tenant admin kendi kuralını tanımlar:

yaml
automation:
  trigger: "inv.stock.below_minimum"
  condition: "product.category == 'A'"
  action:
    - "notify.role: warehouse_manager"
    - "create.purchase_order"
Kural: Kod yazmadan otomasyon.

8. Event Store — Event Kalıcılığı
8.1. Neden Event Store?
Audit — Her olay kalıcı

Replay — Yeniden işlenebilir

Analytics — Olay analizi

Debug — Ne oldu, ne zaman

Compliance — Yasal saklama (7 yıl)

8.2. Event Store Schema
sql
CREATE TABLE event_store (
    id              uuid PRIMARY KEY,
    event_type      varchar(120) NOT NULL,
    aggregate_type  varchar(80) NOT NULL,
    aggregate_id    uuid NOT NULL,
    tenant_id       uuid NOT NULL,
    user_id         bigint NULL,
    layer_id        uuid NULL,
    payload         jsonb NOT NULL,
    metadata        jsonb NOT NULL DEFAULT '{}',
    visibility      varchar(20) NOT NULL DEFAULT 'tenant_only',
    occurred_at     timestamptz NOT NULL,
    version         int NOT NULL DEFAULT 1,
    created_at      timestamptz NOT NULL DEFAULT now()
);

-- Index'ler
CREATE INDEX idx_event_tenant_time ON event_store(tenant_id, occurred_at DESC);
CREATE INDEX idx_event_type_time ON event_store(event_type, occurred_at DESC);
CREATE INDEX idx_event_aggregate ON event_store(aggregate_type, aggregate_id);
CREATE INDEX idx_event_user ON event_store(user_id, occurred_at DESC) WHERE user_id IS NOT NULL;

-- RLS
ALTER TABLE event_store ENABLE ROW LEVEL SECURITY;
ALTER TABLE event_store FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON event_store
    USING (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    )
    WITH CHECK (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    );
8.3. Retention
Event Tipi	Saklama
system.*	1 yıl
fin.*	7 yıl (yasal)
otc.*	7 yıl
inv.*	3 yıl
meta.*	2 yıl
integration.*	1 yıl
audit.*	7 yıl
Retention sonrası: Arşiv (S3, cold storage).

8.4. Replay — Yeniden Oynatma
Senaryo 1: Bug var, 3 gün önceki event'leri yeniden işle.

php
class ReplayEvents {
    public function replay(string $eventType, \DateTime $from, \DateTime $to): void {
        $events = EventStore::where('event_type', $eventType)
            ->whereBetween('occurred_at', [$from, $to])
            ->orderBy('occurred_at')
            ->get();
        
        foreach ($events as $eventRecord) {
            $event = $this->reconstruct($eventRecord);
            
            // Publish again (yeni instance, aynı event)
            event($event);
        }
    }
}
Kural: Replay idempotent olmalı. Aynı event 2 kez işlense sonuç aynı olmalı.

Idempotency key: event_id + subscriber_name → daha önce işlendiyse skip.

8.5. Snapshot
Büyük aggregate'ler için:

sql
CREATE TABLE event_snapshot (
    aggregate_type  varchar(80),
    aggregate_id    uuid,
    snapshot_data   jsonb,          -- o andaki state
    version         int,
    created_at      timestamptz,
    PRIMARY KEY (aggregate_type, aggregate_id)
);
Kullanım: Aggregate'i yeniden inşa etmek için snapshot + sonraki event'ler.

9. Cross-Tenant Visibility — Owner Audit
9.1. Owner Her Şeyi Görür mü?
Evet — ama filtreli.

Owner (superadmin) tüm event'leri görebilir. Ama 3 katman vardır:

Katman	Owner görür	PII
Meta	Özet (sayı, oran)	Yok
Data	Entity detayları	Filtreli
Raw	Her şey	Talep + onay ile
9.2. Visibility Level
Her event'in visibility alanı vardır:

Level	Anlam	Örnek
tenant_only	Sadece tenant görür	hrm.employee.salary_viewed
owner_metadata	Owner sadece meta görür	otc.sales_order.created (müşteri PII yok)
owner_full	Owner her şey görür	system.user.login (audit)
9.3. PII Redaction
Event payload'da PII varsa otomatik redact:

php
class PIIRedactor {
    private array $piiFields = [
        'email', 'phone', 'tax_no', 'tc_kimlik', 'iban',
        'address', 'passport', 'credit_card', 'ssn',
    ];
    
    public function redact(array $payload): array {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->redact($value);
            } elseif (in_array(strtolower($key), $this->piiFields)) {
                $payload[$key] = '***REDACTED***';
            }
        }
        return $payload;
    }
}
Event stored: PII şifreli saklanır. Owner sadece şifre çözme talebi + onay ile görebilir.

9.4. Owner Audit Dashboard
Erişim: /owner/audit (sadece superadmin)

Görünüm:

text
┌──────────────────────────────────────────────────────────┐
│  Owner Audit Dashboard                                   │
├──────────────────────────────────────────────────────────┤
│  🔍 Ara... [Tenant ▾] [Event Type ▾] [Date ▾]           │
├──────────────────────────────────────────────────────────┤
│  Son 24 saat:                                            │
│  ─────────────────────────────────────────────           │
│  📊 Toplam event: 1.234.567                              │
│  🏢 Aktif tenant: 342                                    │
│  ⚠️ Anomali: 12                                          │
│  ❌ Hata: 45                                             │
│                                                          │
│  🔥 Top Event Types                                      │
│  ─────────────────────────────────────────────           │
│  1. otc.sales_order.created: 45.678                      │
│  2. fin.ar_invoice.posted: 23.456                        │
│  3. inv.stock.moved: 18.901                              │
│                                                          │
│  🏢 Top Tenants                                          │
│  ─────────────────────────────────────────────           │
│  1. Acme Corp: 234.567 events (23%)                      │
│  2. XYZ Ltd: 123.456 events (12%)                        │
│                                                          │
│  ⚠️ Anomalies                                            │
│  ─────────────────────────────────────────────           │
│  - Tenant A: 10x normal error rate (last hour)           │
│  - Tenant B: unexpected large volume (1M events)         │
│  - Tenant C: 5 login attempts from unusual geo           │
└──────────────────────────────────────────────────────────┘
9.5. Meta Event'ler — Otomatik Tespit
Sistem, anomaliyi otomatik tespit eder ve meta event yayınlar:

php
class AnomalyDetector {
    public function detect(): void {
        // 1. Error rate spike
        $errorRate = EventStore::where('event_type', 'like', '%.error.%')
            ->where('occurred_at', '>', now()->subHour())
            ->count() / EventStore::where('occurred_at', '>', now()->subHour())->count();
        
        if ($errorRate > 0.05) { // %5 üstü
            event(new ErrorRateHigh($errorRate));
        }
        
        // 2. Volume spike
        $volume = EventStore::where('tenant_id', $tenantId)
            ->where('occurred_at', '>', now()->subHour())
            ->count();
        
        if ($volume > $baseline * 10) {
            event(new UsageSpike($tenantId, $volume));
        }
        
        // 3. Suspicious login
        $logins = EventStore::where('event_type', 'system.user.login')
            ->where('occurred_at', '>', now()->subMinutes(10))
            ->get()
            ->groupBy('metadata.ip')
            ->filter(fn($group) => $group->count() > 5);
        
        if ($logins->isNotEmpty()) {
            event(new SuspiciousLoginActivity($logins));
        }
    }
}
Owner'a meta.anomaly.detected event olarak gider.

9.6. Audit on Audit
Kritik: Owner'ın her erişimi de log'lanır.

php
class OwnerAccessLogger {
    public function log(string $action, string $target, array $data = []): void {
        EventStore::create([
            'event_type' => "owner.access.{$action}",
            'aggregate_type' => $target,
            'aggregate_id' => $data['id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'user_id' => auth()->id(),
            'payload' => [
                'action' => $action,
                'target' => $target,
                'reason' => $data['reason'] ?? null,
                'ip' => request()->ip(),
            ],
            'visibility' => 'owner_full',
            'occurred_at' => now(),
        ]);
    }
}
Kural: Owner bile audit'lenir. Şeffaflık.

9.7. Privacy Filter
Event	Owner görür	Filtre
fin.customer.created	✅	Email/TC maskeli
hrm.employee.hired	✅	Maaş maskeli
otc.sales_order.created	✅	Müşteri adı maskeli
fin.payment.received	✅	Kart no maskeli
system.user.login	✅	IP maskeli (geo için)
hrm.employee.salary_viewed	❌	tenant_only
otc.pricing.negotiated	❌	tenant_only
Kural: Owner operasyonel veriyi görür, ticari gizli veriyi görmez.

9.8. Owner'ın Talep + Onay İle Görme
Belirli durumlarda (yasal talep, hukuki süreç) owner raw data görebilir:

text
1. Owner talep oluşturur (reason + ticket)
2. Başka bir owner/admin onaylar
3. Talep log'lanır
4. Erişim 24 saatlik
5. Her erişim log'lanır
Neden? İki kişi kuralı (four-eyes principle) — kötüye kullanımı engeller.

10. Event Bus Implementation
10.1. Mevcut Laravel Event
Laravel'in Event::listen() mekanizması var. Yeterli değil çünkü:

❌ Outbox yok

❌ Replay yok

❌ Cross-tenant yok

❌ Persistence yok

❌ Plugin subscribe yok

10.2. Event Bus Sınıfı
php
class EventBus {
    public function publish(DomainEvent $event): void {
        // 1. Store (kalıcı)
        $this->store($event);
        
        // 2. Sync listeners (anında)
        $this->dispatchSync($event);
        
        // 3. Async listeners (kuyruk)
        $this->dispatchAsync($event);
        
        // 4. Webhooks (async)
        $this->dispatchWebhooks($event);
        
        // 5. Plugins (sandbox)
        $this->dispatchPlugins($event);
        
        // 6. AI (async)
        $this->dispatchAI($event);
    }
    
    private function dispatchSync(DomainEvent $event): void {
        foreach ($this->getListeners($event, 'sync') as $listener) {
            app($listener)->handle($event);
        }
    }
    
    private function dispatchAsync(DomainEvent $event): void {
        foreach ($this->getListeners($event, 'async') as $listener) {
            dispatch(new ProcessEvent($event, $listener));
        }
    }
    
    private function dispatchWebhooks(DomainEvent $event): void {
        // Webhook subscriber
    }
    
    private function dispatchPlugins(DomainEvent $event): void {
        // Plugin sandbox dispatch
    }
    
    private function dispatchAI(DomainEvent $event): void {
        // AI async
    }
}
10.3. Subscriber Registry
php
class SubscriberRegistry {
    private array $subscribers = [];
    
    public function register(string $eventType, string $handler, string $mode = 'async'): void {
        $this->subscribers[$eventType][] = [
            'handler' => $handler,
            'mode' => $mode,
        ];
    }
    
    public function get(string $eventType): array {
        return $this->subscribers[$eventType] ?? [];
    }
}
Kayıt noktaları:

EventServiceProvider (core)

plugin.json (plugins)

sys_webhook (tenant)

sys_automation (tenant custom)

10.4. Dead Letter Queue
5 kez başarısız olan event → dead letter:

sql
CREATE TABLE event_dead_letter (
    id              uuid PRIMARY KEY,
    event_id        uuid NOT NULL,
    subscriber      varchar(255) NOT NULL,
    error           text,
    attempts        int,
    last_error_at   timestamptz,
    resolved_at     timestamptz NULL,
    created_at      timestamptz
);
Owner dashboard'da görünür. Manuel retry.

11. Performance
11.1. Event Volume
Ölçek	Event/gün	Strateji
SMB	10K	Tek DB, tek worker
Mid-market	1M	Partitioned table, multiple workers
Enterprise	100M	Sharded, event streaming (Kafka)
11.2. Async Worker
bash
# Supervisor config
[program:event-worker]
command=php artisan queue:work --queue=events --tries=3 --timeout=60
numprocs=4
process_name=%(program_name)s_%(process_num)02d
Multiple worker = paralel işleme.

11.3. Partitioning
sql
CREATE TABLE event_store (
    ...
) PARTITION BY RANGE (occurred_at);

CREATE TABLE event_store_2026_11 PARTITION OF event_store
    FOR VALUES FROM ('2026-11-01') TO ('2026-12-01');
Avantaj: Eski partition'lar kolay silinir (retention).

11.4. Idempotency
php
class ProcessEvent implements ShouldQueue {
    public function handle(): void {
        // Idempotency key
        $key = "{$this->event->eventId}:{$this->listener}";
        
        if (Cache::has($key)) return; // zaten işlendi
        
        try {
            app($this->listener)->handle($this->event);
            Cache::put($key, true, now()->addDay());
        } catch (\Throwable $e) {
            Cache::forget($key);
            throw $e;
        }
    }
}
Kural: Her handler idempotent olmalı.

12. Mevcut Sistemden Geçiş
12.1. Mevcut Durum
✅ Laravel event kullanılıyor (event(new ...))

✅ Bazı listener'lar var

❌ Outbox yok

❌ Event store yok

❌ Cross-tenant audit yok

❌ Plugin/Webhook subscriber yok

12.2. Geçiş Aşamaları
Aşama 1 (4 hafta): Event store + outbox

event_store, event_outbox tabloları

EventBus::publish()

Mevcut event() çağrıları → EventBus::publish() (geçiş)

Aşama 2 (4 hafta): Async worker + dead letter

Queue worker

event_dead_letter

Monitoring

Aşama 3 (4 hafta): Cross-tenant audit

Visibility level

PII redaction

Owner dashboard

Aşama 4 (4 hafta): Webhook + plugin

sys_webhook

Webhook delivery

Plugin subscriber registry

Aşama 5 (4 hafta): AI + automation

AI subscriber

Automation engine

Toplam: 20 hafta (~5 ay).

12.3. Backward Compatibility
Mevcut event() çağrıları kırılmaz:

php
// Eski kod (çalışır)
event(new SalesOrderCreated($order));

// Laravel listener → EventBus'a forward
class ForwardToEventBus {
    public function handle($event): void {
        if ($event instanceof DomainEvent) {
            app(EventBus::class)->publish($event);
        }
    }
}
Kural: Yeni kod EventBus::publish(), eski kod event() — ikisi de çalışır.

13. Restoran Örneği — Event Flow
Senaryo: Müşteri masadan sipariş veriyor.

text
1. Waiter tablet: "Masa 5, 2 Adet Burger Menü"
   → API: POST /api/custom/order
   → Service: OrderService::create()
   → DB: custom_order created
   → Event: custom.order.created (outbox)

2. Outbox worker → EventBus::publish()

3. Sync listeners:
   - KitchenService: KDS'ye düşür (anında)
   
4. Async listeners:
   - InventoryService: stok düş
   - NotificationService: "Yeni sipariş" (waiter'a)
   - FinanceService: revenue kaydı
   - PrintService: fiş yazdır
   
5. Webhooks:
   - Tenant's kitchen display system
   - Wolt integration (dış)
   
6. AI:
   - Anomaly detection: "Masa 5, normal 3x sipariş"
   
7. Event stored:
   - type: custom.order.created
   - tenant: tesfirma-restoran
   - visibility: owner_metadata
   - payload: { order_id, table: 5, items: [...], total: 300 }

8. Owner dashboard (real-time):
   - Tenant: restoran-chain
   - Event: 1 sipariş
   - Anomali: 0
Sonuç: 1 API çağrısı → 6+ işlem, hepsi event-driven, hepsi audit'li, hepsi owner-visible.

14. Anti-Patterns
Bu projede yasak olan event hataları:

Event içinde entity — Sadece ID + snapshot

Sync event içinde async iş — Kuyruğa at

Event içinde iş mantığı — Listener'da

PII event'te filtresiz — Redaction zorunlu

Idempotency'siz handler — Replay kırar

Outbox'suz publish — Event kaybolabilir

God event (her şeyi taşır) — Domain-specific event

Event naming yanlış — snake_case + geçmiş zaman

Sınırsız retry — Max 5, sonra dead letter

Replay sırasında prod DB — Sandbox'ta test

15. Glossary
Terim	Anlam
Event	Sistemde olmuş olay
Domain Event	İş olayı (order created)
Integration Event	Dış sistem olayı
System Event	Platform olayı
Meta Event	Owner için özet olay
Event Bus	Event yayınlama mekanizması
Outbox	Güvenli publish (transactional)
Subscriber	Event dinleyici
Listener	Kod içi subscriber
Webhook	HTTP subscriber
Event Store	Kalıcı event depolama
Replay	Yeniden işleme
Idempotency	Tekrar işleme güvenliği
Dead Letter	Başarısız event kuyruğu
Visibility	Owner görme seviyesi
PII Redaction	Kişisel veri maskeleme
Owner Audit	Cross-tenant denetim
16. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — event bus tasarımı + cross-tenant audit