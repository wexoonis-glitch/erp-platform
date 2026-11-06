# 11 — Workflow Engine

**Project:** Kurumsal ERP Platform
**Belge:** Görsel İş Akışı Motoru, Onay Süreçleri, Otomasyon, State Machine
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — henüz kodlanmadı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **Workflow Engine**'ini tanımlar — kullanıcının **kod yazmadan görsel iş akışları** tasarlayabildiği, çalıştırabildiği ve izleyebildiği motor. İçerir:

- Workflow nedir, neden gerekli
- 3 tür workflow (state machine, approval, automation)
- Görsel builder (drag-drop)
- Workflow tanımlama dili (YAML)
- Node tipleri (start, task, gateway, service, end)
- Transition koşulları
- Runtime engine (execution)
- State machine
- Approval flow
- Automation rules
- Workflow + event bus entegrasyonu
- Custom entity + workflow
- Versioning, testing, monitoring
- Restoran örneği — sipariş onay akışı

**Hedef kitle:** Geliştiriciler, AI asistanlar, ürün mimarları, business analistler.

**Kritik:** Workflow engine, **SAP'nin en karmaşık kısmı**. SAP'de "workflow" = ABAP + danışman. Bizde = tıkla + sürükle.

---

## 2. Neden Workflow Engine?

### 2.1. Klasik ERP'nin Dayatması

Her ERP **sabit iş akışı** dayatır:

| ERP | Sabit Akış |
|---|---|
| SAP | PO → onay → GR → invoice → payment (5 adım, sabit) |
| Odoo | SO → confirm → deliver → invoice (4 adım) |
| Logo | Sipariş → onay → sevk → fatura (3 adım) |

**Sorun:** Her müşterinin **kendi iş akışı** var. Sabit akış = **özel geliştirme**.

### 2.2. Gerçek Dünya Farklı

| Sektör | İş Akışı |
|---|---|
| **KOBİ** | Sipariş → sahip onayı → sevk |
| **Kurumsal** | Sipariş → satış müdürü → finans müdürü → genel müdür → sevk |
| **Klinik** | Randevu → hemşire → doktor → eczane |
| **Restoran** | Sipariş → mutfak → garson → kasiyer |
| **İnşaat** | Talep → şantiye şefi → satın alma → finans → onay |
| **Franchise** | Sipariş → şube müdürü → bölge müdürü → merkez |

**Her biri farklı.** Sabit akış = 6 ay geliştirme.

### 2.3. Mevcut Çözüm Yetersiz

| Yaklaşım | Sorun |
|---|---|
| **Sabit workflow** | Müşteriye uymaz |
| **Kod içi if/else** | Bakım kabusu |
| **Configuration file** | Hâlâ kod gerek |
| **BPMN tool** | Ağır, öğrenme eğrisi |

**Bizim çözüm:** **Görsel workflow engine** — kullanıcı sürükle-bırak, platform çalıştırır.

---

## 3. Workflow Nedir?

### 3.1. Tanım

**Workflow** = bir **iş sürecinin** adım adım tanımı. İçerir:

- **Adımlar** (nodes) — ne yapılacak
- **Geçişler** (transitions) — hangi koşulda bir sonraki adım
- **Aktörler** (actors) — kim yapar
- **Veri** (context) — hangi veri taşınır
- **Süre** (SLA) — ne kadar sürede
- **Eskalasyon** (escalation) — gecikirse ne olur

### 3.2. 3 Tür Workflow

| Tür | Ne | Örnek |
|---|---|---|
| **State Machine** | Entity'nin durum geçişi | Fatura: draft → posted → paid |
| **Approval Flow** | Onay zinciri | İzin talebi: çalışan → müdür → İK |
| **Automation** | Olay → aksiyon | "Stok azaldı → PO oluştur" |

**Kural:** Üçü **birlikte** çalışabilir. Örneğin state machine + approval + automation aynı entity'de.

### 3.3. Workflow vs Event Bus

| Kavram | Ne |
|---|---|
| **Event Bus** | "Ne oldu" yayını (Belge 08) |
| **Workflow** | "Ne yapılacak" orkestrasyon |

**Birlikte:**
Event: order.created
↓
Workflow: Order Approval (başlar)
↓ node 1
Approval: sales_manager
↓ (onaylandı)
↓ node 2
Approval: finance_manager
↓ (onaylandı)
↓ node 3
Service: create_invoice
↓ (tamamlandı)
↓ end
Event: order.approved

text

**Kural:** Workflow **event'lerle konuşur** — event tetikler, event dinler.

---

## 4. Workflow Tanımlama Dili (YAML)

### 4.1. Tam Yapı

