@php
    $user = auth()->user();
@endphp

<header class="h-16 flex items-center justify-between px-6 bg-white border-b border-slate-200">
    <div class="flex items-center gap-3">
        <button class="lg:hidden p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100" aria-label="Menu">
            ☰
        </button>
        <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <div class="flex items-center gap-4">
        @if($user)
            <div class="text-right hidden sm:block">
                <div class="text-sm font-medium text-slate-900">{{ $user->name }}</div>
                <div class="text-xs text-slate-500">
                    {{ $user->role }} @if($user->tenant_id)· {{ \Str::limit($user->tenant_id, 8, '…') }}@endif
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-600 hover:text-rose-600 transition">
                    Çıkış
                </button>
            </form>
        @endif
    </div>
</header>
