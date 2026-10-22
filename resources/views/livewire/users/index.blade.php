<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Kullanıcılar</h2>
            <p class="page-subtitle">{{ count($users) }} kullanıcı</p>
        </div>
        <button wire:click="$toggle('showCreate')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Kullanıcı
        </button>
    </div>

    @if($showCreate)
        <div class="card card-body">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Yeni Kullanıcı</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Ad</label>
                    <input type="text" wire:model="name" class="form-input">
                    @error('name') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">E-posta</label>
                    <input type="email" wire:model="email" class="form-input">
                    @error('email') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Parola</label>
                    <input type="password" wire:model="password" placeholder="En az 8 karakter" class="form-input">
                    @error('password') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Rol</label>
                    <select wire:model="role" class="form-select">
                        @if($isSuper)
                            <option value="user">user</option>
                            <option value="manager">manager</option>
                            <option value="admin">admin</option>
                            <option value="superadmin">superadmin</option>
                        @else
                            <option value="user">user</option>
                            <option value="manager">manager</option>
                        @endif
                    </select>
                    @error('role') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                @if($isSuper)
                    <div class="sm:col-span-2">
                        <label class="form-label">Tenant</label>
                        <select wire:model="tenant_id" class="form-select">
                            <option value="">— Seçiniz —</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                            @endforeach
                        </select>
                        @error('tenant_id') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div class="sm:col-span-2 flex items-center gap-3">
                    <button type="submit" class="btn-primary">Oluştur</button>
                    <button type="button" wire:click="$set('showCreate', false)" class="btn-secondary">İptal</button>
                </div>
            </form>
        </div>
    @endif

    @if($editingId)
        <div class="modal-backdrop" wire:click.self="cancelEdit">
            <div class="modal-content max-w-lg">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Kullanıcı Düzenle</h3>
                    <button wire:click="cancelEdit" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit="saveEdit" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Ad</label>
                            <input type="text" wire:model="editName" class="form-input">
                            @error('editName') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">E-posta</label>
                            <input type="email" wire:model="editEmail" class="form-input">
                            @error('editEmail') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Parola (opsiyonel)</label>
                            <input type="password" wire:model="editPassword" placeholder="Boş = değişmez" class="form-input">
                            @error('editPassword') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Rol</label>
                            <select wire:model="editRole" class="form-select">
                                @if($isSuper)
                                    <option value="user">user</option>
                                    <option value="manager">manager</option>
                                    <option value="admin">admin</option>
                                    <option value="superadmin">superadmin</option>
                                @else
                                    <option value="user">user</option>
                                    <option value="manager">manager</option>
                                @endif
                            </select>
                            @error('editRole') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Durum</label>
                            <select wire:model="editStatus" class="form-select">
                                <option value="active">active</option>
                                <option value="suspended">suspended</option>
                                <option value="disabled">disabled</option>
                            </select>
                            @error('editStatus') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
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
            <thead>
                <tr>
                    <th>Ad</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    @if($isSuper) <th>Tenant</th> @endif
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr wire:key="user-{{ $u['id'] }}">
                        <td class="font-semibold text-slate-900 dark:text-white">{{ $u['name'] }}</td>
                        <td class="text-sm">{{ $u['email'] }}</td>
                        <td>
                            @php
                                $roleBadge = match($u['role']) {
                                    'superadmin' => 'badge-primary',
                                    'admin'      => 'badge-info',
                                    default      => 'badge-muted',
                                };
                            @endphp
                            <span class="{{ $roleBadge }}">{{ $u['role'] }}</span>
                        </td>
                        <td>
                            @php
                                $statusBadge = match($u['status']) {
                                    'active'    => 'badge-success',
                                    'suspended' => 'badge-warning',
                                    default     => 'badge-danger',
                                };
                                $statusDot = match($u['status']) {
                                    'active'    => 'bg-emerald-500',
                                    'suspended' => 'bg-amber-500',
                                    default     => 'bg-rose-500',
                                };
                            @endphp
                            <span class="{{ $statusBadge }}">
                                <span class="badge-dot {{ $statusDot }}"></span>
                                {{ $u['status'] }}
                            </span>
                        </td>
                        @if($isSuper)
                            <td class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ $u['tenant_id'] ?: '—' }}</td>
                        @endif
                        <td class="text-center">
                            @if($u['role'] !== 'superadmin')
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="openEdit('{{ $u['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                    <button wire:click="toggleStatus('{{ $u['id'] }}')" class="btn-ghost-warning">
                                        {{ $u['status'] === 'active' ? 'Askıya Al' : 'Aktive Et' }}
                                    </button>
                                    <button wire:click="delete('{{ $u['id'] }}')"
                                            wire:confirm="Devre dışı bırakmak istediğinize emin misiniz?"
                                            class="btn-ghost-danger">Devre Dışı</button>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 6 : 5 }}" class="text-center py-12">
                        <div class="text-4xl mb-2">👥</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Kullanıcı yok.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
