@php
    $user = auth()->user();
    $isSuper = $user?->isSuperadmin();
    $current = request()->route()?->getName() ?? '';

    $icons = [
        'chart-pie' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
        'building'  => 'M3 21h18 M5 21V7l7-4 7 4v14 M9 9h.01 M9 13h.01 M9 17h.01 M15 9h.01 M15 13h.01 M15 17h.01',
        'users'     => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2 M9 11a4 4 0 100-8 4 4 0 000 8z M23 21v-2a4 4 0 00-3-3.87 M16 3.13a4 4 0 010 7.75',
        'puzzle'    => 'M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 01-.837.276c-.47-.07-.802-.48-.968-.925a2.501 2.501 0 10-3.214 3.214c.446.166.855.497.925.968a.979.979 0 01-.276.837l-1.61 1.61a2.404 2.404 0 01-1.705.707 2.402 2.402 0 01-1.704-.706l-1.568-1.568a1.026 1.026 0 00-.877-.29c-.493.074-.84.504-1.02.968a2.5 2.5 0 11-3.237-3.237c.464-.18.894-.527.967-1.02a1.026 1.026 0 00-.289-.877l-1.568-1.568A2.402 2.402 0 011.998 12c0-.617.236-1.234.706-1.704L4.23 8.77c.24-.24.581-.353.917-.303.515.077.877.528 1.073 1.01a2.5 2.5 0 103.259-3.259c-.482-.196-.933-.558-1.01-1.073-.05-.336.062-.676.303-.917l1.525-1.525A2.402 2.402 0 0112 1.998c.617 0 1.234.236 1.704.706l1.568 1.568c.23.23.556.338.877.29.493-.074.84-.504 1.02-.968a2.5 2.5 0 113.237 3.237c-.464.18-.894.527-.967 1.02z',
        'bolt'      => 'M13 10V3L4 14h7v7l9-11h-7z',
        'sliders'   => 'M4 21v-7 M4 10V3 M12 21v-9 M12 8V3 M20 21v-5 M20 12V3 M1 14h6 M9 8h6 M17 16h6',
        'cube'      => 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z M3.27 6.96L12 12.01l8.73-5.05 M12 22.08V12',
        'clock'     => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'list'      => 'M4 6h16 M4 10h16 M4 14h16 M4 18h16',
        'book'      => 'M4 19.5A2.5 2.5 0 016.5 17H20 M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z',
        'truck'     => 'M1 3h15v13H1z M16 8h4l3 3v5h-7V8z M5.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5z M18.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'file-invoice' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8',
        'credit-card' => 'M1 4h22v16H1z M1 10h22',
        'trending-down' => 'M23 18l-9.5-9.5-5 5L1 6 M17 18h6v-6',
    ];

    $sections = [
        ['label' => $isSuper ? 'Platform' : 'Operasyon', 'links' => array_filter([
            ['route' => 'dashboard', 'icon' => 'chart-pie', 'label' => 'Dashboard', 'active' => $current === 'dashboard'],
            $isSuper ? ['route' => 'tenants.index', 'icon' => 'building', 'label' => 'Tenants', 'active' => str_starts_with($current, 'tenants')] : null,
        ])],
        ['label' => 'Operasyon', 'links' => [
            ['route' => 'users.index', 'icon' => 'users', 'label' => 'Users', 'active' => str_starts_with($current, 'users')],
            ['route' => 'modules.index', 'icon' => 'puzzle', 'label' => 'Modules', 'active' => str_starts_with($current, 'modules')],
            ['route' => 'plugins.index', 'icon' => 'bolt', 'label' => 'Plugins', 'active' => str_starts_with($current, 'plugins')],
            ['route' => 'custom-fields.index', 'icon' => 'sliders', 'label' => 'Custom Fields', 'active' => str_starts_with($current, 'custom-fields')],
            ['route' => 'custom-objects.index', 'icon' => 'cube', 'label' => 'Custom Objects', 'active' => str_starts_with($current, 'custom-objects')],
        ]],
        ['label' => 'Finans', 'links' => [
            ['route' => 'fin.coa', 'icon' => 'list', 'label' => 'Hesap Planı', 'active' => str_starts_with($current, 'fin.coa')],
            ['route' => 'fin.journal-entries', 'icon' => 'book', 'label' => 'Yevmiye', 'active' => str_starts_with($current, 'fin.journal')],
            ['route' => 'fin.suppliers', 'icon' => 'truck', 'label' => 'Tedarikçiler', 'active' => str_starts_with($current, 'fin.suppliers')],
            ['route' => 'fin.ap-invoices', 'icon' => 'file-invoice', 'label' => 'Tedarikçi Faturaları', 'active' => str_starts_with($current, 'fin.ap-invoices')],
            ['route' => 'fin.ap-payments', 'icon' => 'credit-card', 'label' => 'Ödemeler', 'active' => str_starts_with($current, 'fin.ap-payments')],
            ['route' => 'fin.ap-aging', 'icon' => 'trending-down', 'label' => 'AP Aging', 'active' => str_starts_with($current, 'fin.ap-aging')],
        ]],
        ['label' => 'Güvenlik', 'links' => [
            ['route' => 'audit-log.index', 'icon' => 'clock', 'label' => 'Audit Log', 'active' => str_starts_with($current, 'audit-log')],
        ]],
    ];
@endphp

<aside class="hidden lg:flex w-64 flex-col bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 dark:from-slate-950 dark:via-slate-950 dark:to-black text-slate-100 border-r border-slate-800/50">
    <div class="h-16 flex items-center px-6 border-b border-slate-800/50">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/20 group-hover:shadow-primary-500/40 transition-all">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold tracking-tight text-white leading-tight">Kurumsal ERP</span>
                <span class="text-[10px] font-medium text-slate-500 tracking-widest uppercase">Enterprise</span>
            </div>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm scrollbar-thin">
        @foreach($sections as $section)
            @php $visibleLinks = array_filter($section['links']); @endphp
            @if(!empty($visibleLinks))
                <div class="pt-4 pb-1.5 px-3 text-[10px] uppercase tracking-widest text-slate-500 font-semibold">{{ $section['label'] }}</div>
                @foreach($visibleLinks as $link)
                    <a href="{{ route($link['route']) }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ $link['active'] ? 'bg-gradient-to-r from-primary-600 to-primary-500 text-white shadow-lg shadow-primary-500/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ $link['active'] ? '' : 'text-slate-500 group-hover:text-primary-400 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$link['icon']] }}"/>
                        </svg>
                        <span class="font-medium">{{ $link['label'] }}</span>
                        @if($link['active'])<span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/80"></span>@endif
                    </a>
                @endforeach
            @endif
        @endforeach
    </nav>

    @if($user)
        <div class="p-3 border-t border-slate-800/50">
            <div class="flex items-center gap-3 px-2 py-2 rounded-xl bg-slate-800/40">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold shadow-md">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-white truncate">{{ $user->name }}</div>
                    <div class="text-[11px] text-slate-400 truncate">{{ $user->role }}</div>
                </div>
            </div>
        </div>
    @endif
</aside>
