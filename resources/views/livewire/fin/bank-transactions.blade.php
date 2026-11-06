<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Banka Hareketleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Hareket</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Hareket</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="form-label">Hesap</label><select wire:model="bank_account_id" class="form-select"><option value="">— Seç —</option>@foreach($bankAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">İşlem Tipi</label><select wire:model="type" class="form-select"><option value="deposit">Giriş (Tahsilat/Yatırma)</option><option value="withdrawal">Çıkış (Ödeme/Çekme)</option></select></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="transaction_date" class="form-input"></div>
                        <div><label class="form-label">Tutar</label><input type="number" wire:model="amount" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Karşı Hesap</label><select wire:model="counter_account_id" class="form-select"><option value="">— Seç —</option>@foreach($accounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} - {{ $a['name'] }}</option>@endforeach</select></div>
                        <div class="col-span-2"><label class="form-label">Açıklama</label><input type="text" wire:model="description" class="form-input"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Tarih</th><th>Hesap</th><th>Tip</th><th>Açıklama</th><th>Tutar</th><th>Karşı Hesap</th></tr></thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr>
                        <td class="text-xs">{{ $t['transaction_date'] }}</td>
                        <td class="font-medium">{{ $t['bank_account']['name'] }}</td>
                        <td>
                            @if(in_array($t['type'], ['deposit', 'transfer_in']))
                                <span class="badge-success">Giriş</span>
                            @else
                                <span class="badge-danger">Çıkış</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $t['description'] }}</td>
                        <td class="font-mono font-semibold {{ in_array($t['type'], ['deposit', 'transfer_in']) ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($t['amount'], 2) }}
                        </td>
                        <td class="text-xs">{{ $t['counter_account']['name'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Hareket yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
