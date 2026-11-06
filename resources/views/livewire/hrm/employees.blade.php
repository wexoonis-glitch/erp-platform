<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Personel Yönetimi</h2><p class="page-subtitle">{{ count($employees) }} çalışan</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Personel</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-3xl">
                <div class="modal-header"><h3 class="text-base font-semibold">{{ $editingId ? 'Personel Düzenle' : 'Yeni Personel' }}</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="form-label">Personel No</label><input type="text" wire:model="employee_no" class="form-input">@error('employee_no') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">TCKN</label><input type="text" wire:model="national_id" class="form-input"></div>
                        <div><label class="form-label">Ad</label><input type="text" wire:model="first_name" class="form-input">@error('first_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Soyad</label><input type="text" wire:model="last_name" class="form-input">@error('last_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">E-posta</label><input type="email" wire:model="email" class="form-input"></div>
                        <div><label class="form-label">Telefon</label><input type="text" wire:model="phone" class="form-input"></div>
                        <div><label class="form-label">Departman</label><input type="text" wire:model="department" class="form-input"></div>
                        <div><label class="form-label">Masraf Merkezi</label><select wire:model="cost_center_id" class="form-select"><option value="">— Seçiniz —</option>@foreach($costCenters as $cc)<option value="{{ $cc['id'] }}">{{ $cc['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Pozisyon</label><input type="text" wire:model="position" class="form-input"></div>
                        <div><label class="form-label">İşe Giriş Tarihi</label><input type="date" wire:model="hire_date" class="form-input">@error('hire_date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Çıkış Tarihi</label><input type="date" wire:model="termination_date" class="form-input"></div>
                        <div><label class="form-label">Brüt Maaş</label><input type="number" step="0.01" wire:model="gross_salary" class="form-input">@error('gross_salary') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Durum</label><select wire:model="status" class="form-select"><option value="active">Aktif</option><option value="on_leave">İzinli</option><option value="terminated">Ayrıldı</option></select></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>No</th><th>Ad Soyad</th><th>Departman</th><th>Pozisyon</th><th>Brüt Maaş</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr wire:key="emp-{{ $e['id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $e['employee_no'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $e['first_name'] }} {{ $e['last_name'] }}</td>
                        <td>{{ $e['department'] ?? '—' }}</td>
                        <td>{{ $e['position'] ?? '—' }}</td>
                        <td class="font-mono">{{ number_format($e['gross_salary'], 2) }}</td>
                        <td><span class="{{ $e['status'] === 'active' ? 'badge-success' : ($e['status'] === 'on_leave' ? 'badge-warning' : 'badge-danger') }}">{{ $e['status'] }}</span></td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="openEdit('{{ $e['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                <button wire:click="delete('{{ $e['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-12 text-slate-500">Henüz personel yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
