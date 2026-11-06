# 20 — Roadmap

**Project:** Kurumsal ERP Platform
**Belge:** 5 Yıllık Yol Haritası, Faz Planlaması, Ekip Büyümesi, Finansal Projeksiyon, Risk Yönetimi
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Living Document — her çeyrek gözden geçirilir
**Depends On:** Tüm önceki belgeler (00-19)

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **5 yıllık yol haritasını** tanımlar — Belge 00'daki vizyonu (meta-platform, %100 pazar, ekosistem) **operasyonel plana** dönüştürür. İçerir:

- 5 yıllık vizyon özeti
- 6 faz (0-60 ay)
- Her fazın hedefleri, çıktıları, KPI'ları
- Ekip büyümesi (kişi sayısı, roller)
- Finansal projeksiyon (gelir, gider, yatırım)
- Risk yönetimi
- Başarı metrikleri
- Go/no-go karar noktaları
- Anti-patterns

**Hedef kitle:** Kurucular, yatırımcılar, CTO, ürün ekibi, AI asistanlar.

**Kritik:** Roadmap **iddialı ama gerçekçi**. SAP'yi 5 yılda geçmek imkansız — ama **kendi kategorimizi** (meta-platform ERP) yaratmak mümkün. Bu belge o yolu gösterir.

---

## 2. 5 Yıllık Vizyon Özeti

### 2.1. Nereden → Nereye

| | Bugün (Ay 0) | 5 Yıl Sonra (Ay 60) |
|---|---|---|
| **Ürün** | MVP, ~80 fonksiyon | Meta-platform, ~8.000 fonksiyon (biz) + ~40.000 (ekosistem) |
| **Müşteri** | 0 | 100.000+ tenant |
| **Developer** | 0 | 100.000+ kayıtlı, 10.000+ aktif |
| **App** | 0 | 50.000+ marketplace |
| **Ülke** | TR | 50+ ülke |
| **Dil** | TR | 30+ dil |
| **ARR** | 0 | $100M+ |
| **Ekip** | 1-3 kişi | 200-500 kişi |
| **Değer** | Pre-seed | $1B+ (unicorn) |

### 2.2. 5 Yılın Tek Cümlesi

> **"Dünyanın ilk sınırsız-genişletilebilir meta-ERP platformu olmak; SAP'nin derinliği, Odoo'nun esnekliği, Salesforce'un ekosistemi — ama modern stack'te."**

### 2.3. Felsefe

**Platform of platforms** (Belge 00):

- %20 biz yazarız (core + framework)
- %80 ekosistem yazar (plugins + verticals + connectors)
- 100.000 developer × ortalama 2 app = **200.000 app potansiyeli**

**Kural:** Biz **inşa etmeyiz**, **inşa edilebilir kılarız**.

---

## 3. 6 Faz — Genel Bakış

| Faz | Ay | Ad | Odak |
|---|---|---|---|
| **0** | 0-6 | Foundation | Çekirdek sağlamlaştırma |
| **1** | 6-12 | MVP Launch | İlk müşteri (beta) |
| **2** | 12-24 | Meta-Platform | Farklılaşma |
| **3** | 24-36 | Ekosistem | Developer economy |
| **4** | 36-48 | Ölçekleme | Multi-country |
| **5** | 48-60 | Global | Unicorn |

---

## 4. FAZ 0 — Foundation (Ay 0-6)

### 4.1. Hedef

**MVP'yi production-grade yap.** Özellik ekleme **durur**, sağlamlaştırma başlar.

### 4.2. Neden?

Mevcut durum: ~80 fonksiyon çalışıyor **ama** test, monitoring, backup, performans yok (Belge 18). Bu haliyle **hiçbir müşteriye** satılamaz.

### 4.3. Çıktılar

**Teknik:**
- ✅ Test coverage ≥ %60
- ✅ Sentry + Laravel Pulse kurulu
- ✅ Backup + restore test edilmiş
- ✅ Zero-downtime deployment
- ✅ Health check endpoint
- ✅ Rate limiting
- ✅ RLS audit (tüm tablolar)
- ✅ Auth hardening (MFA opsiyonel)
- ✅ Documentation (kullanıcı kılavuzu)

