# 12 — Report Builder

**Project:** Kurumsal ERP Platform
**Belge:** Görsel Rapor Tasarımcısı, Dinamik Sorgu, Dağıtım
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md, 11-workflow-engine.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **Report Builder** sistemini tanımlar — kullanıcının **kod yazmadan görsel raporlar** tasarlayabildiği, çalıştırabildiği ve paylaşabildiği motor. İçerir:

- Neden görsel rapor motoru
- Rapor türleri (tablo, grafik, KPI, pivot, harita)
- Veri kaynakları (entity, SQL-safe, computed, API)
- Rapor tanımlama dili (YAML)
- Görsel builder (drag-drop)
- Parametreler, filtreler, gruplama
- Chart tipleri (12+)
- Formüller ve hesaplamalar
- Zamanlanmış raporlar
- Dağıtım (email, PDF, CSV, webhook, dashboard)
- İzinler ve data scope
- Runtime execution
- Performans (cache, materialized view)
- Restoran örneği
- Anti-patterns, roadmap

**Hedef kitle:** Geliştiriciler, AI asistanlar, ürün mimarları, business analistler, tenant admin'ler.

**Kritik:** Report Builder, **SAP'in en zayıf noktası** — SAP'de rapor = Crystal Reports lisansı + danışman. Bizde = tıkla + sürükle.

---

## 2. Neden Report Builder?

### 2.1. Klasik ERP'nin Sınırlaması

| ERP | Rapor Yaklaşımı | Sorun |
|---|---|---|
| **SAP** | Crystal Reports / Web Intelligence | Lisans: $5K+/kullanıcı, danışman gerek |
| **Odoo** | Studio + hardcoded | Basit, özelleştirme zor |
| **Logo** | Crystal Reports | Sabit raporlar |
| **NetSuite** | SuiteAnalytics | Orta, esnek değil |
| **Acumatica** | Generic Inquiry | Orta, UI karmaşık |

**Ortak sorun:** Her yeni rapor = **geliştirici + zaman + para**.

### 2.2. Gerçek Dünya İhtiyacı

| İhtiyaç | Frekans |
|---|---|
| Günlük satış raporu | Her gün |
| Aylık KDV raporu | Aylık |
| Müşteri yaşlandırma | Haftalık |
| Stok değerleme | Aylık |
| Personel performans | Aylık |
| Özel sektör raporu | Değişken |
| Ad-hoc analiz | Anlık |

**Kural:** Tenant admin **kendi raporunu** yapabilmeli. Geliştiriciye muhtaç olmamalı.

### 2.3. Bizim Çözüm

**Görsel Report Builder** — SAP'nin 6 ayda yaptığını 6 dakikada.

---

## 3. Rapor Türleri

### 3.1. Tablo Raporu (Table)

En temel. Satır-sütun veri.

