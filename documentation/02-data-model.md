# 02 — Data Model & Core Entities

**Project:** Kurumsal ERP Platform
**Belge:** Veri Modeli, Çekirdek Varlıklar, Meta-Platform Genişlemesi
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Living Document — mevcut şema + genişleme
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **veri modelini** tanımlar:

- Mevcut tablolar (bugün çalışan sistem)
- Tablo ilişkileri (ER diyagramı)
- Yeni meta-platform tabloları (henüz yok ama gerekli)
- Naming convention, UUID, ortak kolonlar
- Migration stratejisi

**Hedef kitle:** Geliştiriciler, DB yöneticileri, AI asistanlar, veri analistleri.

---

## 2. Naming Convention

### 2.1. Tablo İsimleri

| Kural | Örnek |
|---|---|
| **Prefix = modül kodu** | `fin_*`, `inv_*`, `otc_*`, `prc_*`, `hrm_*`, `mfg_*`, `ppm_*`, `eam_*`, `crm_*`, `qm_*`, `log_*`, `dms_*`, `sys_*` |
| **Snake_case** | `fin_ar_invoice`, `inv_stock_movement` |
| **Tekil isim** | `fin_customer` (çoğul değil) |
| **Framework tabloları istisna** | `users`, `sessions`, `migrations`, `cache` |

### 2.2. Kolon İsimleri

| Kural | Örnek |
|---|---|
| **Snake_case** | `sales_price`, `grand_total` |
| **Kısaltma yok** | `customer_id` (cq_id değil) |
| **Para birimi için suffix** | `amount`, `price`, `grand_total` (birim implicit, `currency` ayrı kolon) |
| **Tarih için suffix** | `_at` (timestamp), `_date` (date) |
| **Boolean için prefix** | `is_active`, `has_children`, `can_edit` |
| **Foreign key** | `<tablo_tekil>_id` → `customer_id` = `fin_customer.id` |

### 2.3. Index İsimleri

| Kural | Örnek |
|---|---|
| **`idx_<tablo>_<kolonlar>`** | `idx_fin_customer_tenant`, `idx_otc_sales_order_tenant_date` |
| **Unique** | `uniq_<tablo>_<kolonlar>` |

---

## 3. UUID Stratejisi

### 3.1. Neden UUID?

| Seçenek | Avantaj | Dezavantaj |
|---|---|---|
| **Auto-increment (int)** | Hızlı, küçük | Tahmin edilebilir, dağıtık sistemde çakışır |
| **UUID v4 (random)** | Dağıtık, tahmin edilemez | Büyük (36 byte), index yavaş |
| **UUID v7 (time-ordered)** | Dağıtık, sıralı, tahmin edilemez | PHP/Laravel'de native değil |
| **ULID** | Sıralı, kısa (26 byte) | PHP kütüphanesi gerekir |

**Bizim seçim:** **UUID v4** (Laravel `HasUuids` trait ile). İleride **UUID v7**'ye geçiş planlı (PostgreSQL 17+ native destek).

### 3.2. Hangi Tablolar UUID Kullanır?

**Tenant-scoped tüm tablolar:** UUID primary key + `tenant_id uuid`.

**Framework tabloları:** `users`, `tenants` (bigint id — Laravel auth uyumu için).

**Not:** `users.id` bigint (Laravel standart), `users.tenant_id` uuid (tenant referansı). Bu karma, ama Laravel auth ile uyumlu.

### 3.3. UUID Üretimi

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FinCustomer extends Model {
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
}
Kural: Yeni model = HasUuids. Str::uuid() sadece manuel insert'te.

4. Ortak Kolonlar (Standard Columns)
Her tenant-scoped tablo zorunlu olarak şu kolonları içerir:

