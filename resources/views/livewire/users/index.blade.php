<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Kullanıcılar</h2>
            <p class="mt-1 text-slate-600">{{ count($users) }} kullanıcı</p>
        </div>
        <button wire:click="$toggle('showCreate')"
                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition">
            + Yeni Kullanıcı
        </button>
    </div>

    {{-- Create Form --}}
    @if($showCreate)
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Yeni Kullanıcı</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ad</label>
                    <input type="text" wire:model="name"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">E-posta</label>
                    <input type="email" wire:model="email"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Parola</label>
                    <input type="password" wire:model="password" placeholder="En az 8 karakter"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rol</label>
                    <select wire:model="role"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                    @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @if($isSuper)
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tenant</label>
                        <select wire:model="tenant_id"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">— Seçiniz —</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                            @endforeach
                        </select>
                        @error('tenant_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div class="sm:col-span-2 flex items-center gap-3">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition">
                        Oluştur
                    </button>
                    <button type="button" wire:click="$set('showCreate', false)"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                        İptal
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($editingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
             wire:click.self="cancelEdit">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Kullanıcı Düzenle</h3>
                    <button wire:click="cancelEdit" class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>
                <form wire:submit="saveEdit" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Ad</label>
                            <input type="text" wire:model="editName"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('editName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">E-posta</label>
                            <input type="email" wire:model="editEmail"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('editEmail') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Parola (opsiyonel)</label>
                            <input type="password" wire:model="editPassword" placeholder="Boş bırakılırsa değişmez"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('editPassword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Rol</label>
                            <select wire:model="editRole"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                            @error('editRole') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Durum</label>
                            <select wire:model="editStatus"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="active">active</option>
                                <option value="suspended">suspended</option>
                                <option value="disabled">disabled</option>
                            </select>
                            @error('editStatus') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition">
                            Kaydet
                        </button>
                        <button type="button" wire:click="cancelEdit"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                            İptal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-6 py-3">Ad</th>
                    <th class="px-6 py-3">E-posta</th>
                    <th class="px-6 py-3">Rol</th>
                    <th class="px-6 py-3">Durum</th>
                    @if($isSuper)
                        <th class="px-6 py-3">Tenant</th>
                    @endif
                    <th class="px-6 py-3 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $u)
                    <tr class="hover:bg-slate-50" wire:key="user-{{ $u['id'] }}">
                        <td class="px-6 py-3 font-medium text-slate-900">{{ $u['name'] }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $u['email'] }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $u['role'] === 'superadmin' ? 'bg-violet-50 text-violet-700' : ($u['role'] === 'admin' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700') }}">
                                {{ $u['role'] }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $u['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : ($u['status'] === 'suspended' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                                <span class="w-1.5 h-1.5 rounded-full
                                    {{ $u['status'] === 'active' ? 'bg-emerald-500' : ($u['status'] === 'suspended' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                                {{ $u['status'] }}
                            </span>
                        </td>
                        @if($isSuper)
                            <td class="px-6 py-3 text-xs font-mono text-slate-500">{{ $u['tenant_id'] ?: '—' }}</td>
                        @endif
                        <td class="px-6 py-3 text-right">
                            @if($u['role'] !== 'superadmin')
                                <div class="flex items-center justify-end gap-3">
                                    <button wire:click="openEdit('{{ $u['id'] }}')"
                                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                        Düzenle
                                    </button>
                                    <button wire:click="toggleStatus('{{ $u['id'] }}')"
                                            class="text-xs font-medium text-amber-600 hover:text-amber-800">
                                        {{ $u['status'] === 'active' ? 'Askıya Al' : 'Aktive Et' }}
                                    </button>
                                    <button wire:click="delete('{{ $u['id'] }}')"
                                            wire:confirm="Bu kullanıcıyı devre dışı bırakmak istediğinize emin misiniz?"
                                            class="text-xs font-medium text-rose-600 hover:text-rose-800">
                                        Devre Dışı
                                    </button>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 6 : 5 }}" class="px-6 py-8 text-center text-slate-500">Kullanıcı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
