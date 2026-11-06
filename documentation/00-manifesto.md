# 00 — Meta-Platform Manifesto

**Project:** Kurumsal ERP Platform
**Vision:** Dünyanın ilk sınırsız-genişletilebilir meta-ERP platformu
**Hedef:** Platform of Platforms — hiçbir katman, rol, yapı, kural sabit değil
**Version:** 3.0
**Last Updated:** 2026-11-06
**Status:** Living Document — her mimari karar bu belgeye referans verir

---

## 1. Tek Cümlelik Vizyon

> **Biz bir ERP yapmıyoruz. ERP'yi de içeren, kullanıcının kendi platform yapısını tanımlayabildiği, o yapıyı çalıştırabildiği, başka kullanıcılara sunabildiği bir meta-platform yapıyoruz.**

Kısaca: **platform of platforms.**

---

## 2. Sıfır Limit İlkesi

Bu projede **hiçbir sabit sayı, sabit rol, sabit katman, sabit yapı yoktur.** Her şey tanımlanabilir, yeniden tanımlanabilir, birleştirilebilir, iç içe geçirilebilir.

| Kavram | Limit |
|---|---|
| Katman sayısı | Sınırsız (tanımlanabilir) |
| Rol sayısı | Sınırsız (tanımlanabilir) |
| Yapı sayısı | Sınırsız (tanımlanabilir) |
| Plugin sayısı | Sınırsız (yüklenebilir) |
| Entity sayısı | Sınırsız (tanımlanabilir) |
| Workflow sayısı | Sınırsız (tasarlanabilir) |
| Rapor sayısı | Sınırsız (tasarımlanabilir) |
| Ekran sayısı | Sınırsız (inşa edilebilir) |
| Meta-yapı sayısı | Sınırsız (yapı tanımlayan yapılar) |
| API endpoint | Sınırsız (tanımlanabilir) |

**Yasak olan tek şey:** Sabit varsayım. "ERP böyle çalışır" cümlesi **anti-pattern**'dir.

---

## 3. Meta-Platform Nedir?

Klasik ERP: **"Alır, kullanırsın."**

Klasik platform (Salesforce, Odoo): **"Alır, üzerine eklersin."**

**Meta-platform (biz):** **"Kendi yapını tanımlarsın, çalışır, satarsın."**