**Ürün:**
- ✅ 3 gerçek beta müşteri (ücretsiz, 6 ay)
- ✅ 30 günlük kullanım verisi
- ✅ Bug listesi (öncelikli)
- ✅ Feedback döngüsü

**Ekip:**
- 1-3 kişi (kurucu + 1-2 developer)

### 4.4. KPI'lar

| Metrik | Hedef |
|---|---|
| **Uptime** | 99% |
| **P95 latency** | < 500ms |
| **Test coverage** | ≥ 60% |
| **Backup restore** | Haftalık test |
| **Beta müşteri** | 3 aktif |
| **Kritik bug** | 0 |

### 4.5. Go/No-Go (Ay 6)

**Devam kriteri:**
- ✅ 3 beta müşteri **aktif kullanıyor**
- ✅ Uptime > %99
- ✅ Kritik bug yok
- ✅ En az 1 müşteri "öderim" diyor

**Dur kriteri:**
- ❌ Beta müşteri kullanmıyor → ürün-pazar uyumu yok
- ❌ Sürekli crash → mimari sorun
- ❌ Backup çalışmıyor → risk

### 4.6. Bütçe

| Kalem | Aylık | 6 ay |
|---|---|---|
| **Infra** | $200 | $1.200 |
| **Araçlar** (Sentry, GitHub) | $100 | $600 |
| **Domain, SSL** | $20 | $120 |
| **Toplam** | **$320** | **~$2.000** |

**Kişi:** Kurucu + 1-2 freelance developer (~$5K-15K/ay).

---

## 5. FAZ 1 — MVP Launch (Ay 6-12)

### 5.1. Hedef

**İlk ödeyen müşteri.** Ürün-pazar uyumu kanıtla.

### 5.2. Çıktılar

**Ürün:**
- ✅ Meta-platform foundation (Belge 05)
  - Platform structure (YAML)
  - Layer system
  - Role system
- ✅ Custom entity framework (Belge 07)
- ✅ Feature flags (Belge 03) — mevcut + genişletme
- ✅ Workflow engine MVP (Belge 11)
  - State machine
  - Approval flow
- ✅ Report builder MVP (Belge 12)
  - Basic table + chart
- ✅ UI builder MVP (Belge 13)
  - Theme + layout seviyesi

**Pazara çıkış:**
- ✅ Landing page
- ✅ Pricing (Free, Starter, Pro)
- ✅ Onboarding wizard
- ✅ Kullanıcı dokümantasyonu
- ✅ Video eğitimler (10+)

**Satış:**
- ✅ 10-30 ödeyen müşteri
- ✅ $5K-15K MRR

**Ekip:**
- 3-5 kişi (kurucu + 2-3 developer + 1 satış/başarı)

### 5.3. KPI'lar

| Metrik | Hedef |
|---|---|
| **Tenant** | 30 aktif |
| **MRR** | $10K |
| **Churn** | < %5/ay |
| **NPS** | > 30 |
| **Uptime** | 99.5% |
| **Kullanıcı başına gelir** | $50/ay |

### 5.4. Go/No-Go (Ay 12)

**Devam:**
- ✅ $10K MRR
- ✅ Churn < %5
- ✅ NPS > 30
- ✅ En az 5 müşteri "arkadaşıma öneriyorum" diyor

**Pivot:**
- ⚠️ MRR $2-5K → ürün farklılaşması yetersiz
- ⚠️ Churn > %10 → retention sorunu

**Dur:**
- ❌ MRR < $2K → pazar yok
- ❌ 12 ay sonra hâlâ MVP → mimari çok yavaş

### 5.5. Bütçe

| Kalem | Aylık | 6 ay |
|---|---|---|
| **Ekip** | $20K-40K | $120K-240K |
| **Infra** | $500 | $3.000 |
| **Marketing** | $2K | $12K |
| **Toplam** | **~$25K/ay** | **~$150K-250K** |

