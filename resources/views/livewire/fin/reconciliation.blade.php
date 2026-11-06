<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Banka Mutabakatı</h2><p class="page-subtitle">Ekstre satırlarını banka hareketleriyle eşleştir</p></div>
        @if($statement)
            <button wire:click="autoMatch" class="btn-primary">Otomatik Eşleştir</button>
        @endif
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($message)
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400' }}">
            {{ $message }}
        </div>
    @endif

    <div class="card card-body">
        <label class="form-label">Ekstre Seç</label>
        <select wire:model.live="statementId" class="form-select sm:max-w-xl">
            <option value="">— Seçiniz —</option>
            @foreach($statements as $s)
                <option value="{{ $s['id'] }}">{{ $s['label'] }}</option>
            @endforeach
        </select>
    </div>

    @if($statement)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Ekstre satırları --}}
            <div class="card overflow-hidden">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase">Ekstre Satırları</h3>
                    <span class="text-xs text-slate-500">{{ count($lines) }} satır</span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[600px] overflow-y-auto">
                    @forelse($lines as $l)
                        <div wire:key="line-{{ $l['id'] }}" class="p-3 {{ $l['status'] === 'matched' ? 'bg-emerald-50/40 dark:bg-emerald-950/20' : ($l['status'] === 'ignored' ? 'opacity-50' : '') }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono text-slate-500">#{{ $l['line_no'] }}</span>
                                        <span class="text-xs text-slate-500">{{ $l['value_date'] }}</span>
                                        <span class="font-mono font-bold {{ $l['amount'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ number_format($l['amount'], 2) }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-slate-700 dark:text-slate-300 mt-1">{{ $l['description'] ?? '—' }}</div>
                                    @if($l['reference'])
                                        <div class="text-xs text-slate-500 mt-0.5">Ref: {{ $l['reference'] }}</div>
                                    @endif
                                    @if($l['status'] === 'matched')
                                        <div class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">
                                            ✓ Eşleşti → {{ $l['matched_date'] }} / {{ number_format($l['matched_amount'] ?? 0, 2) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="{{ $l['status'] === 'matched' ? 'badge-success' : ($l['status'] === 'ignored' ? 'badge-muted' : 'badge-warning') }}">{{ $l['status'] }}</span>
                                    @if($l['status'] === 'matched')
                                        <button wire:click="unmatch('{{ $l['id'] }}')" class="btn-ghost-danger text-[10px]">Kaldır</button>
                                    @elseif($l['status'] === 'unmatched')
                                        <button wire:click="ignore('{{ $l['id'] }}')" class="btn-ghost-secondary text-[10px]">Yok Say</button>
                                    @endif
                                </div>
                            </div>

                            @if($l['status'] === 'unmatched')
                                <div class="mt-2">
                                    <select wire:change="match('{{ $l['id'] }}', $event.target.value)" class="form-select text-xs">
                                        <option value="">— Bir banka hareketi seç —</option>
                                        @foreach($availableTransactions as $tx)
                                            <option value="{{ $tx['id'] }}">
                                                {{ $tx['transaction_date'] }} · {{ $tx['type'] === 'deposit' ? 'Giriş' : 'Çıkış' }} · {{ number_format($tx['amount'], 2) }} · {{ $tx['description'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-500">Satır yok.</div>
                    @endforelse
                </div>
            </div>

            {{-- Kullanılabilir banka hareketleri --}}
            <div class="card overflow-hidden">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase">Banka Hareketleri</h3>
                    <span class="text-xs text-slate-500">{{ count($availableTransactions) }} eşleşmemiş</span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[600px] overflow-y-auto">
                    @forelse($availableTransactions as $tx)
                        <div wire:key="tx-{{ $tx['id'] }}" class="p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xs text-slate-500">{{ $tx['transaction_date'] }}</div>
                                <span class="badge-muted">{{ $tx['type'] === 'deposit' ? 'Giriş' : 'Çıkış' }}</span>
                            </div>
                            <div class="font-mono font-bold {{ $tx['amount'] >= 0 || $tx['type'] === 'deposit' ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                                {{ number_format($tx['amount'], 2) }}
                            </div>
                            <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $tx['description'] }}</div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-500">Eşleşmemiş banka hareketi yok.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
