# 21 — Decision Register

**Project:** Kurumsal ERP Platform
**Belge:** Architecture Decision Record (ADR) + Deferred + Debt + Risk + Known Issues
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Living Document — her karar, erteleme, borç, risk burada
**Depends On:** Tüm önceki belgeler (00-20)

---

## 1. Bu Belgenin Amacı

Bu belge, **kararların hafızasıdır.** 6 ay sonra "neden böyle yaptık?" sorusuna cevap verir. 5 bölüm:

| Bölüm | Ne | Örnek |
|---|---|---|
| **ADR** | Architecture Decision Record | "Neden RLS?" |
| **DEF** | Deferred Items | "Cloudflare WAF ertelendi" |
| **TD** | Technical Debt | "Sərfiyyat Livewire içinde" |
| **RISK** | Risk Register | "Superadmin veri erişimi" |
| **ISSUE** | Known Issues | "Superadmin favorileri çalışmıyor" |

### 1.1. Kullanım Kuralları

- **Her karar** buraya eklenir (tarih, bağlam, alternatifler, sonuç).
- **Her `TODO`/`FIXME`** kodda bir ID'ye referans verir (örn: `TODO: DEF-007`)
- **Her commit mesajı** ilgili ID'yi belirtir (örn: "feat: WAF workaround (DEF-007)")
- **Haftalık review** — CTO + tech lead
- **Çeyreklik audit** — DEF/RISK/TD yeniden değerlendirilir
- **Silme yok** — sadece `status: superseded` veya `status: closed`

### 1.2. ID Formatı
<tip>-<numara>

ADR-001, ADR-002, ... Architecture decisions
DEF-001, DEF-002, ... Deferred items
TD-001, TD-002, ... Technical debt
RISK-001, RISK-002, ... Risks
ISSUE-001, ISSUE-002, ... Known issues

text

**Kural:** Numaralar **asla tekrar kullanılmaz.**

---

## 2. ADR — Architecture Decision Records

Format: Michael Nygard (basitleştirilmiş).

### ADR-001: PostgreSQL RLS ile Multi-Tenancy

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi
**Karar Veren:** Kurucu, CTO

**Bağlam:**
Platform multi-tenant olacak. Her tenant'ın verisi **kesinlikle izole** olmalı. Uygulama katmanı güvenli değil (bug riski). KVKK/GDPR uyumu gerekli.

**Alternatifler:**

| Alternatif | Artı | Eksi | Karar |
|---|---|---|---|
| **RLS** (Row-Level Security) | DB seviyesinde izolasyon, tek DB | %10-15 performans overhead | ✅ |
| **Schema-per-tenant** | Tam izolasyon | Migration karmaşık, connection pool sorunu | ❌ |
| **DB-per-tenant** | Tam izolasyon | Çok pahalı, tek migration tüm tenant'ları etkilemez | ❌ |
| **Sadece uygulama filtresi** | En hızlı | Bug = veri sızıntısı | ❌ |

**Karar:**
PostgreSQL **RLS + FORCE ROW LEVEL SECURITY** zorunlu. Her tenant-scoped tabloda `tenant_id` + policy.

**Sonuç:**
- Her yeni tablo = RLS zorunlu (migration reddedilir)
- Performans %10-15 düşer (kabul edilebilir)
- Güvenlik: uygulama bug'ı olsa bile veri sızmaz

**Etkilenen belgeler:** 01-multi-tenancy, 02-data-model, 18-security

---

### ADR-002: Feature Flags (Statik → Dinamik)

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
Tenant admin'in kendi özelliklerini aç/kapa yapması gerekiyor. Ama tüm flag'leri DB'ye koymak yönetimi zorlaştırır. Tüm flag'leri kodda tutmak ise değişikliği yavaşlatır.

**Karar:**
**Hibrit model:**
1. **Statik flags** (config/features.php) — 27 mevcut, sık değişmeyen
2. **DB flags** (`sys_tenant_feature`) — tenant override
3. **Global default** (`sys_feature_default`) — superadmin override

**Çözümleme sırası:** tenant override → global default → registry default.

**Gelecek:** `sys_feature_registry` tablosu (runtime flag oluşturma) Faz 1'de.

**Etkilenen belgeler:** 03-feature-flags

---

