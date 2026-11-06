# 15 — Compliance Layer

**Project:** Kurumsal ERP Platform
**Belge:** Yasal Uyum, Regülasyon, Denetim, Sertifikasyon, Multi-Country Compliance
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md, 11-workflow-engine.md, 12-report-builder.md, 13-ui-builder.md, 14-integration-hub.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **Compliance Layer** sistemini tanımlar — yasal düzenlemelere uyum, denetim izi, veri koruma, sertifikasyon ve çok ülkeli regülasyon desteği. İçerir:

- Neden compliance birinci sınıf vatandaş olmalı
- Uyum katmanları (ülke, sektör, şirket)
- Türkiye uyumu (e-Fatura, e-Defter, KVKK, TDHP, vergi)
- AB uyumu (GDPR, eIDAS, PEPPOL, IFRS)
- ABD uyumu (SOX, GAAP, HIPAA)
- Denetim izi (audit trail) — WORM, 7 yıl
- Veri saklama ve silme (retention, right to be forgotten)
- Veri ikametgahı (data residency)
- Kişisel veri koruma (PII, pseudonymization, encryption)
- Vergi motoru (multi-country tax engine)
- Muhasebe standartları (paralel ledger)
- Sertifikasyon yol haritası (ISO 27001, SOC 2, ISO 27701)
- Compliance plugin mimarisi
- Restoran/klinik örneği
- Anti-patterns, roadmap

**Hedef kitle:** Geliştiriciler, AI asistanlar, hukuk/compliance ekibi, güvenlik denetçileri, CTO, yöneticiler.

**Kritik:** Compliance, **SAP'nin en büyük kalesi**. SAP = 50 yıllık regülasyon bilgisi. Biz = plugin mimarisi + partner ecosystem ile aynı seviyeye gelir. Bu belge, bu yolu tanımlar.

---

## 2. Neden Compliance Birinci Sınıf?

### 2.1. Compliance Olmadan Satış Yok

| Segment | Compliance Zorunluluğu |
|---|---|
| **SMB (TR)** | e-Fatura, e-Defter, KVKK |
| **Mid-market (TR)** | + TDHP, vergi beyannamesi |
| **Enterprise (TR)** | + Bağımsız denetim, SOX uyumu |
| **AB müşterisi** | GDPR, eIDAS, PEPPOL |
| **ABD müşterisi** | SOX, GAAP, HIPAA (sağlık) |
| **Global müşteri** | Multi-country tax, transfer pricing |

**Kural:** Compliance olmayan ERP **satılamaz**. Nokta.

### 2.2. Klasik ERP'nin Compliance Yaklaşımı

| ERP | Yaklaşım | Sorun |
|---|---|---|
| **SAP** | Yerleşik, 50 yıl birikim | Kapalı, ülke başına lisans |
| **Oracle** | Yerleşik, güçlü | Karmaşık, pahalı |
| **Odoo** | Modül + community | Yüzeysel, güncel değil |
| **Logo** | Yerleşik (TR) | Sadece TR, kapalı |
| **NetSuite** | Yerleşik (multi-country) | Pahalı, sınırlı özelleştirme |

**Ortak sorun:** Compliance **monolitik** — değiştirmek zor, ülke eklemek pahalı.

### 2.3. Bizim Çözüm

**Compliance Layer** — ülke/sektör/şirket bazlı **pluggable**. Her regülasyon = **plugin** (Belge 10).
TR müşterisi → TR plugin (e-Fatura, e-Defter, KVKK)
AB müşterisi → EU plugin (GDPR, PEPPOL, eIDAS)
ABD müşterisi → US plugin (SOX, GAAP)
Sağlık → HIPAA plugin
Finans → PCI-DSS plugin

text

**Hedef:** Yeni ülke/sektör = **plugin config**, kod değil. (Belge 00 — sıfır limit.)

---

## 3. Uyum Katmanları

### 3.1. 4 Katman
┌─────────────────────────────────────────────────────┐
│ KATMAN 4: Şirket İçi Politikalar │
│ ─ Onay eşikleri │
│ ─ Veri saklama süreleri (şirkete özel) │
│ ─ Erişim politikaları │
└─────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────┐
│ KATMAN 3: Sektör Regülasyonu │
│ ─ HIPAA (sağlık) │
│ ─ PCI-DSS (ödeme) │
│ ─ SOX (halka açık) │
│ ─ Basel III (finans) │
└─────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────┐
│ KATMAN 2: Ülke/Bölge Regülasyonu │
│ ─ TR: e-Fatura, e-Defter, KVKK, TDHP, vergi │
│ ─ EU: GDPR, eIDAS, PEPPOL, IFRS │
│ ─ US: SOX, GAAP │
│ ─ UK: UK-GDPR, MTD │
└─────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────┐
│ KATMAN 1: Global Standart │
│ ─ ISO 27001 (bilgi güvenliği) │
│ ─ SOC 2 (güven) │
│ ─ ISO 27701 (gizlilik) │
│ ─ ISO 9001 (kalite) │
└─────────────────────────────────────────────────────┘

text

**Kural:** Her tenant **hangi katmanlara tabi** olduğunu **seçer** (Belge 03 — feature flags).

### 3.2. Compliance Feature Flags