sql
id           uuid         PRIMARY KEY
tenant_id    uuid         NOT NULL REFERENCES tenants(id) ON DELETE CASCADE
created_at   timestamptz  NOT NULL DEFAULT now()
updated_at   timestamptz  NOT NULL DEFAULT now()
4.1. Opsiyonel Ortak Kolonlar
Kolon	Ne zaman	Amaç
deleted_at timestamptz	Soft delete gerektiğinde	Laravel SoftDeletes
created_by bigint	Audit gerektiğinde	Hangi kullanıcı oluşturdu
updated_by bigint	Audit gerektiğinde	Hangi kullanıcı güncelledi
status varchar(20)	İş akışı gerektiğinde	active / inactive / archived
sort_order int	Sıralama gerektiğinde	Custom listeleme
metadata jsonb	Esnek alan gerektiğinde	Ad-hoc veri
4.2. Standart Pattern
php
Schema::create('fin_customer', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    // ... iş kolonları
    
    $table->timestampsTz();
    $table->softDeletesTz();
    
    $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
    $table->index('tenant_id');
});
5. Modül Modül Tablo Listesi (Mevcut)
Aşağıda bugün çalışan sistemin tabloları. Her modülün prefix'i ve kısa görevi.

5.1. Platform Core (sys_*, tenants, users)
Tablo	Görev
tenants	Müşteri organizasyonları (bigint id)
users	Kullanıcı hesapları (bigint id, tenant_id uuid)
sys_role	Rol tanımları (tenant-scoped)
sys_role_module	Rol → modül erişim haritası
sys_feature_flag	Feature flag tanımları
sys_tenant_feature	Tenant bazlı feature override
sys_feature_default	Global feature default (superadmin)
sys_notification	In-app bildirimler
sys_notification_setting	Bildirim kanal ayarları
sys_audit_log	İşlem geçmişi
sys_import_log	CSV import geçmişi
sys_user_nav_item	Kullanıcı favori/son kullanılan
sys_custom_field	Custom field tanımı
sys_custom_field_value	Custom field değeri (polymorphic)
sys_custom_object	Custom object (kullanıcı tablosu) tanımı
sys_custom_object_record	Custom object kayıtları
sys_tenant_setting	Tenant bazlı key-value ayarlar
sys_company_profile	Şirket profili (logo, adres, kiosk_mode)
sys_document	Doküman metadata
sys_saas_plan	SaaS abonelik planları
sys_tenant_subscription	Tenant abonelikleri
sys_plugin	Plugin metadata
sys_module	Core modül kayıtları
sys_approval_request	Onay talepleri
sys_approval_workflow	Onay akışları
sessions	Laravel session
cache, cache_locks	Laravel cache
failed_jobs, job_batches, jobs	Laravel queue
migrations	Laravel migrations
5.2. Finans (fin_*)
Tablo	Görev
fin_account	Hesap planı (COA)
fin_journal_entry	Yevmiye kaydı (başlık)
fin_journal_line	Yevmiye satırı (borç/alacak)
fin_customer	Müşteriler
fin_supplier	Tedarikçiler
fin_ar_invoice	Müşteri faturası
fin_ar_invoice_line	Müşteri fatura satırı
fin_ar_payment	Müşteri tahsilatı
fin_ap_invoice	Tedarikçi faturası
fin_ap_invoice_line	Tedarikçi fatura satırı
fin_ap_payment	Tedarikçi ödemesi
fin_bank_account	Banka/kasa hesabı
fin_bank_transaction	Banka hareketi
fin_bank_statement	Banka ekstresi
fin_reconciliation	Banka mutabakatı
fin_currency	Para birimleri
fin_exchange_rate	Döviz kurları
fin_cost_center	Masraf merkezleri
fin_accounting_period	Dönem kapanışı
fin_fixed_asset	Duran varlıklar
fin_depreciation	Amortisman
fin_budget	Bütçe planları
fin_budget_line	Bütçe satırı
fin_sale_expense	Satış masrafı (sərfiyyat)
fin_sale_expense_default	Varsayılan satış masrafları
fin_tax_regime	Vergi rejimleri
fin_price_list	Fiyat listesi (INV ile paylaşılır)
5.3. Envanter (inv_*)
Tablo	Görev
inv_product	Ürünler
inv_product_barcode	Ürün barkodları (çoklu)
inv_warehouse	Depolar
inv_stock_movement	Stok hareketleri
inv_stock_layer	FIFO katmanları
inv_stock_balance	Anlık stok (materialized)
inv_stock_transfer	Depo transferleri
inv_price_list	Fiyat listeleri
inv_price_list_item	Fiyat listesi kalemleri
5.4. Satın Alma (prc_*)
Tablo	Görev
prc_purchase_order	Satın alma siparişi
prc_purchase_order_line	PO satırı
prc_goods_receipt	Mal kabul
prc_goods_receipt_line	Mal kabul satırı
prc_three_way_match	PO ↔ GR ↔ Fatura eşleştirme
5.5. Satış (otc_*)
Tablo	Görev
otc_sales_order	Satış siparişi
otc_sales_order_line	SO satırı
otc_quote	Teklif
otc_quote_line	Teklif satırı
5.6. İnsan Kaynakları (hrm_*)
Tablo	Görev
hrm_employee	Çalışanlar
hrm_department	Departmanlar
hrm_attendance	Puantaj/izin
hrm_payroll	Bordro
hrm_payroll_line	Bordro satırı
hrm_expense_claim	Masraf talebi
hrm_expense_claim_line	Masraf satırı
5.7. Üretim (mfg_*)
Tablo	Görev
mfg_bom	Reçete (BOM)
mfg_bom_line	Reçete satırı
mfg_production_order	Üretim emri
mfg_production_order_line	Üretim satırı
mfg_mrp_run	MRP çalıştırma
5.8. Projeler (ppm_*)
Tablo	Görev
ppm_project	Projeler
ppm_task	Proje görevleri
ppm_timesheet	Zaman takibi
5.9. Varlık Yönetimi (eam_*)
Tablo	Görev
eam_asset	Varlıklar
eam_maintenance_order	Bakım emirleri
5.10. CRM (crm_*)
Tablo	Görev
crm_lead	Potansiyel müşteri
crm_opportunity	Satış fırsatı
crm_activity	CRM aktivitesi
5.11. Kalite Yönetimi (qm_*)
Tablo	Görev
qm_inspection_plan	Muayene planı
qm_inspection	Muayene
qm_ncr	Uygunsuzluk (NCR)
5.12. Lojistik (log_*)
Tablo	Görev
log_carrier	Kargo firmaları
log_shipment	Sevkiyatlar
log_shipment_line	Sevkiyat satırı
5.13. Doküman Yönetimi (dms_*)
Tablo	Görev
dms_document	Doküman metadata
dms_folder	Doküman klasörleri
5.14. Framework / Sistem
Tablo	Görev
tenants	Multi-tenancy kök
users	Kullanıcılar
sessions	Session
cache, cache_locks	Cache
jobs, job_batches, failed_jobs	Queue
migrations	Migrations
Toplam: ~85 tablo (framework dahil).