### ADR-003: Plugin Sandbox (Hibrit)

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi (mimari karar, kod Faz 2'de)

**Bağlam:**
Üçüncü taraf plugin'ler ana sisteme zarar vermemeli. Ama her plugin için Docker container açmak maliyetli.

**Alternatifler:**

| Yaklaşım | Güvenlik | Maliyet | Hız |
|---|---|---|---|
| **Docker per plugin** | ✅ En yüksek | 🔴 Yüksek (RAM) | 🟡 Orta |
| **PHP-FPM process pool** | 🟠 Orta | 🟢 Düşük | ✅ Hızlı |
| **WASM** | ✅ Yüksek | 🟢 Düşük | ✅ Hızlı |

**Karar:**
**Hibrit:**
- **Core module** (bizim) → sandbox yok
- **Trusted partner** → PHP-FPM pool
- **Public plugin** → Docker container
- **Basit plugin** (WASM uygun) → WASM (Faz 3+, php-wasm olgunlaşınca)

**Etkilenen belgeler:** 10-plugin-sandbox, 18-security

---

### ADR-004: Event Bus — Outbox Pattern

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
Modüller arası iletişim event üzerinden olmalı. Ama DB commit + event publish **atomik** olmalı, yoksa event kaybolur veya yanlış publish olur.

**Alternatifler:**

| Yaklaşım | Güvenilirlik | Hız |
|---|---|---|
| **Sync event** | Event kaybolabilir | Hızlı |
| **Async (queue)** | Event kaybolabilir (queue çökerse) | Hızlı |
| **Outbox** | ✅ Event atomik | Orta |

**Karar:**
**Outbox pattern:**
1. Business data + event **aynı transaction'da** yazılır (`event_outbox` tablosu)
2. Background worker outbox'ı okur, publish eder
3. Idempotency key ile duplicate engellenir

**Etkilenen belgeler:** 08-event-bus

---

### ADR-005: API Stratejisi — Hibrit (REST + Generic + GraphQL)

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
- Core modüller (FIN, OTC, vs) için REST
- Custom entity'ler (yüzlerce olabilir) için REST = 500+ endpoint (bakılamaz)
- Kompleks dashboard/mobil için GraphQL (opsiyonel)

**Karar:**
**3 katman:**

| Katman | Endpoint sayısı | Kullanım |
|---|---|---|
| **REST** | ~80 | Core modüller |
| **Generic** (`/entity/{key}`) | 5 | Custom entity'ler |
| **GraphQL** | 1 | Kompleks sorgu, opsiyonel |

**Toplam:** ~86 endpoint (1000+ değil).

**Etkilenen belgeler:** 09-api-first

---

### ADR-006: AI Core Değil, Plugin-Only

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
AI, ERP core'una girmeli mi?

**Karar:**
**Hayır.** AI **plugin domain**'ine ait. Neden:
- Vendor lock-in (OpenAI/Anthropic)
- Maintenance burden
- Tenant zorlaması
- Maliyet
- Regülasyon (EU AI Act)
- Kod şişmesi

**Core'da sadece:**
- `ai.request` event
- `AiInterface` contract

**Implementasyon:** plugin (marketplace'te satılır).

**Etkilenen belgeler:** 00-manifesto (Bölüm 12.5), 17-marketplace

---

### ADR-007: Navbar — Native HTML (`<details>/<summary>`)

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
Sidebar collapse için Alpine.js kullanılıyordu. Ama Alpine bazen yüklenmiyor, CSS bozuluyordu.

**Alternatifler:**

| Yaklaşım | Bağımlılık | Risk |
|---|---|---|
| **Alpine.js** | JS library | Yüklenmezse çalışmaz |
| **Livewire** | Livewire component | Round-trip, yavaş |
| **Native `<details>/<summary>`** | Yok | ✅ Her zaman çalışır |

**Karar:**
Native HTML `<details>/<summary>` + minimal vanilla JS (sadece favori toggle için).

**Sonuç:** Alpine bozulsa bile sidebar çalışır.

**Etkilenen belgeler:** 13-ui-builder (dolaylı)

---

### ADR-008: Favorites — Form POST (AJAX Değil)

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
Favoriler önce AJAX (`fetch`) ile yapıldı. Ama:
- CSRF token sorunu
- Tenant context sorunu
- Superadmin'de FK violation

**Karar:**
**Form POST** (klasik HTML form):
- CSRF token Blade'de
- Sayfa yenilenir (2 saniye)
- %100 çalışır

**Sonuç:** UX biraz daha yavaş ama **garantili çalışır**.

**Etkilenen belgeler:** 13-ui-builder (dolaylı)

---

### ADR-009: Superadmin İçin `tenant_id` NULL

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
Superadmin'in `tenant_id` yok. Ama favoriler, settings gibi tablolar `tenant_id` gerektiriyor.

**Karar:**
`sys_user_nav_item.tenant_id` **nullable** yapıldı. Superadmin için `tenant_id = NULL`.

**Alternatifler:**
- `00000000-...` (sıfır UUID) — FK violation
- Global tablo (ayrı) — karmaşık
- **NULL (nullable)** — ✅ kabul edildi

**Etkilenen belgeler:** 01-multi-tenancy

---

### ADR-010: PostgreSQL 16 + Aurora (Production)

**Tarih:** 2026-11-06
**Durum:** ✅ Kabul edildi

**Bağlam:**
Production DB seçimi.

**Alternatifler:**

| DB | Artı | Eksi |
|---|---|---|
| **Aurora PostgreSQL** | Managed, HA, PITR, replication | AWS lock-in |
| **Self-managed PostgreSQL** | Kontrol | Operasyon yükü |
| **MySQL/MariaDB** | Popüler | RLS yok |
| **MongoDB** | Esnek | ACID, RLS yok |

**Karar:**
**Aurora PostgreSQL** (production). Self-managed (dev/staging).

**Sonuç:** AWS bağımlılığı kabul edildi. **Multi-region** için global Aurora.

**Etkilenen belgeler:** 19-deployment

---

---

### ADR-012: Meta-Platform Primitives ÖNCE (Hibrit Yaklaşım)

**Tarih:** 2026-11-07
**Durum:** ✅ Kabul edildi
**Karar Veren:** Kurucu, CTO

**Bağlam:**
Belge 00'daki "platform of platforms" vizyonu — kullanıcının kendi platform yapısını
tanımlayabilmesi, modülleri gerçekten silebilmesi.

Sorun: Eğer 14 modül hardcoded olarak yazılırsa (app/Livewire/Fin/, routes/web.php FIN grubu),
sonradan meta-platform kurulsa bile:
- Platform builder "FIN istemiyorum" diyemez
- Sadece "FIN gizle" diyebilir (feature flag)
- Kod, migration, tablo, route hâlâ yüklü
- Belge 00 "sıfır limit" ilkesi KIRILIR

**Alternatifler:**

| Yaklaşım | Vizyon | Hız |
|---|---|---|
| **A) 30 özellik önce, meta-platform sonra** | ❌ configurable | ✅ hızlı |
| **B) Tam meta-platform önce** | ✅ composable | ❌ 8-10 hafta sessiz |
| **C) Hibrit (primitives → retrofit → builders)** | ✅ composable | 🟡 6-8 hafta |

**Karar:**
**Hibrit (C):**

**FAZ A: Meta-Platform Primitives (8 hafta)**
- A1: Module Registry + 1 modül proof (2 hafta)
- A2: Layer + Role system (2 hafta)
- A3: Custom Entity Framework temel (3 hafta)
- A4: Event Bus + Outbox (2 hafta)

