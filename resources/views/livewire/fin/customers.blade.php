<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Müşteriler</h2><p class="page-subtitle">{{ count($customers) }} müşteri</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Müşteri</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>@endforeach</select></div>
    @endif

    @error('customer')
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">{{ $message }}</div>
    @enderror

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-2xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $editingId ? 'Müşteri Düzenle' : 'Yeni Müşteri' }}</h3>
                    <button wire:click="cancel" class="p-1 rounded-lg text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" placeholder="M001" class="form-input">@error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Ad</label><input type="text" wire:model="name" placeholder="ABC Ltd" class="form-input">@error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Vergi No</label><input type="text" wire:model="tax_no" class="form-input"></div>
                        <div><label class="form-label">Vergi Dairesi</label><input type="text" wire:model="tax_office" class="form-input"></div>
                        <div><label class="form-label">E-posta</label><input type="email" wire:model="email" class="form-input">@error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Telefon</label><input type="text" wire:model="phone" class="form-input"></div>
                        <div><label class="form-label">Para Birimi</label><input type="text" wire:model="currency" maxlength="3" class="form-input"></div>
                        <div><label class="form-label">Durum</label><select wire:model="status" class="form-select"><option value="active">Aktif</option><option value="inactive">Pasif</option></select></div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Fiyat Listesi (Opsiyonel)</label>
                            <select wire:model="price_list_id" class="form-select">
                                <option value="">— Varsayılan —</option>
                                @foreach($priceLists as $pl)<option value="{{ $pl['id'] }}">{{ $pl['code'] }} — {{ $pl['name'] }}</option>@endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2"><label class="form-label">Adres</label><textarea wire:model="address" rows="2" class="form-input"></textarea></div>
                    </div>

                    <x-custom-fields-inputs entity="fin_customer" model="customFields" />

                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Ad</th><th>Vergi No</th><th>E-posta</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($customers as $c)
                    <tr wire:key="cus-{{ $c['id'] }}">
                        <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $c['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $c['name'] }}</td>
                        <td class="text-xs">{{ $c['tax_no'] ?? '—' }}</td>
                        <td class="text-xs">{{ $c['email'] ?? '—' }}</td>
                        <td><span class="{{ $c['status'] === 'active' ? 'badge-success' : 'badge-muted' }}">{{ $c['status'] }}</span></td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="openEdit('{{ $c['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                <button wire:click="delete('{{ $c['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-12 text-slate-500">Henüz müşteri yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
