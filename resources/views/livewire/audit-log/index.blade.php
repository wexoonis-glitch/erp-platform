<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Audit Log</h2>
            <p class="page-subtitle">{{ $logs->total() }} kayıt · Denetim izi</p>
        </div>
        <button wire:click="clearFilters" class="btn-secondary">Filtreleri Temizle</button>
    </div>

    {{-- Filters --}}
    <div class="card card-body">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="form-label">Action</label>
                <select wire:model.live="filterAction" class="form-select text-sm">
                    <option value="">Tümü</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Entity</label>
                <select wire:model.live="filterEntity" class="form-select text-sm">
                    <option value="">Tümü</option>
                    @foreach($entities as $e)
                        <option value="{{ $e }}">{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Kullanıcı</label>
                <input type="text" wire:model.live.debounce.400ms="filterUser" placeholder="email ara…" class="form-input text-sm">
            </div>
            @if($isSuper)
                <div>
                    <label class="form-label">Tenant</label>
                    <select wire:model.live="filterTenant" class="form-select text-sm">
                        <option value="">Tümü</option>
                        @foreach($tenants as $t)
                            <option value="{{ $t['id'] }}">{{ $t['code'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="form-label">Başlangıç</label>
                <input type="date" wire:model.live="filterFrom" class="form-input text-sm">
            </div>
            <div>
                <label class="form-label">Bitiş</label>
                <input type="date" wire:model.live="filterTo" class="form-input text-sm">
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-8"></th>
                        <th>Tarih</th>
                        <th>Kullanıcı</th>
                        @if($isSuper) <th>Tenant</th> @endif
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Entity ID</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $actionBadge = match($log->action) {
                                'create'  => 'badge-success',
                                'update'  => 'badge-info',
                                'delete'  => 'badge-danger',
                                default   => 'badge-muted',
                            };
                            $tenant = $isSuper ? ($tenantLookup[$log->tenant_id] ?? null) : null;
                            $isExpanded = $this->expandedId === $log->id;
                        @endphp
                        <tr wire:key="log-{{ $log->id }}" class="cursor-pointer" wire:click="toggleExpand({{ $log->id }})">
                            <td class="text-center text-slate-400">
                                <svg class="w-4 h-4 inline-block transition-transform {{ $isExpanded ? 'rotate-90' : '' }}"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </td>
                            <td class="text-xs whitespace-nowrap">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $log->created_at?->format('d.m.Y') }}</div>
                                <div class="text-slate-500 dark:text-slate-400">{{ $log->created_at?->format('H:i:s') }}</div>
                            </td>
                            <td class="text-xs">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $log->user_email ?? '—' }}</div>
                                <div class="text-slate-500 dark:text-slate-400">ID: {{ $log->user_id ?? '—' }}</div>
                            </td>
                            @if($isSuper)
                                <td class="text-xs">
                                    @if($tenant)
                                        <div class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $tenant['code'] }}</div>
                                        <div class="text-slate-500 dark:text-slate-400">{{ $tenant['name'] }}</div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            @endif
                            <td><span class="{{ $actionBadge }}">{{ $log->action }}</span></td>
                            <td class="text-xs font-mono">{{ $log->entity }}</td>
                            <td class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($log->entity_id, 12, '…') }}</td>
                            <td class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ $log->ip ?? '—' }}</td>
                        </tr>
                        @if($isExpanded)
                            <tr wire:key="log-detail-{{ $log->id }}">
                                <td colspan="{{ $isSuper ? 8 : 7 }}" class="bg-slate-50 dark:bg-slate-800/30">
                                    <div class="p-4 space-y-3">
                                        @if($log->before_state)
                                            <div>
                                                <div class="text-xs font-semibold text-rose-600 dark:text-rose-400 mb-1">Önce</div>
                                                <pre class="text-xs font-mono bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-700 overflow-x-auto text-slate-700 dark:text-slate-300">{{ json_encode($log->before_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        @endif
                                        @if($log->after_state)
                                            <div>
                                                <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-1">Sonra</div>
                                                <pre class="text-xs font-mono bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-700 overflow-x-auto text-slate-700 dark:text-slate-300">{{ json_encode($log->after_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        @endif
                                        @if($log->user_agent)
                                            <div>
                                                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">User Agent</div>
                                                <code class="text-xs text-slate-600 dark:text-slate-400">{{ $log->user_agent }}</code>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="{{ $isSuper ? 8 : 7 }}" class="text-center py-12">
                            <div class="text-4xl mb-2">📋</div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Kayıt bulunamadı.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