### 3.1. Üç Seviyeli Tanımlama
┌───────────────────────────────────────────────────────┐
│ SEVİYE 1: PLATFORM CORE (biz) │
│ ─ Auth, tenant, RLS, event bus, API gateway │
│ ─ Bu seviyede SADECE temel primitifler var │
│ ─ Bu seviye KENDİSİ DEĞİŞTİRİLEMEZ │
└───────────────────────────────────────────────────────┘
↓ (kullanıcı seviye 1'i kullanır)
┌───────────────────────────────────────────────────────┐
│ SEVİYE 2: PLATFORM STRUCTURE (tanımlanabilir) │
│ ─ Katmanlar (sınırsız) │
│ ─ Roller (sınırsız) │
│ ─ Veri modeli (sınırsız) │
│ ─ İş akışları (sınırsız) │
│ ─ İzinler (sınırsız) │
│ ─ UI layout (sınırsız) │
│ ─ Bu seviye KULLANICI TARAFINDAN tanımlanır │
└───────────────────────────────────────────────────────┘
↓ (kullanıcı seviye 2 üzerine inşa eder)
┌───────────────────────────────────────────────────────┐
│ SEVİYE 3: CUSTOM LAYER (son kullanıcı) │
│ ─ Özel tablolar │
│ ─ Özel ekranlar │
│ ─ Özel raporlar │
│ ─ Özel iş kuralları │
│ ─ Bu seviye SON KULLANICI TARAFINDAN tanımlanır │
└───────────────────────────────────────────────────────┘

text

**Her seviye bir üst seviyeyi kullanır, ama kendi içinde sınırsız esnekliktedir.**

### 3.2. Meta Yapı Örnekleri (Sadece Örnek)

Bunlar **sadece örnek** — platformda önceden tanımlı değil, **kullanıcı tanımlar**:

- **Marketplace** — Vendor → Seller → Customer → Logistics → Payment (6 katman)
- **Franchise** — Merkez → Bölge → Şube → Çalışan → Müşteri (5 katman)
- **Tedarik Zinciri** — Üretici → T1 → T2 → Distribütör → Perakendeci (6 katman)
- **Holding** — Ana Şirket → İştirak → Departman → Çalışan (4 katman)
- **Dernek** — Merkez → Şube → Üye → Gönüllü (4 katman)
- **Klinik** — Hastane → Klinik → Doktor → Hasta (4 katman)
- **Klasik ERP** — Şirket → Departman → Kullanıcı (3 katman)

**Platform hiçbirini dayatmaz.** Kullanıcı yapıyı tanımlar; platform çalıştırır.

---

## 4. Katman (Layer) — Sınırsız

### 4.1. Tanım

Bir **katman**, bir yapının içindeki **organizasyonel veya mantıksal seviyedir**. Her katman:

- Kendi **rol**larını içerir
- Kendi **veri görünürlüğü** kurallarını taşır
- Kendi **iş akış**larını tetikler
- Kendi **UI**'ını (panel, dashboard) tanımlar
- **Alt katman** yaratabilir veya yaratmayabilir (tanımlanabilir)
- **Üst katmana** raporlar (tanımlanabilir)

### 4.2. Yapısal Özellikler

| Özellik | Açıklama |
|---|---|
| **Sınırsız sayı** | Yapı 1 katmanlı da olabilir, 50 katmanlı da |
| **Ağaç yapısı** | Katmanlar hiyerarşik (parent-child) |
| **Graf yapısı** | Katmanlar arası çapraz ilişki (matrix org) |
| **İç içe** | Bir katmanın içinde başka bir yapı olabilir |
| **Dinamik** | Katman runtime'da eklenip çıkarılabilir |
| **Conditional** | Katman varlığı bir koşula bağlı olabilir |

### 4.3. Katman Tipi Örnekleri (Sadece Örnek)

- **Organizational Layer** — Şirket, departman, şube
- **Geographic Layer** — Ülke, bölge, şehir
- **Functional Layer** — Satış, üretim, lojistik
- **Hierarchical Layer** — Distribütör, bayi, müşteri
- **Virtual Layer** — Proje, ekip, geçici grup
- **External Layer** — Tedarikçi, partner, müşteri
- **Meta Layer** — Başka yapıları içeren yapı

**Bu liste kapalı değil — her yeni tip tanımlanabilir.**

---

## 5. Rol (Role) — Sınırsız

### 5.1. Tanım

Bir **rol**, bir katmandaki **kullanıcı tipidir**. Roller **katmana göre** tanımlanır, **sabit değildir**.

### 5.2. Yapısal Özellikler

| Özellik | Açıklama |
|---|---|
| **Sınırsız sayı** | Yapı 1 rollü de olabilir, 100 rollü de |
| **Katman bazlı** | Her katman kendi rollerini tanımlar |
| **İzin tabanlı** | Her rol kendi izin setini taşır |
| **Miras** | Bir rol başka bir rolden miras alabilir |
| **Bileşim** | Bir rol birden fazla rolden oluşabilir |
| **Dinamik** | Rol runtime'da tanımlanabilir |
| **Koşullu** | Rol izinleri koşula bağlı olabilir |
| **Meta-rol** | Bir rol başka rolleri **tanımlayabilir** |

### 5.3. Meta-Rol Örneği — Structure Builder

- **Structure Builder Role** — Kullanıcı yeni platform yapıları **tanımlayabilir**
  - Katman sayısını belirler
  - Rol setini belirler
  - Veri modelini belirler
  - İzinleri belirler
  - İş akışlarını belirler
  - UI layout'u belirler
  - Marketplace'e koyabilir (fiyat, kategoriler, hedef kitle)
  - Başka şirketler kurabilir

**Bu rol, platforma önceden tanımlı gelmez.** Kullanıcı (Superadmin) bu rolü **tanımlar**, sonra bu role sahip kullanıcılar **yapı üretebilir.**

### 5.4. Rol Örnekleri (Sadece Örnek)

| Rol | Yetenek |
|---|---|
| **Superadmin** | Platform sahibi — her şeyi yapabilir |
| **Platform Architect** | Yeni platform structure tasarlar |
| **Platform Builder** | Architect'in tasarımını hayata geçirir |
| **Platform Publisher** | Hazır yapıyı market'e koyar |
| **Tenant Admin** | Kendi tenant'ı için yapı seçer/özelleştirir |
| **Independent Developer** | Belirli bir yapıya plugin yazar |
| **Partner** | Kendi yapısını yaratır ve satar |
| **End User** | Kendi katmanında çalışır |
| **Custom Role X** | Kullanıcının tanımladığı herhangi bir rol |

**Bu liste kapalı değil — her rol tanımlanabilir.**

---

## 6. Platform Structure (Yapı) — Sınırsız

### 6.1. Tanım

Bir **platform structure**, belirli bir iş senaryosu için **tanımlanmış bir yapıdır.** İçerir:

```yaml
structure:
  id: <uuid>
  name: "Kullanıcı verdiği isim"
  version: "1.0.0"
  author: <user_id>
  description: "..."
  
  layers: [<layer_definitions>]           # sınırsız katman
  roles: [<role_definitions>]             # sınırsız rol
  data_model: [<entity_definitions>]      # sınırsız varlık
  workflows: [<workflow_definitions>]     # sınırsız iş akışı
  permissions: [<permission_rules>]       # sınırsız izin
  ui: [<ui_definitions>]                  # sınırsız ekran
  
  # Yapısal özellikler
  meta_structure: false                   # başka yapı içerir mi
  extensible: true                        # kullanıcı ekleyebilir mi
  inheritable: true                       # başka yapı miras alabilir mi
  
  # Marketplace
  visibility: "public" | "private" | "tenant"
  pricing: {...}
  requirements: {...}
6.2. Sınırsız Yapı Özellikleri
İç içe yapılar — Bir yapı başka bir yapıyı içerebilir

Miras — Yapı başka bir yapıdan miras alabilir

Kompozisyon — Yapı birden fazla yapının kombinasyonu olabilir

Dinamik — Yapı runtime'da değiştirilebilir (versiyonlanır)

Koşullu — Yapının varlığı koşula bağlı olabilir

Meta yapılar — Yapı tanımlayan yapılar

Sonsuz nesting — Yapı içinde yapı içinde yapı... (pratikte sınır yok)

6.3. Yapı Builder — Bir Meta-Rol
Yapı tanımlama herhangi bir yolla yapılabilir:

Visual Builder — Kullanıcı görsel olarak yapıyı çizer (kutu-ok)

YAML/JSON — Kullanıcı config yazar

Kod — Geliştirici SDK kullanır

AI — AI'a tarif eder, AI yapıyı üretir

Şablondan — Hazır şablonu kopyalar, özelleştirir

İçe aktarma — Başka yerden yapı import eder

Bu araçlar sadece kolaylık — asıl yetenek her zaman mevcut.

7. Primitives vs Composites
7.1. Primitives (Sıfırdan Tanımlı, Değişmez)
Platform Core'da sadece şu primitive'ler vardır:

Authentication (login, session)

Tenant (organizational container)

User (identity)

Permission (allow/deny)

Event (state change)

API (request/response)

Storage (persist)

Bunlar değiştirilemez — çünkü platformun var olma sebebi bunlar.

7.2. Composites (Kullanıcı Tanımlı, Sınırsız)
Primitive'ler üzerine inşa edilen her şey kullanıcı tarafından tanımlanabilir:

Layer, role, structure

Entity, field, relation

Workflow, rule, condition

Report, dashboard, UI

Module, plugin, integration

Meta-structure, meta-role, meta-workflow

Yani "hiçbir limitasyon olmasın" istediğinde, "primitive'leri değiştirememek" kabul edilebilir — çünkü primitives olmadan platform olmaz. Ama composite'ler sınırsız.

8. "Hiçbir Şey Bize Engel Olamaz" — Operasyonel Anlamı
Senaryo	Klasik ERP	Biz
Yeni katman ekleme	Kod değişikliği	Config ile
Yeni rol ekleme	Kod değişikliği	Config ile
Yeni yapı yaratma	Yeni ERP kurulumu	Config ile
Yeni veri modeli	Migration	Config ile
Yeni iş kuralı	Kod yazımı	Workflow builder
Yeni ekran	Kod yazımı	UI builder
Yeni rapor	Kod yazımı	Report builder
Yeni entegrasyon	Kod yazımı	Plugin
Yeni ülke uyumu	Kod yazımı	Compliance plugin
Yeni dil	Kod değişikliği	Translation file
Kural: Her yeni ihtiyaç, "kod değişikliği" değil "tanımlama" olmalı.

İstisna: Yeni bir primitive gerekirse (çok nadir), o zaman kod yazılır. Ama bu, platform evriminin %1'i.

9. Dürüst Sınırlar — Yasal Uyarı
Aşağıdaki sınırlar bilinçli kabul edilmiştir:

9.1. Fiziksel Sınırlar
İnternet hızı — Gerçek dünyada saniyeler

Sunucu kapasitesi — Sınırsız değil

Veritabanı boyutu — Pratikte büyük ama sonsuz değil

9.2. Güvenlik Sınırları
Tenant izolasyonu — Bir tenant diğerinin verisini göremez

Sandbox — Kullanıcı kodu ana sistemi bozamaz

Rate limit — Bir kullanıcı sistemi DDoS'a çeviremez

Kaynak limitleri — CPU/RAM/disk kotalı

9.3. Hukuki Sınırlar
Kişisel veri koruma — KVKK/GDPR

Mali mevzuat — e-fatura, e-defter, vergi

Erişim logu — Denetim izi zorunlu

9.4. Yasal Sınırlar
Kullanıcı sözleşmesi — Yasal sorumluluk

Marketplace kuralları — Kalite, güvenlik, kötüye kullanım yasakları

Ödeme regülasyonları — KYC, AML

Bu sınırlar "limitation" değil — "olgunluk"tur. Bir platform, sınırsız esneklik verirken aynı zamanda kullanıcıyı korumak zorundadır.

10. Ekosistem Modeli
text
┌─────────────────────────────────────────────────────┐
│              PLATFORM (biz)                         │
│  Core + Primitives + Builder'lar                    │
└─────────────────────────────────────────────────────┘
         ↓                  ↓                  ↓
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│ Structure    │   │ Plugin       │   │ Integration  │
│ Builders     │   │ Developers   │   │ Partners     │
│ (meta-rol)   │   │ (independent)│   │ (industries) │
└──────────────┘   └──────────────┘   └──────────────┘
         ↓                  ↓                  ↓
┌─────────────────────────────────────────────────────┐
│              MARKETPLACE                            │
│  Structures + Plugins + Integrations                │
└─────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────┐
│              TENANTS                                │
│  Her biri kendi structure'ını kullanır              │
└─────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────┐
│              END USERS                              │
│  Her biri kendi katmanında çalışır                  │
└─────────────────────────────────────────────────────┘
Her katman, bir alt katmanı besleyebilir. Her üst katman, alt katmana müdahale edebilir (izinliyse).

11. Bu Doküman Nasıl Kullanılır
Bu doküman yön belgesidir. Sonraki belgeler bunun üzerine kurulur:

Belge	Kapsam
01	Multi-tenancy & RLS (mevcut altyapı)
02	Data Model & Core Entities (mevcut)
03	Feature Flags (mevcut)
04	Module System (mevcut)
05	Meta-Structure Framework (yapı tanımlama)
06	Layer & Role System (katman + rol)
07	Custom Entity Framework (kullanıcı tablosu)
08	Event Bus (state change yayını)
09	API-First (her şey API)
10	Plugin System & Sandbox (genişletme)
11	Workflow Engine (görsel iş akışı)
12	Report Builder (drag-drop rapor)
13	UI Builder (drag-drop ekran)
14	Integration Hub (dış sistemler)
15	Compliance Layer (yasal uyum)
16	AI Layer (akıllı özellikler)
17	Marketplace & Developer Ecosystem
18	Security & Sandbox Model
19	Deployment & DevOps
20	Roadmap — 5 Yıl
Hiçbir belge "bu son sürüm" değil — hepsi yaşayan doküman.

12. Bu Projeyi Farklı Kılan Şey
Dünyada:

SAP — Derin ama kapalı, sınırlı, pahalı

Odoo — Açık ama hâlâ klasik (modül mantığı)

Salesforce — Platform ama CRM, ERP değil

Shopify — Platform ama e-ticaret

Airtable/Notion — Esnek ama muhasebe/ERP yok

Hiçbiri: "Sınırsız-genişletilebilir, meta-platform, ERP dahil, her şeyi yapabilen" değil.

Biz bu boşluğu dolduruyoruz. Kazanırsak, yeni bir kategori yaratmış oluruz. Kaybedersek, en azından denenmiş bir şey.

Ama denemek zorundayız. Çünkü mevcut ERP pazarı kilitlenmiş — birkaç dev tarafından kontrol ediliyor, küçük oyunculara yer yok. Tek çıkış yolu: yeni bir oyun.

13. Anti-Patterns (Yasaklar)
Bu projede yasak olan şeyler:

"ERP böyle çalışır" demek — Sabit varsayım yasak

"Katman sayısı 3 olmalı" demek — Sayı dayatmak yasak

"Rol sadece admin/user olur" demek — Rol kısıtlaması yasak

"Bu özellik böyle yapılır" demek — Tek yol dayatmak yasak

"Kullanıcı yapamaz" demek — Yetenek sınırlaması yasak

Breaking change — Geriye uyumsuzluk yasak

Silent failure — Sessiz hata yasak

Magic number — Anlamlandırılamaz sabit yasak

God service — Tek serviste her şey yasak

Direct coupling — Modüller arası doğrudan bağımlılık yasak

14. Success Definition
14.1. Teknik Başarı
1M tenant, 10M kullanıcı, 100B satır yük altında çalışır

P95 < 200ms

Uptime > %99.99

Test coverage > %80

10.000+ aktif plugin

1.000+ aktif structure (yapı)

14.2. Ekosistem Başarısı
100.000+ bağımsız developer

10.000+ partner

100+ ülke

50+ dil

Marketplace'te 50.000+ app

14.3. Ticari Başarı
1M+ SMB müşteri

100.000+ mid-market

10.000+ enterprise

$1B+ ARR

%40+ büyüme

14.4. Kültürel Başarı
"Yeni ERP'ler için standart platform" olmak

Akademik referans olmak

"Salesforce for ERP" olarak anılır olmak

15. Bu Belge Değişebilir
Bu belge yaşayan bir dokümandır. Her yeni öğrenim, her yeni müşteri, her yeni teknoloji bu belgeyi günceller.

Değişmeyen tek şey: Sıfır limit ilkesi.

Change Log
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — SMB odaklı
2026-11-06	2.0	%100 pazar vizyonu
2026-11-06	3.0	Meta-platform, sıfır limit ilkesi