```yaml
workflow:
  # ═══════════════ ZORUNLU ═══════════════
  key: "order-approval"
  name: "Sipariş Onay Akışı"
  version: "1.0.0"
  type: "approval"                       # state_machine | approval | automation
  
  # ═══════════════ TETİKLEYİCİ ═══════════════
  trigger:
    event: "otc.sales_order.created"
    condition: "order.total > 10000"     # opsiyonel
  
  # ═══════════════ DEĞİŞKENLER ═══════════════
  variables:
    - { key: "order_id", type: "uuid", required: true }
    - { key: "order_total", type: "decimal" }
    - { key: "customer_id", type: "uuid" }
    - { key: "approved_by", type: "user_id", nullable: true }
  
  # ═══════════════ NODES ═══════════════
  nodes:
    - key: "start"
      type: "start"
      next: "sales_manager_approval"
    
    - key: "sales_manager_approval"
      type: "approval"
      assignee:
        type: "role"
        value: "sales_manager"
        scope: "layer"
      sla: "4h"
      escalation:
        after: "4h"
        to: "general_manager"
      next_if:
        approved: "finance_manager_approval"
        rejected: "rejected_end"
    
    - key: "finance_manager_approval"
      type: "approval"
      assignee:
        type: "role"
        value: "finance_manager"
      sla: "8h"
      next_if:
        approved: "create_invoice"
        rejected: "rejected_end"
    
    - key: "create_invoice"
      type: "service"
      handler: "InvoiceService::createFromOrder"
      params:
        order_id: "${order_id}"
      next: "notify_customer"
    
    - key: "notify_customer"
      type: "service"
      handler: "NotificationService::notify"
      params:
        user_id: "${customer_id}"
        title: "Sipariş onaylandı"
        body: "Sipariş ${order_id} onaylandı"
      next: "approved_end"
    
    - key: "approved_end"
      type: "end"
      outcome: "approved"
    
    - key: "rejected_end"
      type: "end"
      outcome: "rejected"
      on_enter:
        - "CancelOrderService::cancel"      # otomatik iptal
  
  # ═══════════════ SLA & ESCALATION ═══════════════
  sla:
    total: "24h"
    warning_at: "18h"
    escalation:
      after: "24h"
      to: "admin"
      action: "notify"
4.2. Minimal Örnek
yaml
workflow:
  key: "expense-approval"
  name: "Masraf Onayı"
  type: "approval"
  trigger:
    event: "hrm.expense_claim.submitted"
  nodes:
    - { key: "start", type: "start", next: "manager_approval" }
    - key: "manager_approval"
      type: "approval"
      assignee: { type: "role", value: "manager" }
      next_if: { approved: "end_approved", rejected: "end_rejected" }
    - { key: "end_approved", type: "end", outcome: "approved" }
    - { key: "end_rejected", type: "end", outcome: "rejected" }
Bu 12 satır = tam bir onay akışı.

5. Node Tipleri
5.1. Start Node
Workflow başlangıcı. Her workflow'un tam 1 tane start node'u olmalı.

yaml
- key: "start"
  type: "start"
  next: "first_node"
5.2. End Node
Workflow bitişi. Birden fazla end node olabilir (outcome'a göre).

yaml
- key: "end_approved"
  type: "end"
  outcome: "approved"
  on_enter:
    - "NotificationService::notify_approved"
5.3. Task Node (User Task)
Kullanıcı görevi. Bir kişi/rol tarafından tamamlanır.

yaml
- key: "review"
  type: "task"
  name: "Sipariş İncele"
  assignee:
    type: "user"                     # user | role | group | dynamic
    value: "user-uuid"
    # veya
    type: "role"
    value: "sales_manager"
    scope: "layer"                   # kendi katmanında
  form:
    entity: "otc_sales_order"
    fields: ["customer_id", "total", "notes"]
  actions:                           # kullanıcının yapabilecekleri
    - { key: "approve", label: "Onayla", next: "next_node" }
    - { key: "reject", label: "Reddet", next: "rejected_end" }
    - { key: "request_info", label: "Bilgi İste", next: "waiting_info" }
  sla: "4h"
5.4. Approval Node
Onay görevi. Task'ın özel hali.

yaml
- key: "approval"
  type: "approval"
  name: "Onay"
  assignee:
    type: "role"
    value: "manager"
  threshold: 1                       # kaç onay gerekli
  # veya
  approval_mode: "any"               # any | all | majority
  next_if:
    approved: "next"
    rejected: "rejected_end"
Approval modları:

Mod	Anlam
any	1 onay yeterli
all	Herkes onaylamalı
majority	%50+1 onay
quorum	Belirli sayı (threshold)
5.5. Service Node
Otomatik servis çağrısı. Kullanıcı müdahalesi yok.

yaml
- key: "create_invoice"
  type: "service"
  handler: "InvoiceService::createFromOrder"
  params:
    order_id: "${order_id}"
    auto_post: true
  retry:
    max_attempts: 3
    backoff: "exponential"
  timeout: "30s"
  on_error:
    - "NotificationService::notify_admin"
    - "next": "error_end"
5.6. Gateway Node (Conditional)
Koşullu dallanma. Karar noktası.

yaml
- key: "amount_check"
  type: "gateway"
  gateway_type: "exclusive"           # exclusive | inclusive | parallel
  conditions:
    - when: "order_total < 1000"
      next: "auto_approve"
    - when: "order_total < 10000"
      next: "manager_approval"
    - when: "order_total < 100000"
      next: "director_approval"
    - default: "ceo_approval"
Gateway tipleri:

Tip	Anlam
exclusive	Sadece 1 dal (ilk eşleşen)
inclusive	Birden fazla dal (tüm eşleşen)
parallel	Hepsi paralel
event_based	Event bekleyerek
5.7. Timer Node
Bekleme. Belirli süre sonra devam.

yaml
- key: "wait_24h"
  type: "timer"
  duration: "24h"
  next: "check_status"
Kullanım: SLA bekleme, gecikme, hatırlatma.

5.8. Sub-Process Node
Alt workflow çağırma.

yaml
- key: "sub_invoice"
  type: "sub_process"
  workflow: "invoice-creation"
  params:
    order_id: "${order_id}"
  next: "notify"
5.9. Message Node
Event yayınlama.

yaml
- key: "notify_event"
  type: "message"
  event: "order.approved"
  payload:
    order_id: "${order_id}"
    approved_by: "${approved_by}"
  next: "end"
5.10. Script Node
Küçük kod (sandbox'ta).

yaml
- key: "calculate"
  type: "script"
  language: "javascript"             # sadece JS (güvenli)
  code: |
    const discount = variables.order_total > 1000 ? 0.1 : 0;
    return { discount };
  next: "next"
Kısıt: Sadece JavaScript (sandbox). PHP yasak (güvenlik).

6. Transition (Geçiş)
6.1. Basit Geçiş
yaml
- key: "node_a"
  next: "node_b"
6.2. Koşullu Geçiş
yaml
- key: "node_a"
  next_if:
    condition_1: "node_b"
    condition_2: "node_c"
    default: "node_d"
6.3. Koşul İfadesi
Expression language:

text
order.total > 1000
AND customer.vip = true
OR user.role = 'admin'
Operatörler:

Operatör	Anlam
= !=	Eşit / değil
> < >= <=	Karşılaştırma
AND OR NOT	Mantık
IN NOT IN	Liste
CONTAINS	İçerir
IS NULL IS NOT NULL	Null
MATCHES	Regex
6.4. Koşul Değişkenleri
Değişken	Kaynak
${entity.field}	Entity verisi
${variables.x}	Workflow variable
${user.id}	Aktif kullanıcı
${now}	Şu an
${event.payload.x}	Event data
${env.X}	Environment
7. Runtime Engine
7.1. Workflow Lifecycle
text
1. Trigger (event) → workflow instance oluştur
2. Start node → ilk node aktif
3. Node execute → sonuç
4. Transition → sonraki node
5. ... tekrar
6. End node → workflow biter
7.2. Workflow Instance
sql
CREATE TABLE wf_instance (
    id              uuid PRIMARY KEY,
    workflow_id     uuid NOT NULL REFERENCES wf_definition(id),
    workflow_version int NOT NULL,
    tenant_id       uuid NOT NULL REFERENCES tenants(id),
    aggregate_type  varchar(80) NOT NULL,
    aggregate_id    uuid NOT NULL,
    status          varchar(20) DEFAULT 'active',  -- active | completed | cancelled | failed
    current_node    varchar(80),
    variables       jsonb NOT NULL,
    started_at      timestamptz NOT NULL DEFAULT now(),
    completed_at    timestamptz NULL,
    created_by      bigint REFERENCES users(id)
);

CREATE INDEX idx_wf_instance_tenant ON wf_instance(tenant_id, status);
CREATE INDEX idx_wf_instance_aggregate ON wf_instance(aggregate_type, aggregate_id);
CREATE INDEX idx_wf_instance_active ON wf_instance(status) WHERE status = 'active';

-- RLS
ALTER TABLE wf_instance ENABLE ROW LEVEL SECURITY;
ALTER TABLE wf_instance FORCE ROW LEVEL SECURITY;
CREATE POLICY tenant_isolation ON wf_instance
    USING (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    )
    WITH CHECK (
        current_setting('app.is_superadmin', true) = 'true'
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    );
7.3. Node Instance (History)
sql
CREATE TABLE wf_node_instance (
    id              uuid PRIMARY KEY,
    instance_id     uuid NOT NULL REFERENCES wf_instance(id) ON DELETE CASCADE,
    node_key        varchar(80) NOT NULL,
    node_type       varchar(50) NOT NULL,
    status          varchar(20) DEFAULT 'pending',  -- pending | active | completed | skipped | failed
    assigned_to     bigint NULL,
    input_data      jsonb,
    output_data     jsonb,
    started_at      timestamptz,
    completed_at    timestamptz,
    timeout_at      timestamptz
);
7.4. Execution Engine
php
class WorkflowEngine {
    public function start(string $workflowKey, $aggregate, array $context): WfInstance {
        $definition = WorkflowDefinition::findActive($workflowKey);
        
        $instance = WfInstance::create([
            'id' => Str::uuid(),
            'workflow_id' => $definition->id,
            'workflow_version' => $definition->version,
            'tenant_id' => TenantContext::id(),
            'aggregate_type' => get_class($aggregate),
            'aggregate_id' => $aggregate->id,
            'variables' => $context,
            'status' => 'active',
        ]);
        
        // Start node'dan başla
        $this->executeNode($instance, $definition->start_node);
        
        return $instance;
    }
    
    public function executeNode(WfInstance $instance, string $nodeKey): void {
        $definition = $instance->definition;
        $node = $definition->node($nodeKey);
        
        // Node instance oluştur
        $nodeInstance = WfNodeInstance::create([
            'instance_id' => $instance->id,
            'node_key' => $nodeKey,
            'node_type' => $node['type'],
            'status' => 'active',
            'started_at' => now(),
        ]);
        
        $instance->update(['current_node' => $nodeKey]);
        
        // Node tipine göre işle
        match ($node['type']) {
            'start' => $this->handleStart($instance, $node, $nodeInstance),
            'task' => $this->handleTask($instance, $node, $nodeInstance),
            'approval' => $this->handleApproval($instance, $node, $nodeInstance),
            'service' => $this->handleService($instance, $node, $nodeInstance),
            'gateway' => $this->handleGateway($instance, $node, $nodeInstance),
            'timer' => $this->handleTimer($instance, $node, $nodeInstance),
            'message' => $this->handleMessage($instance, $node, $nodeInstance),
            'script' => $this->handleScript($instance, $node, $nodeInstance),
            'end' => $this->handleEnd($instance, $node, $nodeInstance),
        };
    }
    
    public function completeNode(WfInstance $instance, string $nodeKey, string $outcome, array $data = []): void {
        $node = $instance->definition->node($nodeKey);
        $nodeInstance = $instance->nodeInstances()->where('node_key', $nodeKey)->where('status', 'active')->first();
        
        $nodeInstance->update([
            'status' => 'completed',
            'output_data' => $data,
            'completed_at' => now(),
        ]);
        
        // Sonraki node'u belirle
        $nextNode = $this->resolveNext($node, $outcome, $instance, $data);
        
        if ($nextNode) {
            $this->executeNode($instance, $nextNode);
        }
    }
    
    private function resolveNext(array $node, string $outcome, WfInstance $instance, array $data): ?string {
        // 1. next_if (outcome-based)
        if (isset($node['next_if'][$outcome])) {
            return $node['next_if'][$outcome];
        }
        
        // 2. next (linear)
        if (isset($node['next'])) {
            return $node['next'];
        }
        
        // 3. End
        return null;
    }
}
7.5. Service Node Execution
php
private function handleService(WfInstance $instance, array $node, WfNodeInstance $nodeInstance): void {
    try {
        $params = $this->resolveParams($node['params'] ?? [], $instance);
        
        // Handler çağır
        [$class, $method] = explode('::', $node['handler']);
        $result = app($class)->$method(...$params);
        
        // Tamamlandı
        $this->completeNode($instance, $node['key'], 'success', ['result' => $result]);
    } catch (\Throwable $e) {
        // Retry logic
        if ($this->shouldRetry($nodeInstance, $node)) {
            $this->scheduleRetry($nodeInstance, $node);
        } else {
            $this->failNode($instance, $node['key'], $e);
        }
    }
}
7.6. Approval Node Execution
php
private function handleApproval(WfInstance $instance, array $node, WfNodeInstance $nodeInstance): void {
    // Atanan kişileri belirle
    $assignees = $this->resolveAssignees($node['assignee'], $instance);
    
    // Her biri için task oluştur
    foreach ($assignees as $userId) {
        WfTask::create([
            'id' => Str::uuid(),
            'instance_id' => $instance->id,
            'node_key' => $node['key'],
            'assignee_id' => $userId,
            'status' => 'pending',
            'sla_deadline' => now()->add($node['sla'] ?? '24h'),
        ]);
    }
    
    // Notification gönder
    event(new ApprovalRequested($instance, $node, $assignees));
}

// Kullanıcı onayladığında
public function approve(string $taskId, string $outcome = 'approved', string $comment = ''): void {
    $task = WfTask::find($taskId);
    $task->update([
        'status' => $outcome === 'approved' ? 'approved' : 'rejected',
        'comment' => $comment,
        'completed_at' => now(),
    ]);
    
    // Approval mode kontrolü
    $instance = $task->instance;
    $node = $instance->definition->node($task->node_key);
    $mode = $node['approval_mode'] ?? 'any';
    
    if ($this->allApprovalsComplete($instance, $task->node_key, $mode)) {
        $finalOutcome = $this->resolveApprovalOutcome($instance, $task->node_key);
        $this->completeNode($instance, $task->node_key, $finalOutcome);
    }
}
7.7. Timer Node Execution
php
private function handleTimer(WfInstance $instance, array $node, WfNodeInstance $nodeInstance): void {
    $duration = $this->parseDuration($node['duration']);
    
    // Job schedule
    ProcessWorkflowTimer::dispatch($instance->id, $node['key'])
        ->delay(now()->addSeconds($duration));
    
    // Node bekleme
    $nodeInstance->update(['status' => 'waiting']);
}

class ProcessWorkflowTimer implements ShouldQueue {
    public function handle(): void {
        $instance = WfInstance::find($this->instanceId);
        $engine = app(WorkflowEngine::class);
        $engine->completeNode($instance, $this->nodeKey, 'completed');
    }
}
7.8. Gateway Execution
php
private function handleGateway(WfInstance $instance, array $node, WfNodeInstance $nodeInstance): void {
    $gatewayType = $node['gateway_type'] ?? 'exclusive';
    
    $matched = [];
    foreach ($node['conditions'] ?? [] as $condition) {
        if ($this->evaluateCondition($condition['when'], $instance)) {
            $matched[] = $condition['next'];
        }
    }
    
    // Default
    if (empty($matched) && isset($node['default'])) {
        $matched[] = $node['default'];
    }
    
    match ($gatewayType) {
        'exclusive' => $this->executeExclusive($instance, $matched[0] ?? null),
        'inclusive' => $this->executeInclusive($instance, $matched),
        'parallel' => $this->executeParallel($instance, $matched),
    };
}

private function executeExclusive(WfInstance $instance, ?string $next): void {
    if ($next) $this->executeNode($instance, $next);
    else $this->failWorkflow($instance, 'No matching condition');
}

private function executeParallel(WfInstance $instance, array $nodes): void {
    foreach ($nodes as $node) {
        dispatch(fn() => $this->executeNode($instance, $node));
    }
}
7.9. Condition Evaluation
php
class ExpressionEvaluator {
    public function evaluate(string $expression, WfInstance $instance): bool {
        // Değişkenleri resolve et
        $expression = preg_replace_callback('/\$\{([^}]+)\}/', function ($m) use ($instance) {
            return $this->resolveVariable($m[1], $instance);
        }, $expression);
        
        // Güvenli evaluate (sadece JS-like)
        return (bool) eval("return {$expression};");
    }
    
    private function resolveVariable(string $path, WfInstance $instance): mixed {
        $parts = explode('.', $path);
        
        // Entity verisi
        if ($parts[0] === 'entity') {
            return data_get($instance->aggregate, implode('.', array_slice($parts, 1)));
        }
        
        // Variable
        if ($parts[0] === 'variables') {
            return data_get($instance->variables, implode('.', array_slice($parts, 1)));
        }
        
        // User
        if ($parts[0] === 'user') {
            return data_get(auth()->user(), implode('.', array_slice($parts, 1)));
        }
        
        // Now
        if ($parts[0] === 'now') {
            return now();
        }
        
        return null;
    }
}
8. State Machine
8.1. State Machine Nedir?
Entity'nin durum geçişi. Örneğin fatura:

text
draft → posted → paid → closed
         ↓
      cancelled
8.2. State Machine Tanımı
yaml
workflow:
  key: "invoice-lifecycle"
  name: "Fatura Yaşam Döngüsü"
  type: "state_machine"
  entity: "fin_ar_invoice"
  
  states:
    - key: "draft"
      name: "Taslak"
      initial: true
      transitions:
        - to: "posted"
          action: "post"
          permission: "fin.ar_invoice.post"
          condition: "invoice.total > 0"
          on_enter:
            - "JournalService::postInvoice"
            - "event: invoice.posted"
        
        - to: "cancelled"
          action: "cancel"
          permission: "fin.ar_invoice.cancel"
    
    - key: "posted"
      name: "Kesildi"
      transitions:
        - to: "paid"
          trigger: "payment.received"     # event tetikli
          condition: "invoice.paid_total >= invoice.total"
        
        - to: "cancelled"
          action: "cancel"
          permission: "fin.ar_invoice.cancel"
          condition: "invoice.paid_total = 0"
    
    - key: "paid"
      name: "Ödendi"
      transitions:
        - to: "closed"
          action: "close"
          permission: "fin.ar_invoice.close"
    
    - key: "cancelled"
      name: "İptal"
      final: true
    
    - key: "closed"
      name: "Kapandı"
      final: true
8.3. State Machine Runtime
php
class StateMachine {
    public function transition($entity, string $action): void {
        $currentState = $entity->status;
        $transition = $this->findTransition($entity, $currentState, $action);
        
        if (!$transition) {
            throw new InvalidTransitionException("Cannot {$action} from {$currentState}");
        }
        
        // Permission check
        if (!$this->canTransition($transition)) {
            throw new ForbiddenException();
        }
        
        // Condition check
        if (!$this->evaluateCondition($transition['condition'] ?? 'true', $entity)) {
            throw new ConditionNotMetException();
        }
        
        // on_exit (current state)
        $this->runHooks($transition['on_exit'] ?? [], $entity);
        
        // Transition
        $entity->update(['status' => $transition['to']]);
        
        // on_enter (new state)
        $this->runHooks($transition['on_enter'] ?? [], $entity);
        
        // Audit
        event(new StateTransitioned($entity, $currentState, $transition['to'], $action));
    }
}
8.4. Kullanım
php
// Controller
class InvoiceController {
    public function post(string $id) {
        $invoice = FinArInvoice::findOrFail($id);
        app(StateMachine::class)->transition($invoice, 'post');
        return new InvoiceResource($invoice->fresh());
    }
}
Endpoint:

text
POST /api/v1/fin/ar-invoices/{id}/post
POST /api/v1/fin/ar-invoices/{id}/cancel
POST /api/v1/fin/ar-invoices/{id}/close
Kural: State machine API endpoint olarak açılır. Her action = 1 endpoint.

9. Approval Flow
9.1. Onay Süreci
yaml
workflow:
  key: "expense-approval"
  name: "Masraf Onayı"
  type: "approval"
  trigger:
    event: "hrm.expense_claim.submitted"
  
  nodes:
    - { key: "start", type: "start", next: "amount_check" }
    
    - key: "amount_check"
      type: "gateway"
      gateway_type: "exclusive"
      conditions:
        - when: "entity.amount <= 1000"
          next: "auto_approve"
        - when: "entity.amount <= 10000"
          next: "manager_approval"
        - default: "director_approval"
    
    - key: "auto_approve"
      type: "service"
      handler: "ExpenseService::autoApprove"
      next: "end_approved"
    
    - key: "manager_approval"
      type: "approval"
      assignee:
        type: "role"
        value: "manager"
        scope: "own_department"
      sla: "24h"
      next_if:
        approved: "end_approved"
        rejected: "end_rejected"
    
    - key: "director_approval"
      type: "approval"
      assignee:
        type: "role"
        value: "director"
      sla: "48h"
      escalation:
        after: "48h"
        to: "ceo"
      next_if:
        approved: "end_approved"
        rejected: "end_rejected"
    
    - { key: "end_approved", type: "end", outcome: "approved" }
    - { key: "end_rejected", type: "end", outcome: "rejected" }
9.2. Approval UI
Kullanıcı görevi:

text
┌──────────────────────────────────────────────────────────┐
│  Onay Talebi                                              │
│  Masraf #EXP-001 — 5.000 TL                              │
├──────────────────────────────────────────────────────────┤
│  Talep Eden: Ahmet Yılmaz (Satış Temsilcisi)             │
│  Kategori: Yol Masrafı                                    │
│  Tarih: 06.11.2026                                        │
│  Açıklama: İstanbul-Ankara uçak bileti                    │
│                                                          │
│  Ekler: [fiş.pdf]                                        │
│                                                          │
│  SLA: 18 saat kaldı (24 saat toplam)                    │
│                                                          │
│  [Yorum: ___________]                                    │
│                                                          │
│  [❌ Reddet]  [💬 Bilgi İste]  [✅ Onayla]              │
└──────────────────────────────────────────────────────────┘
9.3. Approval Modes
Herkes onaylamalı (all):

yaml
- key: "board_approval"
  type: "approval"
  assignee:
    type: "group"
    value: "board_members"             # 3 kişi
  approval_mode: "all"
  threshold: 3
Çoğunluk (majority):

yaml
- key: "committee_approval"
  type: "approval"
  assignee: { type: "group", value: "committee" }
  approval_mode: "majority"
  # 5 kişiden 3'ü
Sıralı onay:

yaml
# Node 1: manager
# Node 2: director (manager onayladıktan sonra)
# Node 3: ceo (director onayladıktan sonra)
9.4. Delegasyon
Kullanıcı tatilde → yerine vekil:

yaml
approval:
  assignee: { type: "role", value: "manager" }
  delegation:
    enabled: true
    fallback_to: "assistant_manager"
Veya dinamik:

php
class DelegationResolver {
    public function resolve(int $userId): int {
        $user = User::find($userId);
        
        if ($user->is_on_vacation) {
            return $user->delegate_id;
        }
        
        return $userId;
    }
}
9.5. Escalation
SLA aşılırsa → üstüne gönder:

yaml
approval:
  sla: "24h"
  escalation:
    - after: "24h"
      to: "manager"                    # müdürün müdürü
      action: "notify"
    - after: "48h"
      to: "director"
      action: "auto_approve"           # otomatik onayla
    - after: "72h"
      action: "auto_reject"            # otomatik reddet
9.6. Paralel Onay
Birden fazla kişi aynı anda onaylar:

yaml
- key: "parallel_approval"
  type: "approval"
  assignee:
    type: "group"
    value: "cfo,ceo,cto"               # 3 kişi
  approval_mode: "all"                  # hepsi onaylamalı
  parallel: true                        # aynı anda
10. Automation Rules
10.1. Automation Nedir?
Event → Condition → Action. Onay gerektirmez, otomatik.

10.2. Automation Tanımı
yaml
workflow:
  key: "low-stock-alert"
  name: "Düşük Stok Uyarısı"
  type: "automation"
  trigger:
    event: "inv.stock.below_minimum"
  
  conditions:
    - "product.category IN ('A', 'B')"
    - "product.auto_reorder = true"
  
  actions:
    - type: "notify"
      to: "role:warehouse_manager"
      title: "Stok azaldı: ${product.name}"
      body: "Kalan: ${stock.remaining}"
    
    - type: "create_entity"
      entity: "prc_purchase_order"
      data:
        supplier_id: "${product.default_supplier_id}"
        items:
          - product_id: "${product.id}"
            qty: "${product.reorder_qty}"
    
    - type: "webhook"
      url: "https://tenant-webhook.com/stock"
      payload:
        product_id: "${product.id}"
        remaining: "${stock.remaining}"
10.3. Action Tipleri
Action	Ne yapar
notify	Bildirim gönder
email	Email gönder
create_entity	Yeni kayıt oluştur
update_entity	Kayıt güncelle
webhook	Dış URL çağır
event	Event yayınla
service	Servis çağır
workflow	Başka workflow başlat
10.4. Automation Örnekleri
1. Yeni müşteri karşılama:

yaml
trigger: "fin.customer.created"
actions:
  - notify: "role:sales_rep"
  - email: "${customer.email}"
  - create_entity: "crm_task"
2. Vadesi geçen fatura:

yaml
trigger: "invoice.overdue"
condition: "invoice.overdue_days >= 7"
actions:
  - notify: "role:finance_manager"
  - email: "${customer.email}"
  - update_entity: "fin_ar_invoice" (status = 'escalated')
3. Sipariş onaylanınca stok düş:

yaml
trigger: "otc.sales_order.approved"
actions:
  - service: "InventoryService::decrease"
  - event: "stock.updated"
4. Restoran: masa doldu:

yaml
trigger: "custom.order.created"
condition: "entity.table_id IS NOT NULL"
actions:
  - update_entity: "restaurant_table" (status = 'occupied')
  - notify: "role:waiter"
  - notify_kitchen: "${order.items}"
11. Workflow + Event Bus
11.1. Event Tetikli Workflow
yaml
trigger:
  event: "otc.sales_order.created"
  condition: "event.payload.total > 10000"
Runtime:

text
Event yayınlanır (Belge 08)
    ↓
EventBus dinler
    ↓
WorkflowTrigger eşleşir
    ↓
WorkflowEngine::start()
11.2. Workflow → Event
yaml
nodes:
  - key: "notify"
    type: "message"
    event: "order.approved"
    payload: { order_id: "${order_id}" }
Runtime:

text
Workflow node execute
    ↓
EventBus::publish(new OrderApproved(...))
    ↓
Diğer dinleyiciler işler
11.3. Event + Workflow Birleşik
text
1. User: "Sipariş oluştur"
2. Service: order.create() + Event: order.created
3. Workflow: Order Approval (başlar)
    ├─ Node 1: manager_approval (event: task.created)
    ├─ Node 2: finance_approval
    └─ Node 3: invoice.create (event: invoice.posted)
4. EventBus: invoice.posted
    ↓
5. Başka dinleyici: cari güncelle
6. Başka dinleyici: e-fatura gönder
7. Başka dinleyici: notification
Kural: Workflow event'lerle konuşur — event üretir, event tüketir.

12. Custom Entity + Workflow
12.1. Custom Entity'ye Workflow Ekle
yaml
entity:
  key: "recipe"
  behaviors:
    workflow: true
    workflow_key: "recipe-approval"
12.2. Workflow Kullanımı
php
// Custom entity workflow başlat
$recipe = Custom_Recipe::create([...]);
app(WorkflowEngine::class)->start('recipe-approval', $recipe, [
    'recipe_id' => $recipe->id,
]);
12.3. Custom Entity States
yaml
entity:
  key: "recipe"
  statuses:
    - key: "draft"
      label: "Taslak"
      default: true
    - key: "pending"
      label: "Onay Bekliyor"
    - key: "approved"
      label: "Onaylandı"
    - key: "rejected"
      label: "Reddedildi"
Kural: Custom entity statuses workflow ile uyumlu olmalı.

13. Görsel Builder (UI)
13.1. Canvas
text
┌──────────────────────────────────────────────────────────┐
│  Workflow Builder: Order Approval                        │
├──────────────────────────────────────────────────────────┤
│  ┌────────┐    ┌──────────┐    ┌────────┐    ┌───────┐  │
│  │ Start  │───▶│ Manager  │───▶│Finance │───▶│ End   │  │
│  │        │    │ Approval │    │Approval│    │       │  │
│  └────────┘    └────┬─────┘    └───┬────┘    └───────┘  │
│                     │              │                     │
│                     ▼              ▼                     │
│                 ┌────────┐    ┌────────┐                │
│                 │Reject  │    │Reject  │                │
│                 │ End    │    │ End    │                │
│                 └────────┘    └────────┘                │
│                                                          │
│  [💾 Kaydet]  [▶ Test Et]  [🚀 Yayınla]                │
└──────────────────────────────────────────────────────────┘
13.2. Drag-Drop
Palette — Sol tarafta node tipleri

Canvas — Sürükle-bırak

Properties — Sağ tarafta node ayarları

Validate — Otomatik kontrol

13.3. Node Palette
text
┌───────────────────┐
│  Node Tipleri     │
├───────────────────┤
│  🟢 Start         │
│  🔵 Task          │
│  🟡 Approval      │
│  🟠 Service       │
│  🔶 Gateway       │
│  ⏰ Timer         │
│  📨 Message       │
│  🔴 End           │
└───────────────────┘
13.4. Properties Panel
text
┌─────────────────────────────────────┐
│  Manager Approval                   │
├─────────────────────────────────────┤
│  Node Key: [manager_approval____]   │
│  Assignee Type: [Role ▾]            │
│  Role: [Manager_____________]       │
│  Scope: [Layer ▾]                   │
│  SLA: [24h___]                      │
│                                     │
│  Escalation:                        │
│  After: [24h___]                    │
│  To: [Director___________]          │
│  Action: [Notify ▾]                 │
│                                     │
│  Next:                              │
│  ✅ Approved → [finance_approval▾] │
│  ❌ Rejected → [rejected_end____▾] │
└─────────────────────────────────────┘
13.5. Test Mode
text
┌──────────────────────────────────────┐
│  Test: Order Approval                │
├──────────────────────────────────────┤
│  Variables:                          │
│  order_total: [15000_________]       │
│  customer_id: [demo-customer_]       │
│                                      │
│  [▶ Çalıştır]                        │
│                                      │
│  Sonuç:                              │
│  ✅ Node 1: start                    │
│  ✅ Node 2: gateway (matched: 10000) │
│  ⏳ Node 3: manager_approval         │
│  (test durdu: onay gerekiyor)        │
└──────────────────────────────────────┘
14. Monitoring & Observability
14.1. Workflow Dashboard
text
┌──────────────────────────────────────────────────────────┐
│  Workflow Monitoring                                     │
├──────────────────────────────────────────────────────────┤
│  Aktif instance'lar: 234                                 │
│  Bugün başlatılan: 1.456                                 │
│  Bugün tamamlanan: 1.234                                 │
│  Bugün iptal: 12                                         │
│  Bugün fail: 3                                           │
│                                                          │
│  Ortalama süre: 4.5 saat                                 │
│  SLA aşım: 8 instance                                    │
│                                                          │
│  ⚠️ Yavaş node'lar:                                     │
│  ─ Manager Approval: 6h avg (SLA: 4h)                   │
│  ─ Finance Approval: 2h avg (SLA: 8h)                   │
│                                                          │
│  ❌ Fail:                                                │
│  ─ Instance #abc: Service error (InvoiceService)        │
│  ─ Instance #def: Timeout (48h)                          │
└──────────────────────────────────────────────────────────┘
14.2. Bottleneck Analysis
Hangi node yavaş?

sql
SELECT 
    node_key,
    AVG(EXTRACT(EPOCH FROM (completed_at - started_at))) as avg_seconds,
    COUNT(*) as total
FROM wf_node_instance
WHERE completed_at IS NOT NULL
    AND started_at > now() - interval '30 days'
GROUP BY node_key
ORDER BY avg_seconds DESC;
14.3. SLA Tracking
php
class SLAMonitor {
    public function check(): void {
        $overdue = WfTask::where('status', 'pending')
            ->where('sla_deadline', '<', now())
            ->get();
        
        foreach ($overdue as $task) {
            event(new SLABreached($task));
            $this->escalate($task);
        }
    }
}
14.4. Workflow Replay
Debug için — workflow'u baştan çalıştır:

php
class WorkflowReplay {
    public function replay(WfInstance $instance): void {
        $events = EventStore::where('aggregate_id', $instance->aggregate_id)
            ->orderBy('occurred_at')
            ->get();
        
        // Sandbox'ta yeniden çalıştır
        $sandbox = new WorkflowSandbox();
        foreach ($events as $event) {
            $sandbox->apply($event);
        }
        
        // Sonucu karşılaştır
        return $sandbox->state() === $instance->variables;
    }
}
15. Versioning
15.1. Workflow Version
sql
CREATE TABLE wf_definition (
    id              uuid PRIMARY KEY,
    key             varchar(120) NOT NULL,
    name            varchar(255) NOT NULL,
    version         int NOT NULL DEFAULT 1,
    definition      jsonb NOT NULL,
    status          varchar(20) DEFAULT 'draft',   -- draft | active | deprecated
    tenant_id       uuid NULL REFERENCES tenants(id),
    created_by      bigint,
    created_at      timestamptz,
    updated_at      timestamptz,
    UNIQUE(tenant_id, key, version)
);

CREATE TABLE wf_definition_version (
    id              uuid PRIMARY KEY,
    definition_id   uuid NOT NULL,
    version         int NOT NULL,
    definition      jsonb NOT NULL,
    is_breaking     boolean DEFAULT false,
    created_at      timestamptz
);
15.2. Yeni Versiyon
yaml
workflow:
  key: "order-approval"
  version: "2.0.0"                     # semantic versioning
  breaking: true                        # eski instance'lar etkilenir mi
Runtime:

Yeni instance → yeni versiyon

Eski instance → eski versiyonda biter

Migration → opsiyonel

15.3. Breaking Change
yaml
migration:
  from: "1.0.0"
  to: "2.0.0"
  steps:
    - action: "add_node"
      key: "ceo_approval"
      after: "finance_approval"
    
    - action: "migrate_instances"
      strategy: "continue"              # continue | restart | cancel
Stratejiler:

Strateji	Etki
continue	Eski instance'lar eski versiyonda biter
restart	Eski instance'lar yeni versiyondan başlar
cancel	Eski instance'lar iptal, manuel restart
16. Testing
16.1. Workflow Unit Test
php
public function test_approval_flow(): void {
    $definition = WorkflowDefinition::factory()->create([
        'key' => 'test-approval',
        'definition' => [
            'nodes' => [
                ['key' => 'start', 'type' => 'start', 'next' => 'approve'],
                ['key' => 'approve', 'type' => 'approval', 'assignee' => ['type' => 'user', 'value' => 'user-1'], 'next_if' => ['approved' => 'end', 'rejected' => 'reject']],
                ['key' => 'end', 'type' => 'end', 'outcome' => 'approved'],
                ['key' => 'reject', 'type' => 'end', 'outcome' => 'rejected'],
            ],
        ],
    ]);
    
    $instance = app(WorkflowEngine::class)->start('test-approval', $entity, []);
    
    // Approval pending
    $this->assertEquals('active', $instance->status);
    $this->assertEquals('approve', $instance->current_node);
    
    // Onayla
    $task = WfTask::where('instance_id', $instance->id)->first();
    app(WorkflowEngine::class)->approve($task->id, 'approved');
    
    // Tamamlandı
    $this->assertEquals('completed', $instance->refresh()->status);
    $this->assertEquals('approved', $instance->outcome);
}
16.2. Sandbox Test
php
public function test_workflow_sandbox(): void {
    $sandbox = new WorkflowSandbox($definition);
    $result = $sandbox->run([
        'order_total' => 15000,
    ]);
    
    $this->assertEquals('manager_approval', $result->stopped_at);
    $this->assertCount(2, $result->completed_nodes);
}
16.3. Load Test
bash
# 100 eşzamanlı workflow
k6 run --vus 100 --duration 5m workflow-loadtest.js
Hedef: 1000 instance/dk, P95 < 500ms.

17. Restoran Örneği — Sipariş → Mutfak → Servis
Kullanıcının sorusuna cevap: Restoran workflow'u tam uygulama.

17.1. Workflow Tanımı
yaml
workflow:
  key: "restaurant-order-lifecycle"
  name: "Restoran Sipariş Yaşam Döngüsü"
  type: "state_machine"
  entity: "custom_order"
  
  trigger:
    event: "custom.order.created"
  
  states:
    - key: "new"
      name: "Yeni"
      initial: true
      transitions:
        - to: "preparing"
          action: "start_preparing"
          permission: "kitchen.order.update"
          on_enter:
            - "KitchenDisplay::addTicket"
            - "event: kitchen.ticket.created"
        
        - to: "cancelled"
          action: "cancel"
          permission: "waiter.order.cancel"
    
    - key: "preparing"
      name: "Hazırlanıyor"
      transitions:
        - to: "ready"
          action: "mark_ready"
          permission: "kitchen.order.update"
          on_enter:
            - "WaiterDisplay::notifyReady"
            - "event: kitchen.ticket.ready"
        
        - to: "cancelled"
          action: "cancel"
          permission: "manager.order.cancel"
    
    - key: "ready"
      name: "Hazır"
      transitions:
        - to: "served"
          action: "mark_served"
          permission: "waiter.order.update"
          on_enter:
            - "event: order.served"
        
        - to: "wasted"
          action: "mark_wasted"
          permission: "manager.order.cancel"
          condition: "order.ready_duration > 30 minutes"
    
    - key: "served"
      name: "Servis Edildi"
      transitions:
        - to: "payment_pending"
          action: "request_payment"
          permission: "cashier.payment.create"
          on_enter:
            - "CashierDisplay::addBill"
    
    - key: "payment_pending"
      name: "Ödeme Bekliyor"
      transitions:
        - to: "paid"
          trigger: "payment.received"
          on_enter:
            - "TableService::freeTable"
            - "event: order.paid"
        
        - to: "voided"
          action: "void"
          permission: "manager.payment.void"
    
    - key: "paid"
      name: "Ödendi"
      final: true
    
    - key: "cancelled"
      name: "İptal"
      final: true
    
    - key: "wasted"
      name: "Zayi"
      final: true
    
    - key: "voided"
      name: "İade"
      final: true
17.2. Akış
text
1. Waiter tablet: "Masa 5, 2 Burger"
   → custom_order created (state: new)
   → KitchenDisplay: yeni ticket
   
2. Chef: "Hazırlamaya başla"
   → state: preparing
   → Kitchen timer başlar
   
3. Chef: "Hazır"
   → state: ready
   → Waiter: "Masa 5 hazır"
   
4. Waiter: "Servis edildi"
   → state: served
   → Cashier: bill hazır
   
5. Müşteri ödeme yapar
   → payment.received event
   → state: paid
   → TableService: masa boşaltıldı
17.3. SLA & Automation
yaml
# SLA: Sipariş 20 dk'da hazır olmalı
sla:
  new_to_ready: "20m"
  escalation:
    - after: "15m"
      action: "notify_manager"
    - after: "20m"
      action: "notify_owner"
Automation:

yaml
automation:
  - trigger: "order.ready"
    actions:
      - notify: "waiter:${order.table.waiter_id}"
      - update: "table.status = 'serving'"
  
  - trigger: "order.paid"
    actions:
      - update: "table.status = 'cleaning'"
      - schedule: "5 minutes later, table.status = 'free'"
17.4. Cevaplar
Soru	Cevap
Masa, sipariş, workflow?	✅ Custom entity + workflow
Sipariş → mutfak?	✅ State machine + event
Mutfak → garson?	✅ State transition + notification
Garson → kasiyer?	✅ Workflow
Ödeme → masa boşaltma?	✅ Automation
SLA takibi?	✅ Workflow SLA
KFC/McDonald's tarzı?	✅ Custom workflow
18. Anti-Patterns
Bu projede yasak olan workflow hataları:

Hardcoded workflow — Kod içi if/else yasak

Sınırsız node — Pratikte 50 node max

Döngüsel workflow — Cycle detection zorunlu

SLA'sız approval — Her approval'da timeout

Delegasyon'suz approval — Kişi tatilde tıkanma

Escalation'sız SLA — Aşım = kilitlenme

Sandbox'sız workflow — Test edilemez

Version'sız değişiklik — Breaking change

eval() kullanıcı ifadesi — Sadece safe expression

PHP script node — Sadece JS (güvenlik)

Service'te iş mantığı — Service handler sadece

State machine bypass — Transition API zorunlu

Workflow delete (aktif) — Sadece deprecate

Nested workflow (deep) — Max 2 seviye

Approval + State conflict — Ayrı entity, ayrı workflow

19. Roadmap
Aşama	Süre	Kapsam
1. State Machine	4 hafta	Entity state, transition, API
2. Approval Flow	6 hafta	Task, assignee, delegation, escalation
3. Automation	4 hafta	Event trigger, actions, conditions
4. Service Node	3 hafta	Handler, retry, error
5. Gateway	3 hafta	Exclusive, parallel, inclusive
6. Timer	2 hafta	Wait, SLA, timeout
7. Visual Builder	8 hafta	Drag-drop, properties, test
8. Monitoring	4 hafta	Dashboard, SLA tracking, replay
Toplam: 34 hafta (~8 ay).

20. Glossary
Terim	Anlam
Workflow	İş süreci tanımı
State Machine	Entity durum geçişi
Approval Flow	Onay zinciri
Automation	Event → Action
Node	Workflow adımı
Transition	Adım geçişi
Gateway	Koşullu dallanma
SLA	Service Level Agreement
Escalation	SLA aşımında üstüne gönder
Delegation	Vekalet
Approval Mode	any/all/majority
Parallel	Aynı anda
Service Node	Otomatik servis çağrısı
Script Node	JS sandbox
Instance	Çalışan workflow
Replay	Yeniden çalıştırma
Sandbox	Test ortamı
21. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — state machine, approval, automation, restoran örneği