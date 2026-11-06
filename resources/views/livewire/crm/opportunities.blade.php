<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Satış Fırsatları</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Fırsat</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Fırsat</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Fırsat Adı</label><input type="text" wire:model="name" class="form-input" placeholder="Örn: 50 Kullanıcılı ERP Lisansı">@error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Tahmini Tutar (₺)</label><input type="number" wire:model="amount" class="form-input">@error('amount') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Beklenen Kapanış Tarihi</label><input type="date" wire:model="expected_close_date" class="form-input"></div>
                        <div><label class="form-label">Notlar</label><textarea wire:model="notes" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Fırsat</th><th>Tutar</th><th>Kapanış</th><th>Aşama</th><th>Olasılık</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($opportunities as $o)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $o['name'] }}</td>
                        <td class="font-mono text-emerald-600">{{ number_format($o['amount'], 2) }} ₺</td>
                        <td class="text-xs">{{ $o['expected_close_date'] ?? '—' }}</td>
                        <td><span class="{{ $o['stage'] === 'closed_won' ? 'badge-success' : ($o['stage'] === 'closed_lost' ? 'badge-danger' : 'badge-info') }}">{{ $o['stage'] }}</span></td>
                        <td class="text-xs font-bold">{{ $o['probability'] }}%</td>
                        <td>
                            @if(!in_array($o['stage'], ['closed_won', 'closed_lost']))
                                <select wire:change="updateStage('{{ $o['id'] }}', $event.target.value)" class="form-select text-xs">
                                    <option value="qualification" @selected($o['stage'] === 'qualification')>Nitelikli</option>
                                    <option value="proposal" @selected($o['stage'] === 'proposal')>Teklif Verildi</option>
                                    <option value="negotiation" @selected($o['stage'] === 'negotiation')>Müzakere</option>
                                    <option value="closed_won" @selected($o['stage'] === 'closed_won')>Kazanıldı</option>
                                    <option value="closed_lost" @selected($o['stage'] === 'closed_lost')>Kaybedildi</option>
                                </select>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Fırsat bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