**Finansman:** Pre-seed ($300K-500K) veya bootstrap.

---

## 6. FAZ 2 — Meta-Platform (Ay 12-24)

### 6.1. Hedef

**Farklılaşmayı kanıtla.** "Fully customizable" gerçek olsun.

### 6.2. Çıktılar

**Ürün — Meta-Platform tam:**
- ✅ Platform structure builder (visual) — Belge 05
- ✅ Layer + role system (sınırsız) — Belge 06
- ✅ Custom entity framework (tam) — Belge 07
- ✅ Event bus — Belge 08
- ✅ API-first (REST + generic) — Belge 09
- ✅ Plugin system MVP (sandbox) — Belge 10
- ✅ Workflow engine (tam) — Belge 11
- ✅ Report builder (tam, 12+ chart) — Belge 12
- ✅ UI builder (screen level) — Belge 13

**Ekosistem temeli:**
- ✅ Developer SDK
- ✅ 20+ built-in connector (banka, GİB, kargo, e-ticaret)
- ✅ Compliance TR paketi (Belge 15)
  - e-Fatura, e-Defter, KVKK, TDHP

**Pazara çıkış:**
- ✅ Self-service onboarding (video + guide)
- ✅ Marketplace v1 (Belge 17)
  - İlk 10-20 app (partner'lar)
- ✅ API dokümantasyonu (OpenAPI)
- ✅ Postman collection

**Satış:**
- ✅ 100-300 müşteri
- ✅ $30K-80K MRR

**Ekip:**
- 8-15 kişi
- 4-6 developer
- 1-2 DevOps
- 1 designer
- 1-2 satış
- 1 customer success
- 1 product manager

### 6.3. KPI'lar

| Metrik | Hedef |
|---|---|
| **Tenant** | 200 aktif |
| **MRR** | $50K |
| **Churn** | < %3/ay |
| **NPS** | > 40 |
| **Marketplace app** | 20 |
| **API calls/gün** | 100K |
| **Uptime** | 99.9% |
| **Developer kayıt** | 50 |

### 6.4. Go/No-Go (Ay 24)

**Devam (Serie A hazır):**
- ✅ $50K MRR
- ✅ Churn < %3
- ✅ 20+ marketplace app
- ✅ En az 10 partner developer
- ✅ Meta-platform (custom entity) aktif kullanımda (5+ müşteri)

**Pivot:**
- ⚠️ Marketplace tutmuyor → değer önerisi zayıf
- ⚠️ Custom entity kullanılmıyor → "no-code" fikri çalışmıyor

**Dur:**
- ❌ MRR durgun
- ❌ Churn artıyor

### 6.5. Bütçe

| Kalem | Aylık | 12 ay |
|---|---|---|
| **Ekip** | $80K-150K | $960K-1.8M |
| **Infra** | $3K | $36K |
| **Marketing** | $10K | $120K |
| **Toplam** | **~$100K/ay** | **~$1.2M/yıl** |

**Finansman:** Seed ($2M-4M).

---

## 7. FAZ 3 — Ekosistem (Ay 24-36)

### 7.1. Hedef

**Developer economy** — marketplace büyüsün.

### 7.2. Çıktılar

**Ürün:**
- ✅ Plugin system tam (sandbox production) — Belge 10
- ✅ Marketplace v2 — Belge 17
  - Billing, payout, komisyon
  - Rating + review
  - Partner program
- ✅ Compliance EU paketi (GDPR, eIDAS, PEPPOL)
- ✅ Compliance US paketi (SOX, GAAP)
- ✅ 50+ built-in connector
- ✅ AI plugin'ler (developer yazar, Belge 00 — 12.5)
- ✅ Multi-language (TR + EN + DE + FR + AR)

**Ekosistem:**
- ✅ 500+ app marketplace'te
- ✅ 1.000+ kayıtlı developer
- ✅ 100+ aktif partner
- ✅ Developer Academy (eğitim)
- ✅ Developer Conference (yıllık)

**Pazara çıkış:**
- ✅ Partner program (reseller)
- ✅ Enterprise satış (mid-market)
- ✅ Case studies (20+)
- ✅ Analyst relations (Gartner, Forrester)

**Satış:**
- ✅ 1.000-2.000 müşteri
- ✅ $150K-300K MRR
- ✅ İlk $1M+ ARR

**Ekip:**
- 25-50 kişi
- 15-25 developer
- 3-5 DevOps
- 3 designer
- 5-10 satış
- 3-5 customer success
- 2-3 product

### 7.3. KPI'lar

| Metrik | Hedef |
|---|---|
| **Tenant** | 1.500 aktif |
| **MRR** | $200K |
| **ARR** | $2.4M |
| **Churn** | < %2/ay |
| **Marketplace app** | 500 |
| **Developer aktif** | 1.000 |
| **Partner** | 100 |
| **Ülke** | 10+ |
| **API calls/gün** | 10M |
| **Uptime** | 99.95% |

### 7.4. Go/No-Go (Ay 36)

**Devam (Serie B):**
- ✅ $2.4M ARR
- ✅ 500+ app
- ✅ %30+ gelir marketplace'ten (indirect)
- ✅ 10+ ülkede aktif
- ✅ Churn < %2

**Pivot:**
- ⚠️ Marketplace %10'un altında → ekosistem zayıf
- ⚠️ Enterprise satış yok → sadece SMB

### 7.5. Bütçe

| Kalem | Aylık | 12 ay |
|---|---|---|
| **Ekip** | $300K-500K | $3.6M-6M |
| **Infra** | $15K | $180K |
| **Marketing** | $50K | $600K |
| **Toplam** | **~$400K/ay** | **~$4.8M/yıl** |

**Finansman:** Serie A ($8M-15M).

---

## 8. FAZ 4 — Ölçekleme (Ay 36-48)

### 8.1. Hedef

**Multi-country + Enterprise.** Unicorn'a hazırlık.

### 8.2. Çıktılar

**Ürün:**
- ✅ Multi-country (10+ ülke tam)
- ✅ Multi-currency (paralel ledger — Belge 15)
- ✅ IFRS + GAAP + TDHP (paralel)
- ✅ Consolidation (holding)
- ✅ Enterprise features:
  - SSO (SAML, OIDC)
  - Audit trail (WORM, hash chain)
  - Granular permissions (action-level)
  - Advanced workflow (parallel, escalation)
- ✅ AI plugin'ler (marketplace'te)
- ✅ Marketplace v3 (AI, enterprise)
- ✅ API v2 (GraphQL)

