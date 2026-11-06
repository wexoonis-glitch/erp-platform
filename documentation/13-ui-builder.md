# 13 — UI Builder

**Project:** Kurumsal ERP Platform
**Belge:** Görsel Arayüz Tasarımcısı, Dinamik Ekran, Layout Engine, Component System
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md, 11-workflow-engine.md, 12-report-builder.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **UI Builder** sistemini tanımlar — kullanıcının **kod yazmadan görsel arayüz** tasarlayabildiği, çalıştırabildiği ve paylaşabildiği motor. İçerir:

- Neden görsel UI builder
- 3 seviye özelleştirme (theme, layout, screen)
- UI tanımlama dili (YAML/JSON)
- Component kataloğu (50+ built-in)
- Layout sistemi (grid, flex, absolute)
- Data binding (entity, query, computed, API)
- Action binding (CRUD, navigation, workflow, script)
- Custom component (JS sandbox)
- Theme & styling
- Responsive design
- Screen inheritance
- Multi-language
- Visual builder (drag-drop)
- Runtime render
- Testing, performance, security
- Restoran örneği — KDS/table map
- Anti-patterns, roadmap

**Hedef kitle:** Geliştiriciler, AI asistanlar, ürün mimarları, UI/UX tasarımcıları, tenant admin'ler.

**Kritik:** UI Builder, "fully customizable" hedefinin **en görünür katmanı**. Kullanıcı **kendi ekranını** tasarlayabilmeli. SAP'de bu ABAP + Web Dynpro. Bizde sürükle-bırak.

---

## 2. Neden UI Builder?

### 2.1. Klasik ERP'nin Sınırlaması

| ERP | UI Özelleştirme | Sorun |
|---|---|---|
| **SAP** | Web Dynpro (ABAP) | 6 ay + danışman |
| **Odoo** | XML view + Python | Kod gerek |
| **Logo** | Sabit ekranlar | Özelleştirme yok |
| **NetSuite** | SuiteBuilder | Orta, sınırlı |
| **Salesforce** | Lightning Builder | İyi ama CRM |

**Ortak sorun:** Her ekran değişikliği = **geliştirici + zaman**.

### 2.2. Gerçek Dünya İhtiyacı

| İhtiyaç | Frekans |
|---|---|
| Dashboard düzeni değiştir | Sık |
| Listeye yeni sütun ekle | Sık |
| Formda alan sırası | Sık |
| Yeni ekran (özel süreç) | Orta |
| Marka rengi / logo | Az |
| Mobil optimizasyon | Orta |
| Kiosk mode ekranı | Az |

**Kural:** Tenant admin **kendi ekranını** yapabilmeli. Geliştiriciye muhtaç olmamalı.

### 2.3. Bizim Çözüm

**3 seviyeli özelleştirme** — en basitten en güçlüye:
Seviye 1: Theme → Renk, logo, font (5 dk, herkes)
↓
Seviye 2: Layout → Alan sırası, sütun, sekme (30 dk, admin)
↓
Seviye 3: Screen → Yeni ekran, custom component (2 saat, power user)

text

---

## 3. 3 Seviyeli Özelleştirme

### 3.1. Seviye 1: Theme

**Kapsam:** Renk, logo, font, tema modu.

**Kim:** Herhangi admin.

**Nasıl:** Ayarlar → Görünüm → Renk seç.

