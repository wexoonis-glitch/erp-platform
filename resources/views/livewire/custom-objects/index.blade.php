<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Custom Objects</h2>
            <p class="page-subtitle">{{ count($objects) }} nesne tanımlı</p>
        </div>
        <button wire:click="$toggle('showCreate')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Object
        </button>
    </div>

    {{-- Create Panel --}}
    @if($showCreate)
        <div class="card card-body">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Yeni Custom Object</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                @if($isSuper)
                    <div class="sm:col-span-3">
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
                    <label class="form-label">Kod</label>
                    <input type="text" wire:model="code" placeholder="fleet_vehicle" class="form-input">
                    @error('code') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Label</label>
                    <input type="text" wire:model="label" placeholder="Araç Filosu" class="form-input">
                    @error('label') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Alanlar</h4>
                    <button wire:click="addField" class="btn-ghost-primary" type="button">+ Alan Ekle</button>
                </div>
                @error('schemaFields') <p class="mb-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

                <div class="space-y-2">
                    @foreach($schemaFields as $i => $f)
                        <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">
                            <div class="col-span-12 sm:col-span-3">
                                <input type="text" wire:model="schemaFields.{{ $i }}.code" placeholder="code" class="form-input text-xs">
                                @error("schemaFields.{$i}.code") <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-12 sm:col-span-3">
                                <input type="text" wire:model="schemaFields.{{ $i }}.label" placeholder="label" class="form-input text-xs">
                                @error("schemaFields.{$i}.label") <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-6 sm:col-span-2">
                                <select wire:model.live="schemaFields.{{ $i }}.data_type" class="form-select text-xs">
                                    <option value="string">string</option>
                                    <option value="number">number</option>
                                    <option value="date">date</option>
                                    <option value="bool">bool</option>
                                    <option value="enum">enum</option>
                                    <option value="json">json</option>
                                </select>
                            </div>
                            <div class="col-span-6 sm:col-span-2 flex items-center gap-2 pt-2">
                                <input type="checkbox" wire:model="schemaFields.{{ $i }}.required" id="req-{{ $i }}" class="rounded border-slate-300 dark:border-slate-700 text-primary-600">
                                <label for="req-{{ $i }}" class="text-xs text-slate-600 dark:text-slate-400">Zorunlu</label>
                            </div>
                            <div class="col-span-12 sm:col-span-1 flex justify-end pt-1">
                                @if(count($schemaFields) > 1)
                                    <button wire:click="removeField({{ $i }})" type="button" class="text-rose-600 dark:text-rose-400 hover:text-rose-800 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            @if(($f['data_type'] ?? '') === 'enum')
                                <div class="col-span-12">
                                    <input type="text" wire:model="schemaFields.{{ $i }}.options" placeholder="Seçenekler (virgülle: low,normal,high)" class="form-input text-xs">
                                    @error("schemaFields.{$i}.options") <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="create" class="btn-primary">Oluştur</button>
                <button wire:click="$set('showCreate', false)" class="btn-secondary">İptal</button>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($editingId)
        <div class="modal-backdrop" wire:click.self="cancelEdit">
            <div class="modal-content max-w-3xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Object Düzenle</h3>
                    <button wire:click="cancelEdit" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div>
                        <label class="form-label">Label</label>
                        <input type="text" wire:model="editLabel" class="form-input">
                        @error('editLabel') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Alanlar</h4>
                            <button wire:click="addEditField" class="btn-ghost-primary" type="button">+ Alan Ekle</button>
                        </div>
                        @error('schemaFields') <p class="mb-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

                        <div class="space-y-2">
                            @foreach($editSchemaFields as $i => $f)
                                <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">
                                    <div class="col-span-12 sm:col-span-3">
                                        <input type="text" wire:model="editSchemaFields.{{ $i }}.code" placeholder="code" class="form-input text-xs">
                                        @error("editSchemaFields.{$i}.code") <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="col-span-12 sm:col-span-3">
                                        <input type="text" wire:model="editSchemaFields.{{ $i }}.label" placeholder="label" class="form-input text-xs">
                                        @error("editSchemaFields.{$i}.label") <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="col-span-6 sm:col-span-2">
                                        <select wire:model.live="editSchemaFields.{{ $i }}.data_type" class="form-select text-xs">
                                            <option value="string">string</option>
                                            <option value="number">number</option>
                                            <option value="date">date</option>
                                            <option value="bool">bool</option>
                                            <option value="enum">enum</option>
                                            <option value="json">json</option>
                                        </select>
                                    </div>
                                    <div class="col-span-6 sm:col-span-2 flex items-center gap-2 pt-2">
                                        <input type="checkbox" wire:model="editSchemaFields.{{ $i }}.required" id="ereq-{{ $i }}" class="rounded border-slate-300 dark:border-slate-700 text-primary-600">
                                        <label for="ereq-{{ $i }}" class="text-xs text-slate-600 dark:text-slate-400">Zorunlu</label>
                                    </div>
                                    <div class="col-span-12 sm:col-span-1 flex justify-end pt-1">
                                        @if(count($editSchemaFields) > 1)
                                            <button wire:click="removeEditField({{ $i }})" type="button" class="text-rose-600 dark:text-rose-400 hover:text-rose-800 p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                    @if(($f['data_type'] ?? '') === 'enum')
                                        <div class="col-span-12">
                                            <input type="text" wire:model="editSchemaFields.{{ $i }}.options" placeholder="Seçenekler (virgülle)" class="form-input text-xs">
                                            @error("editSchemaFields.{$i}.options") <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button wire:click="saveEdit" class="btn-primary">Kaydet</button>
                        <button wire:click="cancelEdit" class="btn-secondary">İptal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>Label</th>
                    <th>Alan Sayısı</th>
                    @if($isSuper) <th>Tenant</th> @endif
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($objects as $o)
                    <tr wire:key="obj-{{ $o['id'] }}">
                        <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $o['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $o['label'] }}</td>
                        <td><span class="badge-info">{{ $o['field_count'] }} alan</span></td>
                        @if($isSuper)
                            <td class="text-xs">
                                @php $tenant = $tenantLookup[$o['tenant_id']] ?? null; @endphp
                                @if($tenant)
                                    <div class="flex flex-col">
                                        <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $tenant['code'] }}</span>
                                        <span class="text-slate-500 dark:text-slate-400">{{ $tenant['name'] }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('custom-objects.records', $o['id']) }}" class="btn-ghost-primary">Kayıtlar</a>
                                <button wire:click="openEdit('{{ $o['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                <button wire:click="delete('{{ $o['id'] }}')"
                                        wire:confirm="Object ve tüm kayıtları silinecek. Emin misiniz?"
                                        class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 5 : 4 }}" class="text-center py-12">
                        <div class="text-4xl mb-2">📦</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Henüz object yok.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