**Pazar:**
- ✅ EU'da aktif (Frankfurt + local)
- ✅ US'te başlangıç
- ✅ APAC'ta başlangıç
- ✅ Enterprise müşteri (Fortune 1000)
- ✅ Sector verticals (sağlık, üretim, perakende, finans)

**Satış:**
- ✅ 5.000-10.000 müşteri
- ✅ $500K-1M MRR
- ✅ $6M-12M ARR
- ✅ Enterprise deal ($50K-500K/yıl)

**Ekip:**
- 60-100 kişi
- Multi-region (TR + EU + US)

### 8.3. KPI'lar

| Metrik | Hedef |
|---|---|
| **Tenant** | 7.500 aktif |
| **ARR** | $10M |
| **MRR** | $800K |
| **Enterprise customer** | 50+ |
| **Marketplace app** | 2.000 |
| **Developer aktif** | 5.000 |
| **Partner** | 300 |
| **Ülke** | 25+ |
| **Uptime** | 99.98% |
| **NPS** | > 50 |
| **Churn** | < %1.5/ay |

### 8.4. Go/No-Go (Ay 48)

**Devam (Serie C / IPO prep):**
- ✅ $10M ARR
- ✅ %40+ YoY büyüme
- ✅ Enterprise satış (50+)
- ✅ Multi-country operasyon
- ✅ NPS > 50

**Pivot:**
- ⚠️ Büyüme < %30 → pazar doygun
- ⚠️ Enterprise satış zayıf

