<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Hesap Planı</h2>
            <p class="page-subtitle">{{ count($accounts) }} hesap</p>
        </div>
        <button wire:click="openCreate" class="btn-primary" @if(!$selectedTenantId && $isSuper) disabled @endif>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Hesap
        </button>
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">Tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Tenant Seçiniz —</option>
                @foreach($tenants as $t)
                    <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                @endforeach
            </select>
            @error('tenant_id') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>
    @endif

    @error('account')
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
            {{ $message }}
        </div>
    @enderror

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-2xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ $editingId ? 'Hesap Düzenle' : 'Yeni Hesap' }}
                    </h3>
                    <button wire:click="cancel" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Kod</label>
                            <input type="text" wire:model="code" placeholder="100" class="form-input">
                            @error('code') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Ad</label>
                            <input type="text" wire:model="name" placeholder="Kasa" class="form-input">
                            @error('name') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Tip</label>
                            <select wire:model="type" class="form-select">
                                <option value="asset">Varlık</option>
                                <option value="liability">Yükümlülük</option>
                                <option value="equity">Öz Kaynak</option>
                                <option value="revenue">Gelir</option>
                                <option value="expense">Gider</option>
                            </select>
                            @error('type') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Üst Hesap</label>
                            <select wire:model="parent_id" class="form-select">
                                <option value="">— Yok (kök) —</option>
                                @foreach($accounts as $a)
                                    @if($a['id'] !== $editingId)
                                        <option value="{{ $a['id'] }}">{{ $a['code'] }} — {{ $a['name'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('parent_id') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Para Birimi</label>
                            <input type="text" wire:model="currency" maxlength="3" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Durum</label>
                            <select wire:model="status" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="inactive">Pasif</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" wire:model="is_postable" class="rounded border-slate-300 dark:border-slate-700 text-primary-600">
                                Postable (kayıt girilebilir)
                            </label>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Açıklama</label>
                            <textarea wire:model="description" rows="2" class="form-input"></textarea>
                        </div>
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
                <tr>
                    <th>Kod</th>
                    <th>Ad</th>
                    <th>Tip</th>
                    <th>Durum</th>
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $a)
                    <tr wire:key="acc-{{ $a['id'] }}">
                        <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $a['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $a['name'] }}</td>
                        <td><span class="badge-muted">{{ $a['type'] }}</span></td>
                        <td><span class="{{ $a['status'] === 'active' ? 'badge-success' : 'badge-muted' }}">{{ $a['status'] }}</span></td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="openEdit('{{ $a['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                <button wire:click="delete('{{ $a['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-12">
                        <div class="text-4xl mb-2">📊</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            @if($isSuper && !$selectedTenantId)
                                Yukarıdan bir tenant seçin.
                            @else
                                Henüz hesap yok.
                            @endif
                        </p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