6. Meta-Platform Genişlemesi (Yeni Tablolar)
Belge 00'daki vizyonu gerçekleştirmek için yeni tablo grupları gerekir. Aşağıdaki tablolar henüz yok, tasarım aşamasında.

6.1. Platform Structure Framework (psf_*)
Tablo	Görev
psf_structure	Platform yapısı tanımı (meta)
psf_structure_version	Yapı versiyonu (immutable)
psf_layer	Katman tanımı
psf_role	Yapıya özel rol tanımı
psf_permission	İzin tanımı
psf_data_scope	Veri görünürlük kuralı
psf_workflow	İş akışı tanımı
psf_workflow_node	İş akışı düğümü
psf_workflow_transition	İş akışı geçişi
psf_ui_layout	Ekran düzeni tanımı
6.2. Metadata Core (meta_*)
Tablo	Görev
meta_entity	Kullanıcı tanımlı entity (tablo)
meta_field	Entity alanı (kolon)
meta_relation	Entity ilişkisi
meta_validation	Doğrulama kuralı
meta_index	Dinamik index tanımı
meta_view	Dinamik view tanımı
6.3. Custom Entity Data
Tablo	Görev
custom_entity_data	Kullanıcı tanımlı entity verisi (JSONB)
custom_entity_data_index	Aranabilir index (typed)
6.4. Event Bus (event_*)
Tablo	Görev
event_log	Yayınlanan event'ler (audit)
event_subscription	Event dinleyicileri
event_outbox	Outbox pattern (güvenli yayın)
event_dead_letter	Başarısız event'ler
6.5. Plugin System (plugin_*)
Tablo	Görev
plugin	Yüklü plugin'ler
plugin_version	Versiyon geçmişi
plugin_dependency	Bağımlılıklar
plugin_installation	Hangi tenant'ta hangi versiyon
plugin_setting	Plugin ayarları
plugin_permission	Plugin izinleri
plugin_event_subscription	Plugin'in dinlediği event'ler
6.6. Developer & Marketplace (dev_*, mkt_*)
Tablo	Görev
dev_developer	Bağımsız developer hesabı
dev_verification	KYC/vergi doğrulama
dev_payout	Developer ödeme geçmişi
mkt_app	Marketplace uygulaması
mkt_app_version	App versiyonu
mkt_app_pricing	Fiyatlandırma
mkt_app_installation	Tenant kurulumu
mkt_app_review	Yorum/puan
mkt_app_category	Kategori
mkt_transaction	Satış işlemi
mkt_commission	Platform komisyonu
6.7. Workflow Engine (wf_*)
Tablo	Görev
wf_definition	Workflow tanımı
wf_instance	Çalışan workflow instance
wf_node_instance	Node durumu
wf_history	Geçmiş
wf_task	Kullanıcı görevi
6.8. Report Builder (rpt_*)
Tablo	Görev
rpt_report	Rapor tanımı
rpt_report_version	Versiyon
rpt_dataset	Veri kaynağı
rpt_field	Rapor alanı
rpt_filter	Filtre
rpt_schedule	Zamanlanmış rapor
rpt_execution	Çalıştırma geçmişi
6.9. UI Builder (ui_*)
Tablo	Görev
ui_screen	Ekran tanımı
ui_component	Bileşen tanımı
ui_layout	Layout tanımı
ui_theme	Tema
6.10. Integration Hub (intg_*)
Tablo	Görev
intg_connector	Entegrasyon tanımı
intg_connector_version	Versiyon
intg_connection	Tenant bağlantısı
intg_sync_log	Senkronizasyon geçmişi
intg_webhook	Outbound webhook
intg_webhook_delivery	Webhook gönderim geçmişi
6.11. Compliance (cmp_*)
Tablo	Görev
cmp_standard	Standart tanımı (IFRS, GAAP)
cmp_rule	Kural
cmp_ledger_config	Defter konfigürasyonu
cmp_audit_trail	Denetim izi (SOX)
6.12. AI Layer (ai_*)
Tablo	Görev
ai_model	Model tanımı
ai_prediction	Tahmin geçmişi
ai_embedding	Vektör embedding
ai_conversation	AI sohbet geçmişi
6.13. Multi-Tenancy Genişleme
Belge 01'de tanımlanan yeni tablolar:

