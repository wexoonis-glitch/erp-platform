<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Üretim Emirleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Üretim Emri</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Üretim Emri</h3><button wire:click="cancel">X</button></div>
                <div class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Reçete Seç</label><select wire:model="bom_id" class="form-select"><option value="">— Seç —</option>@foreach($boms as $b)<option value="{{ $b['id'] }}">{{ $b['code'] }} - {{ $b['product']['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Üretim Tarihi</label><input type="date" wire:model="order_date" class="form-input"></div>
                        <div><label class="form-label">Üretim Miktarı</label><input type="number" wire:model="quantity" class="form-input"></div>
                    </div>
                    <div class="modal-footer"><button wire:click="save" class="btn-primary">Emri Oluştur</button><button wire:click="cancel" class="btn-secondary">İptal</button></div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Emir No</th><th>Ürün</th><th>Tarih</th><th>Miktar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td class="font-mono">{{ $o['order_no'] }}</td><td>{{ $o['product']['name'] }}</td><td>{{ $o['order_date'] }}</td><td class="font-mono">{{ number_format($o['quantity'], 2) }}</td>
                        <td><span class="{{ $o['status'] === 'completed' ? 'badge-success' : 'badge-muted' }}">{{ $o['status'] }}</span></td>
                        <td>
                            @if($o['status'] === 'draft')
                                <button wire:click="complete('{{ $o['id'] }}')" class="btn-ghost-warning">Tamamla (Stoğa Al)</button>
                            @else
                                <span class="text-xs text-slate-400">Bitti</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Emir yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
