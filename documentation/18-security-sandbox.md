# 18 — Security & Sandbox

**Project:** Kurumsal ERP Platform
**Belge:** Güvenlik Mimarisi, Sandbox İzolasyonu, Threat Model, Incident Response
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Design Document — kısmen mevcut (RLS, auth), kısmen planlı
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md, 11-workflow-engine.md, 12-report-builder.md, 13-ui-builder.md, 14-integration-hub.md, 15-compliance.md, 17-marketplace.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **güvenlik mimarisini** ve **sandbox izolasyonunu** bir bütün olarak tanımlar — Belge 01, 09, 10, 14, 15'te dağınık güvenlik konularını **tek çatı altında** birleştirir. İçerir:

- Threat model (STRIDE)
- Defense in depth (7 katman)
- Kimlik ve erişim (auth, RBAC, ABAC)
- Veri güvenliği (encryption, masking)
- Sandbox mimarisi (Belge 10 özeti + derinleştirme)
- API güvenliği (rate limit, WAF, OWASP)
- Plugin güvenliği (audit, kill switch)
- Infrastructure güvenliği (network, OS, DB)
- Incident response (detect → respond → recover)
- Security operations (SOC, monitoring, alerting)
- Compliance mapping (ISO 27001, SOC 2, KVKK, GDPR)
- Testing (pentest, SAST, DAST, threat modeling)
- Anti-patterns, roadmap

**Hedef kitle:** Güvenlik ekibi, geliştiriciler, AI asistanlar, CTO, compliance ekibi.

**Kritik:** Güvenlik **sonradan eklenmez**, **doğuştan gelir**. Bu belge, "Security by Design" ilkesini operasyonelleştirir.

---

## 2. Threat Model (STRIDE)

### 2.1. STRIDE Kategorileri

Her tehdit kategorisi için platformdaki risk ve mitigasyon:

| Kategori | Tehdit | Platformda Risk | Mitigasyon |
|---|---|---|---|
| **Spoofing** | Kimlik sahtekarlığı | Tenant admin çalınması | MFA (TOTP), session rotation, IP whitelist |
| **Tampering** | Veri değişikliği | Fatura tutarı değişimi | Audit trail (WORM), hash chain |
| **Repudiation** | İnkar | "Ben yapmadım" | Non-repudiation, dijital imza |
| **Information Disclosure** | Veri sızıntısı | Cross-tenant leak | RLS, data scope, PII masking |
| **Denial of Service** | Hizmet reddi | Plugin CPU tüketimi | Sandbox limits, rate limit, circuit breaker |
| **Elevation of Privilege** | Yetki yükseltme | Plugin superadmin bypass | Sandbox + scope + kill switch |

### 2.2. Tehdit Aktörleri

| Aktör | Motivasyon | Risk Seviyesi |
|---|---|---|
| **Dış saldırgan** | Veri, para | Yüksek |
| **Kötü niyetli plugin developer** | Veri çalma, sabotaj | Yüksek |
| **Kötü niyetli tenant admin** | Rakip tenant verisi | Orta |
| **Kötü niyetli tenant kullanıcı** | Yetki aşımı | Orta |
| **İç tehdit (çalışan)** | Kötüye kullanım | Orta |
| **AI/otomatik bot** | Brute force, scraping | Düşük-Orta |

### 2.3. Saldırı Yüzeyi
┌─────────────────────────────────────────────────────────┐
│ DIŞ YÜZEY │
│ ─ Web UI (HTTPS) │
│ ─ API (REST, GraphQL) │
│ ─ Webhook (inbound) │
│ ─ File upload │
└─────────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────────┐
│ APPLICATION YÜZEYİ │
│ ─ Laravel / Livewire │
│ ─ Plugin sandbox │
│ ─ Custom component (WASM/iframe) │
│ ─ Report SQL (safe) │
└─────────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────────┐
│ VERİ YÜZEYİ │
│ ─ PostgreSQL (RLS) │
│ ─ Redis (cache, queue) │
│ ─ S3 (files, backups) │
│ ─ KMS (encryption keys) │
└─────────────────────────────────────────────────────────┘
↓
┌─────────────────────────────────────────────────────────┐
│ INFRASTRUCTURE YÜZEYİ │
│ ─ Cloud (AWS/Hetzner) │
│ ─ OS (Ubuntu) │
│ ─ Network │
└─────────────────────────────────────────────────────────┘