Tablo	Görev
sys_user_membership	Kullanıcı ↔ tenant üyeliği (multi-tenant)
sys_tenant_delegation	Tenant'lar arası geçici erişim
tenants (genişleme)	parent_tenant_id, depth, path, tenant_type
Toplam yeni tablo: ~70 tablo.

Büyük resim: Mevcut ~85 + yeni ~70 = ~155 tablo hedef.

7. İlişki (Relationship) Örnekleri
7.1. Müşteri → Fatura → Satır
text
fin_customer (1) ─── (N) fin_ar_invoice (1) ─── (N) fin_ar_invoice_line
                                          │
                                          ├─ (N) fin_ar_payment
                                          └─ (N) fin_journal_entry
Kurallar:

Bir müşteri birden fazla fatura

Bir fatura birden fazla satır

Bir fatura birden fazla ödeme (taksit)

Bir fatura → bir yevmiye (posting)

7.2. Ürün → Stok → Hareket
text
inv_product (1) ─── (N) inv_stock_layer (FIFO)
                (1) ─── (N) inv_stock_movement
                (1) ─── (N) inv_product_barcode
                (N) ─── (1) fin_tax_regime
Kurallar:

Bir ürün birden fazla FIFO katmanı (her alım ayrı)

Bir ürün birden fazla barkod (tedarikçi değişince)

Stok hareketi → FIFO katmanı (in/out)

7.3. Satış Siparişi → Fatura
text
otc_sales_order (1) ─── (N) otc_sales_order_line
                (1) ─── (0..1) fin_ar_invoice  (dönüşüm)
Kurallar:

Sipariş onaylanınca faturaya dönüşür

Tek sipariş = tek fatura (genelde)

Kısmi dönüşüm ileride (Belge 10)

