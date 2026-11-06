<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Banka Ekstreleri</h2><p class="page-subtitle">CSV veya MT940 içe aktarma ve mutabakat</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Ekstre İçe Aktar</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($message)
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400' }}">
            {{ $message }}
        </div>
    @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-lg">
                <div class="modal-header"><h3 class="text-base font-semibold">Ekstre İçe Aktar</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Banka Hesabı</label><select wire:model="bank_account_id" class="form-select"><option value="">— Seçiniz —</option>@foreach($accounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }} ({{ $a['currency'] }})</option>@endforeach</select></div>
                        <div><label class="form-label">Format</label><select wire:model.live="format" class="form-select"><option value="csv">CSV (; ayraçlı)</option><option value="mt940">MT940</option></select></div>
                        <div>
                            <label class="form-label">Dosya</label>
                            <input type="file" wire:model="file" class="form-input text-sm">
                            @if($format === 'csv')
                                <p class="mt-1 text-xs text-slate-500">Başlık (opsiyonel): <code>value_date;amount;description;reference</code></p>
                            @endif
                            <div wire:loading wire:target="file" class="text-xs text-primary-600 mt-1">Yükleniyor...</div>
                            @error('file') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn-primary">İçe Aktar</button>
                        <button type="button" wire:click="cancel" class="btn-secondary">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Dosya</th><th>Banka</th><th>Dönem</th><th>Satır</th><th>Eşleşen</th><th>Durum</th><th>Yükleme</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($statements as $s)
                    <tr wire:key="st-{{ $s['id'] }}">
                        <td class="font-medium text-slate-900 dark:text-white">{{ $s['file_name'] }}</td>
                        <td class="text-sm">{{ $s['bank_account'] }}</td>
                        <td class="text-xs">{{ $s['statement_from'] }} → {{ $s['statement_to'] }}</td>
                        <td class="font-mono text-xs">{{ $s['total_lines'] }}</td>
                        <td class="font-mono text-xs text-emerald-600">{{ $s['matched_lines'] }}</td>
                        <td>
                            @php
                                $badge = match($s['status']) {
                                    'reconciled' => 'badge-success',
                                    'partially_reconciled' => 'badge-warning',
                                    default => 'badge-muted',
                                };
                            @endphp
                            <span class="{{ $badge }}">{{ $s['status'] }}</span>
                        </td>
                        <td class="text-xs">{{ $s['created_at'] }}</td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <a href="{{ route('fin.reconciliation') }}?statementId={{ $s['id'] }}" class="btn-ghost-primary">Mutabakat</a>
                                <button wire:click="autoMatch('{{ $s['id'] }}')" class="btn-ghost-warning">Oto Eşleştir</button>
                                <button wire:click="delete('{{ $s['id'] }}')" wire:confirm="Silinsin mi?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6">Ekstre içe aktarılmadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