**FAZ B: Module Retrofit + İlk Yeni Modüller (3 hafta)**
- B1: 14 modülü modules/ formatına taşı (2 hafta)
- B2: Platform Builder UI MVP — module seç + deploy (1 hafta)

**FAZ C: Ekosistem + Visual Builders (sürekli)**
- Plugin System, Sandbox, Report Builder, UI Builder, Marketplace

**Sonuç:**
- Platform builder **gerçekten** modül yükleyebilir/kaldırabilir
- Her modül **module.json** manifest + kendi migration + route
- "FIN istemiyorum" → FIN **hiç yüklenmez** (kod, tablo, route yok)
- Belge 00 "sıfır limit" ilkesi korunur

**Etkilenen belgeler:** 04-module-system, 05-meta-structure, 07-custom-entity, 08-event-bus, 20-roadmap

**İptal ettiği varsayım:** Belge 20'deki "Faz 1'de 30 özellik" planı ertelenir. Modüller meta-platform citizen olarak yeniden doğar.

---

### ADR-013: Module Resolution — Policy-Based, N-Katmanlı

**Tarih:** 2026-11-07
**Durum:** ✅ Kabul edildi
**Karar Veren:** Kurucu

**Bağlam:**
ADR-012'de hibrit yaklaşım kabul edildi (primitives → retrofit → builders).
Ama modül seçimi "kim yapar?" sorusu sabit bir cevap olamaz. Kullanıcı
vizyonu: **"benim keyfim, her şey konfigüre edilebilir."**

Somut örnek: bugün 2 katman yeterli, yarın "General Managers grubu"
tanımlanabilir ve o grup kendi modül setini seçebilir. Sonra bölgesel
müdürlükler, iştirakler, proje ekipleri... her katman eklenebilmeli.

**Kısıtlayıcı yaklaşım (REDDEDİLDİ):**
- Sabit 3 katman (platform / tenant / user)
- Her katman için ayrı tablo
- Yeni katman = yeni migration

**Kabul edilen yaklaşım:**
**Policy-Based Module Resolution**