```yaml
theme:
  mode: "light"                         # light | dark | auto
  primary_color: "#4f46e5"
  secondary_color: "#10b981"
  accent_color: "#f59e0b"
  font_family: "Inter"
  logo_url: "/storage/tenants/abc/logo.png"
  favicon_url: "/storage/tenants/abc/favicon.ico"
  border_radius: "md"                   # sm | md | lg | full
Kullanım: Tüm platformda anında etki.

3.2. Seviye 2: Layout
Kapsam: Mevcut ekranları özelleştir (alan sırası, sütun, sekme).

Kim: Tenant admin.

Nasıl: Her ekranda "Düzenle" butonu → drag-drop.

yaml
layout:
  key: "customer-form"
  base: "fin_customer"                  # hangi entity
  sections:
    - title: "Temel Bilgiler"
      columns: 2
      fields:
        - { key: "code", width: "half" }
        - { key: "name", width: "half" }
        - { key: "tax_no", width: "half" }
        - { key: "tax_office", width: "half" }
    
    - title: "İletişim"
      columns: 2
      fields:
        - { key: "email", width: "half" }
        - { key: "phone", width: "half" }
        - { key: "address", width: "full" }
    
    - title: "Finansal"
      fields:
        - { key: "currency", width: "third" }
        - { key: "credit_limit", width: "third" }
        - { key: "status", width: "third" }
Kullanım: Ekran değişir, kod değişmez.

3.3. Seviye 3: Screen
Kapsam: Yeni ekran yarat (dashboard, özel form, KDS, masa haritası).

Kim: Power user (tenant admin + UI Builder rolü).

Nasıl: UI Builder'da sıfırdan tasarla.

yaml
screen:
  key: "restaurant-kds"
  name: "Mutfak Ekranı"
  type: "custom"
  layout: "fullscreen"
  components: [...]
Kullanım: Sınırsız — istediğin ekran.

4. UI Tanımlama Dili (YAML)
4.1. Tam Yapı
yaml
screen:
  # ═══════════════ ZORUNLU ═══════════════
  key: "restaurant-kds"
  name: "Mutfak Ekranı (KDS)"
  type: "custom"                        # page | modal | widget | dashboard | custom
  
  # ═══════════════ LAYOUT ═══════════════
  layout:
    type: "flex"                        # grid | flex | absolute
    direction: "column"
    gap: "4"
  
  # ═══════════════ DATA BINDING ═══════════════
  data:
    tickets:
      source: "entity"
      entity: "custom_order"
      filter: "status IN ('new', 'preparing')"
      sort: "created_at ASC"
      realtime: true                    # WebSocket
    
    menu:
      source: "entity"
      entity: "custom_product"
      filter: "available = true"
  
  # ═══════════════ COMPONENTS ═══════════════
  components:
    - type: "header"
      props:
        title: "Mutfak Ekranı"
        subtitle: "Aktif siparişler: ${tickets.count}"
    
    - type: "grid"
      props:
        columns: 3
        gap: "4"
      
      children:
        - type: "repeat"
          data: "${tickets}"
          key: "${ticket.id}"
          
          children:
            - type: "card"
              style:
                border: "2px solid ${ticket.urgent ? 'red' : 'gray'}"
                bg: "${ticket.urgent ? '#fff5f5' : 'white'}"
              
              children:
                - type: "text"
                  props:
                    content: "Masa ${ticket.table_number}"
                    size: "xl"
                    weight: "bold"
                
                - type: "list"
                  data: "${ticket.items}"
                  children:
                    - type: "row"
                      children:
                        - type: "text"
                          props: { content: "${item.qty}x ${item.product_name}" }
                        - type: "text"
                          props: { content: "${item.notes}", color: "red" }
                
                - type: "timer"
                  props:
                    from: "${ticket.created_at}"
                    warn_after: 900              # 15 dk saniye
                    danger_after: 1200           # 20 dk
                
                - type: "button"
                  props:
                    label: "Hazır"
                    color: "green"
                    action:
                      type: "transition"
                      entity: "custom_order"
                      id: "${ticket.id}"
                      to: "ready"
  
  # ═══════════════ İZİNLER ═══════════════
  permissions:
    view: ["role:kitchen", "role:branch_manager"]
  
  # ═══════════════ RESPONSIVE ═══════════════
  responsive:
    mobile:
      grid_columns: 1
    tablet:
      grid_columns: 2
    desktop:
      grid_columns: 3
4.2. Minimal Örnek
yaml
screen:
  key: "simple-list"
  name: "Müşteri Listesi"
  type: "page"
  data:
    customers: { source: "entity", entity: "fin_customer" }
  components:
    - type: "data_table"
      props:
        data: "${customers}"
        columns: ["code", "name", "email"]
Bu 10 satır = çalışan liste ekranı.

5. Component Kataloğu
5.1. Layout Components
Component	Kullanım
container	Genel wrapper
grid	CSS grid layout
flex	CSS flex layout
row	Yatay grup
col	Dikey grup
card	Kart (border + shadow)
section	Bölüm (başlık + içerik)
tabs	Sekmeler
accordion	Katlanır
split	İki panel
sidebar	Yan panel
5.2. Display Components
Component	Kullanım
text	Metin
heading	Başlık (h1-h6)
badge	Etiket
icon	İkon
image	Resim
avatar	Kullanıcı avatarı
divider	Ayırıcı
progress	Progress bar
spinner	Yükleniyor
skeleton	Placeholder
tooltip	Tooltip
5.3. Data Components
Component	Kullanım
data_table	Tablo (sıralama, filter, pagination)
list	Liste
card_list	Kart liste
kanban	Kanban board
calendar	Takvim
timeline	Zaman çizelgesi
tree	Ağaç
pivot	Pivot tablo
stat	KPI kartı
5.4. Form Components
Component	Kullanım
input	Metin giriş
textarea	Uzun metin
number	Sayı
select	Dropdown
multiselect	Çoklu seçim
checkbox	Checkbox
radio	Radio
switch	Toggle
date_picker	Tarih
time_picker	Saat
datetime_picker	Tarih + saat
file_upload	Dosya
image_upload	Resim
color_picker	Renk
signature	İmza
rich_editor	WYSIWYG
relation_picker	Entity referansı
address	Adres
5.5. Chart Components
Component	Kullanım
chart_bar	Bar chart
chart_line	Line chart
chart_pie	Pie chart
chart_area	Area chart
chart_scatter	Scatter
chart_radar	Radar
chart_funnel	Funnel
chart_gauge	Gauge
chart_heatmap	Heatmap
chart_map	Harita
chart_sankey	Sankey
5.6. Action Components
Component	Kullanım
button	Buton
button_group	Buton grubu
link	Link
dropdown_menu	Menü
context_menu	Sağ tık menü
fab	Floating button
action_bar	Aksiyon çubuğu
5.7. Navigation Components
Component	Kullanım
menu	Menü
breadcrumb	Breadcrumb
pagination	Sayfalama
stepper	Adım adım
wizard	Sihirbaz
5.8. Feedback Components
Component	Kullanım
alert	Uyarı
toast	Bildirim
modal	Modal
drawer	Yan panel
popover	Popover
confirm	Onay dialogu
empty	Boş durum
error	Hata ekranı
5.9. Advanced Components
Component	Kullanım
repeat	Veri tekrarı (loop)
condition	Koşullu gösterim
slot	Component slot
dynamic	Dinamik component
iframe	Gömülü sayfa
canvas	Serbest çizim (masa haritası)
video	Video
pdf_viewer	PDF göster
barcode	Barkod okuyucu
map	Harita
qr_scanner	QR okuyucu
Toplam: 60+ component. Hepsi responsive, accessible, themable.

6. Layout Sistemi
6.1. Grid Layout
CSS Grid tabanlı.

yaml
layout:
  type: "grid"
  columns: 12
  gap: "4"
  
  children:
    - component: "card"
      props: { grid_column: "span 8" }
    - component: "card"
      props: { grid_column: "span 4" }
Responsive:

yaml
children:
  - component: "card"
    props:
      grid_column:
        mobile: "span 12"
        tablet: "span 6"
        desktop: "span 4"
6.2. Flex Layout
CSS Flex tabanlı.

yaml
layout:
  type: "flex"
  direction: "row"                      # row | column
  justify: "space-between"              # start | center | end | between | around
  align: "center"                       # start | center | end | stretch
  wrap: true
  gap: "4"
6.3. Absolute Layout
Pozisyon bazlı (masa haritası için).

yaml
layout:
  type: "absolute"
  width: 1200
  height: 800
  
  children:
    - component: "table"
      props:
        x: 100
        y: 200
        width: 80
        height: 80
Kullanım: Restoran masa haritası, depo raf planı, clinic oda planı.

6.4. Split Layout
İki panel, resize edilebilir.

yaml
layout:
  type: "split"
  direction: "horizontal"
  panels:
    - { width: "30%", component: "sidebar" }
    - { width: "70%", component: "content" }
7. Data Binding
7.1. Entity Binding
yaml
data:
  customers:
    source: "entity"
    entity: "fin_customer"
    filter: "status = 'active'"
    sort: "name ASC"
    limit: 100
7.2. Query Binding (SQL-Safe)
yaml
data:
  top_products:
    source: "query"
    sql: |
      SELECT product_id, SUM(qty) AS total
      FROM otc_sales_order_line
      WHERE date >= :start_date
      GROUP BY product_id
      ORDER BY total DESC
      LIMIT 10
    params:
      start_date: "${params.start_date}"
7.3. API Binding
yaml
data:
  weather:
    source: "api"
    url: "https://api.weather.com/v1/current"
    params:
      city: "${tenant.city}"
    cache: 300
7.4. Computed Binding
yaml
data:
  stats:
    source: "computed"
    expressions:
      total_customers: "customers.length"
      active_customers: "customers.filter(c => c.status === 'active').length"
      revenue: "SUM(customers.map(c => c.total_revenue))"
7.5. Workflow Binding
yaml
data:
  pending_approvals:
    source: "workflow"
    workflow_key: "expense-approval"
    status: "active"
    assignee: "${current_user.id}"
7.6. Report Binding
yaml
data:
  monthly_sales:
    source: "report"
    report_key: "monthly-sales"
    params:
      start_date: "${params.start_date}"
      end_date: "${params.end_date}"
7.7. Real-time Binding
yaml
data:
  tickets:
    source: "entity"
    entity: "custom_order"
    filter: "status IN ('new', 'preparing')"
    realtime: true                      # WebSocket
Kullanım: KDS, dashboard, canlı monitör.

8. Action Binding
8.1. CRUD Action
yaml
button:
  type: "button"
  props:
    label: "Kaydet"
    action:
      type: "crud"
      operation: "create"                # create | update | delete
      entity: "fin_customer"
      data: "${form}"
8.2. Navigation Action
yaml
button:
  props:
    label: "Detay"
    action:
      type: "navigate"
      route: "/fin/customers/${row.id}"
8.3. Workflow Action
yaml
button:
  props:
    label: "Onayla"
    action:
      type: "transition"
      entity: "fin_ar_invoice"
      id: "${invoice.id}"
      to: "posted"
8.4. Modal Action
yaml
button:
  props:
    label: "Yeni Müşteri"
    action:
      type: "modal"
      screen: "customer-create-modal"
8.5. API Action
yaml
button:
  props:
    label: "Sync"
    action:
      type: "api"
      method: "POST"
      url: "/api/v1/integration/wolt/sync"
      confirm: "Senkronize edilsin mi?"
8.6. Script Action
yaml
button:
  props:
    label: "Hesapla"
    action:
      type: "script"
      language: "javascript"            # SADECE JS
      code: |
        const total = data.items.reduce((s, i) => s + i.qty * i.price, 0);
        setData('total', total);
        toast('Hesaplandı: ' + total);
Kısıt: PHP yasak (güvenlik — Belge 10).

8.7. Chain Action
yaml
action:
  type: "chain"
  actions:
    - { type: "crud", operation: "create", entity: "fin_customer" }
    - { type: "navigate", route: "/fin/customers" }
    - { type: "toast", message: "Müşteri oluşturuldu" }
8.8. Conditional Action
yaml
button:
  props:
    label: "Onayla"
    visible_if: "${invoice.status === 'draft' && user.can('approve')}"
    disabled_if: "${invoice.total <= 0}"
    action: { type: "transition", ... }
9. Custom Component
9.1. Ne Zaman?
Built-in component'ler yetersiz olduğunda:

Özel chart

3D görselleştirme

Karmaşık animasyon

Harita (özel)

Video conference

9.2. Custom Component Yapısı
yaml
component:
  key: "custom-3d-viewer"
  name: "3D Model Görüntüleyici"
  type: "custom"
  
  runtime: "web-component"              # web-component | iframe | wasm
  
  props:
    - { key: "model_url", type: "string", required: true }
    - { key: "background", type: "string", default: "#ffffff" }
  
  events:
    - { key: "loaded", payload: null }
    - { key: "click", payload: { x: "number", y: "number" } }
  
  code:
    html: |
      <div id="viewer" style="width:100%;height:100%"></div>
    js: |
      import * as THREE from 'https://esm.sh/three';
      
      export default class Model3DViewer extends HTMLElement {
        connectedCallback() {
          const scene = new THREE.Scene();
          // ...
        }
      }
      customElements.define('custom-3d-viewer', Model3DViewer);
9.3. Sandbox
Custom component sandbox içinde çalışır:

✅ Sadece belirli props

✅ Sadece belirli events

✅ Web Component (shadow DOM izolasyon)

❌ Ana DOM erişimi yok

❌ Ana state erişimi yok

❌ Cross-tenant yok

9.4. Component Marketplace
Custom component'ler paylaşılabilir:

Marketplace'te yayınla

Başka tenant'lar kurabilir

Rating + review

Kural: Component plugin mantığıyla çalışır (Belge 10).

10. Theme & Styling
10.1. Theme Variables
yaml
theme:
  colors:
    primary: "#4f46e5"
    secondary: "#10b981"
    danger: "#ef4444"
    warning: "#f59e0b"
    info: "#3b82f6"
    gray: "#6b7280"
  
  spacing:
    unit: 4                             # 4px bazlı
  
  radius:
    sm: "4px"
    md: "8px"
    lg: "12px"
    full: "9999px"
  
  shadow:
    sm: "0 1px 3px rgba(0,0,0,.1)"
    md: "0 4px 12px rgba(0,0,0,.15)"
    lg: "0 12px 32px rgba(0,0,0,.2)"
  
  font:
    family: "Inter, sans-serif"
    size: "14px"
10.2. Inline Style
yaml
component:
  type: "card"
  style:
    padding: "16px"
    background: "#f9fafb"
    border-radius: "8px"
    border: "1px solid #e5e7eb"
10.3. Class-Based
yaml
component:
  type: "card"
  class: "shadow-md hover:shadow-lg"
Kural: Tailwind class'ları desteklenir.

10.4. Conditional Style
yaml
component:
  type: "badge"
  style:
    background: "${row.status === 'active' ? 'green' : 'gray'}"
    color: "${row.status === 'active' ? 'white' : 'gray'}"
10.5. Dark Mode
yaml
component:
  type: "card"
  style:
    light: { background: "white", color: "black" }
    dark: { background: "gray-900", color: "white" }
Kural: Her component dark mode uyumlu.

11. Responsive Design
11.1. Breakpoints
Breakpoint	Genişlik
mobile	< 768px
tablet	768-1024px
desktop	> 1024px
wide	> 1440px
11.2. Responsive Props
yaml
component:
  type: "grid"
  props:
    columns:
      mobile: 1
      tablet: 2
      desktop: 3
      wide: 4
11.3. Responsive Visibility
yaml
component:
  type: "button"
  props:
    label: "Detay"
    visible:
      mobile: false
      desktop: true
11.4. Responsive Layout
yaml
layout:
  mobile:
    type: "flex"
    direction: "column"
  desktop:
    type: "grid"
    columns: 12
12. Screen Inheritance
12.1. Base Screen
yaml
screen:
  key: "base-list"
  name: "Temel Liste"
  type: "page"
  abstract: true                        # miras alınabilir
  components:
    - type: "header"
    - type: "data_table"
    - type: "pagination"
12.2. Inherited Screen
yaml
screen:
  key: "customer-list"
  extends: "base-list"                  # miras
  data:
    rows: { source: "entity", entity: "fin_customer" }
  components:
    - type: "data_table"
      override: true
      props:
        columns: ["code", "name", "email", "status"]
Kural: Child, parent'ı ezebilir veya genişletebilir.

12.3. Override Rules
yaml
screen:
  extends: "base-list"
  overrides:
    "header.title": "Müşteriler"
    "data_table.columns": ["code", "name"]
  adds:
    - before: "data_table"
      component:
        type: "button"
        props: { label: "Yeni", action: { type: "modal", screen: "customer-create" } }
13. Multi-Language
13.1. Text Binding
yaml
component:
  type: "text"
  props:
    content: "@t('customer.title')"     # translation key
13.2. Dynamic Language
yaml
component:
  type: "text"
  props:
    content:
      tr: "Müşteriler"
      en: "Customers"
      az: "Müştərilər"
13.3. User Language
yaml
text:
  content: "@t('welcome')"
  fallback: "Welcome"
  # Otomatik current_user.locale kullanır
Kural: Her metin çevrilebilir.

14. Visual Builder (Drag-Drop)
14.1. Builder Layout
text
┌──────────────────────────────────────────────────────────┐
│  UI Builder: Restaurant KDS                              │
├──────────────┬──────────────────────────┬────────────────┤
│  Components  │  Canvas                  │  Properties    │
│              │                          │                │
│  Layout      │  ┌──────────────────┐    │  Card          │
│  ├ Container │  │                  │    │  ────────────  │
│  ├ Grid      │  │  [Header]        │    │  Padding: [__] │
│  ├ Flex      │  │                  │    │  Border: [__]  │
│  └ Row       │  │  ┌────┐ ┌────┐   │    │  Shadow: [__]  │
│              │  │  │Card│ │Card│   │    │                │
│  Display     │  │  └────┘ └────┘   │    │  Data:         │
│  ├ Text      │  │                  │    │  ${ticket}     │
│  ├ Image     │  │  ┌────┐ ┌────┐   │    │                │
│  └ Badge     │  │  │Card│ │Card│   │    │  [Sil] [Kopyala]│
│              │  │  └────┘ └────┘   │    │                │
│  Form        │  │                  │    │                │
│  ├ Input     │  └──────────────────┘    │                │
│  ├ Select    │                          │                │
│  └ Button    │                          │                │
│              │                          │                │
│  Data        │                          │                │
│  ├ Table     │                          │                │
│  ├ Chart     │                          │                │
│  └ List      │                          │                │
└──────────────┴──────────────────────────┴────────────────┘
14.2. Drag-Drop
Component palette → canvas'a sürükle

Reorder → sürükle-bırak

Nest → içine sürükle

Resize → kenardan çek

Duplicate → Ctrl+D

14.3. Live Preview
Real-time render

Real data (ya da örnek veri)

Responsive önizleme

Dark mode önizleme

14.4. Undo/Redo
yaml
history:
  - action: "add_component"
    component: "card"
  - action: "move"
    from: 0
    to: 2
  - action: "delete"
    id: "card-1"
Kural: Sınırsız undo/redo (history).

14.5. Templates
Hazır ekran şablonları:

Blank — Sıfırdan

List — Standart liste

Form — Standart form

Detail — Detay sayfası

Dashboard — KPI + chart

Kanban — Board

KDS — Mutfak

Map — Harita

Kiosk — Kiosk mode

14.6. AI-Assisted Builder
Kullanıcı doğal dil: "Mutfak için sipariş ekranı yap — masa no büyük, ürün listesi, süre timer'ı, hazır butonu"

AI: YAML üretir (yukarıdaki KDS örneği gibi).

Kullanıcı: Önizler, düzenler, kaydeder.

15. Runtime Render
15.1. Server-Side Render
php
class ScreenRenderer {
    public function render(Screen $screen, array $context): string {
        // 1. Component tree
        $tree = $screen->components;
        
        // 2. Data binding
        $data = $this->resolveData($screen->data, $context);
        
        // 3. Conditional evaluation
        $tree = $this->evaluateConditions($tree, $data, $context);
        
        // 4. HTML render
        return $this->renderComponents($tree, $data, $context);
    }
}
15.2. Client-Side Render
javascript
// screen.js
class ScreenRenderer {
    render(screen, data) {
        const tree = this.evaluate(screen.components, data);
        return this.renderTree(tree);
    }
    
    evaluate(components, data) {
        return components
            .filter(c => this.conditionMet(c, data))
            .map(c => this.renderComponent(c, data));
    }
}
15.3. Hybrid (SSR + Hydration)
İlk render → server (SEO + hızlı)

Sonrası → client (interaktif)

Real-time → WebSocket (canlı)

15.4. Component Registry
php
class ComponentRegistry {
    private array $components = [];
    
    public function register(string $key, string $class): void {
        $this->components[$key] = $class;
    }
    
    public function render(string $key, array $props): string {
        $class = $this->components[$key] ?? throw new UnknownComponentException($key);
        return app($class)->render($props);
    }
}
16. Testing
16.1. Screen Definition Test
php
public function test_screen_definition_validates(): void {
    $definition = [
        'key' => 'test',
        'name' => 'Test',
        'type' => 'page',
        'components' => [
            ['type' => 'text', 'props' => ['content' => 'Hello']],
        ],
    ];
    
    $result = ScreenValidator::validate($definition);
    $this->assertTrue($result->isValid());
}

public function test_rejects_unknown_component(): void {
    $this->expectException(InvalidScreenException::class);
    
    ScreenValidator::validate([
        'components' => [['type' => 'nonexistent']],
    ]);
}
16.2. Render Test
php
public function test_screen_renders(): void {
    $screen = Screen::factory()->create([
        'definition' => [...],
    ]);
    
    $html = app(ScreenRenderer::class)->render($screen, []);
    
    $this->assertStringContainsString('Hello', $html);
}
16.3. Data Binding Test
php
public function test_data_binding_resolves(): void {
    $screen = Screen::factory()->create([
        'data' => [
            'customers' => ['source' => 'entity', 'entity' => 'fin_customer'],
        ],
    ]);
    
    $result = app(ScreenRenderer::class)->resolveData($screen, []);
    
    $this->assertArrayHasKey('customers', $result);
}
16.4. Permission Test
php
public function test_screen_respects_permission(): void {
    $screen = Screen::factory()->create([
        'permissions' => ['view' => ['role:admin']],
    ]);
    
    $this->actingAs($regularUser);
    
    $this->expectException(ForbiddenException::class);
    app(ScreenRenderer::class)->render($screen, []);
}
16.5. Visual Regression Test
php
public function test_screen_visual(): void {
    $screen = Screen::factory()->create();
    
    $this->browse(function (Browser $browser) use ($screen) {
        $browser->visit("/screens/{$screen->key}")
            ->assertScreenshot('kds-default.png');
    });
}
Kural: Her ekran snapshot test ile korunur.

17. Performance
17.1. Lazy Loading
Component lazy load — İlk görünür olana kadar

Image lazy load — Intersection Observer

Route lazy load — Sayfa açıldığında

17.2. Caching
Definition cache — Screen YAML → compiled JSON (5 dk)

Data cache — Sorgu sonucu (opsiyonel)

HTML cache — Static screen (opsiyonel)

17.3. Bundle Size
Tree shaking — Sadece kullanılan component'ler

Code splitting — Sayfa başına bundle

Minification — Production build

Hedef: İlk yükleme < 500 KB.

17.4. Render Performance
Ölçek	Strateji
< 50 component	SSR + hydration
50-200	Virtual scroll
> 200	Pagination + lazy
17.5. Real-time
WebSocket (Laravel Reverb / Pusher)

Optimistic update — UI önce, server sonra

Conflict resolution — Server wins

18. Security
18.1. XSS Prevention
Auto-escape — Her text binding escape

HTML whitelist — Sadece izinli tag

Raw HTML yasak — dangerouslySetInnerHTML yok

yaml
# ❌ Yasak
text:
  content: "<script>alert('xss')</script>"
  raw: true

# ✅ Doğru
text:
  content: "@t('safe.text')"            # escape
18.2. Sandbox
Script node — Sadece JS, sandbox

Custom component — Shadow DOM

Iframe — Sandbox attribute

18.3. Permission
Screen-level — Kim açabilir

Component-level — Kim görebilir

Action-level — Kim yapabilir

yaml
screen:
  permissions:
    view: ["role:admin"]
  components:
    - type: "button"
      props:
        label: "Sil"
        visible_if: "user.can('delete')"  # runtime check
18.4. Data Scope
Her data binding otomatik data scope uygular (Belge 06).

18.5. CSP (Content Security Policy)
text
Content-Security-Policy: 
    default-src 'self';
    script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
    style-src 'self' 'unsafe-inline';
    img-src 'self' data: https:;
19. Restoran Örneği — Table Map
19.1. Absolute Layout
yaml
screen:
  key: "restaurant-table-map"
  name: "Masa Haritası"
  type: "custom"
  layout:
    type: "absolute"
    width: 1200
    height: 800
    background_image: "${tenant.floor_plan_url}"
  
  data:
    tables:
      source: "entity"
      entity: "restaurant_table"
      filter: "branch_id = ${params.branch_id}"
      realtime: true
  
  components:
    - type: "repeat"
      data: "${tables}"
      key: "${table.id}"
      
      children:
        - type: "table_widget"
          props:
            x: "${table.position_x}"
            y: "${table.position_y}"
            width: "${table.width}"
            height: "${table.height}"
            rotation: "${table.rotation}"
            shape: "${table.shape}"        # round | square | rectangle
            label: "${table.table_number}"
            capacity: "${table.capacity}"
            status: "${table.status}"      # free | occupied | reserved | cleaning
            
            color:
              free: "#10b981"
              occupied: "#ef4444"
              reserved: "#f59e0b"
              cleaning: "#6b7280"
            
            click_action:
              type: "navigate"
              route: "/restaurant/tables/${table.id}"
19.2. Görünüm
text
┌──────────────────────────────────────────────────────────┐
│  Masa Haritası — Şube 1                                  │
├──────────────────────────────────────────────────────────┤
│                                                          │
│    ┌───┐  ┌───┐  ┌───┐                                  │
│    │T-1│  │T-2│  │T-3│    ← yeşil (boş)                  │
│    └───┘  └───┘  └───┘                                  │
│                                                          │
│    ┌───┐  ┌───┐  ┌───┐                                  │
│    │T-4│  │T-5│  │T-6│    ← kırmızı (dolu)               │
│    └───┘  └───┘  └───┘                                  │
│                                                          │
│    ┌───┐  ┌───┐  ┌───┐                                  │
│    │T-7│  │T-8│  │T-9│    ← sarı (rezerve)               │
│    └───┘  └───┘  └───┘                                  │
│                                                          │
│  Lejant: 🟢 Boş  🔴 Dolu  🟡 Rezerve  ⚫ Temizlik      │
└──────────────────────────────────────────────────────────┘
19.3. Interaction
Tıkla → masa detayı

Uzun bas → hızlı işlem menüsü (sipariş, rezervasyon, temizlik)

Sürükle → masa pozisyonu değiştir (admin)

Renk → gerçek zamanlı (WebSocket)

19.4. KDS ile Birlikte
Akış:

text
1. Müşteri masaya oturur
2. Waiter: masa haritasından masayı seçer
3. Sipariş alır → custom_order created
4. KDS: yeni ticket (Belge 12 — Report)
5. Chef: hazırla → "Hazır" butonu
6. Table map: masa rengi değişir (yellow → ready)
7. Waiter: servis eder
8. Müşteri ödeme yapar → masa free
Tamamı UI Builder + workflow + event bus ile.

20. Anti-Patterns
Bu projede yasak olan UI hataları:

Hardcoded screen — Kod içi ekran yasak

eval() in script — Sadece JS, sandbox

PHP script node — JS zorunlu

Raw HTML injection — Auto-escape zorunlu

No permission check — Her screen + component

No data scope — Otomatik

SELECT * in binding — Sadece gerekli

No lazy load — Büyük ekranlarda

No responsive — Her ekran mobile uyumlu

No dark mode — Her component

No i18n — Her metin çevrilebilir

Silent failure — Hata göster

Infinite loop — Repeat limit

Deep nesting — Max 10 seviye

Screen delete (kullanımda) — Sadece deprecate

Sandbox escape — Custom component izole

21. Roadmap
Aşama	Süre	Kapsam
1. Foundation	6 hafta	Screen entity, YAML parser, validator
2. Components (basic)	8 hafta	30 component (text, button, input, card...)
3. Layouts	4 hafta	Grid, flex, absolute
4. Data binding	4 hafta	Entity, query, computed
5. Actions	3 hafta	CRUD, navigate, modal
6. Visual Builder	10 hafta	Drag-drop, live preview, templates
7. Components (advanced)	6 hafta	Charts, data_table, kanban, canvas
8. Custom component	4 hafta	Web Component, sandbox
9. AI-assisted	4 hafta	Natural language → YAML
Toplam: 49 hafta (~12 ay).

22. Glossary
Terim	Anlam
UI Builder	Görsel ekran tasarımcısı
Screen	Ekran tanımı
Component	UI bileşeni
Layout	Düzen (grid, flex, absolute)
Data Binding	Veri bağlama
Action Binding	Aksiyon bağlama
Theme	Tema (renk, font)
Responsive	Cihaza uyum
Inheritance	Ekran mirası
Custom Component	Kullanıcı bileşeni
Sandbox	İzole ortam
Visual Builder	Drag-drop tasarımcı
Live Preview	Anlık önizleme
Screen Template	Hazır ekran
Kiosk Mode	Kısıtlı mod
23. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — UI builder, restoran masa haritası örneği