```yaml
report:
  type: "table"
  columns: ["number", "date", "customer.name", "total", "status"]
Kullanım: Liste, döküm, export.

3.2. Grafik Raporu (Chart)
Görsel. Bar, line, pie, area, vs.

yaml
report:
  type: "chart"
  chart_type: "bar"
  x_axis: "month"
  y_axis: "total"
3.3. KPI Raporu (KPI Card)
Tek metrik, büyük gösterim.

yaml
report:
  type: "kpi"
  value: "SUM(total)"
  comparison: "vs_last_month"
  format: "currency"
Kullanım: Dashboard kartları.

3.4. Pivot Tablo (Cross-Tab)
İki boyutlu özet.

yaml
report:
  type: "pivot"
  rows: ["customer", "product.category"]
  columns: ["month"]
  values: { "total": "SUM" }
3.5. Dashboard Raporu
Birden fazla widget bir arada.

yaml
report:
  type: "dashboard"
  widgets:
    - { key: "w1", type: "kpi", query: "..." }
    - { key: "w2", type: "chart", query: "..." }
    - { key: "w3", type: "table", query: "..." }
3.6. Detay Raporu (Detail)
Bir entity'nin detay dökümü (fatura, sipariş).

yaml
report:
  type: "detail"
  entity: "fin_ar_invoice"
  record_id_param: "invoice_id"
  sections:
    - header: "invoice"
    - lines: "invoice_lines"
    - totals: "calculated"
3.7. Harita Raporu (Map)
Coğrafi gösterim.

yaml
report:
  type: "map"
  latitude: "customer.lat"
  longitude: "customer.lng"
  value: "total"
3.8. Funnel / Gauge / Heatmap
Özel görselleştirmeler.

Tip	Kullanım
Funnel	Satış hunisi
Gauge	Hedef takip
Heatmap	Yoğunluk analizi
Sankey	Akış diyagramı
Tree	Hiyerarşi
4. Veri Kaynakları
4.1. Entity (Güvenli)
Platform veya custom entity.

yaml
data_source:
  type: "entity"
  entity: "fin_ar_invoice"
  alias: "inv"
Avantaj:

✅ RLS otomatik

✅ Data scope otomatik

✅ İlişki yönetimi

✅ Type-safe

4.2. Query (SQL-Safe)
Sadece SELECT — INSERT/UPDATE/DELETE yasak. Sandbox'ta çalışır.

yaml
data_source:
  type: "query"
  sql: |
    SELECT c.name, SUM(i.total) AS revenue
    FROM fin_customer c
    JOIN fin_ar_invoice i ON i.customer_id = c.id
    WHERE i.date >= :start_date
    GROUP BY c.id
Kısıtlar:

❌ DROP, ALTER, TRUNCATE

❌ INSERT, UPDATE, DELETE

❌ pg_* sistem fonksiyonları

❌ dblink, postgres_fdw

✅ RLS otomatik uygulanır (tenant_id filtresi)

✅ Whitelist tablo

4.3. Computed (Hesaplanmış)
Birden fazla kaynaktan birleşim.

yaml
data_source:
  type: "computed"
  base: "otc_sales_order"
  joins:
    - entity: "fin_customer"
      on: "customer_id"
      select: ["name", "region"]
    - entity: "inv_product"
      via: "order_lines"
      select: ["category"]
  computed_fields:
    - key: "margin"
      expression: "(total - cost) / total * 100"
4.4. API
Dış sistemden veri.

yaml
data_source:
  type: "api"
  endpoint: "https://api.example.com/data"
  auth: "api_key"
  method: "GET"
  cache: 300
Kısıt: Sadece izinli domain (whitelist).

4.5. Event Stream
Event store'dan analiz.

yaml
data_source:
  type: "events"
  filter:
    event_type: "otc.sales_order.created"
    date_range: "last_30_days"
Kullanım: Funnel, anomali, aktivite analizi.

4.6. Hybrid
Entity + SQL + computed karışımı.

yaml
data_source:
  type: "hybrid"
  base: "entity"
  entity: "fin_customer"
  custom_columns:
    - key: "lifetime_value"
      sql: "(SELECT SUM(total) FROM fin_ar_invoice WHERE customer_id = fin_customer.id)"
5. Rapor Tanımlama Dili (YAML)
5.1. Tam Yapı
yaml
report:
  # ═══════════════ ZORUNLU ═══════════════
  key: "monthly-sales"
  name: "Aylık Satış Raporu"
  type: "chart"
  
  # ═══════════════ VERİ KAYNAĞI ═══════════════
  data_source:
    type: "entity"
    entity: "otc_sales_order"
    joins:
      - entity: "fin_customer"
        on: "customer_id"
  
  # ═══════════════ PARAMETRELER ═══════════════
  parameters:
    - key: "start_date"
      type: "date"
      default: "first_day_of_month"
      label: "Başlangıç"
    
    - key: "end_date"
      type: "date"
      default: "today"
      label: "Bitiş"
    
    - key: "customer_id"
      type: "relation"
      target: "fin_customer"
      label: "Müşteri"
      optional: true
  
  # ═══════════════ FİLTRELER ═══════════════
  filters:
    - "date BETWEEN :start_date AND :end_date"
    - "status IN ('posted', 'paid')"
    - "customer_id = :customer_id" (if_not_null)
  
  # ═══════════════ GRUPLAMA ═══════════════
  group_by:
    - field: "date"
      granularity: "month"              # day | week | month | quarter | year
      label: "Ay"
  
  # ═══════════════ TOPLAMA ═══════════════
  aggregates:
    - key: "total_revenue"
      field: "total"
      function: "SUM"                   # SUM | COUNT | AVG | MIN | MAX
  
  # ═══════════════ SIRALAMA ═══════════════
  sort:
    - field: "date"
      direction: "asc"
  
  # ═══════════════ GÖRÜNÜM ═══════════════
  chart_type: "line"
  x_axis: "date"
  y_axis: "total_revenue"
  series: null                          # tek seri
  # veya
  # series: "customer.region"           # her region bir çizgi
  
  # ═══════════════ GÖRÜNÜM AYARLARI ═══════════════
  appearance:
    title: "Aylık Satış Trendi"
    subtitle: "${start_date} - ${end_date}"
    colors: ["#4f46e5", "#10b981", "#f59e0b"]
    legend: true
    data_labels: true
  
  # ═══════════════ İZİNLER ═══════════════
  permissions:
    view: ["role:finance", "role:sales_manager"]
    export: ["role:finance"]
5.2. Minimal Örnek
yaml
report:
  key: "top-customers"
  name: "En İyi Müşteriler"
  type: "chart"
  data_source: { type: "entity", entity: "fin_ar_invoice" }
  group_by: [{ field: "customer_id" }]
  aggregates: [{ key: "revenue", field: "total", function: "SUM" }]
  sort: [{ field: "revenue", direction: "desc" }]
  limit: 10
  chart_type: "bar"
Bu 10 satır = çalışan rapor.

6. Parametreler
6.1. Parametre Tipleri
Tip	Örnek	Kullanım
date	2026-11-06	Tarih seçici
date_range	{ from, to }	Aralık
text	"abc"	Serbest metin
number	1000	Sayı
boolean	true	Checkbox
dropdown	{ value: "a", label: "A" }	Sabit liste
relation	uuid	Entity referansı
multi_select	[uuid1, uuid2]	Çoklu
cascading	parent → child	Bağımlı
6.2. Varsayılan Değerler
yaml
parameters:
  - key: "start_date"
    type: "date"
    default: "first_day_of_month"       # today | yesterday | first_day_of_month | first_day_of_year
  
  - key: "period"
    type: "dropdown"
    options:
      - { value: "today", label: "Bugün" }
      - { value: "week", label: "Bu Hafta" }
      - { value: "month", label: "Bu Ay" }
      - { value: "year", label: "Bu Yıl" }
    default: "month"
6.3. Cascading Parameters
yaml
parameters:
  - key: "region"
    type: "dropdown"
    options: ["Istanbul", "Ankara", "Izmir"]
  
  - key: "city"
    type: "dropdown"
    options_from: "cities WHERE region = :region"
    depends_on: "region"
6.4. Parametre Validasyonu
yaml
parameters:
  - key: "start_date"
    type: "date"
    validations:
      - "not_future"
  
  - key: "end_date"
    type: "date"
    validations:
      - "not_future"
      - "after:start_date"
7. Filtreler
7.1. Sabit Filtre
yaml
filters:
  - "status = 'active'"
7.2. Parametre Filtresi
yaml
filters:
  - "date >= :start_date"
  - "date <= :end_date"
7.3. Conditional Filtre
yaml
filters:
  - "customer_id = :customer_id" (if_not_null: customer_id)
  - "status = :status" (if_not_empty: status)
7.4. Kullanıcı Bazlı Filtre
yaml
filters:
  - "sales_rep_id = :current_user_id"  (if_not_admin: true)
Kural: Satış temsilcisi sadece kendi satışlarını görür.

7.5. Data Scope Filtresi
yaml
filters:
  - data_scope: true                  # Belge 06 otomatik uygula
Kural: Data scope her zaman otomatik — filtre yazmasan bile.

7.6. Filtre Formülü
yaml
filters:
  - "total > 1000 AND (status = 'posted' OR status = 'paid')"
Expression language (Belge 11):

=, !=, >, <, >=, <=

AND, OR, NOT

IN, NOT IN

BETWEEN

LIKE, ILIKE

IS NULL, IS NOT NULL

8. Gruplama ve Toplama
8.1. Gruplama
yaml
group_by:
  - field: "date"
    granularity: "month"
  
  - field: "customer.region"
    label: "Bölge"
Granularity:

Level	Örnek
minute	10:30
hour	10:00
day	06.11.2026
week	W45 2026
month	Kasım 2026
quarter	Q4 2026
year	2026
8.2. Toplama Fonksiyonları
Fonksiyon	Anlam
SUM	Toplam
COUNT	Adet
COUNT_DISTINCT	Benzersiz sayı
AVG	Ortalama
MIN	Minimum
MAX	Maximum
STDDEV	Standart sapma
VARIANCE	Varyans
MEDIAN	Medyan
P25/P75/P95	Percentile
8.3. Hesaplanmış Sütunlar
yaml
computed_columns:
  - key: "profit"
    expression: "SUM(total) - SUM(cost)"
  
  - key: "margin"
    expression: "(SUM(total) - SUM(cost)) / NULLIF(SUM(total), 0) * 100"
  
  - key: "trend"
    expression: "(SUM(total) - LAG(SUM(total)) OVER (ORDER BY date)) / LAG(SUM(total)) OVER (ORDER BY date) * 100"
8.4. Window Fonksiyonları
Fonksiyon	Anlam
LAG / LEAD	Önceki/sonraki satır
RANK	Sıralama
DENSE_RANK	Yoğun sıralama
ROW_NUMBER	Satır numarası
SUM OVER	Kümülatif toplam
AVG OVER	Hareketli ortalama
NTILE	Grup bölme
Örnek: Kümülatif ciro

yaml
computed_columns:
  - key: "cumulative_revenue"
    expression: "SUM(SUM(total)) OVER (ORDER BY date ROWS UNBOUNDED PRECEDING)"
9. Chart Tipleri
9.1. Bar Chart
yaml
chart_type: "bar"
x_axis: "month"
y_axis: "total"
orientation: "vertical"                 # vertical | horizontal
stacked: false
9.2. Line Chart
yaml
chart_type: "line"
x_axis: "date"
y_axis: "total"
smooth: true
area: false
9.3. Pie / Donut
yaml
chart_type: "pie"
label: "category"
value: "total"
donut: true
9.4. Area Chart
yaml
chart_type: "area"
x_axis: "date"
y_axis: "total"
stacked: false
9.5. Scatter
yaml
chart_type: "scatter"
x_axis: "quantity"
y_axis: "total"
size: "profit"
9.6. Radar
yaml
chart_type: "radar"
axes: ["quality", "speed", "cost", "reliability"]
9.7. Funnel
yaml
chart_type: "funnel"
stages: ["lead", "opportunity", "quote", "order", "invoice"]
values: ["count"]
9.8. Gauge
yaml
chart_type: "gauge"
value: "revenue"
max: "target"
thresholds:
  - { value: 60, color: "red" }
  - { value: 80, color: "yellow" }
  - { value: 100, color: "green" }
9.9. Heatmap
yaml
chart_type: "heatmap"
x_axis: "day_of_week"
y_axis: "hour"
value: "order_count"
9.10. Treemap
yaml
chart_type: "treemap"
label: "category"
value: "revenue"
9.11. Sankey
yaml
chart_type: "sankey"
source: "from_category"
target: "to_category"
value: "amount"
9.12. Map
yaml
chart_type: "map"
latitude: "customer.lat"
longitude: "customer.lng"
size: "revenue"
color_by: "region"
Chart.js, D3.js, ApexCharts frontend'de render eder.

10. Zamanlanmış Raporlar
10.1. Schedule Tanımı
yaml
schedule:
  enabled: true
  frequency: "daily"                    # hourly | daily | weekly | monthly | cron
  time: "09:00"
  timezone: "Europe/Istanbul"
  cron: "0 9 * * 1-5"                  # iş günleri 9:00
  
  parameters:
    - { key: "start_date", value: "yesterday" }
    - { key: "end_date", value: "yesterday" }
  
  distribution:
    - type: "email"
      to: ["manager@company.com"]
      cc: ["finance@company.com"]
      subject: "Günlük Satış Raporu — ${date}"
      format: "pdf"
      attach: true
    
    - type: "webhook"
      url: "https://hooks.company.com/report"
      method: "POST"
      format: "json"
    
    - type: "dashboard"
      dashboard_key: "daily-kpis"
10.2. Dağıtım Formatları
Format	Kullanım
PDF	Email, arşiv
Excel	Analiz
CSV	Import
JSON	API
HTML	Email body
10.3. Runtime
php
class ScheduledReportRunner {
    public function run(): void {
        $reports = ReportSchedule::where('enabled', true)
            ->where('next_run_at', '<=', now())
            ->get();
        
        foreach ($reports as $schedule) {
            dispatch(new GenerateReport($schedule));
        }
    }
}

class GenerateReport implements ShouldQueue {
    public function handle(): void {
        $report = Report::find($this->schedule->report_id);
        $data = $report->execute($this->schedule->parameters);
        
        $file = $report->render($this->schedule->format, $data);
        
        foreach ($this->schedule->distribution as $target) {
            $this->send($target, $file, $data);
        }
        
        $this->schedule->update(['last_run_at' => now(), 'next_run_at' => $this->calculateNext()]);
    }
}
10.4. Zamanlama Seçenekleri
Frekans	Örnek
hourly	Her saat başı
daily	Her gün 09:00
weekly	Pazartesi 09:00
monthly	Ayın 1'i 09:00
quarterly	Her çeyrek başı
yearly	Her yıl 1 Ocak
cron	*/15 * * * *
10.5. Dağıtım Kanalları
Kanal	Detay
Email	SMTP, SES, SendGrid
Webhook	HTTP POST
Dashboard	Widget olarak göster
Slack	Slack webhook
WhatsApp	WhatsApp Business API
Telegram	Bot API
S3	Arşiv
Kural: Her kanal plugin olabilir (Belge 10).

11. Görsel Builder
11.1. Canvas
text
┌──────────────────────────────────────────────────────────┐
│  Report Builder: Monthly Sales                           │
├──────────────────────────────────────────────────────────┤
│  📊 Data Source                                          │
│  ┌──────────────────────────────────────────┐           │
│  │ Entity: [otc_sales_order ▾]              │           │
│  │ Joins:   [+ customer]                    │           │
│  └──────────────────────────────────────────┘           │
│                                                          │
│  🔍 Filters                                              │
│  ┌──────────────────────────────────────────┐           │
│  │ date >= [Start Date]                     │           │
│  │ date <= [End Date]                       │           │
│  │ status IN ['posted', 'paid']             │           │
│  └──────────────────────────────────────────┘           │
│                                                          │
│  📈 Chart                                                │
│  ┌──────────────────────────────────────────┐           │
│  │ Type: [Line ▾]                           │           │
│  │ X: [date (month) ▾]                      │           │
│  │ Y: [SUM(total) ▾]                        │           │
│  └──────────────────────────────────────────┘           │
│                                                          │
│  [▶ Preview]  [💾 Save]  [📅 Schedule]                  │
└──────────────────────────────────────────────────────────┘
11.2. Drag-Drop
Field palette — Sol tarafta mevcut alanlar

X-axis drop zone — Sürükle → X eksenine

Y-axis drop zone — Sürükle → Y eksenine

Filters drop zone — Sürükle → filtre ekle

Group drop zone — Sürükle → gruplama

11.3. Live Preview
Her değişiklik → anında önizleme (1-2 saniye).

11.4. Templates
Hazır şablonlar:

Blank — Sıfırdan

Sales Report — Satış raporu

AR Aging — Alacak yaşlandırma

Stock Valuation — Stok değerleme

Customer P&L — Müşteri kâr/zarar

Top Products — En çok satanlar

Monthly Trend — Aylık trend

11.5. AI-Assisted Builder
Kullanıcı doğal dil: "Geçen ayın en çok satan 10 ürününü göster"

AI: YAML üretir:

yaml
report:
  key: "top-products-last-month"
  type: "bar"
  data_source: { type: "entity", entity: "otc_sales_order" }
  filters: ["date BETWEEN :last_month_start AND :last_month_end"]
  group_by: [{ field: "order_lines.product_id" }]
  aggregates: [{ key: "revenue", field: "order_lines.total", function: "SUM" }]
  sort: [{ field: "revenue", direction: "desc" }]
  limit: 10
Kullanıcı: Önizler, düzenler, kaydeder.

12. Formüller
12.1. Basit Formül
yaml
computed:
  - key: "profit"
    expression: "total - cost"
12.2. Fonksiyonlar
Fonksiyon	Anlam
IF(condition, a, b)	Koşul
CASE WHEN	Çoklu koşul
ROUND(value, digits)	Yuvarlama
CONCAT(...)	Birleştirme
UPPER / LOWER	Büyük/küçük
DATE_TRUNC(unit, date)	Tarih kesme
DATE_DIFF(a, b)	Tarih farkı
NULLIF(a, b)	Null handling
COALESCE(...)	İlk non-null
ABS / CEIL / FLOOR	Matematik
12.3. Conditional (IF/CASE)
yaml
computed:
  - key: "category"
    expression: |
      CASE
        WHEN total > 10000 THEN 'High'
        WHEN total > 1000 THEN 'Medium'
        ELSE 'Low'
      END
12.4. Cross-Column
yaml
computed:
  - key: "growth"
    expression: "(this_month - last_month) / last_month * 100"
Not: last_month aynı raporda başka bir sütun.

12.5. Aggregate
yaml
computed:
  - key: "avg_order_value"
    expression: "SUM(total) / COUNT(id)"
12.6. Parametre Referansı
yaml
computed:
  - key: "target_achievement"
    expression: "SUM(total) / :target * 100"
13. İzinler
13.1. Report Permission
yaml
permissions:
  view: ["role:finance", "role:sales_manager", "user:42"]
  edit: ["role:finance"]
  delete: ["role:admin"]
  export: ["role:finance", "role:accountant"]
  schedule: ["role:admin"]
  share: ["role:admin"]
13.2. Data Scope
Rapor otomatik data scope uygular (Belge 06):

Satış temsilcisi → sadece kendi satışları

Bölge müdürü → bölgesindeki satışlar

Admin → tüm tenant

Superadmin → tüm tenant'lar

13.3. Row-Level Filter
yaml
row_filters:
  - when: "user.role = 'sales_rep'"
    filter: "sales_rep_id = :current_user_id"
  
  - when: "user.role = 'manager'"
    filter: "department_id = :current_user_department"
  
  - when: "user.role = 'admin'"
    filter: null                        # tüm tenant
13.4. Column-Level Masking
yaml
columns:
  - key: "customer_name"
    mask_if: "user.role = 'sales_rep'"
    mask_type: "partial"                # Ali Y*** olur
  
  - key: "customer_tax_no"
    visible_if: "user.role IN ('admin', 'finance')"
Kural: PII her zaman maskelenebilir.

14. Runtime Execution
14.1. Query Generation
php
class ReportExecutor {
    public function execute(Report $report, array $params): ReportResult {
        // 1. Data source
        $query = $this->buildQuery($report, $params);
        
        // 2. Data scope (Belge 06)
        $query = DataScope::apply($query, auth()->user());
        
        // 3. RLS (Belge 01) — otomatik
        // 4. Execute
        $rows = $query->get();
        
        // 5. Compute
        $result = $this->compute($rows, $report);
        
        // 6. Format
        return new ReportResult($result);
    }
    
    private function buildQuery(Report $report, array $params): Builder {
        $ds = $report->data_source;
        
        return match ($ds->type) {
            'entity' => $this->buildEntityQuery($ds, $params, $report),
            'query' => $this->buildSqlQuery($ds, $params, $report),
            'computed' => $this->buildComputedQuery($ds, $params, $report),
        };
    }
}
14.2. SQL Generation (Query Type)
php
private function buildSqlQuery($ds, $params, $report): Builder {
    // 1. SQL parse (SELECT only)
    $parser = new SqlParser();
    $ast = $parser->parse($ds->sql);
    
    // 2. Sadece SELECT kontrolü
    if ($ast->type !== 'SELECT') {
        throw new UnauthorizedSqlException();
    }
    
    // 3. RLS otomatik ekle
    $ast = $this->injectRls($ast);
    
    // 4. Parametre bind
    $sql = $this->bindParams($ast, $params);
    
    // 5. Execute
    return DB::table(DB::raw("({$sql}) AS report"));
}
Kural: Her SQL query RLS inject edilir.

14.3. Result Caching
php
class ReportCache {
    public function remember(Report $report, array $params, int $ttl = 300): array {
        $key = "report:{$report->id}:" . md5(json_encode($params));
        
        return Cache::remember($key, $ttl, function () use ($report, $params) {
            return app(ReportExecutor::class)->execute($report, $params)->toArray();
        });
    }
    
    public function invalidate(Report $report): void {
        Cache::tags(["report:{$report->id}"])->flush();
    }
}
14.4. Materialized View (Büyük Raporlar)
php
class MaterializedReport {
    public function create(Report $report): void {
        $sql = $this->buildCreateMaterializedViewSql($report);
        DB::statement($sql);
        
        // Schedule refresh
        $report->update(['refresh_schedule' => 'hourly']);
    }
    
    public function refresh(Report $report): void {
        DB::statement("REFRESH MATERIALIZED VIEW CONCURRENTLY report_{$report->id}");
    }
}
Kullanım: Büyük aggregation raporları (> 1M satır).

15. Performans
15.1. Query Optimization
Teknik	Kullanım
Index	Her filter/group field
Covering index	Sık kullanılan kolonlar
Materialized view	Büyük aggregation
Partition	Tarih bazlı
Cache	5 dk TTL
Cursor	Büyük result
15.2. Timeout
Rapor Boyutu	Timeout
< 10K satır	10 sn
10K-100K	30 sn
100K-1M	120 sn
> 1M	Materialized + async
15.3. Async Execution
Büyük raporlar → async job:

php
class RunLargeReport implements ShouldQueue {
    public $timeout = 600;
    
    public function handle(): void {
        $result = app(ReportExecutor::class)->execute($this->report, $this->params);
        
        // PDF generate
        $pdf = $this->report->renderPdf($result);
        
        // Store
        $url = Storage::put("reports/{$this->report->id}/{$this->id}.pdf", $pdf);
        
        // Notify user
        event(new ReportReady($this->user, $url));
    }
}
15.4. Ölçek Sınırları
Ölçek	Strateji
SMB	Tek DB, cache
Mid-market	Read replica, materialized
Enterprise	Data warehouse (BigQuery, ClickHouse)
16. Testing
16.1. Report Definition Test
php
public function test_report_definition_validates(): void {
    $definition = [
        'key' => 'test',
        'name' => 'Test',
        'type' => 'table',
        'data_source' => ['type' => 'entity', 'entity' => 'fin_customer'],
        'columns' => ['code', 'name'],
    ];
    
    $result = ReportValidator::validate($definition);
    $this->assertTrue($result->isValid());
}

public function test_rejects_invalid_entity(): void {
    $this->expectException(InvalidReportException::class);
    
    ReportValidator::validate([
        'data_source' => ['type' => 'entity', 'entity' => 'nonexistent'],
    ]);
}
16.2. Execution Test
php
public function test_report_executes(): void {
    $report = Report::factory()->create([...]);
    
    $result = app(ReportExecutor::class)->execute($report, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    
    $this->assertNotEmpty($result);
    $this->assertArrayHasKey('total_revenue', $result[0]);
}
16.3. Permission Test
php
public function test_sales_rep_only_sees_own_data(): void {
    $salesRep = User::factory()->sales_rep()->create();
    $this->actingAs($salesRep);
    
    $result = app(ReportExecutor::class)->execute($report, []);
    
    // Sadece kendi satışları
    foreach ($result as $row) {
        $this->assertEquals($salesRep->id, $row->sales_rep_id);
    }
}
16.4. SQL Injection Test
php
public function test_sql_injection_blocked(): void {
    $this->expectException(UnauthorizedSqlException::class);
    
    ReportValidator::validate([
        'data_source' => [
            'type' => 'query',
            'sql' => 'SELECT * FROM users; DROP TABLE users;',
        ],
    ]);
}
17. Restoran Örneği — Günlük Satış Raporu
17.1. Rapor Tanımı
yaml
report:
  key: "restaurant-daily-sales"
  name: "Günlük Satış Raporu"
  type: "chart"
  
  data_source:
    type: "entity"
    entity: "custom_order"
    joins:
      - entity: "restaurant_table"
        on: "table_id"
  
  parameters:
    - key: "date"
      type: "date"
      default: "yesterday"
      label: "Tarih"
    
    - key: "branch_id"
      type: "relation"
      target: "branch"
      optional: true
      label: "Şube"
  
  filters:
    - "date = :date"
    - "branch_id = :branch_id" (if_not_null: branch_id)
    - "status = 'paid'"
  
  group_by:
    - field: "date"
      granularity: "hour"
  
  aggregates:
    - key: "order_count"
      field: "id"
      function: "COUNT"
    - key: "revenue"
      field: "total"
      function: "SUM"
    - key: "avg_ticket"
      field: "total"
      function: "AVG"
  
  computed_columns:
    - key: "hour_label"
      expression: "TO_CHAR(date, 'HH24:00')"
  
  chart_type: "line"
  x_axis: "hour_label"
  y_axis: "revenue"
  
  appearance:
    title: "Saatlik Satış — ${date}"
    colors: ["#4f46e5"]
    data_labels: true
  
  permissions:
    view: ["role:branch_manager", "role:owner"]
17.2. Görünüm
text
Saatlik Satış — 05.11.2026
┌──────────────────────────────────────────┐
│  Revenue                                  │
│  12K ┤                          ╭─╮       │
│  10K ┤              ╭─╮   ╭──╮  │ │       │
│   8K ┤      ╭─╮     │ │   │  │  │ │       │
│   6K ┤  ╭─╮ │ │ ╭─╮ │ │   │  │  │ │       │
│   4K ┤  │ │ │ │ │ │ │ │   │  │  │ │       │
│   2K ┤──╯ ╰─╯ ╰─╯ ╰─╯ ╰───╯  ╰──╯ ╰───    │
│      └─08──10──12──14──16──18──20──22──    │
└──────────────────────────────────────────┘

Toplam Sipariş: 456   Toplam Ciro: 45.678 TL   Ortalama: 100 TL
17.3. KDS Performance Report
yaml
report:
  key: "kitchen-performance"
  name: "Mutfak Performansı"
  type: "table"
  
  data_source: { type: "entity", entity: "custom_order" }
  
  filters:
    - "date = :date"
    - "status IN ('ready', 'served')"
  
  computed_columns:
    - key: "prep_time"
      expression: "EXTRACT(EPOCH FROM (ready_at - created_at)) / 60"
    
    - key: "served_time"
      expression: "EXTRACT(EPOCH FROM (served_at - ready_at)) / 60"
  
  group_by:
    - field: "chef_id"
  
  aggregates:
    - { key: "avg_prep", field: "prep_time", function: "AVG" }
    - { key: "avg_served", field: "served_time", function: "AVG" }
    - { key: "total", field: "id", function: "COUNT" }
  
  columns: ["chef.name", "total", "avg_prep", "avg_served"]
17.4. Sipariş Analizi
yaml
report:
  key: "menu-performance"
  name: "Menü Performansı"
  type: "pivot"
  
  data_source: { type: "entity", entity: "custom_order_item" }
  
  rows: ["product.category", "product.name"]
  columns: ["order.date.month"]
  values:
    - { key: "qty", field: "qty", function: "SUM" }
    - { key: "revenue", field: "total", function: "SUM" }
  
  sort: [{ field: "revenue", direction: "desc" }]
  limit: 20
Sonuç: Hangi ürün hangi ayda ne kadar sattı.

18. Anti-Patterns
Bu projede yasak olan rapor hataları:

Hardcoded report — Kod içi rapor yasak

Raw SQL (unsafe) — Sadece SELECT, whitelist tablo

No RLS injection — Her SQL'de RLS zorunlu

No data scope — Kullanıcı filtre otomatik

PII maskesiz — Hassas veri maskeli

No permission — Rapor erişim kontrolü

SQL injection — Parametre bind zorunlu

N+1 — Eager load

No cache — 5 dk TTL minimum

No timeout — Sorgu timeout

No limit — LIMIT zorunlu (export hariç)

Sınırsız sonuç — Max 1M satır

SELECT * — Sadece gerekli kolonlar

Report delete (kullanımda) — Sadece deprecate

Superadmin bypass audit'siz — Her bypass log

19. Roadmap
Aşama	Süre	Kapsam
1. Foundation	6 hafta	Report entity, YAML parser, validator, simple table
2. Chart Types	6 hafta	Bar, line, pie, area, kpi, pivot
3. Filters & Parameters	4 hafta	Date, text, relation, cascading
4. Computed & Aggregations	4 hafta	SUM, COUNT, formulas, window
5. Scheduling	4 hafta	Cron, email, PDF, webhook
6. Visual Builder	8 hafta	Drag-drop, live preview, AI
7. Advanced Charts	6 hafta	Funnel, gauge, heatmap, map, sankey
8. Performance	4 hafta	Cache, materialized, async
Toplam: 42 hafta (~10 ay).

20. Glossary
Terim	Anlam
Report	Rapor tanımı
Report Builder	Görsel tasarımcı
Data Source	Veri kaynağı
Entity Source	Güvenli entity
Query Source	SQL (safe)
Computed Source	Hesaplanmış
Parameter	Kullanıcı girdisi
Filter	Veri filtresi
Group By	Gruplama
Aggregate	Toplama (SUM, COUNT)
Computed Column	Hesaplanmış sütun
Chart Type	Grafik tipi
Dashboard	Widget koleksiyonu
Pivot	Cross-tab
KPI	Key Performance Indicator
Schedule	Zamanlanmış rapor
Distribution	Dağıtım kanalı
Materialized View	Fiziksel view
Data Scope	Kullanıcı veri sınırı
PII Masking	Kişisel veri maskeleme
21. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — report builder, restoran örnekleri