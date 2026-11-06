<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Potansiyel Müşteriler (Leads)</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Lead</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Lead</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2"><label class="form-label">Ad Soyad</label><input type="text" wire:model="name" class="form-input">@error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Şirket</label><input type="text" wire:model="company" class="form-input"></div>
                        <div><label class="form-label">Kaynak</label><select wire:model="source" class="form-select"><option value="website">Web Sitesi</option><option value="referral">Tavsiye</option><option value="cold_call">Soğuk Arama</option><option value="social">Sosyal Medya</option></select></div>
                        <div><label class="form-label">E-posta</label><input type="email" wire:model="email" class="form-input"></div>
                        <div><label class="form-label">Telefon</label><input type="text" wire:model="phone" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Notlar</label><textarea wire:model="notes" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Ad Soyad</th><th>Şirket</th><th>İletişim</th><th>Kaynak</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($leads as $l)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $l['name'] }}</td>
                        <td>{{ $l['company'] ?? '—' }}</td>
                        <td class="text-xs">{{ $l['email'] ?? '' }}<br>{{ $l['phone'] ?? '' }}</td>
                        <td><span class="badge-muted">{{ $l['source'] }}</span></td>
                        <td><span class="{{ $l['status'] === 'qualified' ? 'badge-success' : ($l['status'] === 'lost' ? 'badge-danger' : 'badge-warning') }}">{{ $l['status'] }}</span></td>
                        <td>
                            <div class="flex gap-1 justify-center">
                                @if($l['status'] === 'new')
                                    <button wire:click="updateStatus('{{ $l['id'] }}', 'contacted')" class="btn-ghost-primary">İletişime Geç</button>
                                @elseif($l['status'] === 'contacted')
                                    <button wire:click="updateStatus('{{ $l['id'] }}', 'qualified')" class="btn-ghost-success">Nitelikli</button>
                                    <button wire:click="updateStatus('{{ $l['id'] }}', 'lost')" class="btn-ghost-danger">Kaybedildi</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Lead bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
