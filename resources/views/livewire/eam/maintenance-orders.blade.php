<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Bakım Emirleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Bakım Emri</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Bakım Emri</h3><button wire:click="cancel">X</button></div>
                <div class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Varlık</label><select wire:model="asset_id" class="form-select"><option value="">— Seç —</option>@foreach($assets as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Bakım Tipi</label><select wire:model="type" class="form-select"><option value="preventive">Periyodik Bakım</option><option value="corrective">Arıza Onarımı</option></select></div>
                        <div><label class="form-label">Planlanan Tarih</label><input type="date" wire:model="scheduled_date" class="form-input"></div>
                        <div><label class="form-label">Açıklama</label><textarea wire:model="description" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button wire:click="save" class="btn-primary">Kaydet</button><button wire:click="cancel" class="btn-secondary">İptal</button></div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Emir No</th><th>Varlık</th><th>Tip</th><th>Tarih</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td class="font-mono text-xs">{{ $o['order_no'] }}</td><td>{{ $o['asset']['name'] }}</td>
                        <td><span class="badge-muted">{{ $o['type'] === 'preventive' ? 'Periyodik' : 'Arıza' }}</span></td>
                        <td class="text-xs">{{ $o['scheduled_date'] }}</td>
                        <td><span class="{{ $o['status'] === 'completed' ? 'badge-success' : 'badge-warning' }}">{{ $o['status'] }}</span></td>
                        <td>
                            @if($o['status'] !== 'completed')
                                <button wire:click="complete('{{ $o['id'] }}')" class="btn-ghost-primary">Tamamla</button>
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
