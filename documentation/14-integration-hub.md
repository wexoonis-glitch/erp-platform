# 14 — Integration Hub

**Project:** Kurumsal ERP Platform
**Belge:** Dış Sistem Entegrasyonları, Connector Framework, Sync Engine, Webhook System
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md, 11-workflow-engine.md, 12-report-builder.md, 13-ui-builder.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **Integration Hub** sistemini tanımlar — dış sistemlerle (banka, e-fatura, kargo, e-ticaret, ödeme, muhasebe, CRM, marketplace) **esnek, izlenebilir, güvenli** entegrasyon. İçerir:

- Neden Integration Hub
- 4 entegrasyon modeli (REST, Webhook, File, Event Stream)
- Connector architecture
- Sync engine (real-time, batch, scheduled)
- Field mapping (transform, convert, validate)
- Authentication (OAuth2, API Key, mTLS)
- Rate limiting, retry, circuit breaker
- Monitoring, logging, alerting
- Türkiye spesifik entegrasyonlar (banka, GİB, kargo, e-ticaret)
- Restoran örneği (Wolt, Trendyol, Getir)
- Anti-patterns, roadmap

**Hedef kitle:** Geliştiriciler, AI asistanlar, entegrasyon mimarları, partner geliştiricileri.

**Kritik:** Integration Hub, **"entegrasyon 1 gün"** prensibinin (Belge 00) teknik altyapısı. Her yeni dış sistem = **connector config**, kod değil.

---

## 2. Neden Integration Hub?

### 2.1. Gerçek Dünya — Entegrasyon Sayısı

Bir ERP'nin bağlanması gereken dış sistemler:

| Kategori | Örnekler | TR Sayısı |
|---|---|---|
| **Banka** | İş Bankası, Garanti, Akbank, Ziraat, Yapı Kredi | 30+ |
| **E-Fatura (GİB)** | Uyumsoft, Foriba, Digital Planet, İzibiz | 10+ |
| **E-Ticaret** | Trendyol, Hepsiburada, N11, Amazon TR, Çiçeksepeti | 20+ |
| **Kargo** | Aras, Yurtiçi, MNG, PTT, Sürat, UPS, DHL | 15+ |
| **Ödeme** | iyzico, PayTR, Param, Stripe, PayPal | 10+ |
| **Muhasebe** | Logo, Mikro, Netsis, Paraşüt | 5+ |
| **Marketplace** | Wolt, Getir, Yemeksepeti, Trendyol Yemek | 10+ |
| **Mesaj** | Slack, WhatsApp, Telegram, SMS | 5+ |
| **CRM** | HubSpot, Salesforce, Zoho | 3+ |
| **AI** | OpenAI, Anthropic, Google Vision | 5+ |

**Toplam:** 100+ entegrasyon.

### 2.2. Klasik ERP'nin Sorunu

| ERP | Entegrasyon Yaklaşımı | Sorun |
|---|---|---|
| **SAP** | SAP PI/PO | Ağır, danışman gerek |
| **Odoo** | Python modül | Kod gerek |
| **Logo** | Özel geliştirme | Her entegrasyon 2-6 ay |
| **NetSuite** | SuiteScript | Kod + danışman |

**Ortak sorun:** Yeni entegrasyon = **haftalar/aylar**.

### 2.3. Bizim Çözüm

**Connector framework** — her yeni dış sistem = **config + plugin**, kod değil.
Yeni banka eklemek = 1 connector config (JSON)
Yeni pazaryeri = 1 plugin (Belge 10)
Yeni GİB entegratör = 1 connector + field mapping

text

**Hedef:** Yeni entegrasyon = **1 gün** (Belge 00).

---

## 3. 4 Entegrasyon Modeli

### 3.1. REST API (Outbound + Inbound)

**Ne:** HTTP üzerinden JSON/XML.

**Kullanım:**
- Kargo oluşturma (Aras, Yurtiçi)
- E-fatura gönderme (GİB)
- Ödeme (iyzico, Stripe)
- Marketplace sipariş çekme (Trendyol, Wolt)

**Yön:** Bidirectional (outbound request + inbound webhook).

### 3.2. Webhook (Inbound + Outbound)

**Ne:** Event bazlı HTTP POST.

**Kullanım:**
- **Inbound:** Wolt sipariş → ERP (biz endpoint)
- **Outbound:** ERP event → müşteri webhook (Belge 08)

**Yön:** Bidirectional.

### 3.3. File (SFTP / S3 / FTP)

**Ne:** Dosya transfer.

**Kullanım:**
- Banka ekstresi (MT940, CSV)
- GİB e-defter (XML)
- E-fatura toplu (XML)
- Kargo toplu etiket (PDF)

**Yön:** Bidirectional.

### 3.4. Event Stream (Kafka / RabbitMQ / Redis)

**Ne:** Mesaj kuyruğu.

**Kullanım:**
- Real-time sync (yüksek hacim)
- Event streaming (Belge 08)
- IoT (üretim, sensör)

**Yön:** Bidirectional (genelde outbound).

---

## 4. Connector Architecture

### 4.1. Connector Nedir?

**Connector** = bir dış sistemle konuşma **tanımı** (config + logic). 3 tür:

| Tür | Kim yazar | Örnek |
|---|---|---|
| **Built-in** | Platform (biz) | Banka, GİB, Kargo |
| **Partner** | Onaylı partner | Dikey entegrasyon |
| **Custom** | Tenant admin / developer | Özel sistem |