Tek tablo: `sys_module_policy`
Her satır bir policy:
- `scope_type` (platform | structure | tenant | user | role | group | runtime | **custom-***)
- `scope_id` (ilgili varlığın ID'si — nullable = global)
- `modules` (JSONB — array)
- `mode` (override | merge | patch)
- `priority` (int — yüksek önce)
- `conditions` (JSONB — plan, region, role, herhangi bir attribute)
- `is_active`

**Çözümleme (resolution) algoritması:**
1. Tüm aktif policy'ler `priority DESC` sıralanır
2. Her policy için `matches(actor, conditions)` kontrolü
3. Eşleşen policy uygulanır (mode'a göre):
   - `override`: mevcut listeyi **değiştir**
   - `merge`: listeye **ekle**
   - `patch`: `!fin` = çıkar, `+pulse` = ekle

**Örnek (bugün):**
tenant_id=NULL, scope=platform, priority=100, modules=['*'], mode=override ← default
tenant_id=A, scope=tenant, priority=50, modules=['pulse','otc','inv'], mode=override
tenant_id=A, scope=role:admin, priority=40, modules=['+fin'], mode=patch

text

**Örnek (yarın — grup eklenince):**
tenant_id=A, scope=group:gm, priority=60, modules=['fin','hrm'], mode=merge

text

**Sonuç:**
- Yeni katman = yeni policy satırı (migration YOK)
- Modül seçimi = tamamen veri
- Kim seçer = policy + permission sistemi (Belge 06)
- Runtime = `ModuleResolver::resolve($actor)`

**Etkilenen belgeler:** 04-module-system, 05-meta-structure, 06-layer-role-system

---
Change Log:

markdown
| 2026-11-07 | 1.4 | ADR-013: policy-based N-katmanlı module resolution |

## 3. DEF — Deferred Items (Ertelenen)

### DEF-001: Cloudflare WAF

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Karar Veren:** Kurucu
**Neden:** Aylık $200 bütçe kısıtı. Beta aşamasında trafik düşük (< 10K/gün).

**Mitigasyon (şu an):**
- Laravel rate limiting (uygulama katmanı)
- Fail2ban (OS katmanı)
- nginx `limit_req`
- Manuel IP block (Sentry alert + fail2ban)
- Cloudflare **DNS + SSL** (ücretsiz plan) — sadece CDN, WAF yok

**Risk:**
- DDoS (L3/L4/L7)
- SQL injection denemeleri (uygulama filtresi durdurur ama WAF daha hızlı)
- Bot scraping
- Zero-day exploit

**Yeniden Değerlendirme:** Ay 12 (Faz 1 sonu)
**Kabul Kriteri:** MRR > $5K **veya** trafik > 100K/gün **veya** ilk DDoS olayı
**Sorumlu:** CTO
**Bağımlı:** 18-security, 19-deployment

---

### DEF-002: Kiosk Mode

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Faz 1'de değil, Faz 2'de. Öncelik diğer özelliklerde.

**Kapsam (planlanan):**
- Company settings'te `kiosk_mode` toggle
- Kiosk modunda kısıtlı UI (basit satış ekranı)
- "İdarəetmə" butonu — manager şifresi ile full UI
- Topbar kısıtlamaları (arama, bildirimler yok)

**Yeniden Değerlendirme:** Ay 18 (Faz 2 ortası)
**Sorumlu:** Product manager
**Bağımlı:** 05-meta-structure, 13-ui-builder

---

### DEF-003: Barkod (Donanım + Kamera)

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Şimdilik manuel giriş yeterli. Barkod Faz 2'de.

**Kapsam (planlanan):**
- `inv.barcode.hardware` flag (Belge 03)
- `inv.barcode.camera` flag
- `inv_product_barcode` tablosu (multi-barcode)
- Kamera (ZXing-js / html5-qrcode)
- Donanım okuyucu (input + hızlı algılama)
- Etiket yazdırma (opsiyonel)

**Yeniden Değerlendirme:** Ay 14 (Faz 2 başlangıç)
**Sorumlu:** Backend + frontend
**Bağımlı:** 04-module-system, 07-custom-entity

---

### DEF-004: Log Değişikliği Webhook

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Log değişikliklerini webhook'a gönderme özelliği istendi ama Faz 2'de (event bus olgunlaşınca).

**Kapsam (planlanan):**
- `sys_notification_setting` değiştiğinde event yayınla
- Event bus üzerinden webhook'a gönder (Belge 08)
- Retry + dead letter

**Yeniden Değerlendirme:** Ay 18 (event bus production'da)
**Sorumlu:** Backend
**Bağımlı:** 08-event-bus, 14-integration-hub

---

### DEF-005: Ürün Arama (Settings)

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** `inv.products` sayfasına arama eklenecek ama şimdi değil.

**Kapsam (planlanan):**
- `/inv/products` sayfasında arama input
- Debounced search (Livewire `wire:model.live.debounce`)
- Barkod + isim + kod arama
- Filter + sort

**Yeniden Değerlendirme:** Ay 14 (Faz 2 başlangıç)
**Sorumlu:** Frontend
**Bağımlı:** 04-module-system

---

### DEF-006: Barkod Duplicate → Quantity++

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Barkod sistemi kurulunca otomatik gelecek.

**Kapsam (planlanan):**
- Satış ekranında barkod 2 kez okutulursa
- Aynı ürün sepette varsa → `qty++`
- Değilse → yeni satır

**Yeniden Değerlendirme:** DEF-003 ile birlikte (Ay 14)
**Bağımlı:** DEF-003

---

### DEF-007: Multi-Currency (Paralel Ledger)

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Şu an sadece TRY. Multi-currency Faz 4'te (Belge 20).

**Kapsam (planlanan):**
- Paralel ledger (TDHP + IFRS + tax)
- Kur farkı hesaplama
- Yeniden değerleme
- Multi-currency AR/AP

**Yeniden Değerlendirme:** Ay 36 (Faz 4)
**Bağımlı:** 15-compliance, 02-data-model

---

### DEF-008: e-Fatura GİB Entegrasyonu

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Şu an TXT/XML export var, GİB canlı değil. Faz 1'de aktif olacak.

**Mitigasyon (şu an):**
- XML/PDF export (manuel gönderim)
- UBL-TR generator (kod var)
- Kullanıcı GİB portalına elle yükler

**Yeniden Değerlendirme:** Ay 8 (Faz 1)
**Kabul Kriteri:** 5+ müşteri e-Fatura talep ediyor
**Bağımlı:** 15-compliance, 14-integration-hub

---

### DEF-009: Analytics Dashboard (KPI)

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Dashboard var ama KPI widget'ları yok. Faz 1'de.

**Kapsam (planlanan):**
- Ciro, alacak, stok değeri, açık sipariş
- Chart'lar (Belge 12 — Report Builder kullanacak)
- Role-based default layout

**Yeniden Değerlendirme:** Ay 10 (Faz 1)
**Bağımlı:** 12-report-builder

---

### DEF-010: Test Coverage ≥ %80

**Durum:** ⏳ Ertelendi (Faz 0'da %60 hedefi)
**Tarih:** 2026-11-06
**Neden:** Test coverage %0'dan %60'a Faz 0'da. %80 Faz 1'de.

**Kapsam (planlanan):**
- Unit test (her servis)
- Feature test (her endpoint)
- Integration test (modüller arası)
- E2E test (kritik akış)

**Yeniden Değerlendirme:** Ay 12 (Faz 1 sonu)
**Sorumlu:** Her developer
**Bağımlı:** 19-deployment (CI)

---

### DEF-011: Multi-Region Deployment

**Durum:** ⏳ Ertelendi
**Tarih:** 2026-11-06
**Neden:** Tek region (eu-central-1) şu an yeterli. Multi-region Faz 4.

**Kapsam (planlanan):**
- Cross-region Aurora replica
- Route53 failover
- Data residency (Belge 15)

**Yeniden Değerlendirme:** Ay 30 (Faz 4)
**Sorumlu:** DevOps
**Bağımlı:** 15-compliance, 19-deployment

---

## 4. TD — Technical Debt (Teknik Borç)

### TD-001: Sərfiyyat Logic Livewire İçinde

**Durum:** 🟡 Bilinçli borç
**Tarih:** 2026-11-06
**Oluşturulma:** Hızlı prototip (sərfiyyat MVP)
**Şu anki durum:**
- `sarfiyyatTotal()` hesabı `SalesOrders.php` içinde
- `sarfiyyat_charge_customer` kontrolü Livewire'da
- Validasyon karışık

**Doğru olması gereken:**
- `SarfiyyatService` içinde (Belge 04 — servis katmanı kuralı)
- Unit test edilebilir
- Yeniden kullanılabilir (başka modüller)

**Maliyet:** ~4 saat refactor
**Ödeme Planı:** Faz 1 (Ay 6-12) — test coverage artırırken
**Risk:** Bug fix zorlaşır, test yazılamaz
**Sorumlu:** Backend developer

---

### TD-002: Superadmin Favorileri Çalışmıyor

**Durum:** 🟡 Bilinen borç (ISSUE-001 ile birlikte)
**Tarih:** 2026-11-06
**Şu anki durum:**
- Superadmin favori tıklayınca FK violation
- Sebep: `tenant_id` sıfır UUID gönderiliyor

**Kısmi çözüm:** `tenant_id` nullable yapıldı (ADR-009), ama:
- Navigasyon UI superadmin için eksik
- `NULL tenant_id` için feature flag ayrı

**Maliyet:** ~2 saat (UI + test)
**Ödeme Planı:** Faz 1 (Ay 8)
**Sorumlu:** Backend developer

---

### TD-003: Feature Flag Cache Invalidation

**Durum:** 🟡 Bilinçli borç
**Tarih:** 2026-11-06
**Şu anki durum:**
- `FeatureFlagService` request-scoped cache
- Ama tenant admin flag değiştirdiğinde **diğer user'lar eski değeri görebilir** (5 dk TTL)

**Doğru olması gereken:**
- `FeatureFlagChanged` event yayınla
- Redis cache invalidate
- Gerçek zamanlı güncelleme

**Maliyet:** ~6 saat
**Ödeme Planı:** Faz 1 (Ay 10)
**Risk:** Flag değişikliği anında yansımıyor
**Sorumlu:** Backend

---

### TD-004: Migration Idempotency (Bazı Migration'lar)

**Durum:** 🟡 Bilinçli borç
**Tarih:** 2026-11-06
**Şu anki durum:**
- Bazı migration'lar `Schema::hasTable` kontrolü yapmıyor
- Tekrar çalıştırılırsa hata verir

**Doğru olması gereken:**
- Her `up()` başında `if (Schema::hasTable(...)) return;`
- Her `down()` başında `if (!Schema::hasTable(...)) return;`

**Maliyet:** ~3 saat (tüm migration'lar)
**Ödeme Planı:** Faz 1 (Ay 8)
**Risk:** Production'da migration hatası
**Sorumlu:** Backend

---

### TD-005: Multi-Language (Sadece TR + AZ Hazır)

**Durum:** 🟡 Bilinçli borç
**Tarih:** 2026-11-06
**Şu anki durum:**
- TR (default) + AZ (kısmi) var
- EN, DE, FR, AR yok (Faz 4)

**Doğru olması gereken:**
- Laravel `lang/` dosyaları tam
- Her metin çevrilebilir
- Tenant default locale

**Maliyet:** ~80 saat (5 dil × 16 saat)
**Ödeme Planı:** Faz 4 (Ay 36)
**Sorumlu:** Translator + developer

---

### TD-006: API Versioning (Sadece v1)

**Durum:** 🟡 Bilinçli borç
**Tarih:** 2026-11-06
**Şu anki durum:**
- `/api/v1/*` var
- v2 yok (v1 kırılınca gelecek)

**Doğru olması gereken:**
- Deprecation policy (Belge 09)
- Sunset header
- Migration guide

**Maliyet:** ~20 saat
**Ödeme Planı:** v1 breaking change gerektiğinde
**Sorumlu:** Backend

---

### TD-007: PDF Kalitesi (Fatura)

**Durum:** 🟡 Bilinçli borç
**Tarih:** 2026-11-06
**Şu anki durum:**
- PDF var (dompdf)
- Ama görsel kalite orta seviye (logo, imza alanı zayıf)

**Doğru olması gereken:**
- Demo'daki görsel kalite (Belge 20 — Polar ERP örneği)
- Şirket logosu, imza, watermark
- Multi-language

**Maliyet:** ~16 saat
**Ödeme Planı:** Faz 1 (Ay 10)
**Sorumlu:** Frontend + backend

---

### TD-008: Event Bus (Outbox Worker Yok)

**Durum:** 🟡 Bilinçli borç
**Tarih:** 2026-11-06
**Şu anki durum:**
- Laravel `event()` kullanılıyor
- Ama `event_outbox` tablosu yok
- Outbox worker yok

**Doğru olması gereken:**
- Belge 08 uygulaması
- Outbox pattern
- Idempotency

**Maliyet:** ~40 saat
**Ödeme Planı:** Faz 2 (Ay 18)
**Risk:** Event kaybolabilir
**Sorumlu:** Backend

---

## 5. RISK — Risk Register

### RISK-001: Superadmin'in Tenant Verisine Erişimi

**Durum:** 🟡 Kabul edildi
**Olasılık:** Düşük
**Etki:** Yüksek (KVKK/GDPR ihlali)
**Oluşturulma:** 2026-11-06

**Neden kabul edildi:** Teknik destek için gerekli (debug, migration).

**Mitigasyon:**
- Her superadmin erişimi **audit'lenir** (Belge 08)
- 2 kişi kuralı (four-eyes) — talep + onay
- 24 saat süreli erişim
- Tenant'a bildirim (opt-in)
- Sadece metadata, PII maskeli

**Yeniden Değerlendirme:** Ay 24
**Sorumlu:** CTO + Legal

---

### RISK-002: Tek Region Deployment

**Durum:** 🟡 Kabul edildi
**Olasılık:** Düşük (AWS SLA %99.99)
**Etki:** Yüksek (region down = tüm sistem down)
**Oluşturulma:** 2026-11-06

**Neden kabul edildi:** Faz 0-1'de maliyet. Multi-region Faz 4.

**Mitigasyon:**
- Aurora backup (cross-region, 5 dk PITR)
- S3 cross-region replication
- DR plan hazır (Belge 19) — ama DR region aktif değil
- Manual failover prosedürü

**Yeniden Değerlendirme:** Ay 30
**Sorumlu:** DevOps

---

### RISK-003: Test Coverage Düşük (%0 → %60)

**Durum:** 🟡 Kabul edildi (Faz 0'da %60)
**Olasılık:** Yüksek (şu an)
**Etki:** Orta (bug riski)
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Faz 0'da %60 hedef
- CI'da test zorunlu (merge blocker)
- Yeni kod: %80 zorunlu
- Refactor: %70 minimum

**Yeniden Değerlendirme:** Ay 6
**Sorumlu:** Her developer

---

### RISK-004: Backup Test Edilmemiş

**Durum:** 🟡 Kabul edildi (Faz 0'da düzeltilecek)
**Olasılık:** Orta
**Etki:** Kritik (veri kaybı)
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Faz 0'da backup + restore test
- Haftalık otomatik restore testi (staging'e)
- Monitoring alert (backup fail ise)

**Yeniden Değerlendirme:** Ay 2 (Faz 0 ortası)
**Sorumlu:** DevOps

---

### RISK-005: Plugin Sandbox Escape

**Durum:** 🟡 Kabul edildi (mimari azaltır)
**Olasılık:** Düşük
**Etki:** Kritik (cross-tenant leak, RCE)
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Docker per plugin (Belge 10)
- Scope enforcement
- Behavioral audit
- Kill switch
- Pentest (her plugin review'da)

**Yeniden Değerlendirme:** Sürekli (plugin sistem canlıyken)
**Sorumlu:** Security + Backend

---

### RISK-006: Regülasyon Değişimi (TR/EU)

**Durum:** 🟡 Kabul edildi
**Olasılık:** Yüksek (mevzuat sık değişiyor)
**Etki:** Orta (compliance bozulur)
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Compliance plugin mimarisi (Belge 15)
- Her değişiklik = plugin güncelleme
- Legal advisor (dışarıdan)
- KVKK/GDPR monitoring

**Yeniden Değerlendirme:** Çeyreklik
**Sorumlu:** Legal + CTO

---

### RISK-007: Finansman Sıkıntısı

**Durum:** 🟡 Kabul edildi
**Olasılık:** Orta
**Etki:** Yüksek (operasyon durur)
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Bootstrap fallback (freelance gelir)
- Profitability erken (Faz 2'de cash-positive)
- Bridge round hazır (angel)
- Cost reduction plan (infra + ekip)

**Yeniden Değerlendirme:** Ay 6, 12, 18, 24
**Sorumlu:** CEO + CFO

---

### RISK-008: Ekip Kaybı (Key Person)

**Durum:** 🟡 Kabul edildi
**Olasılık:** Orta
**Etki:** Yüksek (bilgi kaybı)
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Documentation (bu 21 belge)
- Pair programming
- Code review (her PR)
- ESOP (equity — retention)
- 2+ kişi her kritik alanda

**Yeniden Değerlendirme:** Sürekli
**Sorumlu:** CEO + HR

---

### RISK-009: Vendor Lock-in (AWS)

**Durum:** 🟡 Kabul edildi (ADR-010)
**Olasılık:** Orta (uzun vade)
**Etki:** Orta (migration zor)
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Container-based deployment (portable)
- S3-compatible storage (MinIO fallback)
- PostgreSQL (standard)
- Terraform (infra as code)

**Yeniden Değerlendirme:** Ay 36
**Sorumlu:** CTO

---

### RISK-010: Rekabet (SAP/Odoo Karşı Taarruz)

**Durum:** 🟡 Kabul edildi
**Olasılık:** Düşük (kısa vade)
**Etki:** Yüksek
**Oluşturulma:** 2026-11-06

**Mitigasyon:**
- Farklılaşma (meta-platform, sıfır limit)
- Modern stack (hız)
- SMB/mid-market odak (SAP zor)
- Ekosistem moat (Belge 17)

**Yeniden Değerlendirme:** Ay 24
**Sorumlu:** CEO

---

## 6. ISSUE — Known Issues

### ISSUE-001: Superadmin Favorileri Çalışmıyor

**Durum:** 🔴 Bilinen
**Öncelik:** P3 (düşük)
**Tarih:** 2026-11-06

**Sorun:**
Superadmin favori tıklayınca FK violation (tenant_id = NULL, ama policy gereği).

**Etki:**
- Sadece superadmin
- Normal kullanıcılar **etkilenmiyor**

**Çözüm (planlanan):**
- `tenant_id` nullable (yapıldı — ADR-009)
- Superadmin UI ayrı
- Test

**Tahmini:** Ay 8
**Sorumlu:** Backend
**Bağımlı:** TD-002

---

### ISSUE-002: Flash Mesaj Görünmüyor (Bazı Sayfalar)

**Durum:** 🟡 Bilinen
**Öncelik:** P3
**Tarih:** 2026-11-06

**Sorun:**
Feature disabled flash mesajı sidebar'da görünmüyor. Layout'ta `nav_fav` / `nav_error` bloğu eksikti.

**Etki:**
- Kullanıcı neden dashboard'a attığını anlamıyor

**Çözüm (planlanan):**
- Layout'ta flash block ekle (yapıldı)
- Test her sayfada

**Tahmini:** Yapıldı (Faz 0'da test)
**Sorumlu:** Frontend

---

### ISSUE-003: Navbar Bozulma (Alpine Yok)

**Durum:** 🟢 Çözüldü (ADR-007)
**Tarih:** 2026-11-06

**Sorun:**
Alpine.js bazen yüklenmiyor → CSS bozuk.

**Çözüm:**
Native HTML `<details>/<summary>` kullan.

**Sonuç:** ✅ Çalışıyor.

---

### ISSUE-004: Sərfiyyat Kaydetme (SalesOrderService Dönüş)

**Durum:** 🟡 Bilinen
**Öncelik:** P2
**Tarih:** 2026-11-06

**Sorun:**
`SalesOrderService::create()` dönüş değeri `OtcSalesOrder|null` olabilir. Null ise sərfiyyat kaydedilemiyor.

**Etki:**
- Satış kaydedilir ama sərfiyyat kaydedilmez
- Sessiz hata

**Çözüm (planlanan):**
- `SalesOrderService::create()` her zaman `OtcSalesOrder` döndürmeli
- Exception fırlat, null değil
- Test

**Tahmini:** Ay 2
**Sorumlu:** Backend
**Bağımlı:** TD-001

---

### ISSUE-005: `sys_user_nav_item` Migration Sırası

**Durum:** 🟢 Çözüldü
**Tarih:** 2026-11-06

**Sorun:**
Migration timestamp çakışması → tablo oluşmadı.

**Çözüm:**
`make:migration` + timestamp unique.

**Sonuç:** ✅ Çalışıyor.

---

### ISSUE-006: Route Cache (Feature Middleware)

**Durum:** 🟢 Çözüldü
**Tarih:** 2026-11-06

**Sorun:**
`feature:xxx` middleware kaydedilmemiş → route bulunamadı.

**Çözüm:**
`bootstrap/app.php`'de `alias` eklendi.

**Sonuç:** ✅ Çalışıyor.

---

### ISSUE-007: CSS Bozulması (Tailwind Conditional Class)

**Durum:** 🟢 Çözüldü
**Tarih:** 2026-11-06

**Sorun:**
Conditional Tailwind class (`bg-primary` vs `bg-gray`) build'de kayboluyor.

**Çözüm:**
Plain CSS class + `is-on` state.

**Sonuç:** ✅ Çalışıyor.



---

### ISSUE-009: Backup Otomasyonu Yok

**Durum:** 🔴 Bilinen
**Öncelik:** P1 (Faz 0'da düzeltilecek)
**Tarih:** 2026-11-06

**Sorun:**
Otomatik backup yok. Manuel.

**Çözüm (planlanan):**
- Aurora automated backup (6h)
- S3 replication
- Restore test (haftalık)

**Tahmini:** Ay 2
**Sorumlu:** DevOps
**Bağımlı:** RISK-004

---

### ISSUE-010: Sürüm Yönetimi (Semver)

**Durum:** 🟡 Bilinen
**Öncelik:** P3
**Tarih:** 2026-11-06

**Sorun:**
Plugin/API için semantic versioning tam uygulanmıyor.

**Çözüm (planlanan):**
- Her plugin: semver zorunlu
- API: `/v1`, `/v2` (Belge 09)
- Changelog otomatik

**Tahmini:** Ay 12
**Sorumlu:** Backend

---

## 7. Özet İstatistikler

### 7.1. Tablo

| Kategori | Açık | Çözüldü | Toplam |
|---|---|---|---|
| **ADR** | 10 | 0 | 10 |
| **DEF** | 10 | 1 (DEF-012) | 11 |
| **TD** | 8 | 0 | 8 |
| **RISK** | 11 | 0 | 11 |
| **ISSUE** | 3 | 7 | 10 |
| **TOPLAM** | **42** | **8** | **50** |

### 7.2. Öncelik Dağılımı

| Öncelik | Sayı | Örnek |
|---|---|---|
| **P1 (Kritik)** | 3 | ISSUE-008, ISSUE-009, RISK-004 |
| **P2 (Yüksek)** | 8 | DEF-001 (WAF), ISSUE-004 |
| **P3 (Normal)** | 20 | DEF-002 (kiosk), TD-005 (i18n) |
| **P4 (Düşük)** | 13 | DEF-006 (barkod dup) |

### 7.3. Faz Dağılımı

| Faz | DEF/TD/RISK | Örnek |
|---|---|---|
| **Faz 0** | 8 | Test, backup, monitoring |
| **Faz 1** | 10 | Sərfiyyat refactor, e-Fatura |
| **Faz 2** | 8 | Kiosk, barkod, event bus |
| **Faz 3** | 4 | Multi-language |
| **Faz 4** | 6 | Multi-currency, multi-region |
| **Faz 5** | 0 | — |
| **Sürekli** | 8 | Superadmin access, plugin security |

---

## 8. Workflow — Nasıl Kullanılır

### 8.1. Yeni Karar
Karar ortaya çıktı
↓

ADR-XXX oluştur (veya DEF/TD/RISK/ISSUE)
↓

Tarih + karar veren + bağlam
↓

Alternatifler + karar + sonuç
↓

Etkilenen belgeyi referans
↓

Commit: "docs: ADR-XXX eklendi"

text

### 8.2. Kod Referansı

```php
// TODO: DEF-001 — Cloudflare WAF alınınca kaldır
if (!$this->isRateLimited($ip)) {
    return $next($request);
}
Kural: Her TODO/FIXME ID'li olmalı. ID'siz TODO → CI warning.

8.3. Commit Mesajı
text
feat: rate limiting (DEF-001 workaround)
fix: superadmin favorites (ISSUE-001)
refactor: sarfiyyat service (TD-001)
docs: ADR-011 eklendi
8.4. Haftalık Review
Her hafta Cuma:

DEF: yeni deferral var mı?

TD: borç ödendi mi?

RISK: yeni risk var mı?

ISSUE: kapatılabilir mi?

Çıktı: Güncellenmiş 21-decision-register.md

8.5. Çeyreklik Audit
Her 3 ayda:

Tüm DEF/RISK/TD yeniden değerlendir

Öncelik değişti mi?

Bazı DEF'ler kapatılabilir mi?

Yeni risk ortaya çıktı mı?

Çıktı: Board'a özet rapor

9. Anti-Patterns
Bu belgede yasak olan hatalar:

ID'siz TODO — Her TODO'da ID

Silinen ADR — Sadece superseded işaretle

Retrospektif ADR yok — Her karar sonradan belgelenemez, anında belgelenir

DEF takibi yok — Ertelenen unutulmaz, kayıt altında

Risk görmezden gelme — Her risk kayıtlı

TD ödeme yok — Her TD'nin planı var

ISSUE kapatmama — Çözülen issue kapatılır

Commit mesajı eksik — Her commit ID referansı

Review atlama — Haftalık review zorunlu

Audit atlama — Çeyreklik audit

10. Roadmap — Bu Belgenin Evrimi
Faz	Eklenen DEF	Eklenen TD	Eklenen RISK	Eklenen ISSUE
Faz 0	3	2	3	5
Faz 1	5	3	2	3
Faz 2	6	3	2	3
Faz 3	4	2	1	2
Faz 4	3	1	1	1
Faz 5	2	1	1	1
Kural: Her faz sonu net sayı — artış olursa "problem büyüyor" demek.

11. Glossary
Terim	Anlam
ADR	Architecture Decision Record
DEF	Deferred item (ertelenen)
TD	Technical Debt (teknik borç)
RISK	Risk register
ISSUE	Known issue
Superseded	Geçersiz kılındı (yeni ADR ile)
P1-P4	Öncelik seviyeleri
Four-eyes	2 kişi kuralı
Workaround	Geçici çözüm
Mitigasyon	Azaltma stratejisi
12. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — 10 ADR, 11 DEF, 8 TD, 10 RISK, 10 ISSUE
Bu belge canlıdır. Her yeni karar, erteleme, borç, risk, issue — buraya.

### DEF-012: Pulse Route `web.auth` Eksikti — Çözüldü

**Durum:** 🟢 Çözüldü (2026-11-06)
**Öncelik:** P2 → Çözüldü

**Sorun:**
Pulse `/pulse` route'u sadece `web` + `Authorize` middleware'i kullanıyordu.
`Auth::user()` null dönüyordu (RLS context yok) → Gate deny → 403.

**Çözüm:**
`config/pulse.php` → `middleware` bloğuna `'web.auth'` eklendi.

**Öğrenim:**
3rd-party paket route'ları (Pulse, Telescope, Horizon, vs.)
`web.auth` altına alınmalı — aksi halde RLS + Auth çalışmaz.

**Kural (yeni):**
Her yeni paket route'u için kontrol listesi:
1. Route `web.auth` altında mı?
2. RLS context set ediliyor mu?
3. Auth::user() dolu mu?

### RISK-011: 3rd-Party Paket Route'larında `web.auth` Unutulabilir

**Durum:** 🟡 Kabul edildi
**Olasılık:** Orta (yeni paket her seferinde)
**Etki:** Orta (route çalışmaz, 403/500)
**Oluşturulma:** 2026-11-06

**Neden:** Pulse'da yaşandı. Her yeni paket (Horizon, Telescope,
Octane dashboard, vs.) aynı tuzağa düşebilir.

**Mitigasyon:**
- Yeni paket kurulunca **test** zorunlu
- `php artisan route:list -v | grep <paket>` → middleware kontrol
- Deployment checklist'e ekle (Belge 19)
- CI'da route middleware validation (gelecek)

**Sorumlu:** Backend



### ISSUE-008: Test Coverage %0

**Durum:** 🟢 Çözüldü (2026-11-07)
**Çözüm:** 9 test kuruldu (auth, RLS, sərfiyyat, feature flag, CRUD, example)
**Sonuç:** 9/9 PASS, ~30 assertion
**Kapsam:** Temel güvenlik ağı hazır
**Sonraki:** CI entegrasyonu (GitHub Actions)

**CI Entegrasyonu:** ✅ Tamamlandı (2026-11-07)
- GitHub Actions: `.github/workflows/tests.yml`
- Trigger: push to main/develop + PR
- Runtime: ~2-3 dk
- Status: Green



---

## 8. Ek Kayıtlar (Geç Eklenen)

### ADR-011: Pulse — Systemd Daemon (Cron Değil)

**Tarih:** 2026-11-07
**Durum:** ✅ Kabul edildi
**Karar Veren:** CTO

**Bağlam:**
`pulse:check` komutu **tek seferlik değil, daemon'dur** (sonsuz çalışır, her saniye metric toplar). Yanlışlıkla cron'a eklendi → 12 saatte ~144 daemon birikti → RAM tükendi → OOM killer PostgreSQL'i öldürdü → 15 dk kesinti.

**Alternatifler:**

| Yaklaşım | Risk | Kaynak |
|---|---|---|
| **Cron** (`*/5 * * * *`) | ❌ Her 5 dk yeni process | Yüksek |
| **Systemd service** | ✅ Tek process, Restart=always | Kontrollü |
| **Supervisor** | ✅ Tek process | Kontrollü |

**Karar:**
Systemd service (`/etc/systemd/system/erp-pulse.service`):
```ini
[Service]
Type=simple
ExecStart=/usr/bin/php artisan pulse:check
Restart=always
RestartSec=10
MemoryMax=256M
CPUQuota=50%
Sonuç:

Tek daemon, 43 MB RAM, stabil

Resource limit (256M/50% CPU) → taşma koruması

Log: /var/log/erp-pulse.log

Kural (yeni):

Daemon komutları (pulse:check, horizon, octane) → systemd

One-shot komutlar (backup, migrate, prune) → cron

Karıştırma → OOM riski

Etkilenen belgeler: 19-deployment

DEF-013: UptimeRobot — Email Gerekli, Atlandı
Durum: ⏳ Ertelendi (atlandı)
Tarih: 2026-11-07
Neden: UptimeRobot ücretsiz plan email signup zorunlu. Kullanıcı kuralı: "email gerekliyse geçelim."

Alternatif (gelecek):

Self-hosted health check (bash + curl + Discord webhook)

Ya da Faz 1'de: Sentry Free uptime monitoring

Ya da: Kendi Prometheus + Alertmanager stack

Yeniden Değerlendirme: Faz 1 (Ay 6-12)
Sorumlu: DevOps

ISSUE-009: Backup Otomasyonu Yok → ÇÖZÜLDÜ
Durum: 🟢 Çözüldü (2026-11-07)
Öncelik: P1 → Çözüldü

Çözüm:

erp_backup user (BYPASSRLS) — RLS'yi bypass ederek tam dump

/var/www/erp/scripts/backup-database.sh (her 6 saat)

/var/www/erp/scripts/restore-database.sh (test edilmiş)

Cron: 0 */6 * * * deploy ...

Retention: 30 gün

Restore test: 101 tablo, 89 RLS policy doğrulandı

Sonuç: ✅ Backup + restore çalışıyor, test edildi.

Etkilenen belgeler: 19-deployment, 18-security

text

---

## 🎯 Decision Register Sonrası