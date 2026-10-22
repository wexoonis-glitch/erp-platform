<div class="space-y-6">
    {{-- Welcome --}}
    <div>
        <h2 class="page-title">Merhaba, {{ auth()->user()->name }} 👋</h2>
        <p class="page-subtitle">
            @if($isSuper)
                Platform yöneticisi olarak tüm kiracıları ve modülleri görüyorsunuz.
            @else
                {{ auth()->user()->tenant?->name ?? 'Tenant' }} kiracısının yönetim paneli.
            @endif
        </p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @php
            $iconColors = [
                'indigo'  => 'from-indigo-500 to-indigo-700 shadow-indigo-500/30',
                'emerald' => 'from-emerald-500 to-emerald-700 shadow-emerald-500/30',
                'amber'   => 'from-amber-500 to-amber-700 shadow-amber-500/30',
                'sky'     => 'from-sky-500 to-sky-700 shadow-sky-500/30',
                'violet'  => 'from-violet-500 to-violet-700 shadow-violet-500/30',
                'rose'    => 'from-rose-500 to-rose-700 shadow-rose-500/30',
            ];
        @endphp
        @foreach($stats as $s)
            <div class="kpi-card" wire:key="kpi-{{ $loop->index }}">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $s['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ number_format($s['value']) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $s['sub'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $iconColors[$s['color']] ?? $iconColors['indigo'] }} shadow-lg flex items-center justify-center text-2xl flex-shrink-0">
                        {{ $s['icon'] }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Recent Activity --}}
    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Son Aktiviteler</h3>
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ count($recent) }} kayıt</span>
        </div>
        <div>
            @forelse($recent as $r)
                @php
                    $badgeClass = match($r['status']) {
                        'processed' => 'badge-success',
                        'pending'   => 'badge-warning',
                        'failed'    => 'badge-danger',
                        default     => 'badge-muted',
                    };
                    $dotClass = match($r['status']) {
                        'processed' => 'bg-emerald-500',
                        'pending'   => 'bg-amber-500',
                        'failed'    => 'bg-rose-500',
                        default     => 'bg-slate-400',
                    };
                @endphp
                <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-b-0"
                     wire:key="evt-{{ $loop->index }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-2 h-2 rounded-full {{ $dotClass }} flex-shrink-0"></div>
                        <code class="text-sm font-mono text-slate-700 dark:text-slate-300 truncate">{{ $r['key'] }}</code>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 ml-4">
                        <span class="{{ $badgeClass }}">{{ $r['status'] }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $r['time'] }}</span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="text-4xl mb-2">📭</div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Henüz aktivite yok.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