### 4.2. Connector Yapısı

```yaml
connector:
  # ═══════════════ KİMLİK ═══════════════
  key: "aras-cargo"
  name: "Aras Kargo"
  version: "1.0.0"
  category: "logistics"
  provider: "aras"
  
  # ═══════════════ TİP ═══════════════
  type: "rest"                          # rest | soap | file | stream | graphql
  protocol: "https"
  base_url: "https://api.araskargo.com.tr/v1"
  
  # ═══════════════ AUTH ═══════════════
  auth:
    type: "oauth2"                      # api_key | oauth2 | basic | mtls
    token_url: "https://api.araskargo.com.tr/oauth/token"
    client_id_field: "aras_client_id"
    client_secret_field: "aras_client_secret"
    scope: "shipment.create"
  
  # ═══════════════ OPERASYONLAR ═══════════════
  operations:
    - key: "create_shipment"
      method: "POST"
      endpoint: "/shipments"
      
      # ═══ INPUT MAPPING ═══
      input:
        receiver_name: "${order.customer.name}"
        receiver_phone: "${order.customer.phone}"
        receiver_address: "${order.customer.address}"
        receiver_city: "${order.customer.city}"
        receiver_district: "${order.customer.district}"
        weight: "${order.total_weight}"
        pieces: "${order.line_count}"
        reference: "${order.number}"
        payment_type: "${order.payment_type}"  # prepaid | cod
        cod_amount: "${order.cod_amount}"
      
      # ═══ TRANSFORMATIONS ═══
      transform:
        - field: "receiver_phone"
          type: "format"
          rule: "digits_only"
        
        - field: "weight"
          type: "convert"
          from: "kg"
          to: "gram"
          multiply: 1000
      
      # ═══ OUTPUT MAPPING ═══
      output:
        tracking_number: "data.trackingNo"
        shipment_id: "data.shipmentId"
        label_url: "data.labelUrl"
      
      # ═══ SIDEEFFECT ═══
      side_effects:
        - action: "update_entity"
          entity: "log_shipment"
          data:
            tracking_number: "${output.tracking_number}"
            carrier: "aras"
            status: "created"
        
        - action: "event"
          event: "shipment.created"
          payload:
            shipment_id: "${output.shipment_id}"
            tracking_number: "${output.tracking_number}"
    
    - key: "track_shipment"
      method: "GET"
      endpoint: "/tracking/{tracking_number}"
      input:
        tracking_number: "${shipment.tracking_number}"
      output:
        status: "data.status"
        last_update: "data.lastUpdate"
        location: "data.location"
    
    - key: "cancel_shipment"
      method: "DELETE"
      endpoint: "/shipments/{shipment_id}"
      input:
        shipment_id: "${shipment.shipment_id}"
  
  # ═══════════════ WEBHOOK (INBOUND) ═══════════════
  webhooks:
    - key: "shipment_status_changed"
      endpoint: "/webhooks/aras/status"  # bizim URL
      events:
        - "shipment.delivered"
        - "shipment.returned"
        - "shipment.failed"
      signature:
        header: "X-Aras-Signature"
        algorithm: "hmac-sha256"
        secret_field: "aras_webhook_secret"
  
  # ═══════════════ RATE LIMIT ═══════════════
  rate_limit:
    requests_per_second: 5
    requests_per_day: 10000
    burst: 10
  
  # ═══════════════ RETRY ═══════════════
  retry:
    max_attempts: 3
    backoff: "exponential"
    retry_on: [429, 500, 502, 503, 504]
  
  # ═══════════════ SETTINGS ═══════════════
  settings:
    - { key: "aras_client_id", type: "string", required: true, secret: true }
    - { key: "aras_client_secret", type: "string", required: true, secret: true }
    - { key: "aras_webhook_secret", type: "string", required: false, secret: true }
    - { key: "default_payment_type", type: "enum", options: ["prepaid", "cod"], default: "prepaid" }
    - { key: "default_weight_unit", type: "enum", options: ["kg", "g"], default: "kg" }
4.3. Connector Registry
sql
CREATE TABLE intg_connector (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NULL REFERENCES tenants(id) ON DELETE CASCADE,  -- NULL = built-in
    key             varchar(120) NOT NULL,
    name            varchar(255) NOT NULL,
    version         varchar(20) NOT NULL,
    category        varchar(50) NOT NULL,
    provider        varchar(100),
    definition      jsonb NOT NULL,
    status          varchar(20) DEFAULT 'active',
    created_at      timestamptz,
    updated_at      timestamptz,
    UNIQUE(tenant_id, key)
);

CREATE TABLE intg_connection (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NOT NULL,
    connector_id    uuid NOT NULL,
    name            varchar(255),
    settings        jsonb NOT NULL,      -- encrypted secrets
    status          varchar(20) DEFAULT 'active',
    last_sync_at    timestamptz,
    last_error_at   timestamptz,
    last_error      text,
    created_by      bigint,
    created_at      timestamptz,
    updated_at      timestamptz
);
Kural: Secrets encrypted (Laravel Crypt::encrypt).

5. Sync Engine
5.1. 3 Sync Modu
Mod	Trigger	Kullanım
Real-time	Event / Webhook	Sipariş, ödeme
Batch	Cron (saatlik, günlük)	Banka ekstresi, e-fatura
Scheduled	Belirli zaman	Rapor gönderimi
5.2. Real-time Sync
php
class RealTimeSync {
    public function handle(SalesOrderCreated $event): void {
        $connection = IntgConnection::where('tenant_id', $event->tenantId)
            ->where('connector_key', 'wolt')
            ->where('status', 'active')
            ->first();
        
        if (!$connection) return;
        
        $connector = app(ConnectorLoader::class)->load($connection);
        
        try {
            $result = $connector->execute('create_order', [
                'order' => $event->payload,
            ]);
            
            IntgSyncLog::create([
                'connection_id' => $connection->id,
                'operation' => 'create_order',
                'status' => 'success',
                'input' => $event->payload,
                'output' => $result,
                'duration_ms' => $connector->getDuration(),
            ]);
        } catch (\Throwable $e) {
            IntgSyncLog::create([
                'connection_id' => $connection->id,
                'operation' => 'create_order',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            
            // Retry queue
            dispatch(new RetrySync($connection, 'create_order', $event->payload))
                ->delay(now()->addMinutes(5));
        }
    }
}
5.3. Batch Sync
php
class BatchSync implements ShouldQueue {
    public function handle(): void {
        $connections = IntgConnection::whereHas('connector', function ($q) {
            $q->where('definition->sync_type', 'batch');
        })->where('status', 'active')->get();
        
        foreach ($connections as $conn) {
            dispatch(new BatchSyncConnection($conn));
        }
    }
}

class BatchSyncConnection implements ShouldQueue {
    public function handle(): void {
        $connector = app(ConnectorLoader::class)->load($this->connection);
        $operations = $connector->batchOperations();  // config'den
        
        foreach ($operations as $op) {
            try {
                $items = $connector->fetch($op['source']);
                
                foreach ($items as $item) {
                    $connector->execute($op['target'], ['item' => $item]);
                }
                
                $this->connection->update(['last_sync_at' => now()]);
            } catch (\Throwable $e) {
                Log::error("Batch sync failed", ['conn' => $this->connection->id, 'op' => $op['key'], 'error' => $e->getMessage()]);
            }
        }
    }
}
5.4. Scheduled Sync
yaml
scheduled_sync:
  - connector: "bank-isbank"
    operation: "fetch_statements"
    cron: "0 8 * * *"                   # her sabah 8:00
    params:
      since: "yesterday"
  
  - connector: "gib-efatura"
    operation: "query_status"
    cron: "*/15 * * * *"                # her 15 dk
5.5. Sync State Machine
text
pending → processing → success
                    ↘ failed → retry → (success | dead_letter)
php
class SyncStateMachine {
    public function transition(IntgSyncLog $log, string $to): void {
        match ($to) {
            'processing' => $log->update(['status' => 'processing', 'started_at' => now()]),
            'success'    => $log->update(['status' => 'success', 'completed_at' => now()]),
            'failed'     => $log->update(['status' => 'failed', 'error' => $this->error]),
            'retry'      => $this->scheduleRetry($log),
            'dead_letter' => $this->moveToDeadLetter($log),
        };
    }
}
6. Field Mapping
6.1. Basit Mapping
yaml
input:
  receiver_name: "${order.customer.name}"
  receiver_phone: "${order.customer.phone}"
Kural: ${path.to.field} → nested erişim.

6.2. Transformasyon Tipleri
Type	Örnek
format	Telefon: digits_only
convert	Ağırlık: kg → gram
uppercase	Büyük harf
lowercase	Küçük harf
trim	Boşluk temizle
date_format	06.11.2026 → 2026-11-06
currency	1000 TRY → 1000.00
enum_map	active → A
concat	first_name + ' ' + last_name
split	full_name → [first, last]
regex_replace	Regex değiştir
hash	SHA-256
default	Boşsa default
lookup	Başka tablodan bak
6.3. Complex Mapping
yaml
input:
  # Basit
  receiver_name: "${order.customer.name}"
  
  # Transform
  receiver_phone:
    source: "${order.customer.phone}"
    transform: ["digits_only", "prefix_plus90"]
  
  # Conditional
  payment_type:
    if: "${order.payment === 'cash_on_delivery'}"
    then: "cod"
    else: "prepaid"
  
  # Computed
  total_weight:
    expression: "SUM(order.items.map(i => i.weight * i.qty))"
  
  # Lookup
  city_code:
    lookup:
      source: "${order.customer.city}"
      table: "tr_cities"
      key: "name"
      value: "code"
  
  # Default
  currency:
    source: "${order.currency}"
    default: "TRY"
6.4. Output Mapping
yaml
output:
  tracking_number: "data.trackingNo"
  shipment_id: "data.shipmentId"
  label_url: "data.labelUrl"
  
  # Transform
  status:
    source: "data.status"
    transform:
      enum_map:
        "created": "pending"
        "in_transit": "shipped"
        "delivered": "delivered"
        "failed": "failed"
6.5. Reverse Mapping (Inbound)
yaml
reverse_mapping:
  # Gelen webhook → entity
  - webhook_field: "shipment_status"
    entity_field: "status"
    transform:
      enum_map:
        "delivered": "delivered"
        "returned": "returned"
7. Authentication
7.1. Auth Tipleri
Tip	Kullanım	Örnek
API Key	Basit auth	Trendyol, Wolt
OAuth2	Token-based	Google, Aras
Basic Auth	Kullanıcı/şifre	Eski API'ler
mTLS	Sertifika	Banka
JWT	Token	Modern API'ler
HMAC	Signature	Webhook
7.2. OAuth2 Flow
yaml
auth:
  type: "oauth2"
  flow: "client_credentials"           # client_credentials | authorization_code
  token_url: "https://api.example.com/oauth/token"
  client_id_field: "oauth_client_id"
  client_secret_field: "oauth_client_secret"
  scope: "read write"
  refresh_before: 300                   # 5 dk önce yenile
Runtime:

php
class OAuth2Authenticator {
    public function authenticate(IntgConnection $conn): array {
        $cached = Cache::get("oauth_token:{$conn->id}");
        if ($cached) return $cached;
        
        $response = Http::asForm()->post($conn->connector->auth['token_url'], [
            'grant_type' => 'client_credentials',
            'client_id' => decrypt($conn->settings['oauth_client_id']),
            'client_secret' => decrypt($conn->settings['oauth_client_secret']),
            'scope' => $conn->connector->auth['scope'],
        ]);
        
        $token = [
            'access_token' => $response->json('access_token'),
            'expires_at' => now()->addSeconds($response->json('expires_in') - 300),
        ];
        
        Cache::put("oauth_token:{$conn->id}", $token, $token['expires_at']);
        
        return $token;
    }
}
7.3. Webhook Signature
Inbound webhook — signature doğrulama:

php
class WebhookSignatureVerifier {
    public function verify(Request $request, IntgConnection $conn): bool {
        $secret = decrypt($conn->settings['webhook_secret']);
        $payload = $request->getContent();
        $signature = $request->header('X-Signature');
        
        $expected = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expected, $signature);
    }
}
Kural: Signature invalid → 401 + audit log.

7.4. Secret Management
php
class SecretManager {
    public function store(IntgConnection $conn, string $key, string $value): void {
        $settings = $conn->settings;
        $settings[$key] = Crypt::encrypt($value);
        $conn->update(['settings' => $settings]);
    }
    
    public function get(IntgConnection $conn, string $key): ?string {
        $encrypted = $conn->settings[$key] ?? null;
        return $encrypted ? Crypt::decrypt($encrypted) : null;
    }
}
Kural: Secrets asla plain text DB'de.

8. Rate Limiting & Retry
8.1. Rate Limit
yaml
rate_limit:
  requests_per_second: 5
  requests_per_minute: 300
  requests_per_day: 10000
  burst: 10
Uygulama:

php
class RateLimiter {
    public function acquire(IntgConnection $conn): void {
        $limit = $conn->connector->definition['rate_limit'];
        $key = "intg:{$conn->id}:{$limit['requests_per_second']}s";
        
        $current = Cache::get($key, 0);
        if ($current >= $limit['requests_per_second']) {
            throw new RateLimitExceededException();
        }
        
        Cache::put($key, $current + 1, now()->addSecond());
    }
}
Backoff: Rate limit aşılırsa → exponential backoff (1s, 2s, 4s, 8s...).

8.2. Retry Strategy
yaml
retry:
  max_attempts: 3
  backoff: "exponential"                # fixed | linear | exponential
  retry_on: [429, 500, 502, 503, 504]   # status kodları
  retry_after_header: true              # Retry-After header kullan
Uygulama:

php
class RetryManager {
    public function handle(IntgSyncLog $log, \Throwable $e): void {
        $attempt = $log->retry_count + 1;
        $max = $log->connector->definition['retry']['max_attempts'];
        
        if ($attempt > $max) {
            $this->deadLetter($log, $e);
            return;
        }
        
        $delay = match ($log->connector->definition['retry']['backoff']) {
            'fixed' => 60,
            'linear' => 60 * $attempt,
            'exponential' => 60 * pow(2, $attempt - 1),
        };
        
        $log->update(['status' => 'retry', 'retry_count' => $attempt, 'next_retry_at' => now()->addSeconds($delay)]);
        
        dispatch(new RetrySyncLog($log))->delay(now()->addSeconds($delay));
    }
}
8.3. Circuit Breaker
Problem: Dış sistem down → sürekli retry → kaynak israfı.

Çözüm: Circuit breaker.

text
Closed → (X failure) → Open → (wait) → Half-Open → (success) → Closed
php
class CircuitBreaker {
    private const FAILURE_THRESHOLD = 5;
    private const TIMEOUT = 60;
    
    public function call(IntgConnection $conn, callable $fn): mixed {
        $state = Cache::get("circuit:{$conn->id}:state", 'closed');
        
        if ($state === 'open') {
            $openedAt = Cache::get("circuit:{$conn->id}:opened_at");
            if (now()->diffInSeconds($openedAt) < self::TIMEOUT) {
                throw new CircuitOpenException();
            }
            $state = 'half-open';
        }
        
        try {
            $result = $fn();
            
            if ($state === 'half-open') {
                Cache::put("circuit:{$conn->id}:state", 'closed');
            }
            
            Cache::forget("circuit:{$conn->id}:failures");
            return $result;
        } catch (\Throwable $e) {
            $failures = Cache::increment("circuit:{$conn->id}:failures");
            
            if ($failures >= self::FAILURE_THRESHOLD) {
                Cache::put("circuit:{$conn->id}:state", 'open');
                Cache::put("circuit:{$conn->id}:opened_at", now());
            }
            
            throw $e;
        }
    }
}
9. Monitoring & Logging
9.1. Sync Log
sql
CREATE TABLE intg_sync_log (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NOT NULL,
    connection_id   uuid NOT NULL REFERENCES intg_connection(id) ON DELETE CASCADE,
    operation       varchar(120) NOT NULL,
    direction       varchar(20),        -- outbound | inbound
    status          varchar(20),        -- pending | processing | success | failed | retry | dead_letter
    request_data    jsonb,
    response_data   jsonb,
    error_message   text,
    error_code      varchar(50),
    http_status     int,
    duration_ms     int,
    retry_count     int DEFAULT 0,
    next_retry_at   timestamptz,
    started_at      timestamptz,
    completed_at    timestamptz,
    created_at      timestamptz DEFAULT now()
);

CREATE INDEX idx_intg_log_conn_time ON intg_sync_log(connection_id, created_at DESC);
CREATE INDEX idx_intg_log_status ON intg_sync_log(status) WHERE status IN ('failed', 'retry', 'dead_letter');
9.2. Dashboard
text
┌──────────────────────────────────────────────────────────┐
│  Integration Dashboard                                   │
├──────────────────────────────────────────────────────────┤
│  Aktif Bağlantılar: 12/15                                │
│  Son 24 saat:                                            │
│    ✅ Success: 12.345 (98.5%)                            │
│    ❌ Failed: 187 (1.5%)                                 │
│    ⚠️ Retry: 45                                          │
│    💀 Dead letter: 3                                     │
│                                                          │
│  🔥 Top Connectors                                       │
│  ─ Wolt: 5.234 istek, 0.2% hata                         │
│  ─ Aras Kargo: 3.456 istek, 1.1% hata                   │
│  ─ GİB e-Fatura: 2.123 istek, 3.5% hata                 │
│                                                          │
│  ⚠️ Sorunlu                                          │
│  ─ Trendyol: 45 failed (rate limit)                     │
│  ─ Banka A: 12 failed (auth error)                      │
│                                                          │
│  🔔 Aktif Alarmlar                                      │
│  ─ Wolt: Circuit breaker OPEN (5 failures)              │
└──────────────────────────────────────────────────────────┘
9.3. Alerting
Kurallar:

yaml
alerts:
  - name: "High Failure Rate"
    condition: "failure_rate > 5%"
    window: "15 minutes"
    notify: ["email:ops@company.com", "slack:#alerts"]
  
  - name: "Circuit Breaker Open"
    condition: "circuit_state = 'open'"
    notify: ["email:ops@company.com", "email:dev@company.com"]
  
  - name: "Dead Letter Queue Growing"
    condition: "dead_letter_count > 10"
    window: "1 hour"
    notify: ["email:ops@company.com"]
  
  - name: "Long-running Sync"
    condition: "duration > 60 seconds"
    notify: ["email:ops@company.com"]
9.4. Audit
Her sync audit log'a da gider (Belge 08 event bus):

php
class IntegrationAudit {
    public function log(IntgSyncLog $log): void {
        EventStore::create([
            'event_type' => "integration.{$log->connection->connector_key}.{$log->operation}",
            'aggregate_type' => 'intg_sync_log',
            'aggregate_id' => $log->id,
            'tenant_id' => $log->tenant_id,
            'payload' => [
                'status' => $log->status,
                'duration_ms' => $log->duration_ms,
                'http_status' => $log->http_status,
                // Request/response detayı owner görür (Belge 08)
            ],
        ]);
    }
}
10. Webhook System (Inbound + Outbound)
10.1. Inbound Webhook
Dış sistem → ERP (örneğin Wolt sipariş).

Endpoint:

text
POST /api/v1/webhooks/{connector_key}/{tenant_slug}
Örnek:

text
POST /api/v1/webhooks/wolt/tesfirma
X-Signature: abc123...
X-Tenant-Slug: tesfirma

{
    "order_id": "wolt-order-123",
    "customer": {...},
    "items": [...]
}
Handler:

php
class WebhookController {
    public function handle(Request $request, string $connectorKey, string $tenantSlug) {
        // 1. Tenant resolve
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        
        // 2. Connection bul
        $connection = IntgConnection::where('tenant_id', $tenant->id)
            ->where('connector_key', $connectorKey)
            ->where('status', 'active')
            ->firstOrFail();
        
        // 3. Signature verify
        if (!app(WebhookSignatureVerifier::class)->verify($request, $connection)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }
        
        // 4. Deduplication (X-Event-Id)
        $eventId = $request->header('X-Event-Id');
        if ($eventId && Cache::has("webhook:{$connection->id}:{$eventId}")) {
            return response()->json(['status' => 'duplicate']);
        }
        if ($eventId) Cache::put("webhook:{$connection->id}:{$eventId}", true, now()->addHours(24));
        
        // 5. Process (async)
        dispatch(new ProcessInboundWebhook($connection, $request->all()));
        
        // 6. 200 OK (async için)
        return response()->json(['status' => 'accepted']);
    }
}
10.2. Outbound Webhook
ERP → dış sistem (Belge 08).

Kayıt:

sql
CREATE TABLE intg_webhook_subscription (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NOT NULL,
    url             varchar(500) NOT NULL,
    event_types     text[] NOT NULL,
    secret          varchar(255) NOT NULL,      -- HMAC key
    status          varchar(20) DEFAULT 'active',
    retry_count     int DEFAULT 3,
    last_delivery_at timestamptz,
    last_failure_at timestamptz,
    consecutive_failures int DEFAULT 0,
    created_at      timestamptz
);
Delivery:

php
class WebhookDeliverer implements ShouldQueue {
    public function handle(): void {
        $sub = IntgWebhookSubscription::find($this->subscriptionId);
        $event = $this->event;
        
        $payload = json_encode($event->toArray());
        $signature = hash_hmac('sha256', $payload, decrypt($sub->secret));
        
        $response = Http::timeout(10)
            ->withHeaders([
                'X-Event-Type' => $event->eventType,
                'X-Event-Id' => $event->eventId,
                'X-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])
            ->withBody($payload, 'application/json')
            ->post($sub->url);
        
        if ($response->successful()) {
            $sub->update(['last_delivery_at' => now(), 'consecutive_failures' => 0]);
        } else {
            $this->retryOrDisable($sub, $response);
        }
    }
    
    private function retryOrDisable(IntgWebhookSubscription $sub, $response): void {
        $failures = $sub->consecutive_failures + 1;
        $sub->update([
            'last_failure_at' => now(),
            'consecutive_failures' => $failures,
        ]);
        
        if ($failures >= 10) {
            $sub->update(['status' => 'disabled']);
            event(new WebhookDisabled($sub));
        } else {
            dispatch(new WebhookDeliverer($this->subscriptionId, $this->event))
                ->delay(now()->addMinutes(pow(2, $failures)));
        }
    }
}
Kural: 10 ardışık hatadan sonra subscription otomatik devre dışı.

11. Türkiye Spesifik Entegrasyonlar
11.1. Banka Ekstresi
Format: MT940, CSV, XML, API.

Örnek: İş Bankası

yaml
connector:
  key: "bank-isbank"
  name: "İş Bankası"
  type: "file"
  protocol: "sftp"
  base_url: "sftp.isbank.com.tr"
  
  auth:
    type: "basic"
    username_field: "isbank_user"
    password_field: "isbank_pass"
  
  operations:
    - key: "fetch_statements"
      operation: "download"
      remote_path: "/statements/{YYYY}/{MM}/{DD}/*.mt940"
      local_path: "/storage/bank/isbank/{tenant_id}/"
      
      on_success:
        - action: "parse_mt940"
        - action: "create_transactions"
          entity: "fin_bank_transaction"
        - action: "event"
          event: "bank.statement.synced"
MT940 Parser: (Kütüphane + custom)

php
class MT940Parser {
    public function parse(string $filePath): array {
        $lines = file($filePath);
        $transactions = [];
        
        // MT940 → array
        // ...
        
        return $transactions;
    }
}
11.2. GİB E-Fatura
Format: XML (UBL-TR).

Entegratörler: Uyumsoft, Foriba, Digital Planet, İzibiz.

Örnek: Uyumsoft

yaml
connector:
  key: "einvoice-uyumsoft"
  name: "Uyumsoft e-Fatura"
  type: "rest"
  base_url: "https://erpefaturatest1.uyumsoft.com.tr"
  
  auth:
    type: "basic"
    username_field: "uyumsoft_user"
    password_field: "uyumsoft_pass"
  
  operations:
    - key: "send_invoice"
      method: "POST"
      endpoint: "/api/outgoing-einvoice/send"
      input:
        invoice_uuid: "${invoice.uuid}"
        invoice_xml: "${invoice.ubl_xml}"
      output:
        status: "status"
        gib_invoice_id: "gibInvoiceId"
      
      on_success:
        - action: "update_entity"
          entity: "fin_ar_invoice"
          data:
            gib_status: "${output.status}"
            gib_invoice_id: "${output.gib_invoice_id}"
    
    - key: "query_status"
      method: "GET"
      endpoint: "/api/outgoing-einvoice/{uuid}/status"
      input:
        uuid: "${invoice.uuid}"
UBL-TR Generator: (Belge 02 — schema)

11.3. Kargo
Connector'lar: Aras, Yurtiçi, MNG, PTT, Sürat, UPS, DHL.

Ortak pattern:

yaml
connector:
  key: "aras-cargo"
  type: "rest"
  operations:
    - { key: "create_shipment", ... }
    - { key: "track_shipment", ... }
    - { key: "cancel_shipment", ... }
  
  webhooks:
    - key: "status_changed"
      events: ["delivered", "returned", "failed"]
11.4. E-Ticaret / Marketplace
Trendyol:

yaml
connector:
  key: "trendyol"
  name: "Trendyol"
  type: "rest"
  base_url: "https://apigw.trendyol.com/integration"
  
  auth:
    type: "basic"
    username_field: "trendyol_supplier_id"
    password_field: "trendyol_api_key"
  
  operations:
    - key: "fetch_orders"
      method: "GET"
      endpoint: "/order/sellers/{supplierId}/orders"
      params:
        startDate: "${params.start_date}"
        endDate: "${params.end_date}"
      output:
        orders: "content"
      
      on_success:
        - action: "create_entities"
          entity: "otc_sales_order"
          foreach: "output.orders"
          mapping:
            order_number: "${item.orderNumber}"
            customer_name: "${item.customerFirstName} ${item.customerLastName}"
            total: "${item.totalPrice}"
            items: "${item.lines}"
    
    - key: "update_stock"
      method: "POST"
      endpoint: "/inventory/sellers/{supplierId}/products/price-and-inventory"
      input:
        items:
          expression: "products.map(p => ({ barcode: p.barcode, quantity: p.stock }))"
  
  webhooks:
    - key: "order_created"
      events: ["OrderCreated"]
Wolt:

yaml
connector:
  key: "wolt"
  type: "rest"
  
  webhooks:
    - key: "order_created"
      events: ["order.created"]
Hepsiburada, N11, Amazon TR, Getir, Yemeksepeti — benzer pattern.

11.5. Ödeme
iyzico:

yaml
connector:
  key: "iyzico"
  type: "rest"
  base_url: "https://api.iyzipay.com"
  
  auth:
    type: "custom"
    header_name: "Authorization"
    header_value: "IYZWSv2 ${iyzico_token}"
  
  operations:
    - key: "create_payment"
    - key: "refund"
    - key: "query_payment"
PayTR, Param, Stripe — benzer.

11.6. Muhasebe
Logo:

yaml
connector:
  key: "logo-accounting"
  type: "soap"                          # Logo REST değil, SOAP
  base_url: "https://logo-server/api"
  
  operations:
    - key: "sync_invoice"
    - key: "sync_payment"
    - key: "query_balance"
Mikro, Netsis — benzer.

11.7. Mesaj
Slack:

yaml
connector:
  key: "slack"
  type: "rest"
  
  operations:
    - key: "send_message"
      method: "POST"
      endpoint: "https://slack.com/api/chat.postMessage"
      input:
        channel: "${connection.channel}"
        text: "${message}"
WhatsApp, Telegram, SMS — benzer.

12. Restoran Örneği — Wolt + Trendyol + Getir
12.1. Wolt → ERP
text
1. Wolt: yeni sipariş → webhook
    ↓
2. ERP webhook endpoint: /api/v1/webhooks/wolt/restoran-x
    ↓
3. Signature verify → connection load
    ↓
4. Reverse mapping: Wolt JSON → custom_order
    ↓
5. custom_order created (event: order.created)
    ↓
6. Kitchen Display (KDS) — Belge 13
    ↓
7. Chef: hazırla
    ↓
8. ERP → Wolt webhook: "order ready"
    ↓
9. Wolt: kurye gönder
    ↓
10. ERP → Wolt: "order picked"
    ↓
11. ERP → Wolt: "order delivered"
Connector config:

yaml
connector:
  key: "wolt"
  type: "rest"
  
  webhooks:
    - key: "order_created"
      events: ["order.created"]
      reverse_mapping:
        order_id: "id"
        customer_name: "customer.name"
        table_number: "venue.table"
        items:
          foreach: "items"
          mapping:
            product_name: "name"
            qty: "count"
            notes: "options"
        total: "total"
  
  operations:
    - key: "update_status"
      method: "POST"
      endpoint: "/orders/{order_id}/status"
      input:
        order_id: "${order.wolt_order_id}"
        status: "${order.status}"       # confirmed | preparing | ready | delivered
12.2. Trendyol Yemek → ERP
Benzer pattern. Webhook + reverse mapping.

12.3. Getir → ERP
Benzer pattern. API + polling.

12.4. Multi-Platform Sync
text
            ┌──────────────┐
            │   ERP        │
            │ custom_order │
            └──────┬───────┘
                   │
        ┌──────────┼──────────┐
        ▼          ▼          ▼
    ┌───────┐ ┌─────────┐ ┌───────┐
    │ Wolt  │ │ Trendyol│ │ Getir │
    │Conn.  │ │ Yemek   │ │Conn.  │
    └───────┘ └─────────┘ └───────┘
Kural: Aynı sipariş birden fazla platformda → single source of truth ERP.

12.5. Örnek Akış
text
Müşteri Wolt'tan sipariş verdi
    ↓
Wolt → ERP (webhook)
    ↓
ERP: custom_order (state: new)
    ↓
ERP → Kitchen Display (Belge 13)
    ↓
Chef hazırladı → "Ready" butonu
    ↓
ERP: state = ready
    ↓
ERP → Wolt (API): "order ready"
    ↓
Wolt: kurye gönder
    ↓
ERP: state = delivered
    ↓
ERP → Wolt (API): "order delivered"
Kural: Her adım event bus üzerinden (Belge 08).

13. Connector Development (Developer Experience)
13.1. Connector SDK
bash
# Yeni connector oluştur
php artisan connector:new aras-cargo

# Test
php artisan connector:test aras-cargo

# Publish
php artisan connector:publish aras-cargo
13.2. Connector Structure
text
connectors/aras-cargo/
├── connector.json                      # manifest
├── src/
│   ├── Connector.php                   # custom logic
│   └── Operations/
│       ├── CreateShipment.php
│       └── TrackShipment.php
├── tests/
│   ├── CreateShipmentTest.php
│   └── TrackShipmentTest.php
└── README.md
13.3. Custom Logic
php
// src/Connector.php
class ArasConnector extends BaseConnector
{
    public function beforeRequest(array $payload): array
    {
        // Custom transform
        $payload['special_field'] = $this->computeSpecial($payload);
        return $payload;
    }
    
    public function afterResponse(array $response): array
    {
        // Custom parse
        if (isset($response['data']['errors'])) {
            throw new IntegrationException($response['data']['errors'][0]);
        }
        return $response;
    }
}
13.4. Testing
php
class ArasConnectorTest extends ConnectorTestCase
{
    public function test_create_shipment(): void
    {
        $this->mockHttpClient()->expect('POST', 'https://api.araskargo.com.tr/v1/shipments')
            ->withBody($this->expectedPayload())
            ->respondWith(['data' => ['trackingNo' => 'TR123']]);
        
        $result = $this->connector->execute('create_shipment', [
            'order' => $this->orderFixture(),
        ]);
        
        $this->assertEquals('TR123', $result['tracking_number']);
    }
}
13.5. Connector Marketplace
Connector'lar marketplace'te satılabilir (Belge 10):

Banka connector'ları

GİB entegratör connector'ları

Kargo connector'ları

E-ticaret connector'ları

Kural: Connector = plugin olarak paketlenir.

14. Testing
14.1. Unit Test (Connector)
php
public function test_field_mapping(): void
{
    $mapper = new FieldMapper();
    $result = $mapper->map([
        'receiver_name' => '${order.customer.name}',
    ], ['order' => ['customer' => ['name' => 'Ali']]]);
    
    $this->assertEquals('Ali', $result['receiver_name']);
}
14.2. Integration Test
php
public function test_sync_from_trendyol(): void
{
    Http::fake([
        'apigw.trendyol.com/*' => Http::response(['content' => [$this->trendyolOrder()]]),
    ]);
    
    $sync = new SyncTrendyolOrders($this->connection);
    $sync->handle();
    
    $this->assertDatabaseHas('otc_sales_order', [
        'order_number' => 'TY-12345',
    ]);
}
14.3. Load Test
bash
k6 run --vus 50 --duration 5m integration-loadtest.js
Hedef: 100 sync/dk, P95 < 2s.

14.4. Chaos Test
Timeout: Dış sistem yavaş

502: Dış sistem down

Rate limit: 429

Invalid signature: Webhook reddedilir

15. Security
15.1. Secret Management
Encrypted at rest (Laravel Crypt)

Never in logs

Rotation policy (90 gün)

Access audit

15.2. Network Security
Whitelist — Sadece izinli domain

mTLS — Banka

Certificate validation — Self-signed yasak

DNS pinning — SSRF koruması

15.3. Inbound Webhook
Signature verify (HMAC)

Deduplication (X-Event-Id)

Rate limit (per tenant)

IP whitelist (opsiyonel)

Body size limit (1 MB)

15.4. Outbound HTTP
Timeout (30s default)

Body size limit (10 MB)

Redirect limit (3)

SSRF koruması (private IP yasak)

15.5. Audit
Her sync — audit log (Belge 08). Owner görür.

16. Performance
16.1. Concurrency
Ölçek	Strateji
< 100 sync/dk	Single worker
100-1000	Multi worker (5)
> 1000	Queue-based (RabbitMQ/Kafka)
16.2. Cache
OAuth tokens (5 dk TTL)

Field mappings (1 saat TTL)

Connector configs (5 dk TTL)

16.3. Batch Processing
php
class BulkSync {
    public function sync(array $items, int $batchSize = 100): void {
        foreach (array_chunk($items, $batchSize) as $chunk) {
            Http::pool(function ($pool) use ($chunk) {
                foreach ($chunk as $item) {
                    $pool->post("https://api.example.com/items", $item);
                }
            });
        }
    }
}
Kural: Bulk sync'te parallel (pool).

17. Roadmap
Aşama	Süre	Kapsam
1. Foundation	4 hafta	Connector schema, sync engine, log
2. REST connector	4 hafta	HTTP client, auth, retry
3. Webhook	3 hafta	Inbound + outbound, signature
4. File connector	3 hafta	SFTP, S3, XML, MT940
5. Built-in connectors	8 hafta	Banka (5), GİB (3), Kargo (5)
6. E-commerce connectors	6 hafta	Trendyol, Hepsiburada, Wolt, Getir
7. Marketplace	4 hafta	Connector marketplace
8. Monitoring	3 hafta	Dashboard, alerts, dead letter
Toplam: 35 hafta (~9 ay).

18. Anti-Patterns
Bu projede yasak olan integration hataları:

Hardcoded URL — Config'de

Secret in code — Encrypted DB

Sync HTTP çağrısı (uzun) — Async queue

Retry'siz — Her sync retry

Rate limit'siz — Dış sistem DDoS

Circuit breaker'sız — Kaynak israfı

Signature'sız webhook — 401 zorunlu

Deduplication'sız — Duplicate sipariş

No timeout — Sonsuz bekleme

Monolithic connector — Tek işlev

No monitoring — Kör sync

No alerting — Sessiz hata

Plain secrets in logs — Mask zorunlu

Cross-tenant data leak — RLS + audit

Sync without event — Audit + downstream

19. Glossary
Terim	Anlam
Connector	Dış sistem tanımı
Connection	Tenant → connector bağlantısı
Operation	Tek işlem (create_shipment)
Sync	Veri transfer
Real-time	Event/webhook tetikli
Batch	Cron tetikli
Scheduled	Zamanlı
Field Mapping	Alan eşleştirme
Transform	Değer dönüşümü
Reverse Mapping	Inbound mapping
Signature	HMAC doğrulama
Circuit Breaker	Hata sonrası durdurma
Retry	Tekrar deneme
Backoff	Bekleme süresi
Dead Letter	Başarısız kuyruk
Webhook	Event HTTP POST
mTLS	Mutual TLS
OAuth2	Token auth
MT940	Banka ekstre formatı
UBL-TR	E-fatura XML formatı
20. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — integration hub, TR entegrasyonlar, restoran örneği