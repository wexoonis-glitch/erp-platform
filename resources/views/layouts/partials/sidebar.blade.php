@php
    $user     = auth()->user();
    $current  = request()->route()?->getName() ?? '';
    $nav      = app(\App\Services\Platform\NavigationService::class);
    $sections = $nav->visibleForUser($user?->id);
    $favorites = $user ? collect($nav->favorites((int) $user->id))->all() : [];

    // Favori route → label haritası
    $favMap = [];
    foreach ($sections as $sec) {
        foreach ($sec['groups'] as $g) {
            if (($g['type'] ?? 'link') === 'link') $favMap[$g['route']] = $g;
            elseif (($g['type'] ?? '') === 'group') {
                foreach ($g['items'] as $it) $favMap[$it['route']] = $it;
            }
        }
    }

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
        'user'      => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2 M12 11a4 4 0 100-8 4 4 0 000 8z',
        'shopping-cart' => 'M9 21a1 1 0 100-2 1 1 0 000 2z M20 21a1 1 0 100-2 1 1 0 000 2z M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6',
        'chart-bar' => 'M18 20V10 M12 20V4 M6 20v-6',
        'archive'   => 'M21 8v13H3V8 M1 3h22v5H1z M10 12h4',
        'document-text' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8',
        'clipboard-list' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'inbox-in'  => 'M22 12h-6l-2 3h-4l-2-3H2 M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z',
        'cash'      => 'M2 6h20v12H2z M12 15a3 3 0 100-6 3 3 0 000 6z',
        'identification' => 'M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z M9 12a3 3 0 100-6 3 3 0 000 6z M15 8h2 M15 12h2 M15 16h2 M7 16h4',
        'calendar'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'cog'       => 'M12 15a3 3 0 100-6 3 3 0 000 6z M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z',
        'banknote'  => 'M2 6h20v12H2z M12 15a3 3 0 100-6 3 3 0 000 6z M6 6h.01 M6 18h.01 M18 6h.01 M18 18h.01',
        'funnel'    => 'M22 3H2l8 9.46V19l4 2v-8.54L22 3z',
        'star'      => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
        'briefcase' => 'M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16',
        'alert-triangle' => 'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z M12 9v4 M12 17h.01',
        'switch-horizontal' => 'M16 3h5v5 M4 20L21 3 M21 16v5h-5 M15 15l6 6 M4 4l5 5',
        'chevron-right' => 'M9 5l7 7-7 7',
        'calculator' => 'M9 7h6m-6 4h.01M9 15h.01M9 19h.01M15 11h.01M15 15h.01M15 19h.01M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z',
        'folder'    => 'M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z',
        'map'       => 'M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4zM8 2v16m8-12v16',
        'receipt'   => 'M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1zM8 7h8M8 11h8M8 15h4',
        'currency-exchange' => 'M4 10h12a2 2 0 012 2v2a2 2 0 01-2 2H4 M4 10V6a2 2 0 012-2h12 M4 10l4-4 M4 14l-4 4 M20 14H8a2 2 0 01-2-2v-2a2 2 0 012-2h12 M20 14v4a2 2 0 01-2 2H6',
        'target'    => 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z M12 18c3.314 0 6-2.686 6-6s-2.686-6-6-6-6 2.686-6 6 2.686 6 6 6z M12 14c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z',
        'lock-closed' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'home'      => 'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z M9 22V12h6v10',
        'trending-up' => 'M23 6l-9.5 9.5-5-5L1 18 M17 6h6v6',
        'scales'    => 'M12 3v18 M3 21h18 M6 6l6-3 6 3 M6 6l-3 9a4 4 0 006 0z M18 6l3 9a4 4 0 01-6 0z',
        'office'    => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'tag'       => 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z M7 7h.01',
        'document-duplicate' => 'M8 7v13a2 2 0 002 2h8a2 2 0 002-2V9a2 2 0 00-2-2h-8a2 2 0 00-2 2z M4 4h10a2 2 0 012 2v2 M16 3l5 5v11a2 2 0 01-2 2h-3',
        'check-badge' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'banknotes' => 'M2 7h20v10H2z M2 12h20 M6 9v6 M18 9v6',
        'arrows-right-left' => 'M8 3L4 7l4 4 M4 7h16 M16 21l4-4-4-4 M20 17H4',
        'clock-2'   => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z M4 4l2 2 M20 4l-2 2',
        'inbox'     => 'M22 12h-6l-2 3h-4l-2-3H2 M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z',
        'git-branch' => 'M6 3v12 M18 9a3 3 0 100-6 3 3 0 000 6z M6 21a3 3 0 100-6 3 3 0 000 6z M18 9a9 9 0 01-9 9',
        'upload'    => 'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4 M17 8l-5-5-5 5 M12 3v12',
        'download'  => 'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4 M7 10l5 5 5-5 M12 15V3',
    ];
