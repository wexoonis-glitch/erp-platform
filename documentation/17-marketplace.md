# 17 — Marketplace & Developer Ecosystem

**Project:** Kurumsal ERP Platform
**Belge:** App Marketplace, Developer Economy, Partner Program, Revenue Share, Trust & Safety
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md, 11-workflow-engine.md, 12-report-builder.md, 13-ui-builder.md, 14-integration-hub.md, 15-compliance.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **Marketplace** ve **Developer Ecosystem** sistemini tanımlar — bağımsız developer'ların plugin/connector/vertical paketleri yazıp sattığı, tenant'ların bunları keşfedip kurduğu, platformun komisyon aldığı **ekonomik ekosistemi**. İçerir:

- Neden marketplace (Belge 00 — %100 pazar stratejisi)
- 4 katılımcı rolü (owner, developer, partner, tenant)
- Developer onboarding (kayıt, KYC, verifikasi)
- App listing (submission, review, approval)
- Kategoriler & keşif
- Fiyatlandırma modelleri
- Billing & payment (komisyon, payout, vergi)
- Rating & review
- Support & SLA
- Trust & safety (audit, ban, dispute)
- Partner program (verified, certified)
- Tenant deneyimi (keşif, kurulum, upgrade, yönetim)
- Finansal akış (payment → split → payout)
- Analytics (developer + owner + tenant)
- Marketplace API
- Anti-patterns, roadmap

**Hedef kitle:** Ürün mimarları, developer'lar, finance/legal, CTO, AI asistanlar.

**Kritik:** Marketplace, **"SAP modeli" değil "Salesforce modeli"** (Belge 00 — Bölüm 3). Core %20, ekosistem %80. Bu belge o ekosistemin **ekonomik ve teknik altyapısını** tanımlar.

---

## 2. Neden Marketplace?

### 2.1. Platform vs Ürün

| Model | Kim yazar | Kapsam | Örnek |
|---|---|---|---|
| **Ürün (SAP modeli)** | Biz yazarız | %100 biz | SAP (50 yıl, 100K kişi) |
| **Platform (bizim model)** | Biz + ekosistem | %20 biz, %80 ekosistem | Salesforce, Shopify, Odoo |

**Kural:** 25.000 fonksiyonu tek başına yazmak **imkansız**. Ekosistemle **mümkün**.

### 2.2. Marketplace'in Değeri

| Paydaş | Kazanç |
|---|---|
| **Biz (owner)** | Komisyon geliri (%15-30), ürün genişlemesi, marka değeri |
| **Developer** | Gelir, itibar, müşteri erişimi |
| **Partner** | Kendi iş modelini kurma, ölçeklenme |
| **Tenant** | İhtiyacına özel çözüm, tek platform |
| **Son kullanıcı** | Daha iyi UX, entegre deneyim |

### 2.3. Kanıtlanmış Modeller

| Platform | Developer | App | Değer |
|---|---|---|---|
| **Salesforce AppExchange** | 500K+ | 5.000+ | $30B+ |
| **Shopify App Store** | 20K+ | 10.000+ | $100B şirket |
| **Odoo Apps** | 30K+ | 40.000+ | $5B |
| **WordPress Plugins** | 100K+ | 60.000+ | %43 web |
| **AWS Marketplace** | 100K+ | 15.000+ | $2T+ pazar |

**Sonuç:** Hepsi aynı modelle %100 pazar kapsamına ulaştı. Biz de aynı yolu izliyoruz.

---

## 3. 4 Katılımcı Rolü

### 3.1. Roller

| Rol | Kim | Ne yapar | Gelir |
|---|---|---|---|
| **Owner** | Biz | Platform işletmecisi | Komisyon (%15-30) |
| **Developer** | Bağımsız geliştirici | Plugin/connector yazar, satar | Satış geliri (%70-85) |
| **Partner** | Onaylı firma | Dikey/enterprise çözüm, danışmanlık | Satış + hizmet |
| **Tenant** | Müşteri şirket | App kurar, kullanır | Maliyet |

### 3.2. Rol İlişkileri
┌─────────────────────────────────────────────────────┐
│ OWNER (StokPro) │
│ Platform + Marketplace + Review + Billing │
└─────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────┐
│ DEVELOPER / PARTNER │
│ App yazar + yayınlar + destek verir │
└─────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────┐
│ MARKETPLACE │
│ Katalog + Arama + Rating + Payment │
└─────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────┐
│ TENANT │
│ App kurar + kullanır + değerlendirir │
└─────────────────────────────────────────────────────┘

text

### 3.3. Developer vs Partner

