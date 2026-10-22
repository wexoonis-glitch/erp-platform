<div class="space-y-6">
    <div>
        <h2 class="page-title">Plugins</h2>
        <p class="page-subtitle">{{ count($available) }} eklenti keşfedildi · Tier 2 Extension System</p>
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">İşlem yapılacak tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)
                    <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($errors->has('general'))
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
            {{ $errors->first('general') }}
        </div>
    @endif
    @if($errors->has('security') || $errors->has('listeners') || $errors->has('requires'))
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
            {{ $errors->first('security') ?: ($errors->first('listeners') ?: $errors->first('requires')) }}
        </div>
    @endif
    @if(session('message'))
        <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900 rounded-xl p-3 text-sm text-emerald-700 dark:text-emerald-400">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($available as $id => $manifest)
            @php
                $inst = $installed[$id] ?? null;
                $status = $inst['status'] ?? 'not_installed';
            @endphp
            <div class="card p-5 space-y-3">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold shadow-md">
                                {{ strtoupper(substr($manifest->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ $manifest->name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono truncate">{{ $manifest->id }}</p>
                            </div>
                        </div>
                    </div>
                    @php
                        $statusBadge = match($status) {
                            'active'    => 'badge-success',
                            'disabled'  => 'badge-warning',
                            default     => 'badge-muted',
                        };
                    @endphp
                    <span class="{{ $statusBadge }} whitespace-nowrap">
                        {{ $status === 'active' ? 'Aktif' : ($status === 'disabled' ? 'Pasif' : 'Kurulu değil') }}
                    </span>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">{{ $manifest->description }}</p>

                <div class="flex flex-wrap gap-1 text-xs">
                    <span class="badge-muted">v{{ $manifest->version }}</span>
                    @if($manifest->vendor)
                        <span class="badge-muted">{{ $manifest->vendor }}</span>
                    @endif
                    @if(count($manifest->eventsConsumed) > 0)
                        <span class="badge-info">{{ count($manifest->eventsConsumed) }} event</span>
                    @endif
                </div>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                    @if($status === 'not_installed')
                        <button wire:click="install('{{ $id }}')" class="btn-primary text-xs flex-1">Kur</button>
                    @elseif($status === 'active')
                        <button wire:click="disable('{{ $id }}')" class="btn-secondary text-xs flex-1">Devre Dışı</button>
                        <button wire:click="uninstall('{{ $id }}')"
                                wire:confirm="Eklentiyi kaldırmak istediğinize emin misiniz?"
                                class="btn-ghost-danger text-xs">Kaldır</button>
                    @elseif($status === 'disabled')
                        <button wire:click="enable('{{ $id }}')" class="btn-primary text-xs flex-1">Aktif Et</button>
                        <button wire:click="uninstall('{{ $id }}')"
                                wire:confirm="Eklentiyi kaldırmak istediğinize emin misiniz?"
                                class="btn-ghost-danger text-xs">Kaldır</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full card p-12 text-center">
                <div class="text-4xl mb-2">🧩</div>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Henüz eklenti yok. <code>plugins/</code> klasörüne bir eklenti ekleyin.
                </p>
            </div>
        @endforelse
    </div>
</div>