@endphp

<aside class="hidden lg:flex w-64 flex-col bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 dark:from-slate-950 dark:via-slate-950 dark:to-black text-slate-100 border-r border-slate-800/50">

    {{-- LOGO --}}
    <div class="h-16 flex items-center px-5 border-b border-slate-800/50 flex-shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold tracking-tight text-white leading-tight">Kurumsal ERP</span>
                <span class="text-[10px] font-medium text-slate-500 tracking-widest uppercase">Enterprise</span>
            </div>
        </a>
    </div>

    {{-- ARAMA --}}
    <div class="px-3 pt-3">
        <button type="button" onclick="erpOpenPalette()"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800/50 hover:bg-slate-800 text-slate-400 hover:text-white text-xs transition border border-slate-700/50">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="flex-1 text-left">Ara…</span>
            <kbd class="px-1.5 py-0.5 rounded bg-slate-900 text-[10px] font-mono border border-slate-700">Ctrl+K</kbd>
        </button>
    </div>

    {{-- FAVORİLER --}}
    @if(!empty($favorites))
        <div class="px-3 pt-3 pb-2 border-b border-slate-800/50">
            <div class="text-[10px] uppercase tracking-widest text-slate-500 font-semibold px-2 mb-1">⭐ Favoriler</div>
            <div class="space-y-0.5">
                @foreach($favorites as $favRoute)
                    @php $fav = $favMap[$favRoute] ?? null; @endphp
                    @if($fav)
                        <a href="{{ route($fav['route']) }}"
                           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs text-slate-300 hover:bg-slate-800/70 hover:text-white transition">
                            <svg class="w-3.5 h-3.5 flex-shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <span class="truncate">{{ $fav['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- NAV --}}
    <nav id="erp-sidebar-nav" class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5 text-sm scrollbar-thin">
        @foreach($sections as $sectionKey => $section)
            @php
                $isActiveSection = false;
                foreach ($section['groups'] as $g) {
                    if (($g['type'] ?? 'link') === 'link' && $g['route'] === $current) { $isActiveSection = true; break; }
                    if (($g['type'] ?? '') === 'group') {
                        foreach ($g['items'] as $it) {
                            if ($it['route'] === $current) { $isActiveSection = true; break 2; }
                        }
                    }
                }
            @endphp
            <details class="erp-section" {{ $isActiveSection ? 'open' : '' }}>
                <summary class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[11px] uppercase tracking-widest font-semibold cursor-pointer transition select-none list-none {{ $isActiveSection ? 'text-primary-300 bg-slate-800/40' : 'text-slate-500 hover:text-slate-300 hover:bg-slate-800/30' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$section['icon']] ?? $icons['list'] }}"/></svg>
                    <span class="flex-1 text-left">{{ $section['label'] }}</span>
                    <svg class="w-3 h-3 erp-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['chevron-right'] }}"/></svg>
                </summary>

                <div class="mt-0.5 space-y-0.5 pl-1">
                    @foreach($section['groups'] as $group)
                        @if(($group['type'] ?? 'link') === 'link')
                            @php $isFav = in_array($group['route'], $favorites, true); @endphp
                            <div class="relative group/link flex items-center">
                                <a href="{{ route($group['route']) }}"
                                   class="flex-1 flex items-center gap-2.5 px-2.5 py-2 rounded-lg transition-all duration-150 pr-8 {{ $group['route'] === $current ? 'bg-gradient-to-r from-primary-600 to-primary-500 text-white shadow-lg shadow-primary-500/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                    <svg class="w-4 h-4 flex-shrink-0 {{ $group['route'] === $current ? '' : 'text-slate-500 group-hover/link:text-primary-400' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$group['icon']] ?? $icons['list'] }}"/></svg>
                                    <span class="font-medium text-[13px] truncate">{{ $group['label'] }}</span>
                                </a>
                                <form method="POST" action="{{ route('nav.favorite.toggle') }}" class="absolute right-1.5 top-1/2 -translate-y-1/2">
                                    @csrf
                                    <input type="hidden" name="route" value="{{ $group['route'] }}">
                                    <input type="hidden" name="back" value="{{ url()->current() }}">
                                    <button type="submit" title="{{ $isFav ? 'Favorilerden çıkar' : 'Favorilere ekle' }}"
                                            class="w-6 h-6 rounded-md flex items-center justify-center transition {{ $isFav ? 'text-amber-400' : 'text-slate-500 hover:text-amber-400' }}">
                                        <svg class="w-3.5 h-3.5" fill="{{ $isFav ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    </button>
                                </form>
                            </div>
                        @elseif(($group['type'] ?? '') === 'group')
                            @php $isGroupActive = collect($group['items'])->contains('route', $current); @endphp
                            <details class="erp-subgroup" {{ $isGroupActive ? 'open' : '' }}>
                                <summary class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[11px] uppercase tracking-wider text-slate-500 hover:text-slate-300 cursor-pointer transition select-none list-none">
                                    <svg class="w-3 h-3 erp-chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['chevron-right'] }}"/></svg>
                                    <span class="flex-1 text-left">{{ $group['label'] }}</span>
                                </summary>
                                <div class="mt-0.5 space-y-0.5 pl-3">
                                    @foreach($group['items'] as $item)
                                        @php $isFav = in_array($item['route'], $favorites, true); @endphp
                                        <div class="relative group/link flex items-center">
                                            <a href="{{ route($item['route']) }}"
                                               class="flex-1 flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg transition-all duration-150 pr-8 {{ $item['route'] === $current ? 'bg-gradient-to-r from-primary-600 to-primary-500 text-white shadow-lg shadow-primary-500/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0 {{ $item['route'] === $current ? '' : 'text-slate-500 group-hover/link:text-primary-400' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] ?? $icons['list'] }}"/></svg>
                                                <span class="font-medium text-[12.5px] truncate">{{ $item['label'] }}</span>
                                            </a>
                                            <form method="POST" action="{{ route('nav.favorite.toggle') }}" class="absolute right-1.5 top-1/2 -translate-y-1/2">
                                                @csrf
                                                <input type="hidden" name="route" value="{{ $item['route'] }}">
                                                <input type="hidden" name="back" value="{{ url()->current() }}">
                                                <button type="submit" title="{{ $isFav ? 'Favorilerden çıkar' : 'Favorilere ekle' }}"
                                                        class="w-6 h-6 rounded-md flex items-center justify-center transition {{ $isFav ? 'text-amber-400' : 'text-slate-500 hover:text-amber-400' }}">
                                                    <svg class="w-3 h-3" fill="{{ $isFav ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @endforeach
                </div>
            </details>
        @endforeach
    </nav>

    @if($user)
        <div class="p-3 border-t border-slate-800/50 flex-shrink-0">
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

