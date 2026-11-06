<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Kullanıcılar</h2><p class="page-subtitle">{{ count($users) }} kullanıcı</p></div>
        <button wire:click="$toggle('showCreate')" class="btn-primary">+ Yeni Kullanıcı</button>
    </div>

    @if($showCreate)
        <div class="card card-body space-y-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Yeni Kullanıcı</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="form-label">Ad</label><input type="text" wire:model="name" class="form-input">@error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                <div><label class="form-label">E-posta</label><input type="email" wire:model="email" class="form-input">@error('email') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                <div><label class="form-label">Parola</label><input type="password" wire:model="password" class="form-input">@error('password') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                <div>
                    <label class="form-label">Rol</label>
                    <select wire:model="role" class="form-select">
                        @foreach($roles as $r)<option value="{{ $r['code'] }}">{{ $r['name'] }}</option>@endforeach
                    </select>
                    @error('role') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @if($isSuper)
                    <div class="sm:col-span-2">
                        <label class="form-label">Tenant</label>
                        <select wire:model.live="tenant_id" class="form-select">
                            <option value="">— Seçiniz —</option>
                            @foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>@endforeach
                        </select>
                        @error('tenant_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div class="sm:col-span-2 flex gap-2">
                    <button type="submit" class="btn-primary">Oluştur</button>
                    <button type="button" wire:click="$set('showCreate', false)" class="btn-secondary">İptal</button>
                </div>
            </form>
        </div>
    @endif

    @if($editingId)
        <div class="modal-backdrop" wire:click.self="cancelEdit">
            <div class="modal-content max-w-lg">
                <div class="modal-header"><h3 class="text-base font-semibold">Kullanıcı Düzenle</h3><button wire:click="cancelEdit">X</button></div>
                <form wire:submit="saveEdit" class="modal-body space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="form-label">Ad</label><input type="text" wire:model="editName" class="form-input">@error('editName') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">E-posta</label><input type="email" wire:model="editEmail" class="form-input">@error('editEmail') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Parola (ops.)</label><input type="password" wire:model="editPassword" class="form-input">@error('editPassword') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div>
                            <label class="form-label">Rol</label>
                            <select wire:model="editRole" class="form-select">
                                @foreach($roles as $r)<option value="{{ $r['code'] }}">{{ $r['name'] }}</option>@endforeach
                            </select>
                            @error('editRole') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Durum</label>
                            <select wire:model="editStatus" class="form-select">
                                <option value="active">active</option>
                                <option value="suspended">suspended</option>
                                <option value="disabled">disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn-primary">Kaydet</button>
                        <button type="button" wire:click="cancelEdit" class="btn-secondary">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th>@if($isSuper)<th>Tenant</th>@endif<th>İşlem</th></tr></thead>
            <tbody>
                @forelse($users as $u)
                    <tr wire:key="u-{{ $u['id'] }}">
                        <td class="font-medium text-slate-900 dark:text-white">{{ $u['name'] }}</td>
                        <td class="text-slate-600">{{ $u['email'] }}</td>
                        <td><span class="badge-muted">{{ $u['role'] }}</span></td>
                        <td><span class="{{ $u['status'] === 'active' ? 'badge-success' : 'badge-danger' }}">{{ $u['status'] }}</span></td>
                        @if($isSuper)<td class="text-xs font-mono">{{ $u['tenant_id'] ?: '—' }}</td>@endif
                        <td>
                            @if($u['role'] !== 'superadmin')
                                <div class="flex justify-center gap-1">
                                    <button wire:click="openEdit('{{ $u['id'] }}')" class="btn-ghost-primary text-xs">Düzenle</button>
                                    <button wire:click="toggleStatus('{{ $u['id'] }}')" class="btn-ghost-warning text-xs">{{ $u['status'] === 'active' ? 'Askı' : 'Aktif' }}</button>
                                    <button wire:click="delete('{{ $u['id'] }}')" wire:confirm="Devre dışı bırakılsın mı?" class="btn-ghost-danger text-xs">Devre Dışı</button>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 6 : 5 }}" class="text-center py-6">Kullanıcı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
