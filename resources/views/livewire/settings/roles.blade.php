<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Roller & Yetkiler</h2>
            <p class="page-subtitle">Her rol için hangi modüllerin görüneceğini seçin.</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Rol</button>
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">Tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>@endforeach
            </select>
        </div>
    @endif

    @if($message)
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' }}">{{ $message }}</div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700 text-sm rounded-xl">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-3xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold">{{ $editingId ? 'Rol Düzenle' : 'Yeni Rol' }}</h3>
                    <button wire:click="cancel">X</button>
                </div>
                <form wire:submit="save" class="modal-body space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Kod</label>
                            <input type="text" wire:model="code" class="form-input" placeholder="accountant" @if($editingId && isset($roles[0]) && false) @endif>
                            @error('code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Ad</label>
                            <input type="text" wire:model="name" class="form-input" placeholder="Muhasebeci">
                            @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Açıklama</label>
                            <input type="text" wire:model="description" class="form-input" placeholder="Bu rolün görevi...">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="is_active" class="rounded"> Aktif
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                        <label class="form-label">Görebileceği Modüller</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                            @foreach($allModules as $mCode => $mLabel)
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-sm cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <input type="checkbox" wire:model="selectedModules" value="{{ $mCode }}" class="rounded">
                                    <span>{{ $mLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            PLT (Dashboard, Users, Settings) her rol için açık kalmalı — aksi halde kullanıcı sistemden çıkış yapamaz.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn-primary">Kaydet</button>
                        <button type="button" wire:click="cancel" class="btn-secondary">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr><th>Kod</th><th>Ad</th><th>Modüller</th><th>Kullanıcı</th><th>Tip</th><th>Durum</th><th>İşlem</th></tr>
            </thead>
            <tbody>
                @forelse($roles as $r)
                    <tr wire:key="role-{{ $r['id'] }}">
                        <td class="font-mono text-xs font-bold">{{ $r['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $r['name'] }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($r['modules'] as $m)
                                    <span class="badge-primary text-[10px]">{{ $m }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-xs font-mono">{{ $userCounts[$r['code']] ?? 0 }}</td>
                        <td>
                            @if($r['is_system'])
                                <span class="badge-muted text-[10px]">Sistem</span>
                            @else
                                <span class="badge-info text-[10px]">Custom</span>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $r['is_active'] ? 'badge-success' : 'badge-muted' }}">{{ $r['is_active'] ? 'Aktif' : 'Pasif' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="openEdit('{{ $r['id'] }}')" class="btn-ghost-primary text-xs">Düzenle</button>
                                @if(!$r['is_system'])
                                    <button wire:click="delete('{{ $r['id'] }}')" wire:confirm="Silinsin mi?" class="btn-ghost-danger text-xs">Sil</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6 text-slate-500">Henüz rol yok. Varsayılan rolleri seed edin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
