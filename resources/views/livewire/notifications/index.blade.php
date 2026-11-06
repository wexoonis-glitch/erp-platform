<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Bildirimler</h2></div>
        <button wire:click="markAllAsRead" class="btn-secondary">Tümünü Okundu İşaretle</button>
    </div>

    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($notifications as $n)
                <div class="p-4 flex items-start gap-4 {{ $n['is_read'] ? 'opacity-60' : 'bg-primary-50/30 dark:bg-primary-950/20' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                        {{ $n['type'] === 'danger' ? 'bg-rose-100 text-rose-600' : ($n['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : ($n['type'] === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-sky-100 text-sky-600')) }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $n['title'] }}</h4>
                            <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($n['created_at'])->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $n['message'] }}</p>
                        @if($n['link'])
                            <a href="{{ $n['link'] }}" class="text-xs font-semibold text-primary-600 hover:underline mt-2 inline-block">Detaya Git →</a>
                        @endif
                    </div>
                    @if(!$n['is_read'])
                        <button wire:click="markAsRead('{{ $n['id'] }}')" class="text-xs text-slate-400 hover:text-primary-600">Okundu</button>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center text-slate-500">
                    <div class="text-4xl mb-2">🔔</div>
                    <p class="text-sm">Hiç bildiriminiz yok.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
