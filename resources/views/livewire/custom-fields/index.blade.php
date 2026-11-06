<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Custom Fields</h2>
            <p class="page-subtitle">{{ count($fields) }} alan tanımlı</p>
        </div>
        <button wire:click="$toggle('showCreate')" class="btn-primary">+ Yeni Alan</button>
    </div>

    {{-- Create Form --}}
    @if($showCreate)
        <div class="card card-body space-y-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Yeni Custom Field</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($isSuper)
                    <div class="sm:col-span-2">
                        <label class="form-label">Tenant</label>
                        <select wire:model.live="tenant_id" class="form-select">
                            <option value="">— Seçiniz —</option>
                            @foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>@endforeach
                        </select>
                        @error('tenant_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div>
                    <label class="form-label">Entity</label>
                    <select wire:model="entity" class="form-select">
                        <option value="">— Seçiniz —</option>
                        @foreach($entities as $code => $label)
                            <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                        @endforeach
                    </select>
                    @error('entity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Kod</label>
                    <input type="text" wire:model="code" placeholder="alerji_notu" class="form-input">
                    <p class="mt-1 text-[10px] text-slate-500">Küçük harf, rakam, alt çizgi</p>
                    @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Label</label>
                    <input type="text" wire:model="label" placeholder="Alerji Notu" class="form-input">
                    @error('label') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
                        <input type="text" wire:model="enum_options" placeholder="low, normal, high" class="form-input">
                        @error('enum_options') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="is_required" class="rounded">
                        Zorunlu alan
                    </label>
                </div>
                <div class="sm:col-span-2 flex items-center gap-3">
                    <button type="submit" class="btn-primary">Oluştur</button>
                    <button type="button" wire:click="$set('showCreate', false)" class="btn-secondary">İptal</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($editingId)
        <div class="modal-backdrop" wire:click.self="cancelEdit">
            <div class="modal-content max-w-lg">
                <div class="modal-header">
                    <h3 class="text-base font-semibold">Custom Field Düzenle</h3>
                    <button wire:click="cancelEdit">X</button>
                </div>
                <form wire:submit="saveEdit" class="modal-body space-y-4">
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3 text-xs text-slate-600 dark:text-slate-400">
                        <strong>Entity ve data_type kilitli</strong> (veri tutarlılığı için).
                    </div>
                    <div>
                        <label class="form-label">Label</label>
                        <input type="text" wire:model="editLabel" class="form-input">
                        @error('editLabel') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    @php $current = collect($fields)->firstWhere('id', $editingId); @endphp
                    @if($current && $current['data_type'] === 'enum')
                        <div>
                            <label class="form-label">Seçenekler (virgülle)</label>
                            <input type="text" wire:model="editEnumOptions" class="form-input">
                            @error('editEnumOptions') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="editIsRequired" class="rounded">
                            Zorunlu alan
                        </label>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn-primary">Kaydet</button>
                        <button type="button" wire:click="cancelEdit" class="btn-secondary">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Entity</th>
                    <th>Kod</th>
                    <th>Label</th>
                    <th>Tip</th>
                    <th>Zorunlu</th>
                    @if($isSuper)<th>Tenant</th>@endif
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fields as $f)
                    <tr wire:key="field-{{ $f['id'] }}">
                        <td class="font-mono text-xs text-slate-700 dark:text-slate-300">
                            {{ $f['entity'] }}
                            <div class="text-[10px] font-sans text-slate-500">{{ $entities[$f['entity']] ?? '—' }}</div>
                        </td>
                        <td class="font-mono text-xs">{{ $f['code'] }}</td>
                        <td class="text-sm">{{ $f['label'] }}</td>
                        <td><span class="badge-muted">{{ $f['data_type'] }}</span></td>
                        <td>
                            @if($f['config']['required'] ?? false)
                                <span class="badge-danger">Zorunlu</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        @if($isSuper)
                            <td class="text-xs font-mono text-slate-500">{{ substr($f['tenant_id'], 0, 8) }}…</td>
                        @endif
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="openEdit('{{ $f['id'] }}')" class="btn-ghost-primary text-xs">Düzenle</button>
                                <button wire:click="delete('{{ $f['id'] }}')" wire:confirm="Silinsin mi?" class="btn-ghost-danger text-xs">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 7 : 6 }}" class="text-center py-6 text-slate-500">Henüz alan yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
