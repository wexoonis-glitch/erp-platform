<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Duran Varlıklar</h2><p class="page-subtitle">{{ count($assets) }} varlık</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Varlık</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    {{-- Create Modal --}}
    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-3xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Duran Varlık</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input"></div>
                        <div><label class="form-label">Ad</label><input type="text" wire:model="name" class="form-input"></div>
                        <div><label class="form-label">Kategori</label><select wire:model="category" class="form-select"><option value="machinery">Makine</option><option value="vehicle">Taşıt</option><option value="building">Bina</option><option value="furniture">Demirbaş</option><option value="it">BT Ekipmanı</option></select></div>
                        <div><label class="form-label">Edinme Tarihi</label><input type="date" wire:model="acquisition_date" class="form-input"></div>
                        <div><label class="form-label">Maliyet</label><input type="number" step="0.01" wire:model="acquisition_cost" class="form-input"></div>
                        <div><label class="form-label">Hurda Değeri</label><input type="number" step="0.01" wire:model="salvage_value" class="form-input"></div>
                        <div><label class="form-label">Faydalı Ömür (Ay)</label><input type="number" wire:model="useful_life_months" class="form-input"></div>
                        <div><label class="form-label">Masraf Merkezi</label><select wire:model="cost_center_id" class="form-select"><option value="">— Seçiniz —</option>@foreach($costCenters as $cc)<option value="{{ $cc['id'] }}">{{ $cc['name'] }}</option>@endforeach</select></div>
                        <div class="sm:col-span-2 border-t pt-3 mt-2 border-slate-200 dark:border-slate-700"><h4 class="text-sm font-semibold">Muhasebe Hesapları</h4></div>
                        <div><label class="form-label">Varlık Hesabı (253/254/255)</label><select wire:model="asset_account_id" class="form-select"><option value="">— Seç —</option>@foreach($assetAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} - {{ $a['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Birikmiş Amortisman (257)</label><select wire:model="accumulated_dep_account_id" class="form-select"><option value="">— Seç —</option>@foreach($accumulatedAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} - {{ $a['name'] }}</option>@endforeach</select></div>
                        <div class="sm:col-span-2"><label class="form-label">Amortisman Gider Hesabı (730/770)</label><select wire:model="depreciation_expense_account_id" class="form-select"><option value="">— Seç —</option>@foreach($expenseAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} - {{ $a['name'] }}</option>@endforeach</select></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    {{-- Dispose Modal --}}
    @if($showDispose)
        <div class="modal-backdrop" wire:click.self="cancelDispose">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Varlığı Elden Çıkar</h3><button wire:click="cancelDispose">X</button></div>
                <form wire:submit="dispose" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Elden Çıkarma Tarihi</label><input type="date" wire:model="disposal_date" class="form-input"></div>
                        <div><label class="form-label">Satış Bedeli</label><input type="number" step="0.01" wire:model="disposal_amount" class="form-input"></div>
                        <div><label class="form-label">Notlar</label><textarea wire:model="disposal_notes" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Elden Çıkar</button><button type="button" wire:click="cancelDispose" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Ad</th><th>Kategori</th><th>Maliyet</th><th>Birikmiş Amort.</th><th>Defter Değeri</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($assets as $a)
                    <tr wire:key="fa-{{ $a['id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $a['code'] }}</td>
                        <td class="font-medium">{{ $a['name'] }}</td>
                        <td><span class="badge-muted">{{ $a['category'] }}</span></td>
                        <td class="font-mono text-xs">{{ number_format($a['acquisition_cost'], 2) }}</td>
                        <td class="font-mono text-xs text-rose-600">{{ number_format($a['accumulated_depreciation'], 2) }}</td>
                        <td class="font-mono text-xs font-bold text-emerald-600">{{ number_format($a['book_value'], 2) }}</td>
                        <td><span class="{{ $a['status'] === 'active' ? 'badge-success' : ($a['status'] === 'disposed' ? 'badge-muted' : 'badge-warning') }}">{{ $a['status'] }}</span></td>
                        <td>
                            @if($a['status'] !== 'disposed')
                                <button wire:click="openDispose('{{ $a['id'] }}')" class="btn-ghost-danger">Elden Çıkar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6">Duran varlık yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
