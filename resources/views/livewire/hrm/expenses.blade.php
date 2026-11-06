<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Masraf Yönetimi</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Masraf</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    {{-- Masraf Ekleme Modalı --}}
    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Masraf</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2"><label class="form-label">Personel</label><select wire:model="employee_id" class="form-select"><option value="">— Seç —</option>@foreach($employees as $e)<option value="{{ $e['id'] }}">{{ $e['first_name'] }} {{ $e['last_name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="expense_date" class="form-input"></div>
                        <div><label class="form-label">Tutar</label><input type="number" wire:model="amount" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Kategori</label><select wire:model="category" class="form-select"><option value="travel">Seyahat</option><option value="meal">Yemek</option><option value="accommodation">Konaklama</option><option value="other">Diğer</option></select></div>
                        <div class="col-span-2"><label class="form-label">Açıklama</label><input type="text" wire:model="description" class="form-input"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    {{-- Ödeme Modalı --}}
    @if($showPayForm)
        <div class="modal-backdrop" wire:click.self="cancelPay">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Masraf Ödemesi</h3><button wire:click="cancelPay">X</button></div>
                <form wire:submit="pay" class="modal-body">
                    <div><label class="form-label">Ödeme Yapılacak Hesap</label><select wire:model="payment_account_id" class="form-select"><option value="">— Seç —</option>@foreach($bankAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select></div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Ödemeyi Yap</button><button type="button" wire:click="cancelPay" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Personel</th><th>Tarih</th><th>Kategori</th><th>Açıklama</th><th>Tutar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($expenses as $e)
                    <tr>
                        <td class="font-medium">{{ $e['employee']['first_name'] }} {{ $e['employee']['last_name'] }}</td>
                        <td class="text-xs">{{ $e['expense_date'] }}</td>
                        <td><span class="badge-muted">{{ $e['category'] }}</span></td>
                        <td class="text-sm">{{ $e['description'] }}</td>
                        <td class="font-mono font-semibold">{{ number_format($e['amount'], 2) }} ₺</td>
                        <td><span class="{{ $e['status'] === 'paid' ? 'badge-success' : ($e['status'] === 'approved' ? 'badge-info' : 'badge-warning') }}">{{ $e['status'] }}</span></td>
                        <td>
                            <div class="flex justify-center gap-1">
                                @if($e['status'] === 'draft')
                                    <button wire:click="submit('{{ $e['id'] }}')" class="btn-ghost-primary">Onaya Sun</button>
                                @elseif($e['status'] === 'submitted')
                                    <button wire:click="approve('{{ $e['id'] }}')" class="btn-ghost-success">Onayla</button>
                                @elseif($e['status'] === 'approved')
                                    <button wire:click="openPay('{{ $e['id'] }}')" class="btn-ghost-warning">Öde</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6">Masraf kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
