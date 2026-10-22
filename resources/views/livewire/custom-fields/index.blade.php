<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Custom Fields</h2>
            <p class="page-subtitle">{{ count($fields) }} alan tanımlı</p>
        </div>
        <button wire:click="$toggle('showCreate')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Alan
        </button>
    </div>

    @if($showCreate)
        <div class="card card-body">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Yeni Custom Field</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <div>
                    <label class="form-label">Entity</label>
                    <select wire:model="entity" class="form-select">
                        <option value="">— Seçiniz —</option>
                        @foreach($entities as $e)
                            <option value="{{ $e }}">{{ $e }}</option>
                        @endforeach
                    </select>
                    @error('entity') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Kod</label>
                    <input type="text" wire:model="code" placeholder="alerji_notu" class="form-input">
                    @error('code') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Label</label>
                    <input type="text" wire:model="label" placeholder="Alerji Notu" class="form-input">
                    @error('label') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Tip</label>
                    <select wire:model.live="data_type" class="form-select">
                        <option value="string">string</option>
                        <option value="number">number</option>
                        <option value="date">date</option>
                        <option value="bool">bool</option>
                        <option value="enum">enum</option>
                        <option value="json">json</option>
                    </select>
                </div>
                @if($data_type === 'enum')
                    <div class="sm:col-span-2">
                        <label class="form-label">Seçenekler (virgülle)</label>
                        <input type="text" wire:model="enum_options" placeholder="low,normal,high" class="form-input">
                        @error('enum_options') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
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
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Custom Field Düzenle</h3>
                    <button wire:click="cancelEdit" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit="saveEdit" class="modal-body">
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3 text-xs text-slate-600 dark:text-slate-400">
                        <strong class="text-slate-900 dark:text-white">Not:</strong> <code>entity</code>, <code>code</code>, <code>data_type</code> kilitlidir (veri tutarlılığı).
                    </div>
                    <div>
                        <label class="form-label">Label</label>
                        <input type="text" wire:model="editLabel" class="form-input">
                        @error('editLabel') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    @php $f = collect($fields)->firstWhere('id', $editingId); @endphp
                    @if($f && $f['data_type'] === 'enum')
                        <div>
                            <label class="form-label">Seçenekler (virgülle)</label>
                            <input type="text" wire:model="editEnumOptions" class="form-input">
                            @error('editEnumOptions') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    @endif
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
                    <th>Entity</th>
                    <th>Kod</th>
                    <th>Label</th>
                    <th>Tip</th>
                    @if($isSuper) <th>Tenant</th> @endif
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fields as $f)
                    <tr wire:key="field-{{ $f['id'] }}">
                        <td class="font-mono text-xs">{{ $f['entity'] }}</td>
                        <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $f['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $f['label'] }}</td>
                        <td><span class="badge-muted">{{ $f['data_type'] }}</span></td>
                        @if($isSuper)
                            <td class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ substr($f['tenant_id'], 0, 8) }}…</td>
                        @endif
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="openEdit('{{ $f['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                <button wire:click="delete('{{ $f['id'] }}')"
                                        wire:confirm="Silmek istediğinize emin misiniz?"
                                        class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 6 : 5 }}" class="text-center py-12">
                        <div class="text-4xl mb-2">⚙️</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Henüz alan yok.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
