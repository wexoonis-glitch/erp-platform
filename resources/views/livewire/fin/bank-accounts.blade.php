<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Banka & Kasa Hesapları</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Hesap</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Hesap</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2"><label class="form-label">Hesap Adı</label><input type="text" wire:model="name" class="form-input" placeholder="Garanti TRY"></div>
                        <div><label class="form-label">Tip</label><select wire:model="type" class="form-select"><option value="bank">Banka</option><option value="cash">Kasa</option></select></div>
                        <div><label class="form-label">Muhasebe Hesabı</label><select wire:model="gl_account_id" class="form-select"><option value="">— Seç —</option>@foreach($glAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} - {{ $a['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Hesap No</label><input type="text" wire:model="account_no" class="form-input"></div>
                        <div><label class="form-label">IBAN</label><input type="text" wire:model="iban" class="form-input"></div>
                        <div><label class="form-label">Döviz</label><input type="text" wire:model="currency" maxlength="3" class="form-input"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Hesap Adı</th><th>Tip</th><th>Döviz</th><th>Muhasebe Hesabı</th><th>Durum</th></tr></thead>
            <tbody>
                @forelse($accounts as $a)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $a['name'] }}</td>
                        <td><span class="badge-muted">{{ $a['type'] === 'bank' ? 'Banka' : 'Kasa' }}</span></td>
                        <td class="font-mono">{{ $a['currency'] }}</td>
                        <td class="text-sm">{{ $a['gl_account']['name'] ?? '—' }}</td>
                        <td><span class="badge-success">{{ $a['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6">Hesap bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
