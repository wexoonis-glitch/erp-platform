<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Uygunsuzluk Raporları (NCR)</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni NCR</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni NCR</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2"><label class="form-label">Ürün</label><select wire:model="product_id" class="form-select"><option value="">— Seç —</option>@foreach($products as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="report_date" class="form-input"></div>
                        <div><label class="form-label">Kaynak</label><select wire:model="source" class="form-select"><option value="incoming">Mal Kabul</option><option value="production">Üretim</option><option value="customer">Müşteri</option></select></div>
                        <div><label class="form-label">Referans No</label><input type="text" wire:model="reference_no" class="form-input" placeholder="Fatura veya Üretim Emri"></div>
                        <div><label class="form-label">Hatalı Miktar</label><input type="number" wire:model="quantity" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Açıklama</label><textarea wire:model="description" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>NCR No</th><th>Ürün</th><th>Kaynak</th><th>Miktar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($ncrs as $n)
                    <tr>
                        <td class="font-mono text-xs">{{ $n['ncr_no'] }}</td><td>{{ $n['product']['name'] }}</td>
                        <td><span class="badge-muted">{{ $n['source'] }}</span></td>
                        <td class="font-mono text-rose-600">{{ number_format($n['quantity'], 2) }}</td>
                        <td><span class="{{ $n['status'] === 'open' ? 'badge-danger' : ($n['status'] === 'closed' ? 'badge-success' : 'badge-warning') }}">{{ $n['status'] }}</span></td>
                        <td>
                            @if($n['status'] === 'open')
                                <button wire:click="updateStatus('{{ $n['id'] }}', 'investigating')" class="btn-ghost-warning">İncele</button>
                            @elseif($n['status'] === 'investigating')
                                <button wire:click="updateStatus('{{ $n['id'] }}', 'closed')" class="btn-ghost-success">Kapat</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">NCR yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
