<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Bordro Yönetimi</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Bordro</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Bordro Dönemi</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Dönem (YYYY-MM)</label><input type="text" wire:model="period" class="form-input" placeholder="2026-10">@error('period') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Ödeme Tarihi</label><input type="date" wire:model="payment_date" class="form-input">@error('payment_date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Bordroyu Oluştur</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    @if($viewingRunId && $details)
        <div class="card card-body bg-slate-50 dark:bg-slate-800/50 border-primary-500">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $details['period'] }} Bordro Detayı</h3>
                <button wire:click="closeDetails" class="btn-secondary text-xs">Kapat</button>
            </div>
            <table class="table-modern bg-white dark:bg-slate-900 rounded-lg overflow-hidden">
                <thead><tr><th>Personel</th><th>Brüt</th><th>SGK İşçi</th><th>Gelir V.</th><th>Damga V.</th><th>Net</th></tr></thead>
                <tbody>
                    @foreach($details['items'] as $item)
                        <tr>
                            <td>{{ $item['employee']['first_name'] }} {{ $item['employee']['last_name'] }}</td>
                            <td class="font-mono">{{ number_format($item['gross_salary'], 2) }}</td>
                            <td class="font-mono text-rose-600">{{ number_format($item['sgk_employee'], 2) }}</td>
                            <td class="font-mono text-rose-600">{{ number_format($item['income_tax'], 2) }}</td>
                            <td class="font-mono text-rose-600">{{ number_format($item['stamp_tax'], 2) }}</td>
                            <td class="font-mono font-bold text-emerald-600">{{ number_format($item['net_salary'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Dönem</th><th>Ödeme Tarihi</th><th>Toplam Brüt</th><th>Toplam Kesinti</th><th>Toplam Net</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($runs as $r)
                    <tr>
                        <td class="font-mono font-semibold">{{ $r['period'] }}</td>
                        <td class="text-xs">{{ $r['payment_date'] }}</td>
                        <td class="font-mono">{{ number_format($r['total_gross'], 2) }}</td>
                        <td class="font-mono text-rose-600">{{ number_format($r['total_deductions'], 2) }}</td>
                        <td class="font-mono text-emerald-600 font-bold">{{ number_format($r['total_net'], 2) }}</td>
                        <td><span class="{{ $r['status'] === 'posted' ? 'badge-success' : 'badge-muted' }}">{{ $r['status'] }}</span></td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="viewDetails('{{ $r['id'] }}')" class="btn-ghost-primary">Detay</button>
                                @if($r['status'] === 'draft')
                                    <button wire:click="post('{{ $r['id'] }}')" class="btn-ghost-warning">Post Et</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6">Bordro kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
