<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Merhaba, {{ auth()->user()->name }} 👋</h2>
        <p class="mt-1 text-slate-600">
            @if($isSuper)
                Platform yöneticisi olarak tüm kiracıları ve modülleri görüyorsunuz.
            @else
                {{ auth()->user()->tenant?->name ?? 'Tenant' }} kiracısının yönetim paneli.
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($stats as $s)
            <div class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $s['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($s['value']) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $s['sub'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-{{ $s['color'] }}-50 flex items-center justify-center text-2xl">
                        {{ $s['icon'] }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-900">Son Aktiviteler</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recent as $r)
                <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-2 h-2 rounded-full
                            {{ $r['status'] === 'processed' ? 'bg-emerald-500' : ($r['status'] === 'pending' ? 'bg-amber-500' : 'bg-rose-500') }}">
                        </div>
                        <code class="text-sm font-mono text-slate-700 truncate">{{ $r['key'] }}</code>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 ml-4">
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $r['status'] === 'processed' ? 'bg-emerald-50 text-emerald-700' : ($r['status'] === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                            {{ $r['status'] }}
                        </span>
                        <span class="text-xs text-slate-500">{{ $r['time'] }}</span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-500 text-sm">
                    Henüz aktivite yok.
                </div>
            @endforelse
        </div>
    </div>
</div>