text

**Kural:** Her yüzey **ayrı savunma** gerektirir.

---

## 3. Defense in Depth — 7 Katman

### 3.1. Katman Diyagramı
┌──────────────────────────────────────────────────────────┐
│ KATMAN 7: COMPLIANCE & AUDIT │
│ ─ Audit trail, WORM, hash chain │
│ ─ GDPR/KVKK uyumu │
└──────────────────────────────────────────────────────────┘
↓
┌──────────────────────────────────────────────────────────┐
│ KATMAN 6: MONITORING & RESPONSE │
│ ─ Sentry (error), Pulse (perf) │
│ ─ Anomaly detection │
│ ─ Incident response │
└──────────────────────────────────────────────────────────┘
↓
┌──────────────────────────────────────────────────────────┐
│ KATMAN 5: APPLICATION SECURITY │
│ ─ Input validation (FormRequest) │
│ ─ Output encoding (Blade escape) │
│ ─ CSRF, XSS, SQL injection │
│ ─ Rate limiting │
└──────────────────────────────────────────────────────────┘
↓
┌──────────────────────────────────────────────────────────┐
│ KATMAN 4: AUTHENTICATION & AUTHORIZATION │
│ ─ Sanctum, OAuth2, MFA │
│ ─ RBAC + ABAC │
│ ─ Session management │
└──────────────────────────────────────────────────────────┘
↓
┌──────────────────────────────────────────────────────────┐
│ KATMAN 3: DATA SECURITY │
│ ─ RLS (tenant isolation) │
│ ─ Data scope (layer) │
│ ─ Encryption (at rest, in transit) │
│ ─ PII masking │
└──────────────────────────────────────────────────────────┘
↓
┌──────────────────────────────────────────────────────────┐
│ KATMAN 2: SANDBOX │
│ ─ Plugin isolation │
│ ─ Custom component isolation │
│ ─ SQL safe query │
└──────────────────────────────────────────────────────────┘
↓
┌──────────────────────────────────────────────────────────┐
│ KATMAN 1: INFRASTRUCTURE SECURITY │
│ ─ Network (VPC, firewall) │
│ ─ OS (hardening, patching) │
│ ─ Physical (cloud provider) │
└──────────────────────────────────────────────────────────┘

text

**Kural:** Bir katman delinebilir, sonraki **durdurur**.

### 3.2. Katman Öncelikleri

| Katman | Öncelik | Kapsam |
|---|---|---|
| **Data Security** | 🔴 Kritik | RLS, encryption |
| **Authentication** | 🔴 Kritik | Auth, MFA |
| **Application** | 🟠 Yüksek | Validation, XSS, CSRF |
| **Sandbox** | 🟠 Yüksek | Plugin, component |
| **Infrastructure** | 🟠 Yüksek | Network, OS |
| **Monitoring** | 🟡 Orta | Sentry, alerts |
| **Compliance** | 🟡 Orta | Audit, WORM |

---

## 4. Kimlik ve Erişim

### 4.1. Authentication (Kimlik Doğrulama)

#### 4.1.1. Kullanıcı Auth

**Laravel Sanctum** (web + API):

