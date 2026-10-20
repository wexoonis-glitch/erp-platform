@php
    $user = auth()->user();
    $isSuper = $user?->isSuperadmin();
    $current = request()->route()?->getName() ?? '';
@endphp

<aside class="hidden lg:flex w-64 flex-col bg-slate-900 text-slate-100 border-r border-slate-800">
    <div class="h-16 flex items-center px-6 border-b border-slate-800">
        <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight text-white">
            ◆ Kurumsal ERP
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ $current === 'dashboard' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <span>📊</span> Dashboard
        </a>

        @if($isSuper)
            <div class="pt-4 pb-1 px-3 text-xs uppercase tracking-wider text-slate-500">Platform</div>
            <a href="{{ route('tenants.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($current, 'tenants') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>🏢</span> Tenants
            </a>
        @endif

        <div class="pt-4 pb-1 px-3 text-xs uppercase tracking-wider text-slate-500">Operasyon</div>
        <a href="{{ route('users.index') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($current, 'users') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <span>👥</span> Users
        </a>
        <a href="{{ route('modules.index') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($current, 'modules') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <span>🧩</span> Modules
        </a>
        <a href="{{ route('custom-fields.index') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ str_starts_with($current, 'custom-fields') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <span>⚙️</span> Custom Fields
        </a>
    </nav>

    <div class="p-4 border-t border-slate-800 text-xs text-slate-500">
        v0.1 · {{ now()->year }}
    </div>
</aside>
