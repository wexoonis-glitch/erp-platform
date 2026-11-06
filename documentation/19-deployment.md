# 19 — Deployment & DevOps

**Project:** Kurumsal ERP Platform
**Belge:** Deployment, CI/CD, Infrastructure, Environment Management, Observability, Disaster Recovery
**Version:** 1.0
**Last Updated:** 2026-11-06
**Status:** Living Document — mevcut altyapı + hedef mimari
**Depends On:** 00-manifesto.md, 01-multi-tenancy.md, 02-data-model.md, 03-feature-flags.md, 04-module-system.md, 05-meta-structure.md, 06-layer-role-system.md, 07-custom-entity.md, 08-event-bus.md, 09-api-first.md, 10-plugin-sandbox.md, 11-workflow-engine.md, 12-report-builder.md, 13-ui-builder.md, 14-integration-hub.md, 15-compliance.md, 17-marketplace.md, 18-security-sandbox.md

---

## 1. Bu Belgenin Amacı

Bu belge, platformun **deployment ve DevOps** pratiklerini tanımlar:

- Environment stratejisi (dev, staging, prod, DR)
- Infrastructure as Code (Terraform, Ansible)
- CI/CD pipeline (GitHub Actions)
- Deployment stratejileri (blue-green, canary, rolling)
- Zero-downtime deployment
- Database migration stratejisi
- Backup & restore
- Monitoring & observability
- Disaster recovery
- Cost optimization
- Multi-region deployment
- Türkiye/global pazar farklılıkları
- Anti-patterns, roadmap

**Hedef kitle:** DevOps mühendisleri, geliştiriciler, AI asistanlar, CTO.

**Kritik:** Deployment **korkulan değil, beklenen** olmalı. Hedef: **günde 10+ deploy, sıfır downtime**.

---

## 2. Environment Stratejisi

### 2.1. 5 Environment

| Environment | Amaç | Kim kullanır | URL |
|---|---|---|---|
| **Local** | Geliştirme | Developer | localhost |
| **Dev** | Entegrasyon testi | Developer | dev.erp.local |
| **Staging** | Production yakın | QA, PM | staging.erp.com |
| **Production** | Canlı | Son kullanıcı | app.erp.com |
| **DR (Disaster Recovery)** | Yedek | Sadece felaket | dr.erp.com |

### 2.2. Environment Özellikleri

