@php
    $user = auth()->user();
    $unreadCount = \App\Models\SysNotification::where('user_id', auth()->id())->where('is_read', false)->count();
@endphp

<header class="h-16 flex items-center justify-between px-4 sm:px-6 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30">
    <div class="flex items-center gap-3 min-w-0">
        <button class="lg:hidden p-2 -ml-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('flex')"
                aria-label="Menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <h1 class="text-lg font-semibold text-slate-900 dark:text-white truncate">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <div class="flex items-center gap-2">
        {{-- Theme Toggle --}}
        <button type="button" id="theme-toggle-btn"
                class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                title="Tema değiştir"
                onclick="window.dispatchEvent(new CustomEvent('open-theme-panel'))">
            <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>

        {{-- Bildirimler --}}
        <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Bildirimler">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            @if($unreadCount > 0)
                <span class="absolute top-0.5 right-0.5 flex items-center justify-center w-4 h-4 text-[9px] font-bold text-white bg-rose-500 rounded-full border border-white dark:border-slate-900">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </a>

        {{-- Divider --}}
        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>

        {{-- User --}}
        @if($user)
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <div class="text-sm font-semibold text-slate-900 dark:text-white leading-tight">{{ $user->name }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ $user->role }}
                    </div>
                </div>
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 text-white flex items-center justify-center font-bold shadow-md">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition" title="Çıkış">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        @endif
    </div>
</header>