7.4. Platform Structure → Tenant
text
psf_structure (1) ─── (N) psf_structure_version
                (1) ─── (N) sys_tenant (hangi yapıyı kullanır)
                        (1) ─── (N) psf_layer
                        (1) ─── (N) psf_role
                        (1) ─── (N) psf_workflow
Kurallar:

Bir yapının birden fazla versiyonu (v1, v2)

Bir tenant bir yapıyı kullanır (dondurulmuş versiyon)

Yapı değişirse tenant yeni versiyona geçebilir (kontrollü)

8. Migration Stratejisi
8.1. İsimlendirme
text
YYYY_MM_DD_HHMMSS_<action>_<tablo>_<detail>.php
Örnek:

2026_11_06_120000_create_fin_customer_table.php

2026_11_06_120100_add_kiosk_mode_to_company_profile.php

2026_11_06_120200_create_psf_structure_table.php

8.2. Idempotent Migration
Tüm migration'lar tekrar çalıştırılabilir olmalı:

php
public function up(): void {
    if (Schema::hasTable('fin_customer')) return;
    
    Schema::create('fin_customer', function (Blueprint $table) {
        // ...
    });
}
Neden: Production'da beklenmedik durumlar için güvenli.

8.3. Rollback
Her migration'ın down() metodu ters işlemi yapmalı:

php
public function down(): void {
    Schema::dropIfExists('fin_customer');
}
Kural: up() çalıştıysa down() de çalışmalı. Sıra: en son migration'dan geriye.

8.4. Uzun Süren Migration
Büyük tablolar için:

php
// Index — concurrent (downtime yok)
DB::statement('CREATE INDEX CONCURRENTLY idx_fin_customer_tenant ON fin_customer(tenant_id)');

// Kolon ekleme — nullable, default yok (hızlı)
Schema::table('fin_customer', fn($t) => $t->string('new_col')->nullable());

// Backfill — ayrı job (Belge 19)
Kural: Milyonlarca satır varsa CONCURRENTLY + backfill job.

8.5. Veri Migration
Schema değişikliği + veri dönüşümü ayrı migration:

php
// 1. Schema (hızlı)
// 2. Data backfill (job, scheduled)

public function up(): void {
    Schema::table('fin_customer', function (Blueprint $table) {
        $table->string('new_status', 20)->nullable();
    });
}

// Ayrı artisan komutu: app/Console/Commands/BackfillCustomerStatus.php
Neden: Büyük migration'lar transaction lock'u tutar → downtime. Ayrı job ile parça parça.

9. Model İlişkileri (Eloquent)
9.1. Standart Pattern
php
class FinCustomer extends Model {
    use HasUuids, SoftDeletes;
    
    public function invoices(): HasMany {
        return $this->hasMany(FinArInvoice::class, 'customer_id');
    }
    
    public function payments(): HasManyThrough {
        return $this->hasManyThrough(FinArPayment::class, FinArInvoice::class, 'customer_id', 'invoice_id');
    }
}
9.2. Cross-Module İlişki
php
class OtcSalesOrder extends Model {
    public function customer(): BelongsTo {
        return $this->belongsTo(FinCustomer::class, 'customer_id');
    }
    
    public function invoice(): HasOne {
        return $this->hasOne(FinArInvoice::class, 'sales_order_id');
    }
}
Kural: Modüller arası ilişki izole — sadece FK, ters bağımlılık yok.

9.3. Polymorphic İlişki
Custom field value için:

php
class SysCustomFieldValue extends Model {
    public function field(): BelongsTo {
        return $this->belongsTo(SysCustomField::class);
    }
    
    public function entity(): MorphTo {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }
}
Kullanım: sys_custom_field_value tablosunda entity_type + entity_id → hangi modele ait.

10. Query Performansı
10.1. Eager Loading (ZORUNLU)
php
// ✅ Doğru
$invoices = FinArInvoice::with('customer', 'lines.product')->get();

// ❌ Yanlış (N+1)
$invoices = FinArInvoice::all();
foreach ($invoices as $inv) {
    echo $inv->customer->name; // her seferinde 1 sorgu
}
Kural: with() kullanmayan ilişki sorgusu code review'da reddedilir.