| Özellik | Developer | Partner |
|---|---|---|
| Kayıt | Self-service | Başvuru + onay |
| KYC | Bireysel | Şirket |
| Yayın | Review (3-7 gün) | Review (1-2 gün, hızlı) |
| Komisyon | %20 | %15 |
| Verified badge | ❌ | ✅ |
| SLA | Best effort | Zorunlu |
| Enterprise satış | Sınırlı | Tam |
| Marketplace priority | Normal | Yüksek |
| Destek | Email | 7/24 zorunlu |

**Kural:** Partner, **verified** ve **certified** developer'ın üst seviyesi.

### 3.4. Tenant'ın Rolü

- **Keşif** — Marketplace'te arama
- **Kurulum** — 1 tıkla kur
- **Kullanım** — Günlük kullanım
- **Değerlendirme** — Rating + review
- **Upgrade** — Yeni versiyonlar

**Kural:** Tenant **kod yazmaz**, sadece **seçer**.

---

## 4. Developer Onboarding

### 4.1. Kayıt Süreci
Sign up (email + password)
↓

Profil (ad, email, ülke)
↓

KYC (kimlik doğrulama)
↓

Banka hesabı (payout için)
↓

Developer Agreement (yasal)
↓

Developer dashboard aktif

text

### 4.2. KYC (Know Your Customer)

**Neden?** Yasal zorunluluk (AML, vergi).

**Kişisel developer:**
- ✅ Kimlik (TC/pasaport)
- ✅ Adres
- ✅ Vergi no
- ✅ Banka hesabı

**Şirket developer (partner):**
- ✅ Vergi levhası
- ✅ Ticaret sicil
- ✅ İmza sirküleri
- ✅ Yetkili kişi kimliği
- ✅ Banka hesabı (şirket adına)

**Doğrulama:**
- Otomatik (MASAK, KKB, uluslararası KYB)
- Manuel (belge inceleme)
- Süre: 1-3 iş günü

### 4.3. Developer Agreement

**Kapsam:**