### 8.5. Bütçe

| Kalem | Aylık | 12 ay |
|---|---|---|
| **Ekip** | $1M-1.5M | $12M-18M |
| **Infra** | $50K | $600K |
| **Marketing** | $150K | $1.8M |
| **Toplam** | **~$1.2M/ay** | **~$15M/yıl** |

**Finansman:** Serie B ($25M-50M).

---

## 9. FAZ 5 — Global (Ay 48-60)

### 9.1. Hedef

**Unicorn.** Global lider.

### 9.2. Çıktılar

**Ürün:**
- ✅ 50+ ülke
- ✅ 30+ dil
- ✅ Full compliance (her ülke)
- ✅ Enterprise-grade (Fortune 500)
- ✅ Marketplace 50.000+ app
- ✅ AI layer (plugin ecosystem)

**Pazar:**
- ✅ Global (tüm kıtalar)
- ✅ Her segment (SMB, mid-market, enterprise)
- ✅ Sektör liderliği (ERP + meta-platform)

**Satış:**
- ✅ 50.000-100.000 müşteri
- ✅ $8M-12M MRR
- ✅ $100M+ ARR
- ✅ $1B+ valuation (unicorn)

**Ekip:**
- 200-500 kişi
- 5+ region

### 9.3. KPI'lar

| Metrik | Hedef |
|---|---|
| **Tenant** | 75.000 |
| **ARR** | $100M |
| **MRR** | $8M |
| **Enterprise customer** | 500+ |
| **Marketplace app** | 50.000 |
| **Developer aktif** | 50.000 |
| **Partner** | 2.000 |
| **Ülke** | 50+ |
| **Dil** | 30+ |
| **Uptime** | 99.99% |
| **NPS** | > 60 |
| **Churn** | < %1/ay |

### 9.4. Başarı

- ✅ **Unicorn** ($1B+ değer)
- ✅ **Pazar lideri** (ERP SaaS)
- ✅ **Ekosistem** (100K+ developer)
- ✅ **Kategori yaratıcısı** (meta-platform ERP)
- ✅ **SAP'nin genç nesil alternatifi**

### 9.5. Bütçe

| Kalem | Aylık | 12 ay |
|---|---|---|
| **Ekip** | $3M-5M | $36M-60M |
| **Infra** | $200K | $2.4M |
| **Marketing** | $500K | $6M |
| **Toplam** | **~$4M/ay** | **~$48M/yıl** |

**Finansman:** Serie C+ veya IPO ($50M-200M).

---

## 10. Özet Tablo — 5 Yıl

| Faz | Ay | Tenant | ARR | Ekip | Marketplace App | Ülke |
|---|---|---|---|---|---|---|
| **0** | 0-6 | 3 beta | $0 | 3 | 0 | 1 |
| **1** | 6-12 | 30 | $120K | 5 | 0 | 1 |
| **2** | 12-24 | 200 | $600K | 15 | 20 | 3 |
| **3** | 24-36 | 1.500 | $2.4M | 50 | 500 | 10 |
| **4** | 36-48 | 7.500 | $10M | 100 | 2.000 | 25 |
| **5** | 48-60 | 75.000 | $100M | 500 | 50.000 | 50 |

**Kritik eşikler:**
- **Ay 6:** İlk beta kullanıcı aktif
- **Ay 12:** $10K MRR (product-market fit)
- **Ay 24:** $50K MRR (Serie A hazır)
- **Ay 36:** $2.4M ARR (Serie B hazır)
- **Ay 48:** $10M ARR (unicorn track)
- **Ay 60:** $100M ARR (unicorn)

---

## 11. Ekip Büyümesi

### 11.1. 5 Yıl Headcount