10.2. Index Öncelikleri
Tablo Tipi	Zorunlu Index
Tenant-scoped tüm tablo	tenant_id
Tablo + tarih	(tenant_id, created_at DESC)
Tablo + status	(tenant_id, status)
FK ilişki	FK kolonu
Arama (kod, isim)	(tenant_id, code) veya (tenant_id, name)
10.3. Cursor Pagination
Büyük tablolarda:

php
// ✅ Cursor (hızlı)
$items = Model::where('tenant_id', $tid)->orderBy('id')->cursorPaginate(50);

// ⚠️ Offset (yavaş, milyonlarda)
$items = Model::where('tenant_id', $tid)->paginate(50);
Kural: 100K+ satırlı tablo = cursor.

10.4. Materialized View
Raporlar için:

sql
CREATE MATERIALIZED VIEW fin_ar_summary AS
SELECT tenant_id, customer_id, SUM(grand_total) AS total, COUNT(*) AS count
FROM fin_ar_invoice
WHERE status = 'posted'
GROUP BY tenant_id, customer_id;

CREATE INDEX ON fin_ar_summary(tenant_id);
Yenileme: Cron job (Belge 19).

Uyarı: Materialized view RLS'yi bypass eder → dikkat!

11. Veri Saklama & Arşivleme
11.1. Retention Policy
Tablo	Saklama	Arşiv
sys_audit_log	7 yıl (yasal)	5 yıl sonra soğuk storage
sys_notification	90 gün	Sonra silinir
sys_import_log	1 yıl	Sonra silinir
event_log	1 yıl	Sonra silinir
fin_journal_entry	Süresiz	—
fin_ar_invoice	10 yıl (yasal)	Arşiv view
sessions	30 gün	Sonra silinir
11.2. Soft Delete vs Hard Delete
Tablo	Yaklaşım
Kullanıcı verisi (müşteri, ürün)	Soft delete (deleted_at)
Finansal kayıt (fatura, yevmiye)	Hard delete yok — iptal (status='cancelled')
Log (audit, notification)	Hard delete (retention sonrası)
Yapı (structure)	Soft delete
Kural: Finansal kayıt asla hard delete. İptal + iade ile dönüşüm.

12. Migration Roadmap (Meta-Platform)
Aşama	Yeni Tablolar
1-2 ay	sys_user_membership, sys_tenant_delegation, tenants genişleme
3-4 ay	psf_* grubu (structure, layer, role)
5-6 ay	meta_* grubu (entity, field, relation)
7-8 ay	event_* grubu (log, subscription, outbox)
9-12 ay	plugin_*, mkt_*, dev_* (plugin + marketplace)
13-18 ay	wf_*, rpt_*, ui_* (workflow, report, UI)
19-24 ay	intg_*, cmp_*, ai_* (integration, compliance, AI)
Kural: Hiçbir yeni tablo mevcut tabloları bozmaz. Sadece eklenir.

13. Anti-Patterns
Bu projede yasak olan veri modeli hataları:

tenant_id olmayan tenant-scoped tablo — RLS çalışmaz

UUID yerine auto-increment (tenant-scoped tablo için) — dağıtık sistem uyumsuz

EAV (Entity-Attribute-Value) saf pattern — performans kabusu (metadata-driven'ı bununla karıştırma)

Cascade delete zinciri — veri kaybı riski

Finansal kayıt hard delete — yasal ihlal

N+1 query — with() zorunlu

Polymorphic FK'sız — entity_type + entity_id index zorunlu

JSONB içinde aranabilir veri (index'siz) — GIN index zorunlu

SELECT * — sadece gerekli kolonlar

Raw SQL (RLS statement hariç) — Eloquent/Query Builder

14. Glossary
Terim	Anlam
Tenant-scoped	Her tenant için ayrı veri
Platform Core	Tenant'sız framework tablosu
UUID	128-bit benzersiz tanımlayıcı
Soft Delete	deleted_at ile işaretleme
Hard Delete	Gerçek silme
Cascade	Ana kayıt silinince alt kayıtlar silinir
FK	Foreign Key
N+1	Sorgu sayısı problemi
Eager Loading	with() ile ilişki önceden yükle
Polymorphic	Çoklu modele bağlanabilen ilişki
Materialized View	Fiziksel olarak saklanan view
Metadata	Veri tanımı (tablo, alan)
15. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — mevcut şema + meta-platform genişleme