```yaml
environments:
  local:
    database: "postgres-local"
    cache: "redis-local"
    queue: "sync"                       # anlık çalışır
    debug: true
    error_tracking: false
    payment: "mock"
    email: "mailpit"
    storage: "local-filesystem"
    
  dev:
    database: "postgres-dev"
    cache: "redis-dev"
    queue: "redis"
    debug: true
    error_tracking: true
    payment: "sandbox"
    email: "mailtrap"
    storage: "s3-dev"
    
  staging:
    database: "postgres-staging"        # production kopyası (anonim)
    cache: "redis-cluster"
    queue: "redis"
    debug: false
    error_tracking: true
    payment: "sandbox"
    email: "mailtrap"
    storage: "s3-staging"
    
  production:
    database: "aurora-postgres"         # managed
    cache: "elasticache-redis"
    queue: "sqs-or-redis"
    debug: false
    error_tracking: true
    payment: "live"
    email: "ses-or-postmark"
    storage: "s3-production"
    
  dr:
    database: "aurora-postgres-dr"      # cross-region replica
    cache: "elasticache-dr"
    queue: "sqs-dr"
    debug: false
    error_tracking: true
    payment: "disabled"
    email: "disabled"
    storage: "s3-dr"
2.3. Environment Parity
Kural: Staging, production'ın birebir kopyası olmalı.

Öğe	Fark
PHP sürümü	Aynı
PostgreSQL sürümü	Aynı
Redis sürümü	Aynı
OS	Aynı
Extension	Aynı
Config	Sadece değer farklı
Yasak: "Staging'de çalışıyor, prod'da çalışmıyor" → environment parity ihlali.

2.4. Data Parity
Staging data:

✅ Production'dan anonim kopya

✅ Haftalık yenilenir

❌ PII yok (maskelenmiş)

❌ Finansal data gerçek değil (%10-20 farklı)

Anonymization:

bash
# Production → Staging
pg_dump production | anonymize.sh | psql staging
bash
# anonymize.sh
sed -E 's/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}/user@example.com/g'
sed -E 's/(\+90|0)[0-9]{10}/+905000000000/g'
sed -E 's/[0-9]{11}/00000000000/g'        # TC
3. Infrastructure as Code
3.1. Tech Stack
Araç	Kullanım
Terraform	Cloud infra (AWS/Hetzner)
Ansible	Server config
Docker	Container
Kubernetes	Orchestration (ölçek)
Helm	K8s package
Vault	Secrets
3.2. Terraform Yapısı
text
infrastructure/
├── modules/
│   ├── network/                        # VPC, subnet
│   ├── database/                       # Aurora, RDS
│   ├── cache/                          # ElastiCache
│   ├── compute/                        # ECS, EC2
│   ├── storage/                        # S3
│   └── monitoring/                     # CloudWatch
├── environments/
│   ├── dev/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── terraform.tfvars
│   ├── staging/
│   └── production/
└── global/
    ├── iam/
    └── dns/
3.3. Terraform Örnek — Production
hcl
# environments/production/main.tf

module "network" {
  source = "../../modules/network"
  
  vpc_cidr     = "10.0.0.0/16"
  environment  = "production"
  region       = "eu-central-1"
  
  availability_zones = ["eu-central-1a", "eu-central-1b", "eu-central-1c"]
  
  public_subnets   = ["10.0.1.0/24", "10.0.2.0/24", "10.0.3.0/24"]
  private_subnets  = ["10.0.11.0/24", "10.0.12.0/24", "10.0.13.0/24"]
  database_subnets = ["10.0.21.0/24", "10.0.22.0/24", "10.0.23.0/24"]
}

module "database" {
  source = "../../modules/database"
  
  engine          = "aurora-postgresql"
  engine_version  = "16.2"
  instance_class  = "db.r6g.xlarge"
  instances       = 2                     # writer + reader
  
  storage_encrypted = true
  backup_retention  = 30                  # gün
  backup_window     = "03:00-04:00"
  maintenance_window = "sun:05:00-sun:06:00"
  
  deletion_protection = true
  performance_insights_enabled = true
  
  vpc_id          = module.network.vpc_id
  subnet_ids      = module.network.database_subnet_ids
}

module "cache" {
  source = "../../modules/cache"
  
  engine       = "redis"
  engine_version = "7.1"
  node_type    = "cache.r6g.large"
  nodes        = 3                        # cluster
  
  automatic_failover_enabled = true
  multi_az_enabled = true
  
  vpc_id          = module.network.vpc_id
  subnet_ids      = module.network.private_subnet_ids
}

module "compute" {
  source = "../../modules/compute"
  
  cluster_name = "erp-production"
  instance_type = "c6g.xlarge"           # ARM (cost-effective)
  desired_count = 4                       # 4 app instance
  
  autoscaling = {
    min = 4
    max = 20
    target_cpu = 70
  }
  
  vpc_id          = module.network.vpc_id
  subnet_ids      = module.network.private_subnet_ids
  security_groups = [module.network.app_sg_id]
}
3.4. Ansible — Server Config
yaml
# ansible/playbooks/app-server.yml
---
- hosts: app_servers
  become: yes
  roles:
    - common                            # base (update, packages)
    - hardening                          # security
    - php
    - nginx
    - supervisor                         # queue workers
    - monitoring-agent                   # datadog/sentry
    - fail2ban
    - ufw
roles/hardening/tasks/main.yml:

yaml
- name: Update packages
  apt:
    upgrade: dist
    update_cache: yes

- name: Set timezone
  timezone:
    name: Europe/Istanbul

- name: Disable root SSH
  lineinfile:
    path: /etc/ssh/sshd_config
    regexp: '^PermitRootLogin'
    line: 'PermitRootLogin no'
  notify: restart sshd

- name: Install fail2ban
  apt:
    name: fail2ban
    state: present

- name: UFW rules
  ufw:
    rule: "{{ item.rule }}"
    port: "{{ item.port }}"
  loop:
    - { rule: allow, port: '22' }
    - { rule: allow, port: '80' }
    - { rule: allow, port: '443' }
3.5. Terraform Workflow
bash
# 1. Plan
cd infrastructure/environments/production
terraform plan -out=tfplan

# 2. Review
terraform show tfplan

# 3. Apply (CI/CD'de)
terraform apply tfplan

# 4. State
# S3 backend + DynamoDB lock
Kural: terraform apply sadece CI/CD'de, elle değil.

4. CI/CD Pipeline
4.1. GitHub Actions
yaml
# .github/workflows/ci.yml

name: CI

on:
  pull_request:
    branches: [main, develop]
  push:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_PASSWORD: test
          POSTGRES_DB: test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      
      redis:
        image: redis:7
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pgsql, redis, bcmath, gd
          coverage: pcov
      
      - name: Install Composer
        run: composer install --prefer-dist --no-interaction --no-progress
      
      - name: Install NPM
        run: npm ci
      
      - name: Build assets
        run: npm run build
      
      - name: Prepare env
        run: |
          cp .env.testing .env
          php artisan key:generate
      
      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: test
          DB_USERNAME: postgres
          DB_PASSWORD: test
      
      - name: Static analysis
        run: ./vendor/bin/phpstan analyse --memory-limit=2G
      
      - name: Code style
        run: ./vendor/bin/pint --test
      
      - name: Security scan
        run: |
          composer audit
          ./vendor/bin/security-checker security:check
      
      - name: Tests
        run: ./vendor/bin/pest --coverage --min=80
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3

  build:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Configure AWS
        uses: aws-actions/configure-aws-credentials@v4
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: eu-central-1
      
      - name: Login to ECR
        id: login-ecr
        uses: aws-actions/amazon-ecr-login@v2
      
      - name: Build & push
        env:
          ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
          ECR_REPOSITORY: erp
          IMAGE_TAG: ${{ github.sha }}
        run: |
          docker build -t $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG .
          docker push $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG
          docker tag $ECR_REGISTRY/$ECR_REPOSITORY:$IMAGE_TAG $ECR_REGISTRY/$ECR_REPOSITORY:latest
          docker push $ECR_REGISTRY/$ECR_REPOSITORY:latest
4.2. Deploy Workflow
yaml
# .github/workflows/deploy.yml

name: Deploy

on:
  workflow_run:
    workflows: [CI]
    types: [completed]
    branches: [main]

jobs:
  deploy-staging:
    if: ${{ github.event.workflow_run.conclusion == 'success' }}
    runs-on: ubuntu-latest
    environment: staging
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Deploy to staging
        uses: ./.github/actions/deploy
        with:
          environment: staging
          image: ${{ github.sha }}
      
      - name: Smoke tests
        run: |
          curl -f https://staging.erp.com/health
          ./scripts/smoke-test.sh staging
  
  deploy-production:
    needs: deploy-staging
    runs-on: ubuntu-latest
    environment: production
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Wait for manual approval
        uses: trstringer/manual-approval@v1
        with:
          approvers: tech-leads
          minimum-approvals: 1
      
      - name: Deploy to production (blue-green)
        uses: ./.github/actions/deploy
        with:
          environment: production
          image: ${{ github.sha }}
          strategy: blue-green
      
      - name: Smoke tests
        run: ./scripts/smoke-test.sh production
      
      - name: Notify
        if: always()
        run: |
          curl -X POST $SLACK_WEBHOOK -d '{"text":"Deploy ${{ job.status }}"}'
4.3. Branch Stratejisi
text
main (production)
    ↑
    │ (PR + review + merge)
develop (staging)
    ↑
    │ (PR)
feature/xyz
hotfix/abc
Kural:

main = production, her commit deploy

develop = staging, her commit deploy

feature/* = lokal, PR açılınca CI

hotfix/* = production bug, direkt main'e

4.4. PR Checklist
Her PR:

✅ CI yeşil

✅ Test coverage ≥ %80

✅ Code review (1+ approval)

✅ Changelog (varsa)

✅ Migration test (varsa)

✅ Security scan (SAST)

✅ Performance check (varsa büyük değişiklik)

Kural: "LGTM" + tüm check'ler → merge.

5. Deployment Stratejileri
5.1. Blue-Green Deployment
En güvenli — sıfır downtime.

text
┌──────────────────────────────────────────────────┐
│  LOAD BALANCER                                   │
└──────────┬───────────────────────────────────────┘
           │
    ┌──────┴──────┐
    │             │
    ▼             ▼
┌────────┐   ┌────────┐
│ BLUE   │   │ GREEN  │
│ v1.0   │   │ v1.1   │
│ (canlı)│   │ (yeni) │
└────────┘   └────────┘
Flow:

text
1. Green environment yeni versiyonla hazırla
2. Green'de smoke test
3. Load balancer'ı green'e çevir
4. Blue'yu kapat (veya yedek tut)
5. Rollback gerekirse: LB'yi blue'ya çevir
Uygulama:

bash
# AWS ALB target group switch
aws elbv2 modify-listener \
    --listener-arn $LISTENER_ARN \
    --default-actions Type=forward,TargetGroupArn=$GREEN_TG_ARN
Avantaj: Anlık rollback.

Dezavantaj: 2x kaynak.

5.2. Canary Deployment
Kademeli — %1 → %10 → %50 → %100.

text
1. Yeni versiyon %1 trafiği alır
2. 10 dk izle (error rate, latency)
3. Sorun yoksa %10
4. Sorun yoksa %50
5. Sorun yoksa %100
6. Sorun varsa: rollback
Uygulama:

yaml
# AWS ALB weighted target groups
- target_group: v1
  weight: 99
- target_group: v2
  weight: 1
Avantaj: Risk minimize.

Dezavantaj: Karmaşık monitoring.

5.3. Rolling Deployment
Kademeli instance değişimi.

text
1. 4 instance var
2. 1'ini durdur → yeni versiyonla başlat
3. Sağlıklı → 2.
4. Sağlıklı → 3.
5. Sağlıklı → 4.
Kullanım: Stateless app (bizim gibi).

Kural: Health check zorunlu.

5.4. Seçim
Strateji	Kullanım
Blue-Green	Major değişiklik, riskli
Canary	Yeni feature, gradual
Rolling	Standart deploy, sık
Bizim seçim: Rolling (standart) + Blue-Green (major release).

6. Zero-Downtime Deployment
6.1. Nedir?
Kullanıcı hiç kesinti görmez. Deploy sırasında:

✅ Site açılır

✅ API çalışır

✅ Session korunur

✅ Queue devam eder

6.2. Gereklilikler
6.2.1. Stateless App
Kural: App stateless olmalı.

Session → Redis (file değil)

Upload → S3 (local değil)

Cache → Redis

Queue → Redis/SQS

6.2.2. Database Migration Strategy
Kritik: Migration break etmemeli.

Pattern: Expand-Contract (3 aşama)

text
Aşama 1 (deploy v1):
- Yeni kolon ekle (nullable)
- Eski kod çalışır
- Yeni kod henüz yok

Aşama 2 (deploy v2):
- Yeni kodu deploy et
- Yeni kolon kullanılır
- Eski kolon hâlâ durur

Aşama 3 (deploy v3):
- Eski kolonu sil
Örnek:

php
// v1: initial
Schema::create('fin_customer', function ($t) {
    $t->string('name');
});

// v2: yeni kolon ekle (expand)
Schema::table('fin_customer', function ($t) {
    $t->string('first_name')->nullable();
    $t->string('last_name')->nullable();
});

// v2.5: backfill (background job)
// UPDATE fin_customer SET first_name = ..., last_name = ...;

// v3: eski kolonu sil (contract)
Schema::table('fin_customer', function ($t) {
    $t->dropColumn('name');
});
Kural: Her migration rollback edilebilir.

6.2.3. Health Check
php
// routes/web.php
Route::get('/health', function () {
    $checks = [
        'app' => true,
        'db' => DB::connection()->getPdo() !== null,
        'redis' => Redis::ping() === 'PONG',
        'storage' => Storage::disk('s3')->exists('health-check.txt'),
    ];
    
    $status = collect($checks)->every(fn($v) => $v) ? 200 : 503;
    
    return response()->json($checks, $status);
});
Load balancer: Health check fail → instance trafik almaz.

6.2.4. Graceful Shutdown
Kural: Instance dururken aktif request'i bitir.

yaml
# kubernetes / ECS
terminationGracePeriodSeconds: 30

# nginx graceful shutdown
nginx -s quit
App:

php
// Laravel signal handling
$app->terminating(function () {
    // Aktif request'leri bitir
    // Queue worker'ları durdur
});
6.3. Deploy Flow (Zero-Downtime)
text
1. Yeni instance'ı başlat (v2)
    ↓
2. Health check geçmesini bekle
    ↓
3. Load balancer'a ekle
    ↓
4. Eski instance'ı LB'den çıkar (v1)
    ↓
5. Aktif request'leri bekle (30 sn)
    ↓
6. Eski instance'ı kapat
    ↓
7. Tekrarla (her instance için)
Sonuç: Kullanıcı hiç kesinti görmez.

7. Database Migration Stratejisi
7.1. Migration Prensipleri
Prensip	Açıklama
Idempotent	Tekrar çalıştırılabilir
Reversible	Rollback çalışır
Backward compatible	Eski kod çalışır
Fast	< 5 dk (büyük tablo hariç)
No lock	CONCURRENTLY (index)
Tested	Staging'de test
7.2. Migration Türleri
7.2.1. Yeni Tablo (Hızlı)
php
Schema::create('fin_new_table', function ($t) {
    // ...
});
Süre: < 1 sn.

7.2.2. Kolon Ekleme (Hızlı)
php
// Nullable + default yok = anlık
Schema::table('fin_customer', function ($t) {
    $t->string('new_col')->nullable();
});
Süre: < 1 sn.

7.2.3. Index Ekleme (Dikkat)
php
// ❌ Yavaş (table lock)
Schema::table('fin_customer', function ($t) {
    $t->index('email');
});

// ✅ Hızlı (concurrent)
DB::statement('CREATE INDEX CONCURRENTLY idx_fin_customer_email ON fin_customer(email)');
Süre: Büyük tabloda 5-30 dk ama lock yok.

Kural: CONCURRENTLY transaction dışında (Laravel'de DB::statement).

7.2.4. Kolon Silme (Contract)
php
// Sadece deploy v3'te (v2 tamamen aktif olduktan sonra)
Schema::table('fin_customer', function ($t) {
    $t->dropColumn('old_col');
});
Kural: Eski kolon hemen silinmez — 1-2 hafta bekle.

7.2.5. Data Migration (Background)
php
// Migration'da YASAK
public function up()
{
    // ❌ 1M satır update = downtime
    DB::table('fin_customer')->update(['new_col' => DB::raw('old_col')]);
}

// ✅ Background job
public function up()
{
    Schema::table('fin_customer', function ($t) {
        $t->string('new_col')->nullable();
    });
    
    dispatch(new BackfillCustomerNewCol());
}
Job:

php
class BackfillCustomerNewCol implements ShouldQueue
{
    public function handle(): void
    {
        FinCustomer::whereNull('new_col')
            ->chunkById(1000, function ($customers) {
                foreach ($customers as $c) {
                    $c->new_col = $c->old_col;
                    $c->save();
                }
            });
    }
}
Kural: Data migration her zaman background.

7.3. Migration Workflow
text
1. Developer migration yazar
    ↓
2. CI'da test (fresh DB)
    ↓
3. Staging'de deploy (otomatik)
    ↓
4. Manual review (varsa büyük)
    ↓
5. Production deploy:
    ├── Migration önce (expand)
    ├── Yeni kod
    └── (contract 1-2 hafta sonra)
7.4. Rollback
bash
# Tek adım geri
php artisan migrate:rollback --step=1

# Belirli batch
php artisan migrate:rollback --batch=5
Kural: Rollback test edilmeli (staging'de).

7.5. Zero-Downtime Migration Checklist
□ Migration idempotent
□ Rollback çalışır
□ Backward compatible (eski kod çalışır)
□ Lock yok (CONCURRENTLY)
□ Data migration background
□ Staging'de test edildi
□ Timeout (max 5 dk)
□ Monitor (deploy sırasında)
8. Backup & Restore
8.1. Backup Stratejisi — 3-2-1
Kural	Uygulama
3 kopya	Production + 2 backup
2 farklı yer	S3 + başka region
1 offsite	Cross-region
8.2. Backup Türleri
8.2.1. Database Full Backup
yaml
schedule: "0 */6 * * *"                # 6 saatte bir
method: "aurora-automated"
retention: 30                          # gün
encryption: "AES-256"
location: "eu-central-1"
offsite: "eu-west-1"                   # cross-region
8.2.2. Database Point-in-Time
yaml
method: "aurora-pitr"
retention: 35                          # gün
rpo: 5                                 # dakika
Kullanım: "Dün 14:32'deki hale dön".

8.2.3. File Backup
yaml
schedule: "0 2 * * *"                  # günlük
source: "s3://erp-production"
destination: "s3://erp-backup"
versioning: enabled
retention: 90
8.2.4. Config Backup
yaml
schedule: "0 3 * * *"
source: "git + terraform state"
destination: "s3://erp-config-backup"
retention: 365
8.3. Restore Test
Kural: Backup test edilmeden sayılmaz.

yaml
test_restore:
  schedule: "weekly (Pazar 04:00)"
  environment: "staging"
  steps:
    - "Restore latest backup to staging"
    - "Run smoke tests"
    - "Verify data integrity"
    - "Report"
8.4. Restore Prosedürü
text
1. Karar (RTO: 1 saat)
    ↓
2. Restore target belirle (time? full?)
    ↓
3. Aurora console → Restore
    ↓
4. Yeni cluster (10-15 dk)
    ↓
5. App config güncelle (DB host)
    ↓
6. Test (health check, smoke)
    ↓
7. DNS switch (eğer ana cluster down)
    ↓
8. Post-mortem
8.5. RTO / RPO
Metrik	Hedef
RTO (Recovery Time)	1 saat
RPO (Recovery Point)	5 dakika
Backup süresi	30 dk
Restore süresi	30 dk
Test sıklığı	Haftalık
9. Monitoring & Observability
9.1. 3 Sütun
Sütun	Araç	Ne
Logs	Loki / CloudWatch	Structured log
Metrics	Prometheus / CloudWatch	CPU, RAM, request
Traces	Jaeger / X-Ray	Request flow
9.2. Laravel Pulse
php
// config/pulse.php
return [
    'recorders' => [
        Recorders\CacheInteractions::class => [
            'enabled' => true,
            'sample_rate' => 0.1,
        ],
        Recorders\Exceptions::class => [
            'enabled' => true,
            'sample_rate' => 1.0,
        ],
        Recorders\Queues::class => [
            'enabled' => true,
            'sample_rate' => 1.0,
        ],
        Recorders\Servers::class => [
            'enabled' => true,
        ],
        Recorders\SlowJobs::class => [
            'enabled' => true,
            'threshold' => 1000,        // ms
        ],
        Recorders\SlowQueries::class => [
            'enabled' => true,
            'threshold' => 500,         // ms
        ],
        Recorders\SlowRequests::class => [
            'enabled' => true,
            'threshold' => 1000,        // ms
        ],
    ],
];
Dashboard: /pulse

9.3. Sentry
php
// config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'environment' => env('APP_ENV'),
'traces_sample_rate' => 0.1,
'profiles_sample_rate' => 0.1,
'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
    // PII filtrele
    return $event;
},
Kural: Her exception Sentry'e.

9.4. Uptime Monitoring
UptimeRobot — 1 dk aralık

Pingdom — multi-region

Custom — Grafana + Prometheus

Uyarı:

yaml
alerts:
  - name: "Site down"
    condition: "uptime_check_failed"
    severity: "P1"
    notify: ["pagerduty", "slack"]
  
  - name: "Slow response"
    condition: "response_time > 2000ms"
    window: "5m"
    severity: "P2"
    notify: ["slack"]
9.5. Dashboard
text
┌──────────────────────────────────────────────────────────┐
│  Production Dashboard                                     │
├──────────────────────────────────────────────────────────┤
│  🚀 Uptime: 99.98% (30d)                                 │
│  📊 Requests: 1.2M/day                                   │
│  ⚡ P95 latency: 145ms                                   │
│  ❌ Error rate: 0.02%                                    │
│                                                          │
│  ── Infrastructure ──                                    │
│  CPU: 45%  │  RAM: 62%  │  Disk: 38%  │  Network: 1Gbps │
│                                                          │
│  ── Database ──                                          │
│  Connections: 45/100  │  CPU: 28%  │  Replication: 12ms │
│                                                          │
│  ── Queue ──                                             │
│  Pending: 234  │  Failed: 2  │  Workers: 8              │
│                                                          │
│  ── Deploy ──                                            │
│  Last: v1.4.2 (5 dk önce)  │  Status: ✅                │
│                                                          │
│  ── Errors (24h) ──                                      │
│  Sentry: 12 errors (all resolved)                        │
└──────────────────────────────────────────────────────────┘
9.6. Alerts (Öncelikli)
Alert	Severity
Site down	P1
DB down	P1
High error rate (> 1%)	P1
Auth failures spike	P1
Cross-tenant violation	P1
Backup failed	P2
Slow response (> 2s)	P2
Queue backlog	P2
Disk > 80%	P2
Memory > 90%	P2
SSL expiring	P3
Notification:

P1 → PagerDuty + SMS + Slack

P2 → Slack + Email

P3 → Slack

10. Disaster Recovery
10.1. DR Plan
Senaryo	Etki	RTO	RPO
App server down	Servis durur	5 dk	0
DB down	Servis durur	15 dk	5 dk
Region down	Servis durur	1 saat	15 dk
Data corruption	Veri kaybı	1 saat	1 saat
Ransomware	Her şey	4 saat	24 saat
10.2. Multi-Region Setup
text
Primary Region (eu-central-1)
├── App (ECS)
├── DB (Aurora writer)
├── Cache (ElastiCache)
└── S3 (versioned)

                    ↕ (replication)

DR Region (eu-west-1)
├── App (ECS - standby)
├── DB (Aurora replica)
├── Cache (ElastiCache - standby)
└── S3 (cross-region replica)
10.3. Failover
bash
# 1. Health check (region down mı?)
aws route53 get-health-check-status --health-check-id X

# 2. DR region'a failover
aws route53 change-resource-record-sets \
    --hosted-zone-id $ZONE_ID \
    --change-batch '{
        "Changes": [{
            "Action": "UPSERT",
            "ResourceRecordSet": {
                "Name": "app.erp.com",
                "Type": "A",
                "AliasTarget": {
                    "HostedZoneId": "ZDR",
                    "DNSName": "dr-alb.eu-west-1.elb.amazonaws.com",
                    "EvaluateTargetHealth": true
                }
            }
        }]
    }'

# 3. DR app scale up
aws ecs update-service --cluster erp-dr --service app --desired-count 10

# 4. Aurora promote replica
aws rds promote-read-replica --db-instance-identifier erp-dr-replica

# 5. Doğrula
curl -f https://app.erp.com/health
Süre: 15-30 dk.

10.4. DR Test
yaml
dr_test:
  schedule: "quarterly"
  steps:
    - "Simulate region down"
    - "Execute failover"
    - "Verify functionality"
    - "Measure RTO"
    - "Failback"
    - "Post-mortem"
Kural: DR test yapılmayan DR = DR değil.

11. Cost Optimization
11.1. Maliyet Kalemleri
Kalem	Aylık (100K tenant)
Compute (ECS)	$2.000
DB (Aurora)	$1.500
Cache (Redis)	$300
Storage (S3)	$500
Bandwidth (CDN)	$400
Backup	$200
Monitoring	$300
Total	~$5.200
Per tenant: $0.05/ay (çok iyi).

11.2. Optimization
Strateji	Kazanç
ARM (Graviton)	%20-40 compute
Spot instances (non-critical)	%60-70
Reserved (DB, cache)	%30-40
S3 lifecycle	%50 storage
CDN caching	%60 bandwidth
Auto-scaling	%30 compute (gece)
11.3. Fiyatlandırma Modeli
text
Tenant planı:
  Free:      $0
  Starter:   $29/ay
  Pro:       $99/ay
  Enterprise:$499/ay

Bizim maliyet:
  Free:      $0.02
  Starter:   $0.05
  Pro:       $0.20
  Enterprise:$1.00

Margin: %95+
Kural: SaaS ekonomisi margin > %80.

12. Türkiye / Global Pazar
12.1. Region Seçimi
Pazar	Region
TR	eu-central-1 (Frankfurt) veya yerel (VDS)
EU	eu-central-1, eu-west-1
US	us-east-1, us-west-2
APAC	ap-southeast-1 (Singapore)
Kural: Tenant kendi bölgesinde (Belge 15 — data residency).

12.2. Data Residency
TR müşterisi → EU region (yakın) veya TR lokal

EU müşterisi → EU region (zorunlu)

US müşterisi → US region (zorunlu)

12.3. Latency
Region	TR'ye latency
eu-central-1 (Frankfurt)	40-60 ms
TR lokal	5-15 ms
us-east-1	150-200 ms
Kural: TR müşterisi için Frankfurt başlangıç, TR lokal ileride.

13. Anti-Patterns
Bu projede yasak olan DevOps hataları:

Manual deploy — CI/CD zorunlu

FTP deploy — Git push değil

git pull production — Container/Pipeline

Downtime deploy — Zero-downtime zorunlu

Migration in prod without test — Staging zorunlu

Data migration in transaction — Background job

No rollback — Her deploy rollback edilebilir

No health check — /health zorunlu

No monitoring — Sentry + Pulse + uptime

No backup — 3-2-1 kural

Backup without test — Haftalık restore

Single region — DR region zorunlu

No DR test — Quarterly

Secret in code — Vault/Secrets Manager

APP_DEBUG=true prod — Bilgi sızıntısı

Same env config — Environment parity

Deploy Friday 17:00 — Deploy freeze (Cuma 14:00 sonrası)

Direct DB access prod — Bastion + audit

php artisan tinker prod — Yasak (audit)

Manual migration prod — CI/CD zorunlu

14. Roadmap
Aşama	Süre	Kapsam
1. Foundation	4 hafta	CI/CD, staging, prod
2. Docker	4 hafta	Container, registry
3. Zero-downtime	4 hafta	Blue-green, health check
4. IaC	6 hafta	Terraform, Ansible
5. Observability	4 hafta	Pulse, Sentry, uptime
6. DR	4 hafta	Multi-region, failover
7. K8s (opsiyonel)	8 hafta	Kubernetes migration
8. Cost optimization	4 hafta	ARM, spot, reserved
Toplam: 38 hafta (~10 ay).

15. Glossary
Terim	Anlam
CI	Continuous Integration
CD	Continuous Deployment
IaC	Infrastructure as Code
Blue-Green	2 environment switch
Canary	Gradual rollout
Rolling	Kademeli update
RTO	Recovery Time Objective
RPO	Recovery Point Objective
DR	Disaster Recovery
PITR	Point-in-Time Recovery
HA	High Availability
Health check	Servis sağlık kontrolü
Graceful shutdown	Nazik kapatma
Expand-Contract	Migration pattern
Pulse	Laravel monitoring
Sentry	Error tracking
PagerDuty	Alert management
Bastion	Secure access server
Rollback	Geri alma
Rollforward	Yeni versiyonla düzeltme
Deploy freeze	Yayın dondurma
16. Değişiklik Geçmişi
Tarih	Sürüm	Değişiklik
2026-11-06	1.0	İlk sürüm — deployment, CI/CD, DR, monitoring