| Rol | Ay 6 | Ay 12 | Ay 24 | Ay 36 | Ay 48 | Ay 60 |
|---|---|---|---|---|---|---|
| **Kurucu/CEO** | 1 | 1 | 1 | 1 | 1 | 1 |
| **CTO** | 1 | 1 | 1 | 1 | 1 | 1 |
| **Developer** | 2 | 4 | 15 | 30 | 60 | 150 |
| **DevOps** | 0 | 1 | 3 | 5 | 10 | 20 |
| **Designer** | 0 | 1 | 2 | 4 | 8 | 15 |
| **Product** | 0 | 0 | 1 | 2 | 5 | 10 |
| **Sales** | 0 | 1 | 4 | 8 | 20 | 50 |
| **Customer Success** | 0 | 0 | 2 | 4 | 10 | 30 |
| **Marketing** | 0 | 0 | 1 | 2 | 5 | 15 |
| **Finance/Ops** | 0 | 0 | 1 | 2 | 5 | 10 |
| **Legal/Compliance** | 0 | 0 | 0 | 1 | 3 | 8 |
| **Support** | 0 | 0 | 0 | 1 | 5 | 20 |
| **Total** | **~3** | **~5** | **~15** | **~50** | **~100** | **~500** |

### 11.2. Organizasyon Şeması
CEO
├── CTO
│ ├── Engineering (Developers)
│ ├── DevOps/Infra
│ ├── Security
│ └── QA
├── CPO (Chief Product Officer)
│ ├── Product
│ ├── Design
│ └── Data
├── CRO (Chief Revenue Officer)
│ ├── Sales
│ ├── Customer Success
│ ├── Partner
│ └── Marketing
├── CFO
│ ├── Finance
│ ├── Legal
│ └── Compliance
└── COO
├── Operations
├── Support
└── HR

text

### 11.3. Kültür

- **Remote-first** (global ekip)
- **Async** (timezone bağımsız)
- **Documentation-first** (bu belgeler)
- **Open source** (mümkün olduğunca)
- **Hackathon** (çeyrekte 1)
- **Learning budget** ($2K/yıl/kişi)

---

## 12. Finansal Projeksiyon

### 12.1. 5 Yıl P&L

| | Yıl 1 | Yıl 2 | Yıl 3 | Yıl 4 | Yıl 5 |
|---|---|---|---|---|---|
| **ARR** | $120K | $600K | $2.4M | $10M | $100M |
| **Gelir** | $80K | $400K | $1.8M | $7M | $70M |
| **COGS** (infra) | $6K | $30K | $120K | $600K | $4M |
| **Gross Margin** | %92 | %92 | %93 | %91 | %94 |
| **Ekip** | $150K | $600K | $2.4M | $8M | $40M |
| **Marketing** | $20K | $120K | $600K | $1.8M | $6M |
| **Diğer** | $30K | $100K | $300K | $1M | $5M |
| **Total OpEx** | $200K | $820K | $3.3M | $10.8M | $51M |
| **Net** | -$126K | -$450K | -$1.6M | -$4.4M | +$15M |
| **Burn rate** | $10K/ay | $38K/ay | $133K/ay | $367K/ay | profitable |

### 12.2. Finansman Roundları

| Round | Ay | Miktar | Valuation | Kullanım |
|---|---|---|---|---|
| **Pre-seed** | Ay 0 | $300K | $2M | MVP, 6 ay |
| **Seed** | Ay 12 | $2M | $10M | Meta-platform, 12 ay |
| **Serie A** | Ay 24 | $8M | $40M | Ekosistem, 12 ay |
| **Serie B** | Ay 36 | $25M | $150M | Scale, 12 ay |
| **Serie C** | Ay 48 | $60M | $600M | Global, 12 ay |
| **Toplam** | | **~$95M** | | |

### 12.3. Path to Profitability

- **Yıl 1-4:** Burn (growth mode)
- **Yıl 5:** Profitability (ARR > $50M)
- **Yıl 6+:** SaaS economics (margin > %80)

### 12.4. Exit

| Yol | Zaman | Değer |
|---|---|---|
| **IPO** | Yıl 6-8 | $1B-10B |
| **M&A (SAP/Oracle)** | Yıl 5-7 | $1B-5B |
| **M&A (Salesforce/Microsoft)** | Yıl 5-7 | $1B-5B |
| **Bağımsız** | Süresiz | — |

