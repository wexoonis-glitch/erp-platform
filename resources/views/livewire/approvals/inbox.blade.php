<div class="space-y-6">
    <div>
        <h2 class="page-title">Onay Kutusu</h2>
        <p class="page-subtitle">Sizin onayınızı bekleyen talepler</p>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($message)
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400' }}">
            {{ $message }}
        </div>
    @endif

    @if($actioningId)
        <div class="modal-backdrop" wire:click.self="cancelAction">
            <div class="modal-content max-w-md">
                <div class="modal-header">
                    <h3 class="text-base font-semibold">{{ $actionType === 'approve' ? 'Onayla' : 'Reddet' }}</h3>
                    <button wire:click="cancelAction">X</button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Not (opsiyonel)</label>
                    <textarea wire:model="notes" rows="3" class="form-input" placeholder="Açıklama veya gerekçe..."></textarea>
                    <div class="modal-footer">
                        <button wire:click="confirmAction" class="btn-primary {{ $actionType === 'reject' ? '!bg-rose-600 hover:!bg-rose-500' : '' }}">
                            {{ $actionType === 'approve' ? 'Onayla' : 'Reddet' }}
                        </button>
                        <button wire:click="cancelAction" class="btn-secondary">İptal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="card-header bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold uppercase">Bekleyen Talepler ({{ count($pending) }})</h3>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($pending as $p)
                <div wire:key="pending-{{ $p['id'] }}" class="p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="badge-muted">{{ $p['entity_type'] }}</span>
                                <span class="text-xs text-slate-500">{{ $p['workflow_name'] }}</span>
                            </div>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ $p['entity_label'] }}</h4>
                            @if($p['description'])
                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">{{ $p['description'] }}</p>
                            @endif
                            <div class="text-xs text-slate-500 mt-2">
                                <strong>Adım:</strong> {{ $p['current_step'] }} · <strong>Tutar:</strong> {{ number_format($p['amount'], 2) }} ₺ · <strong>Talep eden:</strong> {{ $p['requester'] }} · {{ $p['requested_at'] }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="openAction('{{ $p['id'] }}', 'approve')" class="btn-primary text-xs">Onayla</button>
                            <button wire:click="openAction('{{ $p['id'] }}', 'reject')" class="btn-secondary text-xs !text-rose-600 !border-rose-300">Reddet</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 text-sm">Onayınızı bekleyen talep yok.</div>
            @endforelse
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold uppercase">Son Kararlar</h3>
        </div>
        <table class="table-modern">
            <thead><tr><th>Talep</th><th>Akış</th><th>Durum</th><th>Kapanış</th></tr></thead>
            <tbody>
                @forelse($history as $h)
                    <tr>
                        <td class="font-medium">{{ $h['entity_label'] }}</td>
                        <td class="text-xs">{{ $h['workflow_name'] }}</td>
                        <td><span class="{{ $h['status'] === 'approved' ? 'badge-success' : 'badge-danger' }}">{{ $h['status'] }}</span></td>
                        <td class="text-xs">{{ $h['finished_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-6 text-slate-500">Henüz karar yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