```yaml
compliance:
  global:
    - iso27001: true
    - soc2: true
    - iso27701: true
  
  country:
    tr:
      e_fatura: true
      e_defter: true
      kvkk: true
      tdhp: true
      tax_engine: true
    
    eu:
      gdpr: true
      eidas: true
      peppol: true
      ifrs: true
    
    us:
      sox: true
      gaap: true
      hipaa: false                     # sadece sağlık
  
  industry:
    hipaa: false                       # sağlık tenant'ı ise
    pci_dss: true                      # ödeme alıyorsa
    sox: false                         # halka açık değilse
Kural: Her flag plugin'e bağlı (Belge 10).

4. Türkiye Uyumu
4.1. e-Fatura (GİB)
Zorunluluk: Ciro > 5M TL (kademeli eşikler).

Format: UBL-TR 1.2.1 (XML).

Entegrasyon: Entegratör (Uyumsoft, Foriba, Digital Planet, İzibiz) veya doğrudan GİB.

Plugin:

yaml
plugin:
  key: "tr-einvoice"
  name: "Türkiye e-Fatura"
  type: "compliance"
  country: "TR"
  
  features:
    - "outgoing_invoice"               # giden fatura
    - "incoming_invoice"               # gelen fatura
    - "e_arsiv"                        # e-arşiv
    - "e_ihracat"                      # e-ihracat
    - "e_irsaliye"                     # e-irsaliye
    - "e_mustahsil"                    # e-müstahsil
  
  integrations:
    - "einvoice-uyumsoft"
    - "einvoice-foriba"
    - "einvoice-digitalplanet"
    - "einvoice-izibiz"
  
  compliance_rules:
    - "Her satış faturası GİB'e gönderilmeli"
    - "UUID zorunlu"
    - "XSLT transform zorunlu"
    - "İmza zorunlu (mali mühür)"
Runtime:

php
class EInvoiceService
{
    public function send(FinArInvoice $invoice): void
    {
        // 1. UBL-TR XML oluştur
        $xml = app(UblTrGenerator::class)->generate($invoice);
        
        // 2. UUID ekle
        $uuid = Str::uuid();
        $xml = $this->injectUuid($xml, $uuid);
        
        // 3. Mali mühür imzası
        $signed = app(MaliMuhurService::class)->sign($xml, $tenant);
        
        // 4. Entegratöre gönder
        $connector = $this->getConnector($tenant);
        $result = $connector->execute('send_invoice', [
            'uuid' => $uuid,
            'xml' => $signed,
        ]);
        
        // 5. Sonuç
        $invoice->update([
            'gib_uuid' => $uuid,
            'gib_status' => $result['status'],
            'gib_invoice_id' => $result['gib_invoice_id'],
            'gib_sent_at' => now(),
        ]);
    }
}
Uyum kuralı: e-Fatura gönderilmeden muhasebeleştirilemez (Belge 11 — workflow).

4.2. e-Defter (GİB)
Zorunluluk: Ciro > 5M TL.

Format: XBRL-GL (yevmiye + kebir).

Aylık: Berat (GİB onayı).

Plugin:

yaml
plugin:
  key: "tr-edefters"
  name: "Türkiye e-Defter"
  type: "compliance"
  country: "TR"
  
  features:
    - "yevmiye_defteri"                # günlük defter
    - "kebir_defteri"                  # büyük defter
    - "berat"                          # aylık berat
    - "weighted_signature"             # ağırlıklı mali mühür
  
  schedule:
    - { key: "monthly_berat", cron: "0 2 1 * *" }  # her ayın 1'i
Runtime:

php
class EDefterService
{
    public function generateMonthly(string $tenantId, int $year, int $month): void
    {
        // 1. Yevmiye + Kebir çek
        $entries = FinJournalEntry::where('tenant_id', $tenantId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();
        
        // 2. XBRL-GL oluştur
        $xml = app(XbrlGlGenerator::class)->generate($entries);
        
        // 3. Ağırlıklı mali mühür imzala
        $signed = app(MaliMuhurService::class)->signWeighted($xml, $tenantId);
        
        // 4. Berat oluştur
        $berat = app(BeratGenerator::class)->generate($signed);
        
        // 5. GİB'e gönder
        app(EdefterGibService::class)->submit($berat);
    }
}
4.3. KVKK (Veri Koruma)
Zorunluluk: Tüm şirketler.

Yükümlülükler:

✅ Veri envanteri

✅ Aydınlatma metni

✅ Açık rıza

✅ Veri işleme kayıt (VERBIS)

✅ Veri silme hakkı

✅ Veri taşınabilirlik hakkı

✅ Veri ihlal bildirimi (72 saat)

Plugin:

yaml
plugin:
  key: "tr-kvkk"
  name: "Türkiye KVKK Uyumu"
  type: "compliance"
  country: "TR"
  
  features:
    - "data_inventory"                 # veri envanteri
    - "consent_management"             # açık rıza
    - "data_subject_rights"            # ilgili kişi hakları
    - "data_breach_notification"       # ihlal bildirimi
    - "verbis_export"                  # VERBIS formatı
    - "retention_policy"               # saklama politikası
Runtime — Right to be Forgotten:

php
class KvkkRightToBeForgotten
{
    public function process(int $dataSubjectId): void
    {
        // 1. Kullanıcının tüm verisi
        $entities = $this->findAllPII($dataSubjectId);
        
        // 2. Her tablodan sil (hard delete veya pseudonymize)
        foreach ($entities as $entity) {
            if ($this->mustRetain($entity)) {
                // Yasal saklama (7 yıl) → pseudonymize
                $this->pseudonymize($entity);
            } else {
                // Normal veri → hard delete
                $entity->delete();
            }
        }
        
        // 3. Audit
        event(new PIIErased($dataSubjectId));
        
        // 4. Onay
        $this->notifyDataSubject($dataSubjectId, 'Verileriniz silindi');
    }
}
Kural: Yasal saklama (finansal 7 yıl, muhasebe 10 yıl) korunur — ama pseudonymize edilir.

4.4. TDHP (Tek Düzen Hesap Planı)
Zorunluluk: Tüm şirketler (muhasebe).

Yapı: Hesap planı ağacı (100-799, sonra 5 seviye).

Plugin:

yaml
plugin:
  key: "tr-tdhp"
  name: "Türkiye Tek Düzen Hesap Planı"
  type: "compliance"
  country: "TR"
  
  features:
    - "chart_of_accounts"              # hesap planı
    - "journal_validation"             # yevmiye validasyonu
    - "financial_statements"           # mali tablolar
    - "tax_reconciliation"             # vergi mutabakatı
  
  seed:
    accounts: "tdhp-accounts.json"     # standart hesap planı
Hesap Yapısı:

text
1     Dönen Varlıklar
  10  Hazır Değerler
    100 Kasa
    101 Alınan Çekler
    102 Bankalar
  ...
6     Gelir Tablosu
  60  Brüt Satışlar
    600 Yurt İçi Satışlar
    601 Yurt Dışı Satışlar
  62  Satılan Malın Maliyeti
    621 Satılan Ticari Mallar Maliyeti
Kural: Yeni hesap TDHP'ye uyumlu olmalı.

4.5. Vergi Motoru (Multi-Tax)
Zorunluluk: Her fatura için KDV, stopaj, ÖTV, vs.

Yapı:

yaml
tax_engine:
  country: "TR"
  taxes:
    - key: "kdv"
      name: "Katma Değer Vergisi"
      rates: [0, 1, 8, 10, 18, 20]
      default_rate: 20
      
      rules:
        - "invoice.type = 'sale'"
        - "invoice.line.product.vat_rate"
      
      accounting:
        account: "391"                   # Hesaplanan KDV
        reverse_account: "191"           # İndirilecek KDV
    
    - key: "stopaj"
      name: "Gelir Vergisi Stopajı"
      rates: [0, 15, 20]
      
      rules:
        - "invoice.type = 'service'"
        - "invoice.supplier.type = 'individual'"
      
      accounting:
        account: "360"                   # Ödenecek Vergi
Runtime:

php
class TaxCalculator
{
    public function calculate(Invoice $invoice): array
    {
        $taxes = [];
        $rules = $this->getApplicableRules($invoice);
        
        foreach ($rules as $rule) {
            $amount = $this->applyRule($invoice, $rule);
            $taxes[] = [
                'key' => $rule->key,
                'rate' => $rule->rate,
                'amount' => $amount,
                'account' => $rule->account,
            ];
        }
        
        return $taxes;
    }
}
Kural: Vergi her ülkeye göre plugin. TR: KDV + stopaj. EU: VAT. US: Sales tax (state-by-state).

4.6. Muhasebe Standartları (Paralel Ledger)
Neden? Aynı işlem farklı standartlarda farklı.

Örnek:

text
Bir satış:
  TDHP: 600 (gelir), 120 (alacak), 391 (KDV)
  IFRS: Revenue recognition (IFRS 15) — farklı zamanlama
  Tax: Vergi matrahı — KDV zamanlaması farklı
Paralel Ledger:

yaml
plugin:
  key: "parallel-ledger"
  name: "Paralel Muhasebe"
  type: "compliance"
  
  ledgers:
    - key: "primary"                   # TDHP
      standard: "TDHP"
      mandatory: true
    
    - key: "ifrs"
      standard: "IFRS"
      mandatory: false
    
    - key: "management"                # Yönetim muhasebesi
      standard: "custom"
      mandatory: false
    
    - key: "tax"                       # Vergi defteri
      standard: "TR_TAX"
      mandatory: true
Runtime:

php
class JournalEntryService
{
    public function post(Entry $entry): void
    {
        // 1. Primary ledger
        $this->postToLedger($entry, 'primary');
        
        // 2. IFRS ledger (farklı hesaplama)
        if (Features::enabled('ledger.ifrs')) {
            $ifrsEntry = app(IfrsMapper::class)->map($entry);
            $this->postToLedger($ifrsEntry, 'ifrs');
        }
        
        // 3. Tax ledger
        $taxEntry = app(TaxMapper::class)->map($entry);
        $this->postToLedger($taxEntry, 'tax');
        
        // 4. Event
        event(new EntryPosted($entry));
    }
}
Kural: Paralel ledger aynı anda — tek transaction.

4.7. Diğer TR Uyumu
Regülasyon	Kapsam
e-İrsaliye	Sevkiyat belgesi
e-Müstahsil	Çiftçi alımı
e-SMM	Serbest meslek makbuzu
e-Beyanname	Vergi beyannamesi
SGK Bildirge	Personel bildirimi
Damga Vergisi	Sözleşme vergisi
Belediye	Emlak, çevre, ilan vergisi
Her biri plugin (Belge 10).

5. AB Uyumu
5.1. GDPR (Veri Koruma)
Zorunluluk: AB'de iş yapan her şirket.

Yükümlülükler:

✅ Veri işleme envanteri (ROPA)

✅ Veri koruma etki değerlendirmesi (DPIA)

✅ Açık rıza

✅ Veri silme hakkı

✅ Veri taşınabilirlik (JSON/XML export)

✅ Veri ihlal bildirimi (72 saat)

✅ Veri Koruma Görevlisi (DPO)

Plugin:

yaml
plugin:
  key: "eu-gdpr"
  name: "GDPR Uyumu"
  type: "compliance"
  region: "EU"
  
  features:
    - "ropa"                           # Records of Processing Activities
    - "dpia"                           # Data Protection Impact Assessment
    - "consent_management"
    - "right_to_access"                # veri görme
    - "right_to_rectification"         # düzeltme
    - "right_to_erasure"               # silme
    - "right_to_portability"           # taşınabilirlik
    - "breach_notification"
    - "dpo_dashboard"
Runtime — Right to Access:

php
class GdprRightToAccess
{
    public function process(int $dataSubjectId): array
    {
        // 1. Tüm PII
        $pii = $this->findAllPII($dataSubjectId);
        
        // 2. JSON export
        $export = json_encode($pii, JSON_PRETTY_PRINT);
        
        // 3. Download link
        $url = Storage::put("gdpr-exports/{$dataSubjectId}.json", $export);
        
        // 4. Notify
        return $url;
    }
}
5.2. eIDAS (Elektronik İmza)
Zorunluluk: AB'de dijital imza.

Format: XAdES, PAdES, CAdES.

Plugin:

yaml
plugin:
  key: "eu-eidas"
  name: "eIDAS Dijital İmza"
  type: "compliance"
  region: "EU"
  
  features:
    - "xades"                          # XML imza
    - "pades"                          # PDF imza
    - "cades"                          # CMS imza
    - "qualified_signature"            # nitelikli imza
5.3. PEPPOL (E-Fatura AB)
Zorunluluk: AB kamu ihaleleri.

Format: UBL 2.1 (PEPPOL BIS).

Plugin:

yaml
plugin:
  key: "eu-peppol"
  name: "PEPPOL e-Fatura"
  type: "compliance"
  region: "EU"
  
  features:
    - "sending"                        # gönderme
    - "receiving"                      # alma
    - "access_point"                   # PEPPOL AP
5.4. IFRS (Muhasebe Standardı)
Zorunluluk: AB'de halka açık şirketler.

Standart: IFRS 15 (revenue), IFRS 16 (lease), IFRS 9 (financial instruments).

Plugin:

yaml
plugin:
  key: "eu-ifrs"
  name: "IFRS Muhasebe"
  type: "compliance"
  region: "EU"
  
  features:
    - "ifrs_15_revenue"                # hasılat
    - "ifrs_16_leases"                 # kiralama
    - "ifrs_9_financial"               # finansal araçlar
    - "parallel_ledger"
    - "consolidation"                  # konsolidasyon
5.5. Diğer AB
Regülasyon	Kapsam
UK-GDPR	İngiltere
MTD (Making Tax Digital)	İngiltere vergi
SAF-T	Vergi denetim dosyası
EU VAT	Multi-country KDV
EU DAC7	Platform raporlama
Her biri plugin.

6. ABD Uyumu
6.1. SOX (Sarbanes-Oxley)
Zorunluluk: ABD'de halka açık şirketler.

Yükümlülükler:

✅ İç kontrol (internal controls)

✅ Denetim izi (audit trail)

✅ Ayrılık (segregation of duties)

✅ Yönetim onayı (management certification)

Plugin:

yaml
plugin:
  key: "us-sox"
  name: "SOX Uyumu"
  type: "compliance"
  country: "US"
  
  features:
    - "segregation_of_duties"          # ayrılık ilkesi
    - "management_certification"       # yönetim onayı
    - "internal_controls"              # iç kontrol
    - "audit_trail"                    # denetim izi
    - "whistleblower"                  # ihbar hattı
6.2. GAAP (Muhasebe)
Zorunluluk: ABD'de şirketler (bazı durumlarda).

Standart: US GAAP (FASB).

Plugin: IFRS benzer, US GAAP mapping.

6.3. HIPAA (Sağlık)
Zorunluluk: ABD sağlık sektörü.

Yükümlülükler:

✅ PHI koruma (Protected Health Information)

✅ Erişim kontrolü

✅ Denetim

✅ BAA (Business Associate Agreement)

Plugin:

yaml
plugin:
  key: "us-hipaa"
  name: "HIPAA Uyumu"
  type: "compliance"
  industry: "healthcare"
  
  features:
    - "phi_encryption"
    - "access_logging"
    - "baa_management"
    - "breach_notification"
7. Denetim İzi (Audit Trail)
7.1. Audit Trail Nedir?
Her işlem kalıcı, değiştirilemez, zaman damgalı kayıt.

Gereklilikler:

✅ Kim yaptı

✅ Ne yaptı (action)

✅ Ne zaman (timestamp)

✅ Nerede (IP, location)

✅ Neden (reason, if applicable)

✅ Değişiklik (before/after)

✅ Değiştirilemez (immutable)

✅ 7+ yıl saklama

7.2. Mevcut Audit Log
Platform zaten sys_audit_log tutuyor (Belge 02).

Şema:

sql
CREATE TABLE sys_audit_log (
    id              uuid PRIMARY KEY,
    tenant_id       uuid NOT NULL,
    user_id         bigint,
    action          varchar(50) NOT NULL,      -- create | update | delete | login | logout | view | export
    entity_type     varchar(80) NOT NULL,
    entity_id       uuid,
    before_data     jsonb,                     -- önce
    after_data      jsonb,                     -- sonra
    changes         jsonb,                     -- fark
    reason          text,                      -- neden (opsiyonel)
    ip              inet,
    user_agent      text,
    occurred_at     timestamptz NOT NULL DEFAULT now()
);
7.3. WORM (Write Once Read Many)
Kural: Audit log değiştirilemez.

Uygulama:

sql
-- Append-only
REVOKE UPDATE, DELETE ON sys_audit_log FROM PUBLIC;
GRANT INSERT, SELECT ON sys_audit_log TO erp_app;

-- Trigger (extra güvenlik)
CREATE OR REPLACE FUNCTION prevent_audit_modification()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Audit log cannot be modified';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER audit_immutable
BEFORE UPDATE OR DELETE ON sys_audit_log
FOR EACH ROW EXECUTE FUNCTION prevent_audit_modification();
Kural: DB seviyesinde WORM.

7.4. Hash Chain
Extra güvenlik — her kayıt öncekinin hash'ini içerir:

sql
ALTER TABLE sys_audit_log ADD COLUMN prev_hash varchar(64);
ALTER TABLE sys_audit_log ADD COLUMN record_hash varchar(64);

-- Her insert'te
CREATE OR REPLACE FUNCTION set_audit_hash()
RETURNS TRIGGER AS $$
DECLARE
    prev_hash_val varchar(64);
BEGIN
    SELECT record_hash INTO prev_hash_val
    FROM sys_audit_log
    ORDER BY occurred_at DESC
    LIMIT 1;
    
    NEW.prev_hash = COALESCE(prev_hash_val, '');
    NEW.record_hash = encode(
        digest(
            COALESCE(prev_hash_val, '') || 
            NEW.tenant_id || 
            NEW.action ||
            NEW.entity_type ||
            COALESCE(NEW.entity_id::text, '') ||
            NEW.occurred_at::text,
            'sha256'
        ),
        'hex'
    );
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER audit_hash_chain
BEFORE INSERT ON sys_audit_log
FOR EACH ROW EXECUTE FUNCTION set_audit_hash();
Faydası:

Kayıt silinemez (chain kırılır)

Kayıt değiştirilemez (hash uyuşmaz)

Doğrulanabilir (chain replay)

7.5. Retention
Log Tipi	Saklama
Financial	10 yıl
Tax	10 yıl
HR	7 yıl
Security	7 yıl
General	7 yıl
System	1 yıl
Kural: Retention sonrası arşiv (S3 Glacier).

7.6. Audit Dashboard
text
┌──────────────────────────────────────────────────────────┐
│  Audit Trail                                              │
├──────────────────────────────────────────────────────────┤
│  🔍 [Ara...] [User ▾] [Action ▾] [Entity ▾] [Date ▾]    │
├──────────────────────────────────────────────────────────┤
│  Son 24 saat: 1.234 işlem                                │
│                                                          │
│  06.11.2026 10:30:45                                     │
│  👤 Ali Yılmaz (admin)                                   │
│  📝 UPDATE fin_customer #123                             │
│  🔄 credit_limit: 10000 → 15000                          │
│  🌐 192.168.1.100                                        │
│                                                          │
│  06.11.2026 10:28:12                                     │
│  👤 Ayşe Kaya (accountant)                               │
│  📝 POST fin_ar_invoice #456                             │
│  🌐 192.168.1.101                                        │
│                                                          │
│  [📥 Export] [🔍 Verify Hash Chain]                      │
└──────────────────────────────────────────────────────────┘
8. Veri Saklama ve Silme
8.1. Retention Policy
Her entity için saklama süresi:

yaml
retention:
  fin_ar_invoice: 10 years             # yasal
  fin_ap_invoice: 10 years
  fin_journal_entry: 10 years
  hrm_employee: 7 years                # iş hukuku
  hrm_payroll: 7 years
  otc_sales_order: 7 years
  inv_product: indefinite              # ticari değer
  crm_lead: 2 years                    # aktivite
  sys_notification: 90 days
  session: 30 days
  sys_audit_log: 7 years
Kural: Retention sonrası soft-delete (1 yıl) → hard-delete → arşiv.

8.2. Right to be Forgotten (GDPR/KVKK)
Flow:

text
1. Data subject talep eder
2. Kimlik doğrulama (email/SMS)
3. Tüm PII tespit
4. Yasal saklama kontrol (tutulmalı mı?)
5. Sil veya pseudonymize
6. Audit
7. Onay
Runtime:

php
class RightToBeForgotten
{
    public function erase(int $dataSubjectId, string $reason): void
    {
        // 1. Onay
        if (!auth()->user()->can('gdpr.erase')) {
            throw new ForbiddenException();
        }
        
        // 2. Tüm PII
        $piiEntities = app(PiiScanner::class)->scan($dataSubjectId);
        
        // 3. Retention check
        foreach ($piiEntities as $entity) {
            if ($this->mustRetain($entity)) {
                // Legal retention → pseudonymize
                app(Pseudonymizer::class)->pseudonymize($entity);
            } else {
                // Hard delete
                $entity->forceDelete();
            }
        }
        
        // 4. Audit
        event(new PIIErased($dataSubjectId, $reason));
        
        // 5. Onay
        app(NotificationService::class)->notify($dataSubjectId, 'Verileriniz silindi');
    }
}
Pseudonymization:

php
class Pseudonymizer
{
    public function pseudonymize($entity): void
    {
        // PII alanları pseudonymize
        $entity->email = 'REDACTED-' . Str::uuid();
        $entity->phone = 'REDACTED';
        $entity->tax_no = 'REDACTED-' . Str::uuid();
        $entity->address = 'REDACTED';
        $entity->save();
    }
}
Kural: Yasal saklama korunur, ama kişisel veri pseudonymize.

8.3. Veri İkametgahı (Data Residency)
Zorunluluk: Bazı ülkeler veri yerel olmalı.

Ülke	Kural
Rusya	Kişisel veri Rusya'da
Çin	Belirli veri Çin'de
AB	GDPR — AB içinde
Hindistan	Belirli veri Hindistan'da
TR	KVKK — TR'de (yumuşak)
Plugin:

yaml
plugin:
  key: "data-residency"
  name: "Veri İkametgahı"
  type: "compliance"
  
  regions:
    tr:
      required: true
      storage: "eu-central-1"           # AWS Frankfurt (yakın)
      backup: "eu-central-1"
    
    eu:
      required: true
      storage: "eu-central-1"
      backup: "eu-west-1"
    
    us:
      required: true
      storage: "us-east-1"
      backup: "us-west-2"
Runtime:

php
class DataResidencyRouter
{
    public function getStorageForTenant(Tenant $tenant): string
    {
        $residency = $tenant->data_residency ?? 'eu';
        
        return match ($residency) {
            'tr' => 'eu-central-1',
            'eu' => 'eu-central-1',
            'us' => 'us-east-1',
            default => 'eu-central-1',
        };
    }
}
Kural: Tenant'ın verisi sadece o ülkenin storage'ında.

9. Kişisel Veri Koruma (PII)
9.1. PII Tanımı
PII = Personally Identifiable Information. Örnekler:

Kategori	Alanlar
Kimlik	TC kimlik, pasaport, ehliyet
İletişim	Email, telefon, adres
Finansal	IBAN, kredi kartı, vergi no
Biyometrik	Parmak izi, yüz
Lokasyon	GPS, IP (bazen)
Sağlık	Teşhis, ilaç, tahlil
Dini/siyasi	Özel kategori
9.2. PII Sınıflandırma
yaml
pii_fields:
  # Kategori 1: Standart
  - { entity: "fin_customer", field: "email", category: "contact" }
  - { entity: "fin_customer", field: "phone", category: "contact" }
  
  # Kategori 2: Hassas
  - { entity: "fin_customer", field: "tax_no", category: "financial" }
  - { entity: "hr_employee", field: "tc_kimlik", category: "identity" }
  
  # Kategori 3: Özel (extra koruma)
  - { entity: "hr_employee", field: "salary", category: "financial" }
  - { entity: "custom_patient", field: "diagnosis", category: "health" }
9.3. PII Koruma Stratejileri
Strateji	Kullanım
Encryption	Storage (at rest)
Masking	UI'da gösterme
Pseudonymization	İşlem sırasında
Anonymization	Analiz için
Tokenization	Kart no gibi
9.4. Encryption at Rest
php
class EncryptedPii
{
    public function encrypt(string $value, string $context): string
    {
        return Crypt::encryptString($value, $this->getKey($context));
    }
    
    public function decrypt(string $value, string $context): string
    {
        return Crypt::decryptString($value, $this->getKey($context));
    }
}
Kural: Her tenant kendi encryption key (KMS).

9.5. PII Masking
php
class PiiMasker
{
    public function mask(string $field, string $value, string $role): string
    {
        if ($this->canSeeUnmasked($field, $role)) {
            return $value;
        }
        
        return match ($field) {
            'email' => $this->maskEmail($value),
            'phone' => $this->maskPhone($value),
            'tax_no' => $this->maskTaxNo($value),
            'tc_kimlik' => $this->maskTcKimlik($value),
            default => '***',
        };
    }
    
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        return substr($local, 0, 1) . '***@' . $domain;
    }
    
    private function maskPhone(string $phone): string
    {
        return '***-***-' . substr($phone, -4);
    }
    
    private function maskTcKimlik(string $tc): string
    {
        return '***-***-' . substr($tc, -2);
    }
}
Kural: PII asla log'da, export'ta, API'de unmasked değil.

9.6. PII Audit
Her PII erişimi audit:

php
class PiiAccessAudit
{
    public function log(PiiAccess $access): void
    {
        AuditLog::create([
            'action' => 'pii_access',
            'entity_type' => $access->entityType,
            'entity_id' => $access->entityId,
            'user_id' => $access->userId,
            'reason' => $access->reason,        // Neden?
            'fields' => $access->fields,        // Hangi alanlar?
            'ip' => $access->ip,
        ]);
    }
}
Kural: PII erişimi neden açıklanmalı.

10. Sertifikasyon Yol Haritası
10.1. ISO 27001 (Bilgi Güvenliği)
Kapsam: Bilgi güvenliği yönetim sistemi.

Süre: 6-12 ay, ~50-100K USD.

Adımlar:

Gap analysis (1 ay)

ISMS kurulumu (3 ay)

Risk assessment (1 ay)

Internal audit (1 ay)

Certification audit (2 ay)

Gereklilikler:

✅ Güvenlik politikaları

✅ Risk yönetimi

✅ Erişim kontrolü

✅ Şifreleme

✅ Olay yönetimi

✅ İş sürekliliği

✅ Farkındalık eğitimi

10.2. SOC 2 (Güven)
Kapsam: Security, Availability, Processing Integrity, Confidentiality, Privacy.

Süre: 6-12 ay, ~30-80K USD.

Tip:

Type 1: Design (belirli tarihte)

Type 2: Operating (3-12 ay boyunca)

Gereklilikler:

✅ Erişim kontrolü

✅ Değişiklik yönetimi

✅ İzleme

✅ Incident response

✅ Vendor management

✅ Business continuity

10.3. ISO 27701 (Gizlilik)
Kapsam: Gizlilik bilgi yönetim sistemi (GDPR uyumlu).

Süre: ISO 27001 sonrası 3-6 ay.

Gereklilikler:

✅ PII envanteri

✅ PII işleme kaydı (ROPA)

✅ Consent management

✅ Data subject rights

✅ DPO (Data Protection Officer)

10.4. PCI-DSS (Ödeme)
Kapsam: Kart verisi koruma.

Süre: 3-6 ay.

Seviye:

Level 1: > 6M transaction/yıl

Level 2: 1-6M

Level 3: 20K-1M

Level 4: < 20K

Gereklilikler:

✅ Kart verisi şifreleme

✅ Erişim kontrolü

✅ Ağ segmentasyonu

✅ Düzenli test

✅ Güvenlik politikası

10.5. Sertifikasyon Yol Haritası
Yıl	Sertifika	Öncelik
1	ISO 27001	Yüksek
1	SOC 2 Type 1	Yüksek
2	SOC 2 Type 2	Yüksek
2	ISO 27701	Orta
3	PCI-DSS (Level 1)	Orta
3	ISO 9001	Düşük
5	HIPAA	Opsiyonel
Kural: Sertifikasyon satış için, mühendislik değil. Ama gerekli.

11. Compliance Plugin Mimarisi
11.1. Neden Plugin?
Her ülke/sektör farklı regülasyon. Plugin mimarisi ile:

✅ Core'u kirletmez

✅ Bağımsız geliştirilebilir

✅ Yeni ülke = yeni plugin

✅ Marketplace'te satılabilir

11.2. Compliance Plugin Contract
yaml
plugin:
  key: "tr-compliance-pack"
  name: "Türkiye Compliance Paketi"
  type: "compliance"
  
  metadata:
    country: "TR"
    region: "MEA"
    languages: ["tr"]
    version: "1.0.0"
    certified: true
    certification_date: "2026-01-01"
  
  features:
    # Her compliance alanı bir feature
    - e_fatura
    - e_defter
    - e_irsaliye
    - kvkk
    - tdhp
    - tax_engine_tr
    - sgk
    - e_beyanname
  
  dependencies:
    - "core-fin"
    - "core-otc"
    - "integration-hub"
  
  hooks:
    # Plugin core'a bağlanma noktaları
    - event: "invoice.created"
      handler: "EInvoiceAutoSender"
    
    - event: "invoice.posted"
      handler: "EDefterRecorder"
    
    - event: "journal_entry.created"
      handler: "TdhpValidator"
    
    - event: "customer.created"
      handler: "KvkkConsentRecorder"
  
  ui:
    menus:
      - { label: "e-Fatura", route: "/compliance/tr/einvoice", icon: "file-invoice" }
      - { label: "e-Defter", route: "/compliance/tr/edefter", icon: "book" }
      - { label: "KVKK", route: "/compliance/tr/kvkk", icon: "shield" }
    
    settings:
      - { key: "gib_username", type: "string", required: true }
      - { key: "gib_password", type: "string", required: true, secret: true }
      - { key: "mali_muhur_path", type: "file", required: true }
  
  scheduled:
    - { key: "monthly_edefter_berat", cron: "0 2 1 * *" }
    - { key: "daily_gib_status_check", cron: "0 */6 * * *" }
11.3. Compliance Event Flow
text
1. Sale created
    ↓
2. Invoice created (event: invoice.created)
    ↓
3. TR Compliance Plugin: EInvoiceAutoSender.handle()
    ↓
4. GİB'e gönder (integration-hub)
    ↓
5. Invoice posted (event: invoice.posted)
    ↓
6. TR Compliance Plugin: EDefterRecorder.handle()
    ↓
7. Yevmiye kaydı (e-Defter)
    ↓
8. Monthly berat (scheduled)
Kural: Core event → compliance plugin handler.

11.4. Compliance Marketplace
Compliance paketleri marketplace'te:

Paket	Fiyat
TR Compliance	500 TL/ay
EU Compliance	1000 TL/ay
US Compliance	1000 TL/ay
Healthcare (HIPAA)	2000 TL/ay
Financial (PCI-DSS)	1500 TL/ay
Global Multi-Country	5000 TL/ay
Kural: Tenant ihtiyacı olanı seçer.

12. Restoran / Klinik Örneği
12.1. Restoran — TR Compliance
Zorunlu:

e-Fatura (sipariş > 5K TL)

e-Arşiv (bireysel müşteri)

KDV (%1, %8, %10, %20)

KVKK (müşteri verisi)

Yazar kasa (POS)

Plugin config:

yaml
compliance:
  tr:
    e_fatura: true
    e_arsiv: true
    kdv: true
    kvkk: true
    
    einvoice_threshold: 5000            # 5K TL üzeri e-fatura
    einvoice_integrator: "uyumsoft"
    
    kdv_rates:
      food: 10
      beverage: 20
      alcohol: 20
Akış:

text
1. Waiter sipariş alır
2. Order created
3. Payment received → invoice created
4. TR Compliance Plugin:
    ├── Invoice > 5000 TL → e-Fatura
    ├── Invoice < 5000 TL + bireysel → e-Arşiv
    └── KDV hesapla (ürün kategorisine göre)
5. GİB'e gönder (uyumsoft)
6. Yazar kasa fişi
7. Müşteri verisi → KVKK consent
12.2. Klinik — TR + HIPAA
Zorunlu:

e-Fatura (hasta)

KVKK (sağlık verisi — özel kategori)

HIPAA (ABD hastası varsa)

SGK (sağlık sigortası)

Plugin config:

yaml
compliance:
  tr:
    e_fatura: true
    kvkk:
      special_category: true            # sağlık verisi
      explicit_consent: true            # açık rıza
      dpo_required: true
    
    sgk:
      enabled: true
  
  us:
    hipaa:
      enabled: true                     # ABD hastası varsa
      phi_encryption: true
      baa_required: true
Kural: Sağlık verisi çift koruma (KVKK + HIPAA).

12.3. Multi-Country Restoran Zinciri
Senaryo: KFC tarzı, 5 ülkede.

Compliance:

yaml
compliance:
  tr:
    e_fatura: true
    e_arsiv: true
    kvkk: true
    kdv: true
  
  eu:
    gdpr: true
    vat: true
    peppol: true
  
  us:
    sales_tax: true                     # state-by-state
    hipaa: false
  
  uk:
    uk_gdpr: true
    mtd: true
Runtime: Her lokasyon kendi ülkesinin compliance plugin'ini kullanır.

13. Multi-Country Tax Engine
13.1. Tax Engine Nedir?
Her ülke/sektör farklı vergi:

Ülke	Vergi
TR	KDV (%1-20), ÖTV, stopaj
EU	VAT (%0-27), reverse charge
US	Sales tax (state + city)
UK	VAT (%20), MTD
BR	ICMS, ISS, IPI
IN	GST (%5-28)
13.2. Tax Engine Yapısı
php
class MultiCountryTaxEngine
{
    public function calculate(Invoice $invoice): array
    {
        // 1. Ülke belirle
        $country = $invoice->country ?? $invoice->tenant->country;
        
        // 2. Plugin yükle
        $plugin = app(ComplianceRegistry::class)->getTaxPlugin($country);
        
        // 3. Hesapla
        return $plugin->calculate($invoice);
    }
}
13.3. TR Tax Plugin
php
class TrTaxPlugin implements TaxPlugin
{
    public function calculate(Invoice $invoice): array
    {
        $taxes = [];
        
        // 1. KDV
        foreach ($invoice->lines as $line) {
            $rate = $this->getVatRate($line->product);
            $amount = $line->total * $rate / 100;
            $taxes[] = [
                'type' => 'kdv',
                'rate' => $rate,
                'base' => $line->total,
                'amount' => $amount,
                'account' => '391',
            ];
        }
        
        // 2. Stopaj (hizmet ise)
        if ($invoice->type === 'service' && $invoice->supplier->is_individual) {
            $rate = 20;                     // %20 stopaj
            $amount = $invoice->total * $rate / 100;
            $taxes[] = [
                'type' => 'stopaj',
                'rate' => $rate,
                'base' => $invoice->total,
                'amount' => $amount,
                'account' => '360',
            ];
        }
        
        // 3. ÖTV (bazı ürünler)
        if ($this->isOtvSubject($invoice)) {
            // ...
        }
        
        return $taxes;
    }
}
13.4. EU VAT Plugin
php
class EuVatPlugin implements TaxPlugin
{
    public function calculate(Invoice $invoice): array
    {
        // 1. Reverse charge (B2B, farklı ülke)
        if ($this->isReverseCharge($invoice)) {
            return [
                [
                    'type' => 'vat',
                    'rate' => 0,
                    'note' => 'Reverse charge',
                ],
            ];
        }
        
        // 2. Standart VAT
        $rate = $this->getVatRate($invoice->country, $invoice->lines);
        // ...
    }
}
13.5. US Sales Tax Plugin
php
class UsSalesTaxPlugin implements TaxPlugin
{
    public function calculate(Invoice $invoice): array
    {
        // 1. State tax
        $stateRate = $this->getStateRate($invoice->state);
        
        // 2. County tax
        $countyRate = $this->getCountyRate($invoice->county);
        
        // 3. City tax
        $cityRate = $this->getCityRate($invoice->city);
        
        // 4. Nexus check
        if (!$this->hasNexus($invoice->tenant, $invoice->state)) {
            return [];                      // Vergi yok
        }
        
        $totalRate = $stateRate + $countyRate + $cityRate;
        // ...
    }
}
Kural: Her ülke kendi plugin.

14. Anti-Patterns
Bu projede yasak olan compliance hataları:

Hardcoded tax rate — Config'de

Single-country assumption — Multi-country default

PII in logs — Mask zorunlu

Unencrypted PII — KMS zorunlu

No retention policy — Her entity

No audit trail — Her işlem

Mutable audit log — WORM

Retention bypass — Yasal saklama korunur

Pseudonymization yanlış — Gerçekten kişisel veri maskeli

Compliance plugin core'a gömülü — Plugin mimarisi

Ülke sertifikasyonu yok — Market gerekliliği

Data residency ihlali — Tenant ülkesinde

GDPR/KVKK ihlali — Her işlem audit

Fake certification — Gerçek denetim

Compliance docs eksik — Her plugin README

15. Roadmap
Aşama	Süre	Kapsam
1. Foundation	4 hafta	Audit trail, WORM, hash chain
2. TR Base	6 hafta	e-Fatura, KDV, TDHP
3. TR Advanced	6 hafta	e-Defter, e-İrsaliye, KVKK
4. EU Base	6 hafta	GDPR, VAT, PEPPOL
5. Multi-Country	8 hafta	US, UK, BR, IN
6. Tax Engine	6 hafta	Multi-country tax
7. Parallel Ledger	8 hafta	IFRS, GAAP, TDHP
8. Certifications	12 hafta	ISO 27001, SOC 2, ISO 27701
Toplam: 56 hafta (~13 ay).

16. Glossary
Terim	Anlam
Compliance	Yasal uyum
PII	Personally Identifiable Information
GDPR	EU veri koruma
KVKK	TR veri koruma
HIPAA	US sağlık verisi
SOX	US finansal denetim
e-Fatura	TR elektronik fatura
e-Defter	TR elektronik defter
UBL-TR	TR fatura XML formatı
XBRL-GL	Defter XML formatı
TDHP	TR hesap planı
IFRS	Uluslararası muhasebe
GAAP	US muhasebe
PEPPOL	EU e-fatura
eIDAS	EU dijital imza
WORM	Write Once Read Many
Audit Trail	Denetim izi
Retention	Saklama süresi
Pseudonymization	Takma ad
Anonymization	Anonimleştirme
Data Residency	Veri ikametgahı
DPIA	Data Protection Impact Assessment
ROPA	Records of Processing Activities
DPO	Data Protection Officer
BAA	Business Associate Agreement
PCI-DSS	Ödeme kartı standardı
ISO 27001	Bilgi güvenliği
SOC 2	Servis güven
17. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — compliance layer, TR/EU/US, sertifikasyon