---

## 13. Risk Yönetimi

### 13.1. Risk Matrisi

| Risk | Olasılık | Etki | Mitigasyon |
|---|---|---|---|
| **Ürün-pazar uyumu yok** | Orta | Yüksek | Beta, feedback, pivot hazır |
| **Ekosistem büyümez** | Orta | Yüksek | Partner program, incentive |
| **SAP/Odoo karşı taarruz** | Düşük | Yüksek | Farklılaşma (meta-platform) |
| **Teknik borç** | Yüksek | Orta | Test, refactor, code review |
| **Güvenlik ihlali** | Düşük | Çok Yüksek | Belge 18 (security) |
| **Regülasyon değişimi** | Yüksek | Orta | Plugin mimarisi (Belge 15) |
| **Finansman sıkıntısı** | Orta | Yüksek | Bootstrap fallback |
| **Ekip kaybı** | Orta | Orta | Documentation, async |
| **Siber saldırı** | Orta | Yüksek | Pentest, bug bounty |
| **Rekabet (yeni girişim)** | Yüksek | Orta | Ekosistem (moat) |

### 13.2. Mitigasyon Stratejileri

**Ürün-pazar uyumu:**
- Beta müşterilerle yakın
- 30 günlük feedback döngüsü
- Pivot hazır (3 senaryo)

**Ekosistem:**
- Developer incentive ($)
- Partner program
- Marketplace kolaylığı
- Documentation + SDK

**SAP/Odoo:**
- Farklılaşma (meta-platform)
- Modern stack
- SMB/mid-market odak (SAP zor)

**Teknik borç:**
- Test coverage ≥ %80
- Code review
- Refactor sprints (her 3 ay)

**Güvenlik:**
- Belge 18 (comprehensive)
- Pentest (yıllık)
- Bug bounty

**Regülasyon:**
- Plugin mimarisi
- Compliance update (otomatik)

**Finansman:**
- Bootstrap fallback
- Profitability erken
- Bridge round hazır

**Ekip:**
- Documentation (bu belgeler)
- Async culture
- ESOP (equity)

**Siber:**
- Defense in depth
- SOC
- Incident response

**Rekabet:**
- Ekosistem = moat
- Switching cost (data)
- Network effect

---

## 14. Başarı Metrikleri

### 14.1. North Star Metric

**"Aylık Aktif Tenant Başına Gelir (ARPA) × Aktif Tenant"** = MRR

### 14.2. Ana Metrikler

| Kategori | Metrik |
|---|---|
| **Growth** | MRR, ARR, YoY büyüme |
| **Retention** | Churn, NRR (Net Revenue Retention) |
| **Efficiency** | CAC, LTV, LTV/CAC, payback |
| **Product** | DAU/MAU, feature adoption |
| **Ecosystem** | Marketplace apps, developer aktif |
| **Quality** | NPS, CSAT, uptime |
| **Financial** | Burn rate, runway, margin |

### 14.3. Hedefler (Yıl 5)

| Metrik | Hedef |
|---|---|
| **ARR** | $100M |
| **NRR** | > 120% |
| **Churn** | < %1/ay |
| **LTV/CAC** | > 3 |
| **Payback** | < 12 ay |
| **NPS** | > 60 |
| **Uptime** | 99.99% |
| **Marketplace revenue** | > %30 |

---

## 15. Pivot Hazırlığı

### 15.1. 3 Senaryo

**Senaryo A: Plan çalışıyor**
- Her faz hedefe ulaşıyor
- Devam, hızlandır

**Senaryo B: Yavaş büyüme**
- MRR hedefin %50'si
- Pivot: Niche'e odaklan (örneğin restoran)
- Vertical SaaS modeli

**Senaryo C: Ürün-pazar uyumu yok**
- MRR durgun
- Pivot: Farklı segment (mid-market?)
- Ya da: Plugin platform only (vertical değil)

### 15.2. Pivot Kriterleri

Her faz sonu (Ay 6, 12, 24, 36, 48):