- ✅ IP hakları (developer'da kalır, platforma lisans)
- ✅ Komisyon oranı
- ✅ Ödeme koşulları (aylık, 30 gün)
- ✅ Sorumluluk sınırı
- ✅ Gizlilik
- ✅ Rekabet yasağı (yok — free market)
- ✅ Fesih koşulları
- ✅ Uyuşmazlık çözümü

**Kural:** E-imza (eIDAS/KVKK uyumlu).

### 4.4. Developer Dashboard
┌────────────────────────────────────────────────────────────┐
│ Developer Dashboard — Ali Yılmaz │
├────────────────────────────────────────────────────────────┤
│ 📊 Genel Bakış │
│ ─ Toplam gelir (bu ay): 22.500 TL │
│ ─ Toplam gelir (tüm zaman): 245.000 TL │
│ ─ Aktif tenant: 45 │
│ ─ Aktif app: 2 │
│ ─ Ortalama rating: 4.8 │
│ │
│ 📦 App'lerim │
│ ─ Wolt Integration v1.2.0 │ Published │ 45 tenant │
│ ─ Trendyol Sync v0.5.0 │ Draft │ — │
│ ─ Menu Sync v1.0.0 │ Review │ — │
│ │
│ 💰 Finans │
│ ─ Bekleyen payout: 18.000 TL │
│ ─ Son payout: 15.000 TL (01.10.2026) │
│ ─ Faturalar: [Görüntüle] │
│ │
│ 📈 Analytics │
│ ─ API çağrıları (24h): 12.345 │
│ ─ Event dinleme (24h): 890 │
│ ─ Hata oranı: 0.02% │
│ │
│ 💬 Destek │
│ ─ Açık ticket: 2 │
│ ─ Ortalama yanıt: 4 saat │
│ │
│ 🎯 Öneriler │
│ ─ [Test coverage artır] +0.2 rating │
│ ─ [Dokümantasyon güncelle] +0.1 rating │
└────────────────────────────────────────────────────────────┘

text

### 4.5. Developer Tiers

| Tier | Kriter | Avantaj |
|---|---|---|
| **Newcomer** | 0-5 app | Standart komisyon (%20) |
| **Verified** | 5+ app, 4.0+ rating | Komisyon %18, badge |
| **Trusted** | 20+ app, 4.5+ rating | Komisyon %15, priority review |
| **Elite** | 50+ app, 4.8+ rating | Komisyon %12, dedicated support |

**Kural:** Tier, **otomatik** yükselir. Performans = ödül.

---

## 5. App Listing (Submission → Publication)

### 5.1. Submission Flow
Developer app hazırlar
↓

"Publish" tıklar
↓

Otomatik kontroller
├── Manifest valid
├── Signature valid
├── SAST (güvenlik)
├── Dependency check
└── License check
↓

Manuel review
├── Kod inceleme (güvenlik)
├── Permission justification
├── Documentation quality
└── Sandbox test
↓

Approval / Rejection
↓

Published (marketplace'te)

text

### 5.2. Otomatik Kontroller

| Kontrol | Araç | Süre |
|---|---|---|
| **Syntax** | PHP linter | < 1 dk |
| **Signature** | Marketplace key | < 1 dk |
| **SAST** | Semgrep + Psalm | 5 dk |
| **Dependency** | Composer audit | 2 dk |
| **License** | License check | 1 dk |
| **Malware** | ClamAV + YARA | 5 dk |
| **Secret scan** | TruffleHog | 2 dk |

**Toplam:** ~15 dk otomatik.

### 5.3. Manuel Review

**Reviewer kontrol listesi:**

- ✅ Kod kalitesi (okunabilir, test edilmiş)
- ✅ İzinler **minimum** seviyede mi (aşırı izin = red)
- ✅ Cross-tenant erişim riski var mı
- ✅ Sandbox bypass denemesi var mı
- ✅ Data exfiltration riski
- ✅ Documentation (README, changelog, screenshots)
- ✅ Support policy belirtilmiş mi
- ✅ Fiyatlandırma net mi
- ✅ GDPR/KVKK uyumu (PII işliyorsa)

**Süre:**

| Tier | Süre |
|---|---|
| **Newcomer** | 3-7 iş günü |
| **Verified** | 2-3 iş günü |
| **Trusted** | 1-2 iş günü |
| **Elite** | 24 saat |
| **Partner** | 24 saat |

### 5.4. Red Nedenleri

| Neden | Örnek |
|---|---|
| **Güvenlik** | Scope bypass, sandbox escape |
| **Aşırı izin** | "Tüm veriyi oku" ama ihtiyaç yok |
| **Kötü kod** | Test yok, error handling yok |
| **Doc eksik** | README boş, changelog yok |
| **Kötü niyet** | Data exfiltration, backdoor |
| **Legal** | Lisans ihlali |
| **Duplicate** | Aynı app zaten var |

**Feedback:** Detaylı rapor + düzeltme önerileri. Tekrar başvuru: 7 gün sonra.

### 5.5. App Listing Sayfası
┌────────────────────────────────────────────────────────────┐
│ Wolt Integration │
│ by Ali Yılmaz (dev-ali) ✅ Verified │
│ ⭐ 4.8 (45 review) · 45 aktif tenant │
├────────────────────────────────────────────────────────────┤
│ [📸 Screenshot 1] [📸 Screenshot 2] [📸 Screenshot 3] │
├────────────────────────────────────────────────────────────┤
│ Wolt siparişlerini platforma aktarır. │
│ │
│ Özellikler: │
│ ✅ Otomatik sipariş çekme │
│ ✅ Menü senkronizasyonu │
│ ✅ Durum güncelleme (hazır, yolda, teslim) │
│ ✅ Kurye takip │
│ │
│ Kategori: Integration │
│ Son güncelleme: 3 gün önce │
│ Versiyon: 1.2.0 │
│ Uyumluluk: Platform 1.0+ │
│ │
│ Fiyatlandırma: │
│ ─ Aylık: 500 TL │
│ ─ Yıllık: 5.000 TL (%17 tasarruf) │
│ ─ 14 gün ücretsiz deneme │
│ │
│ İzinler: │
│ 📖 Satış siparişleri — okuma │
│ ✍️ Satış siparişleri — oluşturma │
│ 📖 Ürünler — okuma │
│ 🌐 HTTP: wolt.com │
│ 📨 Event: otc.sales_order.created │
│ │
│ [🔍 Kurulum] [📺 Demo] [📖 Docs] [⭐ Değerlendir] │
└────────────────────────────────────────────────────────────┘

text

---

## 6. Kategoriler & Keşif

### 6.1. Kategoriler

| Kategori | Örnek |
|---|---|
| **Integration** | Wolt, Trendyol, Stripe, Slack |
| **Compliance** | e-Fatura, GDPR, HIPAA |
| **Vertical** | Restoran, Klinik, İnşaat |
| **Analytics** | Advanced Reports, BI |
| **Communication** | Email, SMS, WhatsApp |
| **Payment** | iyzico, PayTR, Stripe |
| **AI** | OCR, chatbot, forecast |
| **Security** | 2FA, IP whitelist, audit |
| **Productivity** | Calendar, tasks, notes |
| **Industry** | Manufacturing, Retail, Services |

### 6.2. Keşif Yöntemleri

- **Arama** — Full-text search
- **Kategori** — Kategori bazlı
- **Featured** — Owner seçer
- **Top rated** — Rating bazlı
- **New** — Yeni yayınlananlar
- **Popular** — Kurulum sayısı
- **Similar** — "Bunu kuranlar şunları da kurdu"

### 6.3. Arama Algoritması
Score = 0.4 * relevance +
0.2 * rating +
0.15 * install_count +
0.1 * update_recency +
0.1 * developer_tier +
0.05 * price_competitiveness

text

**Kural:** Yeni developer da **keşfedilebilir** — monopol yok.

### 6.4. Featured Apps

**Owner seçer** — ama **tarafsız**:

- Editör seçimi
- Kategori lideri
- Yeni çıkan iyi
- Özel kampanya

**Kural:** Featured = ücretli değil. Kalite bazlı.

---

## 7. Fiyatlandırma Modelleri

### 7.1. Modeller

| Model | Örnek |
|---|---|
| **Free** | Basit app |
| **Freemium** | Temel ücretsiz, premium ücretli |
| **One-time** | 500 TL tek seferlik |
| **Monthly** | 500 TL/ay |
| **Yearly** | 5.000 TL/yıl |
| **Usage-based** | 0.10 TL/API çağrısı |
| **Tiered** | Basic 200, Pro 500, Enterprise 2000 |
| **Per-user** | 50 TL/kullanıcı/ay |

### 7.2. Ödeme Planları

```yaml
pricing:
  model: "tiered"
  currency: "TRY"
  
  tiers:
    - key: "basic"
      name: "Basic"
      price: 200
      features:
        - "1000 istek/ay"
        - "Email destek"
    
    - key: "pro"
      name: "Pro"
      price: 500
      features:
        - "10000 istek/ay"
        - "Priority destek"
        - "Advanced features"
    
    - key: "enterprise"
      name: "Enterprise"
      price: 2000
      features:
        - "Sınırsız istek"
        - "7/24 destek"
        - "SLA"
        - "Dedicated support"
  
  trial:
    enabled: true
    days: 14
  
  billing_cycle: "monthly"              # monthly | yearly
  yearly_discount: 0.17                 # %17
7.3. Tenant Plan Etkisi
Kural: App fiyatı tenant planından bağımsız. Ama:

Free tenant — Sadece ücretsiz app'ler

Pro tenant — Tüm app'ler

Enterprise tenant — Enterprise-tier app'ler + custom

7.4. Ödeme Akışı
text
Tenant aylık ödeme yapar
    ↓
Platform alır (%20 komisyon)
    ↓
Developer'a %80 (aylık transfer)
    ↓
Fatura otomatik (e-Fatura uyumlu)
8. Billing & Payment
8.1. Ödeme Akışı — Detaylı
text
1. Tenant "Abone Ol" tıklar
    ↓
2. Ödeme yöntemi seçer (kredi kartı, havale)
    ↓
3. Platform payment gateway (iyzico/Stripe) üzerinden çeker
    ↓
4. Platform hesabına düşer
    ↓
5. Split:
    ├── Owner (%20) — platform hesabı
    └── Developer (%80) — developer hesabı (pending)
    ↓
6. Ay sonu:
    ├── Developer'a payout (bank transfer)
    └── Owner'a rapor
8.2. Komisyon Oranları
Tür	Komisyon
Public (marketplace)	%20
Verified developer	%18
Trusted developer	%15
Elite developer	%12
Partner	%15
Private (tenant)	%0
Custom (self)	%0
Kural: Komisyon, developer tier'a bağlı.

8.3. Payment Gateway
Multi-gateway:

Gateway	Kapsam
iyzico	TR
PayTR	TR
Stripe	Global
PayPal	Global
Havale	TR (manuel)
Kural: Tenant ülkesine göre otomatik.

8.4. Developer Payout
text
Ay sonu (30'u):
    ↓
1. Tüm pending payments toplanır
    ↓
2. Komisyon düşülür
    ↓
3. Vergi (stopaj) hesaplanır
    ↓
4. Developer banka hesabına transfer
    ↓
5. Fatura oluşturulur (e-Fatura)
    ↓
6. Developer dashboard'a yansır
Payout süresi: 3-5 iş günü.

Minimum payout: 500 TL.

8.5. Vergi
Developer sorumluluğu:

✅ Kendi vergisini beyan eder

✅ Platforma fatura keser

✅ Stopaj (TR'de %15-20) — platform keser

Platform sorumluluğu:

✅ Stopajı devlete yatırır

✅ Developer'a stopaj belgesi verir

✅ Yıllık gelir bildirimi

8.6. Refund
Kural:

Durum	Refund
14 gün içinde (trial)	%100
App çalışmıyor	%100
Developer hile	%100 + ban
Tenant vazgeçti (> 14 gün)	❌
Aylık abonelik	Sonraki ay durur
Kural: Refund owner onayı ile.

8.7. Chargeback
Kural: Tenant chargeback yaparsa:

Developer ödemez (owner alır zararı)

Tenant ban (fraud)

Legal action

9. Rating & Review
9.1. Rating Sistemi
1-5 yıldız — Genel puan

Alt kategoriler — Kalite, destek, dokümantasyon, fiyat

Verified purchase — Sadece gerçek kullanıcı

9.2. Review
yaml
review:
  rating: 5
  sub_ratings:
    - { key: "quality", value: 5 }
    - { key: "support", value: 4 }
    - { key: "documentation", value: 5 }
    - { key: "value", value: 4 }
  title: "Harika entegrasyon"
  body: "Wolt siparişlerini sorunsuz aktarıyor..."
  screenshots: ["..."]
  verified_purchase: true
9.3. Rating Etkisi
Arama sıralaması:

4.8+ — Top rated

4.5-4.8 — Good

4.0-4.5 — Average

< 4.0 — Low visibility

< 3.0 — Auto-suspend (owner review)

Kural: Rating kaliteyi yansıtır.

9.4. Developer Yanıtı
Her review'a developer yanıt verebilir:

Teşekkür

Açıklama

Çözüm önerisi

Gelecek planı

Kural: Yanıt süresi < 7 gün. Aksi halde olumsuz etki.

9.5. Fake Review Önleme
Yöntem	Uygulama
Verified purchase	Sadece ödeyen
Rate limit	1 review/user/app
IP kontrolü	Aynı IP'den çok review
Text analizi	AI + manuel
Owner moderation	Şüpheli review silinir
Kural: Fake review = ban (developer + reviewer).

10. Support & SLA
10.1. Developer Sorumluluğu
Tier	SLA
Newcomer	Best effort (48 saat)
Verified	24 saat
Trusted	12 saat
Elite	4 saat
Partner	2 saat + 7/24
10.2. Support Kanalları
Email — Zorunlu

In-app ticket — Zorunlu

Docs — Zorunlu

Chat — Opsiyonel (Trusted+)

Phone — Opsiyonel (Partner)

10.3. Ticket Flow
text
1. Tenant ticket açar
    ↓
2. Developer'a atanır
    ↓
3. SLA timer başlar
    ↓
4. Developer yanıtlar
    ↓
5. Çözüm / escalate
    ↓
6. Tenant değerlendirir
10.4. SLA İhlali
İhlal	Etki
1 ihlal	Uyarı
3 ihlal/ay	Tier düşürme
5 ihlal/ay	Marketplace suspend
10 ihlal	Ban
Kural: SLA = ciddiyet.

11. Trust & Safety
11.1. Trust Signals
Her app'te gösterilen güven sinyalleri:

✅ Developer verified (KYC tamamlandı)

✅ Signature verified (kod imzalı)

✅ Security scan passed (SAST + malware)

✅ Rating (4.8/5)

✅ Install count (45 tenant)

✅ Update frequency (3 gün önce)

✅ Support SLA (24 saat)

⚠️ Uyarılar (varsa)

11.2. Ban Sebebi
Sebep	Sonuç
Data exfiltration	Permanent ban
Sandbox escape	Permanent ban
Backdoor	Permanent ban + legal
Scope violation	30 gün suspend
Repeated SLA ihlali	Tier düşürme
Fake review	30 gün suspend
Malware	Permanent ban
License ihlali	App kaldırma
11.3. Dispute Resolution
Tenant ↔ Developer uyuşmazlığı:

text
1. Tenant dispute açar
    ↓
2. Developer'a bildirim (7 gün yanıt)
    ↓
3. Çözüm yoksa → Owner müdahale
    ↓
4. Karar (owner):
    ├── Tenant haklı → refund + developer uyarı
    ├── Developer haklı → dispute kapat
    └── Karmaşık → detailed review
    ↓
5. Karar **bağlayıcı**
11.4. Incident Response
text
1. Anomali tespit (Belge 10 — behavioral audit)
    ↓
2. Auto-suspend (kritikse)
    ↓
3. Security review
    ↓
4. Karar:
    ├── False positive → resume
    ├── Fix required → developer 7 gün süre
    └── Malicious → ban
    ↓
5. Notification (developer + tenant)
    ↓
6. Post-mortem
11.5. Kill Switch
Owner tek tıkla kapatabilir (Belge 10 — Bölüm 8.9):

php
$plugin->kill(reason: "Security issue", duration: "indefinite");
Etki:

Tüm tenant'larda devre dışı

Marketplace'ten kalkar

Developer'a bildirim

Audit

12. Partner Program
12.1. Partner Tipleri
Tip	Kapsam
Technology Partner	App geliştirici (verified)
Solution Partner	Danışmanlık + app
Reseller Partner	Satış + destek
OEM Partner	White-label
12.2. Partner Başvuru
text
1. Online başvuru
    ↓
2. Belge yükleme (şirket, ekip, portföy)
    ↓
3. Interview (video call)
    ↓
4. Pilot project (opsiyonel)
    ↓
5. Approval
    ↓
6. Partner Agreement
    ↓
7. Badge + dashboard
Süre: 2-4 hafta.

12.3. Partner Avantajları
✅ Verified badge (trust)

✅ Priority review (24 saat)

✅ Dedicated support (owner)

✅ Co-marketing (case study, webinar)

✅ Lead share (owner'dan tenant lead)

✅ Training (certification)

✅ Reduced commission (%15)

✅ Early access (yeni feature)

12.4. Partner Tiers
Tier	Kriter
Registered	Başvuru onaylı
Silver	5+ app, 3+ müşteri
Gold	20+ app, 20+ müşteri, $50K+
Platinum	50+ app, 100+ müşteri, $500K+
12.5. Partner Certification
Kategoriler:

Wolt Integration Certified — Teknik yeterlilik

TR Compliance Certified — Yasal

Enterprise Ready — Performans + güvenlik

AI Compliance — EU AI Act

Kural: Certification = pazarlama avantajı.

13. Tenant Deneyimi
13.1. Keşif
Tenant admin marketplace'e girer:

text
┌────────────────────────────────────────────────────────────┐
│  Marketplace                                               │
├────────────────────────────────────────────────────────────┤
│  🔍 Ara... [Kategori ▾] [Fiyat ▾] [Rating ▾]              │
├────────────────────────────────────────────────────────────┤
│  ⭐ Featured                                               │
│  ─ Wolt Integration (4.8)                                  │
│  ─ TR Compliance Pack (4.9)                                │
│  ─ Advanced Reports (4.7)                                  │
│                                                            │
│  🔥 Popular                                                │
│  ─ Trendyol Sync (1.234 tenant)                            │
│  ─ Stripe Payments (987 tenant)                            │
│  ─ AI OCR (456 tenant)                                     │
│                                                            │
│  💡 Öneriler (senin işine göre)                            │
│  ─ Restoran KDS (restoran tenant'ısın)                     │
│  ─ Wolt Integration (restoran tenant'ısın)                 │
└────────────────────────────────────────────────────────────┘
13.2. Install Flow
text
1. "Kur" tıklar
    ↓
2. Permission onay ekranı (Belge 10 — Bölüm 7.7)
    ↓
3. Fiyat + ödeme yöntemi
    ↓
4. Trial başlar (14 gün)
    ↓
5. App aktif
    ↓
6. Onboarding wizard (opsiyonel)
13.3. Manage
text
┌────────────────────────────────────────────────────────────┐
│  Kurulu App'ler                                            │
├────────────────────────────────────────────────────────────┤
│  Wolt Integration v1.2.0                                   │
│  ─ Durum: ✅ Aktif                                         │
│  ─ Plan: Pro (500 TL/ay)                                   │
│  ─ Son kullanım: 5 dk önce                                 │
│  ─ [Ayarlar] [Loglar] [Durdur] [Kaldır]                    │
│                                                            │
│  Trendyol Sync v0.5.0                                      │
│  ─ Durum: ⚠️ Trial (3 gün kaldı)                           │
│  ─ Plan: Basic (200 TL/ay)                                 │
│  ─ [Satın Al] [Kaldır]                                     │
└────────────────────────────────────────────────────────────┘
13.4. Upgrade
text
1. Yeni versiyon çıkar (developer)
    ↓
2. Tenant'a bildirim
    ↓
3. "Güncelle" tıklar
    ↓
4. Migration çalışır
    ↓
5. Yeni versiyon aktif
Kural: Upgrade tenant onayı ile (auto-update yok).

13.5. Uninstall
text
1. "Kaldır" tıklar
    ↓
2. Data silme onayı
    ├── Veri silinsin mi? (default: hayır)
    └── Veri korunsun mu?
    ↓
3. App devre dışı
    ↓
4. Audit
Kural: Default veri korunur. Silme açık onay ile.

14. Finansal Akış
14.1. Tam Akış
text
┌─────────────────────────────────────────────────────────────┐
│  1. TENANT ÖDEMESİ                                          │
│     Tenant → Payment Gateway → Platform hesabı              │
│     Örnek: 500 TL/ay                                        │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  2. SPLIT                                                   │
│     Platform (owner):  500 × 0.20 = 100 TL                  │
│     Developer:         500 × 0.80 = 400 TL                  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  3. AY SONU (30'u)                                          │
│     Developer pending: 400 × 45 tenant = 18.000 TL          │
│     Komisyon:          18.000 × 0.20   = 3.600 TL (owner)   │
│     Developer net:     18.000 - 3.600 - stopaj = ...         │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  4. PAYOUT                                                  │
│     Developer banka hesabına transfer (3-5 iş günü)         │
│     Fatura otomatik (e-Fatura)                              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  5. VERGİ                                                   │
│     Stopaj (TR'de %15-20) → devlet                           │
│     Developer'a stopaj belgesi                               │
│     Yıllık gelir bildirimi                                   │
└─────────────────────────────────────────────────────────────┘
14.2. Owner Geliri
Aylık tahmini:

App sayısı	Ortalama gelir	Owner komisyon
10 app	10K TL/ay	2K TL
100 app	100K TL/ay	20K TL
1.000 app	1M TL/ay	200K TL
10.000 app	10M TL/ay	2M TL
Kural: Marketplace geliri pasif — developer çalışır, owner kazanır.

14.3. Fatura & Muhasebe
Otomatik e-Fatura (Belge 15 — TR compliance)

GİB'e gönderim (e-Fatura plugin)

Muhasebe kaydı (otomatik)

KDV (%20 TR)

15. Analytics
15.1. Owner Dashboard
text
┌────────────────────────────────────────────────────────────┐
│  Marketplace Analytics                                     │
├────────────────────────────────────────────────────────────┤
│  📊 Genel                                                   │
│  ─ Toplam app: 156                                         │
│  ─ Toplam developer: 89                                    │
│  ─ Toplam tenant: 1.234                                    │
│  ─ Toplam kurulum: 4.567                                   │
│  ─ Aylık GMV (Gross Merchandise Value): 234.567 TL        │
│  ─ Aylık komisyon geliri: 46.913 TL                        │
│                                                            │
│  📈 Büyüme                                                  │
│  ─ Son 30 gün: +23 app, +12 developer, +89 tenant          │
│  ─ MoM büyüme: +18%                                        │
│  ─ Churn: %3                                               │
│                                                            │
│  🏆 Top App'ler                                              │
│  1. Wolt Integration (234 tenant, 46.800 TL/ay)            │
│  2. Trendyol Sync (189 tenant, 37.800 TL/ay)               │
│  3. AI OCR (89 tenant, 44.500 TL/ay)                       │
│                                                            │
│  👤 Top Developer'lar                                        │
│  1. Ali Yılmaz (12 app, 34.500 TL komisyon)                │
│  2. Acme Corp (8 app, 28.900 TL komisyon)                  │
│                                                            │
│  ⚠️ Sorunlar                                                  │
│  ─ 3 app düşük rating (< 3.0)                              │
│  ─ 2 app SLA ihlali                                        │
│  ─ 1 app kill switch (güvenlik)                            │
└────────────────────────────────────────────────────────────┘
15.2. Developer Analytics
Developer dashboard'da:

App performansı — Kurulum, aktif kullanım, churn

Gelir — Aylık, yıllık, tier dağılımı

Destek — Ticket, SLA, memnuniyet

Rating — Trend, alt kategoriler

Competitor — Aynı kategorideki app'ler

15.3. Tenant Analytics
Tenant kendi app'lerini görür:

Kullanım — Hangi app ne kadar aktif

Maliyet — Aylık toplam

ROI — App'in iş değeri

Alternatif — Aynı kategorideki diğer app'ler

16. Marketplace API
16.1. Public API
text
GET    /api/v1/marketplace/apps                    → list
GET    /api/v1/marketplace/apps/{key}              → detail
GET    /api/v1/marketplace/apps/{key}/reviews      → reviews
GET    /api/v1/marketplace/categories              → categories
GET    /api/v1/marketplace/search?q=...            → search
POST   /api/v1/marketplace/apps/{key}/install      → install (tenant)
POST   /api/v1/marketplace/apps/{key}/uninstall    → uninstall
16.2. Developer API
text
GET    /api/v1/dev/apps                            → my apps
POST   /api/v1/dev/apps                            → create
PUT    /api/v1/dev/apps/{key}                      → update
DELETE /api/v1/dev/apps/{key}                      → delete
POST   /api/v1/dev/apps/{key}/publish              → publish
GET    /api/v1/dev/apps/{key}/analytics            → analytics
GET    /api/v1/dev/earnings                        → earnings
GET    /api/v1/dev/payouts                         → payouts
16.3. Webhook
Marketplace → developer:

yaml
webhooks:
  - event: "app.published"
    url: "https://developer.com/webhook/published"
  
  - event: "app.rejected"
    url: "https://developer.com/webhook/rejected"
  
  - event: "app.installed"
    url: "https://developer.com/webhook/installed"
  
  - event: "app.uninstalled"
    url: "https://developer.com/webhook/uninstalled"
  
  - event: "payout.completed"
    url: "https://developer.com/webhook/payout"
  
  - event: "review.created"
    url: "https://developer.com/webhook/review"
17. Restoran Örneği — Wolt Plugin Satışı
17.1. Developer Perspektifi
Ali Yılmaz — bağımsız developer

text
1. Wolt entegrasyonu plugin'i yazar (30 gün)
2. Marketplace'e gönderir
3. Review: 5 gün → approved
4. Yayınlar: 500 TL/ay
5. İlk ay: 5 tenant kurar
6. Gelir: 5 × 500 × 0.80 = 2.000 TL
7. 6 ay sonra: 45 tenant
8. Gelir: 45 × 500 × 0.80 = 18.000 TL/ay
9. Tier: Verified → komisyon %18
10. Yeni gelir: 45 × 500 × 0.82 = 18.450 TL/ay
17.2. Tenant Perspektifi
Tesfirma Restoran

text
1. Marketplace'te Wolt arar
2. Wolt Integration bulur (4.8 ⭐)
3. 14 gün trial başlar
4. Kurar → Wolt API key girer
5. Test eder → çalışıyor
6. Trial biter → 500 TL/ay abone olur
7. Wolt siparişleri otomatik ERP'ye düşer
8. KDS'ye gider (Belge 13)
9. Chef hazırlar → Wolt'a "hazır" gönderir
10. Kurye teslim eder → Wolt'a "teslim" gönderir
17.3. Owner Perspektifi
StokPro (biz)

text
1. Ali plugin gönderir
2. Review: kod kalitesi ✅, izinler ✅, güvenlik ✅
3. Approve
4. Tesfirma kurar
5. Aylık 500 TL alırız
6. Komisyon: %18 (Ali tier)
7. Owner payı: 500 × 0.18 = 90 TL/ay
8. 45 tenant = 4.050 TL/ay pasif gelir
9. Ali yeni app çıkarır → daha fazla tenant
10. Marketplace büyür → daha fazla app
Kural: Bu döngü ekosistemi büyütür.

18. Anti-Patterns
Bu projede yasak olan marketplace hataları:

Pay-to-win — Featured ücretli olamaz

Monopol — Yeni developer keşfedilebilir

Fake review — Ban

Review manipülasyonu — Developer kendi app'ini review edemez

Silent reject — Detaylı feedback zorunlu

Commission hidden — Şeffaf komisyon

Payment delay — 30 gün max

Secret in billing — PCI-DSS uyumlu

SLA yaptırımsız — İhlal etkisi var

Kill switch abuse — Sadece güvenlik için

Auto-update zorla — Tenant onayı

Data lock-in — Kaldırma = veri export

Sandbox yok — Her plugin container

Signature yok — Her app imzalı

Cross-tenant data — RLS + audit

Owner arbitraj — Kararlar belgeli

App delete (kullanımda) — Sadece deprecate

19. Roadmap
Aşama	Süre	Kapsam
1. Marketplace foundation	6 hafta	App listing, kategori, arama
2. Developer onboarding	4 hafta	Kayıt, KYC, dashboard
3. Review system	3 hafta	Submission, review, approval
4. Install flow	4 hafta	Permission, install, manage
5. Billing	6 hafta	Payment, split, payout, invoice
6. Rating & review	3 hafta	Star, review, moderation
7. Trust & safety	4 hafta	Audit, ban, dispute
8. Partner program	4 hafta	Application, tier, benefits
9. Analytics	4 hafta	Owner, developer, tenant dashboards
Toplam: 38 hafta (~10 ay).

20. Glossary
Terim	Anlam
Marketplace	App kataloğu
App	Plugin, connector, vertical
Developer	Bağımsız geliştirici
Partner	Onaylı firma
Owner	Platform sahibi (biz)
Tenant	Müşteri şirket
GMV	Gross Merchandise Value
Commission	Platform kesintisi
Payout	Developer'a ödeme
KYC	Know Your Customer
KYB	Know Your Business
Verified	Onaylı (badge)
Certified	Sertifikalı
Featured	Öne çıkan
Trial	Ücretsiz deneme
Freemium	Ücretsiz + premium
Tier	Developer seviyesi
SLA	Service Level Agreement
Dispute	Uyuşmazlık
Kill switch	Acil durdurma
Chargeback	Ters ibraz
Refund	İade
Stopaj	Vergi kesintisi
21. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — marketplace, developer ecosystem, partner program