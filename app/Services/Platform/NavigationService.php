<?php
namespace App\Services\Platform;

use App\Models\SysUserNavItem;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

final class NavigationService
{
    private static ?array $menuCache = null;

    public function menu(): array
    {
        if (self::$menuCache !== null) return self::$menuCache;

        self::$menuCache = [
            'main' => [
                'label'  => 'Ana Menü',
                'icon'   => 'home',
                'groups' => [
                    ['type' => 'link', 'route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'chart-pie', 'module' => 'PLT'],
                ],
            ],

            'fin' => [
                'label'  => 'Finans',
                'icon'   => 'banknote',
                'module' => 'FIN',
                'groups' => [
                    ['type' => 'group', 'label' => 'Muhasebe', 'items' => [
                        ['route' => 'fin.coa',             'label' => 'Hesap Planı',    'icon' => 'list'],
                        ['route' => 'fin.journal-entries', 'label' => 'Yevmiye',        'icon' => 'book'],
                        ['route' => 'fin.periods',         'label' => 'Dönem Kapanışı', 'icon' => 'lock-closed'],
                    ]],
                    ['type' => 'group', 'label' => 'Banka & Kasa', 'items' => [
                        ['route' => 'fin.bank-accounts',     'label' => 'Banka Hesapları', 'icon' => 'building'],
                        ['route' => 'fin.bank-transactions', 'label' => 'Hareketler',       'icon' => 'switch-horizontal'],
                        ['route' => 'fin.bank-statements',   'label' => 'Ekstreler',        'icon' => 'banknotes'],
                        ['route' => 'fin.reconciliation',    'label' => 'Mutabakat',        'icon' => 'arrows-right-left'],
                    ]],
                    ['type' => 'group', 'label' => 'Tedarikçi & Borç', 'items' => [
                        ['route' => 'fin.suppliers',   'label' => 'Tedarikçiler',        'icon' => 'truck'],
                        ['route' => 'fin.ap-invoices', 'label' => 'Tedarikçi Faturaları', 'icon' => 'file-invoice'],
                        ['route' => 'fin.ap-payments', 'label' => 'Tedarikçi Ödemeleri',  'icon' => 'credit-card'],
                        ['route' => 'fin.ap-aging',    'label' => 'AP Aging',             'icon' => 'trending-down'],
                    ]],
                    ['type' => 'group', 'label' => 'Müşteri & Alacak', 'items' => [
                        ['route' => 'fin.customers',   'label' => 'Müşteriler',        'icon' => 'user'],
                        ['route' => 'fin.ar-invoices', 'label' => 'Müşteri Faturaları', 'icon' => 'shopping-cart'],
                        ['route' => 'fin.ar-receipts', 'label' => 'Tahsilatlar',        'icon' => 'cash'],
                        ['route' => 'fin.statement',   'label' => 'Cari Ekstre',        'icon' => 'document-text'],
                    ]],
                    ['type' => 'group', 'label' => 'Bütçe & Varlık', 'items' => [
                        ['route' => 'fin.budgets',         'label' => 'Bütçe Planlama',       'icon' => 'scales'],
                        ['route' => 'fin.budget-variance', 'label' => 'Bütçe vs Gerçekleşen', 'icon' => 'trending-up'],
                        ['route' => 'fin.fixed-assets',    'label' => 'Duran Varlıklar',      'icon' => 'home'],
                        ['route' => 'fin.depreciation',    'label' => 'Amortisman',           'icon' => 'trending-down'],
                    ]],
                    ['type' => 'group', 'label' => 'Kur & Raporlar', 'items' => [
                        ['route' => 'fin.currencies',     'label' => 'Para Birimleri', 'icon' => 'currency-exchange'],
                        ['route' => 'fin.exchange-rates', 'label' => 'Döviz Kurları',  'icon' => 'trending-up'],
                        ['route' => 'fin.reports',        'label' => 'Raporlar',       'icon' => 'chart-bar'],
                    ]],
                    ['type' => 'group', 'label' => 'Masraf Merkezleri', 'items' => [
                        ['route' => 'fin.cost-centers', 'label' => 'Masraf Merkezleri', 'icon' => 'target'],
                    ]],
                ],
            ],

            'otc' => [
                'label'  => 'Satış',
                'icon'   => 'shopping-cart',
                'module' => 'OTC',
                'groups' => [
                    ['type' => 'link', 'route' => 'otc.sales-orders',       'label' => 'Satış Siparişleri',   'icon' => 'shopping-cart'],
                    ['type' => 'link', 'route' => 'otc.convert-to-invoice', 'label' => 'Siparişten Faturaya', 'icon' => 'document-duplicate'],
                    ['type' => 'link', 'route' => 'inv.price-lists',        'label' => 'Fiyat Listeleri',     'icon' => 'tag', 'module' => 'INV'],
                ],
            ],

            'prc' => [
                'label'  => 'Satın Alma',
                'icon'   => 'clipboard-list',
                'module' => 'PRC',
                'groups' => [
                    ['type' => 'link', 'route' => 'prc.purchase-orders', 'label' => 'Satın Alma Siparişleri', 'icon' => 'clipboard-list'],
                    ['type' => 'link', 'route' => 'prc.goods-receipts',  'label' => 'Mal Kabul',              'icon' => 'inbox-in'],
                    ['type' => 'link', 'route' => 'prc.three-way-match', 'label' => '3-Way Match',            'icon' => 'check-badge'],
                ],
            ],

            'inv' => [
                'label'  => 'Envanter',
                'icon'   => 'archive',
                'module' => 'INV',
                'groups' => [
                    ['type' => 'link', 'route' => 'inv.products',        'label' => 'Ürünler',           'icon' => 'archive'],
                    ['type' => 'link', 'route' => 'inv.stock',           'label' => 'Stok Hareketleri',  'icon' => 'cube'],
                    ['type' => 'link', 'route' => 'inv.warehouses',      'label' => 'Depolar',           'icon' => 'building'],
                    ['type' => 'link', 'route' => 'inv.stock-transfers', 'label' => 'Depo Transferleri', 'icon' => 'switch-horizontal'],
                ],
            ],

            'mfg' => [
                'label'  => 'Üretim',
                'icon'   => 'cog',
                'module' => 'MFG',
                'groups' => [
                    ['type' => 'link', 'route' => 'mfg.boms',              'label' => 'Reçeteler (BOM)', 'icon' => 'list'],
                    ['type' => 'link', 'route' => 'mfg.production-orders', 'label' => 'Üretim Emirleri', 'icon' => 'cog'],
                    ['type' => 'link', 'route' => 'mfg.mrp',               'label' => 'MRP',             'icon' => 'calculator'],
                ],
            ],

            'hrm' => [
                'label'  => 'İnsan Kaynakları',
                'icon'   => 'identification',
                'module' => 'HRM',
                'groups' => [
                    ['type' => 'link', 'route' => 'hrm.employees',   'label' => 'Personel',     'icon' => 'identification'],
                    ['type' => 'link', 'route' => 'hrm.attendances', 'label' => 'Puantaj/İzin', 'icon' => 'calendar'],
                    ['type' => 'link', 'route' => 'hrm.payrolls',    'label' => 'Bordro',       'icon' => 'banknote'],
                    ['type' => 'link', 'route' => 'hrm.expenses',    'label' => 'Masraflar',    'icon' => 'receipt'],
                ],
            ],

            'crm' => [
                'label'  => 'CRM',
                'icon'   => 'funnel',
                'module' => 'CRM',
                'groups' => [
                    ['type' => 'link', 'route' => 'crm.leads',         'label' => 'Potansiyel Müşteriler', 'icon' => 'funnel'],
                    ['type' => 'link', 'route' => 'crm.opportunities', 'label' => 'Satış Fırsatları',      'icon' => 'star'],
                ],
            ],

            'ppm' => [
                'label'  => 'Projeler',
                'icon'   => 'briefcase',
                'module' => 'PPM',
                'groups' => [
                    ['type' => 'link', 'route' => 'ppm.projects', 'label' => 'Projeler', 'icon' => 'briefcase'],
                ],
            ],
            'eam' => [
                'label'  => 'Varlık Yönetimi',
                'icon'   => 'archive',
                'module' => 'EAM',
                'groups' => [
                    ['type' => 'link', 'route' => 'eam.assets',             'label' => 'Varlıklar',      'icon' => 'archive'],
                    ['type' => 'link', 'route' => 'eam.maintenance-orders', 'label' => 'Bakım Emirleri', 'icon' => 'cog'],
                ],
            ],
            'qm' => [
                'label'  => 'Kalite',
                'icon'   => 'check-badge',
                'module' => 'QM',
                'groups' => [
                    ['type' => 'link', 'route' => 'qm.inspection-plans', 'label' => 'Muayene Planları', 'icon' => 'list'],
                    ['type' => 'link', 'route' => 'qm.ncrs',             'label' => 'NCR',              'icon' => 'alert-triangle'],
                ],
            ],
            'log' => [
                'label'  => 'Lojistik',
                'icon'   => 'truck',
                'module' => 'LOG',
                'groups' => [
                    ['type' => 'link', 'route' => 'log.carriers',  'label' => 'Kargo Firmaları', 'icon' => 'truck'],
                    ['type' => 'link', 'route' => 'log.shipments', 'label' => 'Sevkiyatlar',     'icon' => 'map'],
                ],
            ],

            'plt' => [
                'label'  => 'Platform',
                'icon'   => 'users',
                'module' => 'PLT',
                'groups' => [
                    ['type' => 'link', 'route' => 'users.index',          'label' => 'Kullanıcılar',    'icon' => 'users'],
                    ['type' => 'link', 'route' => 'modules.index',        'label' => 'Modüller',        'icon' => 'puzzle'],
                    ['type' => 'link', 'route' => 'plugins.index',        'label' => 'Eklentiler',      'icon' => 'bolt'],
                    ['type' => 'link', 'route' => 'custom-fields.index',  'label' => 'Özel Alanlar',    'icon' => 'sliders'],
                    ['type' => 'link', 'route' => 'custom-objects.index', 'label' => 'Özel Nesneler',   'icon' => 'cube'],
                    ['type' => 'link', 'route' => 'approvals.inbox',      'label' => 'Onay Kutusu',     'icon' => 'inbox'],
                    ['type' => 'link', 'route' => 'approvals.workflows',  'label' => 'Onay Akışları',   'icon' => 'git-branch'],
                    ['type' => 'link', 'route' => 'documents.index',      'label' => 'Dokümanlar',      'icon' => 'folder'],
                    ['type' => 'link', 'route' => 'notifications.index',  'label' => 'Bildirimler',     'icon' => 'bolt'],
                    ['type' => 'link', 'route' => 'audit-log.index',      'label' => 'Audit Log',       'icon' => 'clock'],
                    ['type' => 'link', 'route' => 'data.import',          'label' => 'Veri İçe Aktar',  'icon' => 'upload'],
                    ['type' => 'link', 'route' => 'data.export',          'label' => 'Veri Dışa Aktar', 'icon' => 'download'],
                ],
            ],

            'settings' => [
                'label'  => 'Ayarlar',
                'icon'   => 'cog',
                'groups' => [
                    ['type' => 'link', 'route' => 'settings.features',        'label' => 'Sistem Özellikleri',   'icon' => 'sliders'],
                    ['type' => 'link', 'route' => 'settings.company',         'label' => 'Şirket Profili',       'icon' => 'office'],
                    ['type' => 'link', 'route' => 'settings.country',         'label' => 'Ülke & Vergi',         'icon' => 'map'],
                    ['type' => 'link', 'route' => 'settings.roles',           'label' => 'Roller & Yetkiler',    'icon' => 'users'],
                    ['type' => 'link', 'route' => 'settings.notifications',   'label' => 'Bildirim Ayarları',    'icon' => 'sliders'],
                    ['type' => 'link', 'route' => 'settings.scheduled-tasks', 'label' => 'Zamanlanmış Görevler', 'icon' => 'clock-2'],
                ],
            ],
        ];

        return self::$menuCache;
    }

    public function searchable(): array
    {
        $out = [];
        foreach ($this->menu() as $section) {
            foreach ($section['groups'] as $group) {
                if (($group['type'] ?? 'link') === 'link') {
                    $out[] = [
                        'route' => $group['route'],
                        'label' => $group['label'],
                        'icon'  => $group['icon'] ?? 'document-text',
                        'path'  => $section['label'] ?? '',
                        'module'=> $group['module'] ?? ($section['module'] ?? 'PLT'),
                    ];
                } elseif (($group['type'] ?? '') === 'group') {
                    foreach ($group['items'] as $item) {
                        $out[] = [
                            'route' => $item['route'],
                            'label' => $item['label'],
                            'icon'  => $item['icon'] ?? 'document-text',
                            'path'  => ($section['label'] ?? '') . ' › ' . $group['label'],
                            'module'=> $item['module'] ?? ($section['module'] ?? 'PLT'),
                        ];
                    }
                }
            }
        }
        return $out;
    }

    public function favorites(int $userId): array
    {
        try {
            if (!Schema::hasTable('sys_user_nav_item')) return [];
            return SysUserNavItem::query()
                ->where('user_id', $userId)
                ->where('kind', SysUserNavItem::KIND_FAVORITE)
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->get()
                ->map(fn($r) => $r->route_name)
                ->all();
        } catch (\Throwable $e) {
            \Log::warning('Nav favorites failed: ' . $e->getMessage());
            return [];
        }
    }

    public function toggleFavorite(int $userId, string $routeName, ?string $tenantId = null): bool
    {
        $tenantId = $tenantId
            ?: TenantContext::id()
            ?: auth()->user()?->tenant_id;

        if (!$tenantId) {
            throw new \RuntimeException('Tenant context yok.');
        }

        if (!Schema::hasTable('sys_user_nav_item')) {
            throw new \RuntimeException('sys_user_nav_item tablosu yok. Migration çalıştırın: php artisan migrate');
        }

        if (!Route::has($routeName)) {
            throw new \InvalidArgumentException("Bilinmeyen route: {$routeName}");
        }

        $existing = SysUserNavItem::query()
            ->where('user_id', $userId)
            ->where('kind', SysUserNavItem::KIND_FAVORITE)
            ->where('route_name', $routeName)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        $maxOrder = SysUserNavItem::query()
            ->where('user_id', $userId)
            ->where('kind', SysUserNavItem::KIND_FAVORITE)
            ->max('sort_order') ?? 0;

        SysUserNavItem::create([
            'id'         => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'route_name' => $routeName,
            'kind'       => SysUserNavItem::KIND_FAVORITE,
            'sort_order' => (int) $maxOrder + 1,
        ]);

        return true;
    }

    public function recents(int $userId, int $limit = 6): array
    {
        try {
            if (!Schema::hasTable('sys_user_nav_item')) return [];
            return SysUserNavItem::query()
                ->where('user_id', $userId)
                ->where('kind', SysUserNavItem::KIND_RECENT)
                ->orderByDesc('last_used_at')
                ->limit($limit)
                ->get()
                ->map(fn($r) => $r->route_name)
                ->all();
        } catch (\Throwable $e) {
            \Log::warning('Nav recents failed: ' . $e->getMessage());
            return [];
        }
    }

    public function trackVisit(int $userId, ?string $routeName): void
    {
        try {
            if (!$routeName || !Route::has($routeName)) return;
            if (!Schema::hasTable('sys_user_nav_item')) return;

            $known = collect($this->searchable())->pluck('route')->all();
            if (!in_array($routeName, $known, true)) return;

            $tenantId = TenantContext::id() ?: auth()->user()?->tenant_id;
            if (!$tenantId) return;

            $item = SysUserNavItem::query()
                ->where('user_id', $userId)
                ->where('kind', SysUserNavItem::KIND_RECENT)
                ->where('route_name', $routeName)
                ->first();

            if ($item) {
                $item->use_count = $item->use_count + 1;
                $item->last_used_at = now();
                $item->save();
            } else {
                SysUserNavItem::create([
                    'id'           => (string) \Illuminate\Support\Str::uuid(),
                    'tenant_id'    => $tenantId,
                    'user_id'      => $userId,
                    'route_name'   => $routeName,
                    'kind'         => SysUserNavItem::KIND_RECENT,
                    'use_count'    => 1,
                    'last_used_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Nav trackVisit failed: ' . $e->getMessage());
        }
    }

    public function visibleForUser(?int $userId): array
    {
        $isSuper = Auth::user()?->isSuperadmin() ?? false;
        $activeModules = $this->activeModules();

        $result = [];
        $featureControlledSections = ['fin', 'otc', 'inv', 'prc', 'mfg', 'hrm', 'crm', 'ppm', 'eam', 'qm', 'log'];

        foreach ($this->menu() as $sectionKey => $section) {
            if (in_array($sectionKey, $featureControlledSections, true)) {
                try {
                    if (!\App\Support\Features::enabled($sectionKey . '.enabled')) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Nav feature check hata: ' . $e->getMessage());
                }
            }

            $secModule = $section['module'] ?? null;
            if (!$isSuper && $secModule && !in_array($secModule, $activeModules, true)) continue;

            $groups = [];
            foreach ($section['groups'] as $group) {
                if (($group['type'] ?? 'link') === 'link') {
                    $mod = $group['module'] ?? $secModule ?? 'PLT';
                    if (!$isSuper && !in_array($mod, $activeModules, true)) continue;
                    $groups[] = $group;
                } elseif (($group['type'] ?? '') === 'group') {
                    $items = [];
                    foreach ($group['items'] as $item) {
                        $mod = $item['module'] ?? $secModule ?? 'PLT';
                        if (!$isSuper && !in_array($mod, $activeModules, true)) continue;
                        $items[] = $item;
                    }
                    if (!empty($items)) $groups[] = array_merge($group, ['items' => $items]);
                }
            }
            if (!empty($groups)) {
                $result[$sectionKey] = array_merge($section, ['groups' => $groups]);
            }
        }
        return $result;
    }

    public function activeModules(): array
    {
        $user = Auth::user();
        if (!$user) return ['PLT'];
        if ($user->isSuperadmin()) {
            return ['PLT','FIN','INV','PRC','OTC','MFG','HRM','CRM','PPM','EAM','QM','LOG'];
        }
        try {
            $role = \App\Models\SysRole::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('code', $user->role)
                ->where('is_active', true)
                ->first();
            $mods = $role ? $role->moduleCodes() : ['PLT','FIN','INV','PRC','OTC','MFG','HRM','CRM','PPM','EAM','QM','LOG'];
        } catch (\Throwable $e) {
            $mods = ['PLT','FIN','INV','PRC','OTC','MFG','HRM','CRM','PPM','EAM','QM','LOG'];
        }
        if (!in_array('PLT', $mods, true)) $mods[] = 'PLT';
        return $mods;
    }
}