- **< %50 hedef:** Pivot düşün
- **< %25 hedef:** Ciddi pivot
- **< %10 hedef:** Exit / kapat

### 15.3. Exit Hazırlığı

- **Yıl 2:** Acquisition interest (küçük)
- **Yıl 3:** Serie A sonrası (SAP, Oracle, Salesforce)
- **Yıl 4:** Serie B sonrası (unicorn track)
- **Yıl 5:** IPO prep veya strategic acquisition

---

## 16. Anti-Patterns

Bu roadmap için **yasak** olan hatalar:

1. **Feature factory** — Müşteri değeri olmayan özellik
2. **Premature scale** — Ürün-pazar uyumu yokken büyüme
3. **Vanity metrics** — MRR yerine kayıt sayısı
4. **No pivot plan** — Her şey plana bağlı
5. **Over-hiring** — İhtiyaçtan fazla ekip
6. **Under-hiring** — Yavaş büyüme
7. **Cash runway < 12 ay** — Risk
8. **Tek müşteri bağımlılığı** — Konsantrasyon riski
9. **Tek pazar** — Multi-country geç
10. **Tek kanal** — Multi-channel gerekli
11. **Community ihmal** — Developer ecosystem
12. **Compliance ihmal** — Satış imkansız
13. **Security ihmal** — Bir ihlal her şeyi bitirir
14. **Documentation ihmal** — Ekip büyüyemez
15. **Founder burnout** — Sustainable pace

---

## 17. Sonraki Adımlar

### 17.1. İlk 30 Gün

**Hafta 1-2:**
- Bu dokümanı oku (00-20)
- Beta müşteri adaylarını belirle (10 kişi)
- Sentry + Pulse kur

**Hafta 3-4:**
- Beta müşteri ile görüş (3 tanesi)
- Test coverage başla
- Backup + restore test

### 17.2. İlk 90 Gün

- 3 beta müşteri aktif
- Test coverage %60
- Zero-downtime deploy
- Documentation başlangıç
- Landing page

### 17.3. İlk 6 Ay (Faz 0 tamam)

- 3 beta müşteri memnun
- $0 gelir (beta ücretsiz)
- Go/No-Go kararı

---

## 18. Son Söz

Bu roadmap **iddialı**. SAP 50 yılda yaptı, biz 5 yılda **kendi kategorimizi** yaratacağız. Ama:

- **SAP ile yarışmıyoruz** — Farklı oyun
- **Meta-platform** — Yeni kategori
- **Ekosistem** — Ölçek
- **Modern stack** — Hız

**5 yıl sonra:**
- Ya unicorn
- Ya mükemmel exit
- Ya da **denenmiş** bir şey (pişman değiliz)

**Kural:** "Başarısız olmak değil, denememek kayıptır." (Bu roadmap ile deniyoruz.)

---

## 19. Değişiklik Geçmişi

| Tarih | Sürüm | Değişiklik |
|---|---|---|
| 2026-11-06 | 1.0 | İlk sürüm — 5 yıllık roadmap, 6 faz |

---

**SON BELGE — 20/20 ✅**

**Tüm belgeler:**
- 00-manifesto.md ✅
- 01-multi-tenancy.md ✅
- 02-data-model.md ✅
- 03-feature-flags.md ✅
- 04-module-system.md ✅
- 05-meta-structure.md ✅
- 06-layer-role-system.md ✅
- 07-custom-entity.md ✅
- 08-event-bus.md ✅
- 09-api-first.md ✅
- 10-plugin-sandbox.md ✅
- 11-workflow-engine.md ✅
- 12-report-builder.md ✅
- 13-ui-builder.md ✅
- 14-integration-hub.md ✅
- 15-compliance.md ✅
- 16 (iptal — AI plugin-only, Belge 00 Bölüm 12.5)
- 17-marketplace.md ✅
- 18-security-sandbox.md ✅
- 19-deployment.md ✅
- 20-roadmap.md ✅

**Toplam: 20 belge. Dokümantasyon tamamlandı.**