{{-- COMMAND PALETTE --}}
<div id="erp-palette" style="display:none" class="fixed inset-0 z-[99999] items-start justify-center pt-[12vh] px-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="erpClosePalette()"></div>
    <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input id="erp-palette-input" type="text" placeholder="Sayfa, modül veya özellik ara…"
                   class="flex-1 bg-transparent text-base text-slate-900 dark:text-white placeholder-slate-400 outline-none border-none focus:ring-0"
                   autocomplete="off" spellcheck="false">
            <kbd class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-mono">ESC</kbd>
        </div>
        <div id="erp-palette-results" class="max-h-[55vh] overflow-y-auto p-2"></div>
    </div>
</div>

<style>
    .erp-section > summary::-webkit-details-marker,
    .erp-subgroup > summary::-webkit-details-marker { display: none; }
    .erp-section[open] > summary .erp-chevron,
    .erp-subgroup[open] > summary .erp-chevron { transform: rotate(90deg); }
    #erp-palette.is-open { display: flex !important; }
</style>

<script>
(function(){
    'use strict';

    function buildIndex() {
        var items = [];
        document.querySelectorAll('#erp-sidebar-nav a[href]').forEach(function(a){
            var label = (a.querySelector('span')?.textContent || '').trim();
            if (!label) return;
            var path = '';
            var section = a.closest('details.erp-section');
            if (section) path = (section.querySelector('summary span')?.textContent || '').trim();
            var subgroup = a.closest('details.erp-subgroup');
            if (subgroup) {
                var sub = (subgroup.querySelector('summary span')?.textContent || '').trim();
                if (sub) path += ' › ' + sub;
            }
            items.push({ href: a.getAttribute('href'), label: label, path: path });
        });
        return items;
    }

    var INDEX = [];
    var palette = document.getElementById('erp-palette');
    var input = document.getElementById('erp-palette-input');
    var resultsEl = document.getElementById('erp-palette-results');
    var activeIdx = 0;
    var currentResults = [];

    window.erpOpenPalette = function() {
        if (!palette) return;
        INDEX = buildIndex();
        palette.classList.add('is-open');
        palette.style.display = 'flex';
        if (input) setTimeout(function(){ input.focus(); input.select(); }, 30);
        render('');
    };

    window.erpClosePalette = function() {
        if (!palette) return;
        palette.classList.remove('is-open');
        palette.style.display = 'none';
        if (input) input.value = '';
        currentResults = [];
        activeIdx = 0;
    };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    function render(q) {
        if (!resultsEl) return;
        q = (q || '').toLowerCase().trim();
        var terms = q.split(/\s+/).filter(Boolean);

        var matched = INDEX.filter(function(it){
            var hay = (it.path + ' ' + it.label).toLowerCase();
            return terms.every(function(t){ return hay.indexOf(t) !== -1; });
        });

        matched.forEach(function(it){
            var label = it.label.toLowerCase();
            var score = (terms.length && label.indexOf(terms[0]) === 0) ? 50 : 0;
            score += Math.max(0, 30 - label.length);
            it._score = score;
        });
        matched.sort(function(a,b){ return b._score - a._score; });
        currentResults = matched.slice(0, 20);
        activeIdx = 0;

        if (!q) {
            resultsEl.innerHTML = '<div class="text-center py-12 text-slate-400 text-sm">Yazmaya başlayın…</div>';
            return;
        }
        if (!currentResults.length) {
            resultsEl.innerHTML = '<div class="text-center py-12 text-slate-400 text-sm">Sonuç bulunamadı</div>';
            return;
        }

        resultsEl.innerHTML = currentResults.map(function(it, i){
            var active = i === activeIdx;
            var cls = active ? 'bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-700 dark:text-slate-300';
            return '<a href="' + esc(it.href) + '" onclick="erpClosePalette()" data-idx="' + i + '" class="block px-3 py-2.5 rounded-lg text-left transition ' + cls + '">' +
                '<div class="text-sm font-semibold truncate">' + esc(it.label) + '</div>' +
                '<div class="text-xs text-slate-500 truncate">' + esc(it.path) + '</div>' +
                '</a>';
        }).join('');
    }

    if (input) {
        input.addEventListener('input', function(){ render(this.value); });
        input.addEventListener('keydown', function(e){
            if (e.key === 'Escape') { erpClosePalette(); e.preventDefault(); return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, currentResults.length - 1); render(this.value); scrollActive(); }
            if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); render(this.value); scrollActive(); }
            if (e.key === 'Enter') {
                e.preventDefault();
                var it = currentResults[activeIdx];
                if (it) window.location.href = it.href;
            }
        });
    }

    function scrollActive() {
        var el = resultsEl?.querySelector('a[data-idx="' + activeIdx + '"]');
        if (el) el.scrollIntoView({ block: 'nearest' });
    }

    document.addEventListener('keydown', function(e){
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            erpOpenPalette();
        }
    });
})();
</script>