```php
// Session-based (web)
Auth::attempt($credentials);

// Token-based (API)
$user = User::where('email', $email)->first();
if (Hash::check($password, $user->password)) {
    $token = $user->createToken('api')->plainTextToken;
}
4.1.2. MFA (Multi-Factor Authentication)
TOTP (Google Authenticator uyumlu):

yaml
mfa:
  enabled: true
  methods:
    - totp                             # Google Authenticator
    - sms                              # SMS (opsiyonel)
    - email                            # Email (fallback)
    - webauthn                          # FIDO2 (gelecek)
  
  enforced:
    - "role:admin"
    - "role:superadmin"
    - "action:*.delete"                # critical action
    - "amount > 10000"                 # critical amount
Runtime:

php
class MfaMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        
        if ($this->requiresMfa($user, $request)) {
            if (!session('mfa_verified')) {
                return redirect()->route('mfa.challenge');
            }
        }
        
        return $next($request);
    }
}
4.1.3. Session Management
yaml
session:
  driver: "redis"
  lifetime: 120                        # 2 saat
  expire_on_close: false
  encrypt: true
  secure: true                         # HTTPS only
  http_only: true                      # JS erişemez
  same_site: "lax"                     # CSRF koruması
  
  rotation:
    enabled: true
    interval: 900                      # 15 dk
  
  concurrent:
    enabled: true
    max: 3                             # Aynı kullanıcı 3 cihaz
    on_exceed: "logout_oldest"
Kural: Session hijacking → rotation + IP binding.

4.1.4. Password Policy
yaml
password:
  min_length: 12
  require_uppercase: true
  require_lowercase: true
  require_number: true
  require_symbol: true
  prevent_common: true                 # RockYou list
  prevent_user_info: true              # email, name
  history: 5                           # son 5 şifre
  
  rotation:
    enabled: true
    interval_days: 90
  
  lockout:
    attempts: 5
    duration_minutes: 30
4.1.5. Rate Limiting (Auth)
Endpoint	Limit
Login	5/dk per IP
Password reset	3/saat per email
MFA challenge	5/dk
Register	3/saat per IP
API key create	10/gün
Kural: Aşım → 429 + exponential backoff.

4.2. Authorization (Yetkilendirme)
4.2.1. RBAC (Role-Based) — Belge 06
php
// Permission check
if (!auth()->user()->can('fin.customer.edit')) {
    abort(403);
}

// Role check
if (!auth()->user()->hasRole('admin')) {
    abort(403);
}
4.2.2. ABAC (Attribute-Based)
Koşullu izin (Belge 06 — conditional permission):

php
// Sadece belirli tutar altında onaylama
$order = OtcSalesOrder::find($id);
if (!auth()->user()->can('approve', $order)) {
    // Policy kontrolü: amount < 100000 && department == user.department
    abort(403);
}
4.2.3. Data Scope — Belge 06
Layer bazlı veri görünürlüğü otomatik.

4.3. API Auth — Belge 09
Yöntem	Kullanım
Sanctum	1st party
OAuth2	3rd party
API Key	Server-to-server
Kural: Her endpoint auth — public olanlar açık işaretli.

5. Veri Güvenliği
5.1. Encryption at Rest
5.1.1. DB Encryption
PostgreSQL TDE (cloud managed) veya

Kolon seviyesi (Laravel Crypt)

PII kolonlar her zaman encrypted:

php
class FinCustomer extends Model
{
    protected $casts = [
        'tax_no' => 'encrypted',
        'iban' => 'encrypted',
    ];
}
5.1.2. File Encryption
S3 encryption:

php
Storage::disk('s3')->put($path, $content, [
    'ServerSideEncryption' => 'AES256',
]);
5.1.3. Backup Encryption
bash
pg_dump ... | gpg --encrypt --recipient backup@company.com | aws s3 cp - s3://backups/
Kural: Backup her zaman encrypted.

5.1.4. KMS (Key Management)
yaml
kms:
  provider: "aws-kms"
  
  keys:
    - key: "master"                    # ana anahtar
      rotation: 365                    # yıllık
    
    - key: "tenant-{id}"               # her tenant için
      derived_from: "master"
      rotation: 365
    
    - key: "backup"                    # backup şifreleme
      rotation: 90
Kural: Her tenant kendi key → cross-tenant leak imkansız.

5.2. Encryption in Transit
TLS 1.3 zorunlu (TLS 1.2 fallback)

HSTS (HTTP Strict Transport Security)

Certificate pinning (mobile)

mTLS (server-to-server)

nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:...';
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
5.3. PII Masking — Belge 15
PII otomatik maskelenir — role-based.

5.4. Data Classification
Seviye	Örnek	Koruma
Public	Ürün adı	—
Internal	Fiyat	Auth
Confidential	Müşteri verisi	RLS + encryption
Restricted	PII, sağlık	RLS + encryption + audit
5.5. Data Retention — Belge 15
Otomatik retention + silme.

6. Sandbox Mimarisi (Derinleştirme)
6.1. Belge 10 Özeti
Container per plugin (Docker)

Process pool (PHP-FPM)

WASM (hafif)

Network whitelist

File isolation

Resource limits

Scope enforcement

Detay: Belge 10.

6.2. Sandbox Katmanları (Görsel)
text
┌─────────────────────────────────────────────────────────┐
│  PLATFORM (trusted)                                    │
│  ─ Laravel app, DB, Redis                              │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  PLUGIN SANDBOX (untrusted)                            │
│  ─ Container veya process pool                         │
│  ─ Resource limits (CPU, RAM, disk, network)           │
│  ─ Scope enforcement (API calls)                       │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  CUSTOM COMPONENT SANDBOX (untrusted)                  │
│  ─ Web Component (shadow DOM)                          │
│  ─ iframe (sandbox attr)                               │
│  ─ WASM (linear memory)                                │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  SQL SANDBOX (report query)                            │
│  ─ SELECT-only parser                                  │
│  ─ Table whitelist                                     │
│  ─ RLS inject                                          │
└─────────────────────────────────────────────────────────┘
6.3. Sandbox Bypass Test (Pentest)
Sürekli test:

bash
# Plugin sandbox escape testleri
./security-tests/plugin-sandbox-escape.sh
./security-tests/custom-component-escape.sh
./security-tests/sql-injection-report.sh
Kural: Her release'de otomatik.

6.4. Sandbox Monitoring
yaml
sandbox_monitoring:
  metrics:
    - cpu_usage
    - memory_usage
    - network_requests
    - api_calls
    - errors
    - scope_violations
  
  alerts:
    - name: "High CPU"
      condition: "cpu_usage > 80%"
      window: "5m"
    
    - name: "Scope Violation"
      condition: "scope_violations > 0"
      severity: "critical"
    
    - name: "Network Anomaly"
      condition: "network_requests to non-whitelist"
      severity: "critical"
7. API Güvenliği
7.1. OWASP Top 10 (2021)
Risk	Mitigasyon
A01 Broken Access Control	RBAC + ABAC + RLS
A02 Cryptographic Failures	TLS 1.3, encryption at rest
A03 Injection	Eloquent/Query Builder (no raw SQL), validation
A04 Insecure Design	Threat modeling, security review
A05 Security Misconfiguration	Hardening checklist
A06 Vulnerable Components	Composer audit, Dependabot
A07 Auth Failures	MFA, rate limit, session rotation
A08 Data Integrity	Signature, hash chain
A09 Logging Failures	Sentry, Pulse, audit trail
A10 SSRF	Network whitelist
7.2. WAF (Web Application Firewall)
Cloudflare veya AWS WAF:

yaml
waf_rules:
  - name: "OWASP Core Rule Set"
    enabled: true
  
  - name: "Rate Limit"
    threshold: 1000
    window: "1m"
  
  - name: "Geo Block"
    countries: ["KP", "IR"]             # ambargo
  
  - name: "Bot Detection"
    action: "challenge"
  
  - name: "SQL Injection"
    action: "block"
  
  - name: "XSS"
    action: "block"
7.3. Input Validation
Her endpoint FormRequest:

php
class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => 'required|uuid|exists:fin_customer,id',
            'date' => 'required|date|before_or_equal:today',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|uuid|exists:inv_product,id',
            'items.*.qty' => 'required|numeric|min:0.01|max:1000000',
        ];
    }
}
Kural: Her input typed + validated.

7.4. Output Encoding
Blade auto-escape:

blade
{{ $user->name }}        {{-- Escaped --}}
{!! $html !!}            {{-- Raw — YASAK! --}}
Kural: {!! !!} sadece güvenli HTML için (rich text).

7.5. CORS
php
config/cors.php:
'paths' => ['api/*'],
'allowed_origins' => [
    'https://app.example.com',           // specific
    // '*' ❌ production
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['Authorization', 'Content-Type'],
'max_age' => 86400,
Kural: Production'da * yasak.

7.6. Rate Limiting (API)
Kategori	Limit
Unauth	10/dk
Auth (user)	1000/dk
Auth (API key)	5000/dk
Bulk endpoint	100/dk
Export	10/saat
Login	5/dk
Kural: Her endpoint rate limit.

8. Infrastructure Güvenliği
8.1. Network
yaml
network:
  vpc: "10.0.0.0/16"
  
  subnets:
    - { name: "public", cidr: "10.0.1.0/24", az: "eu-central-1a" }
    - { name: "private-app", cidr: "10.0.2.0/24", az: "eu-central-1a" }
    - { name: "private-db", cidr: "10.0.3.0/24", az: "eu-central-1a" }
  
  security_groups:
    - name: "web"
      inbound: ["443 from 0.0.0.0/0"]
      outbound: ["to app"]
    
    - name: "app"
      inbound: ["from web"]
      outbound: ["to db", "to internet (443)"]
    
    - name: "db"
      inbound: ["from app"]
      outbound: []
  
  waf: "cloudflare"
  ddos: "cloudflare"
Kural: DB public internet'e kapalı.

8.2. OS Hardening
bash
# Ubuntu 24.04 LTS
# 1. Update
apt update && apt upgrade -y

# 2. Firewall
ufw default deny incoming
ufw allow 22/tcp                     # SSH (sadece bastion'dan)
ufw allow 80,443/tcp
ufw enable

# 3. SSH
# /etc/ssh/sshd_config
PermitRootLogin no
PasswordAuthentication no            # sadece key
PubkeyAuthentication yes
AllowUsers deploy

# 4. Fail2ban
apt install fail2ban

# 5. Audit
apt install auditd

# 6. Kernel hardening
# /etc/sysctl.d/99-hardening.conf
kernel.dmesg_restrict = 1
kernel.kptr_restrict = 2
net.ipv4.tcp_syncookies = 1

# 7. Automatic updates
apt install unattended-upgrades
Kural: OS hardening zorunlu.

8.3. Container Güvenliği
dockerfile
# Non-root user
FROM php:8.3-fpm
RUN groupadd -r app && useradd -r -g app app
USER app

# Read-only filesystem
# docker-compose.yml:
services:
  app:
    read_only: true
    tmpfs:
      - /tmp
      - /var/run

# Capabilities
    cap_drop:
      - ALL
    cap_add:
      - NET_BIND_SERVICE

# Resource limits
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
Kural: Container minimum yetki.

8.4. Secret Management
yaml
secrets:
  # ❌ Yasak
  - .env in git
  
  # ✅ Doğru
  - AWS Secrets Manager
  - HashiCorp Vault
  - Doppler
Kural: Secret asla git'te.

8.5. Backup & Recovery
yaml
backup:
  database:
    frequency: "6h"
    retention: "30d"
    encryption: "AES-256"
    location: "S3 eu-central-1"
    test_restore: "weekly"
  
  files:
    frequency: "24h"
    retention: "90d"
    encryption: "AES-256"
    location: "S3 eu-central-1"
  
  config:
    frequency: "daily"
    retention: "1y"
    location: "S3 + git"
  
  rto: "1h"                            # Recovery Time Objective
  rpo: "6h"                            # Recovery Point Objective
Kural: Backup test edilmeden sayılmaz.

9. Incident Response
9.1. Incident Seviyeleri
Seviye	Örnek	Response
P1 (Critical)	Veri sızıntısı, sistem down	15 dk
P2 (High)	Cross-tenant erişim, auth bypass	1 saat
P3 (Medium)	Rate limit bypass, minor leak	4 saat
P4 (Low)	UI bug, non-security	24 saat
9.2. Incident Response Flow
text
1. DETECT
    ↓ (monitoring, alert, user report)
2. TRIAGE
    ↓ (severity, scope)
3. CONTAIN
    ↓ (isolate, block, kill switch)
4. ERADICATE
    ↓ (patch, update)
5. RECOVER
    ↓ (restore, verify)
6. POST-MORTEM
    ↓ (root cause, actions, lessons)
7. COMMUNICATE
    ↓ (users, regulators, public)
9.3. Runbook Örnekleri
9.3.1. Veri Sızıntısı Şüphesi
bash
# 1. İzole et
# Tenant'ı suspend et
php artisan tenant:suspend --id=xyz --reason="security_investigation"

# 2. Log topla
php artisan security:collect-evidence --tenant=xyz --since=24h

# 3. Analiz
# Access pattern, anormal query

# 4. Karar
# - False positive → resume
# - Real leak → notify users, GDPR/KVKK report

# 5. Post-mortem
9.3.2. Plugin Sandbox Escape
bash
# 1. Plugin'i durdur
php artisan plugin:kill --key=malicious --reason="security"

# 2. Tüm tenant'larda devre dışı
# (otomatik — kill switch)

# 3. Etki analizi
php artisan security:analyze-plugin --key=malicious

# 4. Kullanıcıları bilgilendir
# (plugin kullanan tenant'lar)

# 5. Developer'ı ban
# (marketplace)

# 6. Post-mortem
9.4. Communication Plan
Seviye	Kime	Ne zaman
P1	Users + public	1 saat içinde
P2	Affected users	4 saat içinde
P3	Affected users	24 saat içinde
P4	Internal	1 hafta içinde
Kural: KVKK/GDPR → 72 saat içinde regulator'a.

10. Security Operations (SOC)
10.1. Monitoring Stack
yaml
monitoring:
  errors: "sentry"                     # exception tracking
  performance: "laravel-pulse"         # perf metrics
  logs: "loki"                         # log aggregation
  metrics: "prometheus + grafana"      # infra metrics
  tracing: "jaeger"                    # distributed tracing
  uptime: "uptime-robot"
  security: "wazuh"                    # SIEM
10.2. Alerts
yaml
alerts:
  - name: "High error rate"
    condition: "error_rate > 1%"
    window: "5m"
    severity: "P2"
  
  - name: "Cross-tenant access attempt"
    condition: "rls_violation > 0"
    severity: "P1"
  
  - name: "Auth brute force"
    condition: "failed_logins > 100"
    window: "5m"
    severity: "P2"
  
  - name: "Sandbox escape attempt"
    condition: "sandbox_violation > 0"
    severity: "P1"
  
  - name: "Unusual data export"
    condition: "export_size > 1GB"
    severity: "P2"
  
  - name: "Plugin scope violation"
    condition: "scope_violation > 0"
    severity: "P1"
  
  - name: "Suspicious IP"
    condition: "ip_blacklist_hit > 0"
    severity: "P3"
10.3. Log Management
yaml
logs:
  retention:
    - { type: "application", days: 30 }
    - { type: "security", days: 365 }
    - { type: "audit", days: 2555 }    # 7 yıl
    - { type: "compliance", days: 3650 } # 10 yıl
  
  format: "json"
  includes:
    - timestamp
    - level
    - user_id
    - tenant_id
    - request_id
    - ip
    - action
    - entity
    - duration
10.4. Security Testing (Sürekli)
Test	Frekans
SAST (Semgrep)	Her PR
Dependency scan (Composer audit)	Her gün
Container scan (Trivy)	Her build
DAST (OWASP ZAP)	Haftalık
Pentest (external)	Yıllık
Threat model	Her major feature
Bug bounty	Sürekli (HackerOne)
11. Compliance Mapping
11.1. ISO 27001
Kontrol	Platform Karşılığı
A.5 Information policies	Bu belge (Security)
A.6 Organization	RACI, security team
A.7 HR security	Background check, NDA
A.8 Asset management	Data classification
A.9 Access control	RBAC + MFA
A.10 Cryptography	TLS + encryption
A.11 Physical	Cloud provider
A.12 Operations	Monitoring, backup
A.13 Communications	Network security
A.14 Development	Secure SDLC
A.15 Supplier	Vendor assessment
A.16 Incident	IR plan
A.17 Business continuity	DR plan
A.18 Compliance	Audit, legal
11.2. SOC 2
Trust Principle	Karşılığı
Security	Defense in depth
Availability	Uptime SLA, DR
Processing integrity	Audit trail, WORM
Confidentiality	Encryption, RLS
Privacy	GDPR/KVKK compliance
11.3. KVKK
Gereklilik	Platform
Veri envanteri	PII scanner
Açık rıza	Consent management
Veri silme	Right to be forgotten
Veri taşınabilirlik	Export API
İhlal bildirimi	72 saat IR
DPO	Atanmış
11.4. GDPR
Gereklilik	Platform
ROPA	Data inventory
DPIA	Risk assessment
Consent	Consent management
Right to access	Export
Right to erasure	Delete
Right to portability	Standard format
Breach notification	72 saat
12. Anti-Patterns
Bu projede yasak olan güvenlik hataları:

{!! !!} (raw HTML) — XSS

Raw SQL — SQL injection

* CORS origin — CSRF

password plain text — Hash zorunlu

SELECT * — Data exposure

Debug mode prod — Bilgi sızıntısı

Secret in code — Vault/Secrets Manager

eval() — RCE

exec(), system() — RCE

file_get_contents unrestricted — SSRF

Auth bypass (superadmin) — Audit zorunlu

Cross-tenant data — RLS + audit

No MFA for admin — Zorunlu

Session without rotation — Hijacking

Weak password — Policy zorunlu

No rate limit — DDoS

No WAF — OWASP

No monitoring — Blind

No backup test — Backup yok

No incident response — Kaos

13. Roadmap
Aşama	Süre	Kapsam
1. Foundation	4 hafta	Auth, RBAC, session, MFA
2. Data security	4 hafta	RLS, encryption, PII masking
3. API security	4 hafta	Rate limit, WAF, OWASP
4. Sandbox	6 hafta	Plugin + component isolation
5. Monitoring	4 hafta	Sentry, Pulse, alerts
6. Incident response	3 hafta	Runbook, IR plan
7. Compliance	6 hafta	ISO 27001, SOC 2
8. Testing	4 hafta	SAST, DAST, pentest
9. Bug bounty	4 hafta	HackerOne
Toplam: 39 hafta (~10 ay).

14. Glossary
Terim	Anlam
STRIDE	Threat model kategorileri
Defense in depth	Çok katmanlı savunma
RBAC	Role-Based Access Control
ABAC	Attribute-Based Access Control
MFA	Multi-Factor Authentication
TOTP	Time-based OTP
WebAuthn	FIDO2 standardı
RLS	Row-Level Security
WAF	Web Application Firewall
SAST	Static Application Security Testing
DAST	Dynamic Application Security Testing
SIEM	Security Information and Event Management
SOC	Security Operations Center
IR	Incident Response
RTO	Recovery Time Objective
RPO	Recovery Point Objective
KMS	Key Management Service
TDE	Transparent Data Encryption
HSTS	HTTP Strict Transport Security
CSRF	Cross-Site Request Forgery
XSS	Cross-Site Scripting
SSRF	Server-Side Request Forgery
PII	Personally Identifiable Information
Kill switch	Acil durdurma
Bug bounty	Ödüllü güvenlik testi
15. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — güvenlik mimarisi, sandbox, incident response
