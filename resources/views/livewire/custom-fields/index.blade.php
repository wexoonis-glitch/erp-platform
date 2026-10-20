<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Custom Fields</h2>
            <p class="mt-1 text-slate-600">{{ count($fields) }} alan tanımlı</p>
        </div>
        <button wire:click="$toggle('showCreate')"
                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition">
            + Yeni Alan
        </button>
    </div>

    {{-- Create Form --}}
    @if($showCreate)
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Yeni Custom Field</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Entity</label>
                    <select wire:model="entity"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Seçiniz —</option>
                        @foreach($entities as $e)
                            <option value="{{ $e }}">{{ $e }}</option>
                        @endforeach
                    </select>
                    @error('entity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kod</label>
                    <input type="text" wire:model="code" placeholder="alerji_notu"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Label</label>
                    <input type="text" wire:model="label" placeholder="Alerji Notu"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('label') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tip</label>
                    <select wire:model.live="data_type"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Seçenekler (virgülle)</label>
                        <input type="text" wire:model="enum_options" placeholder="low,normal,high"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('enum_options') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
                    <h3 class="text-lg font-semibold text-slate-900">Custom Field Düzenle</h3>
                    <button wire:click="cancelEdit" class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>
                <form wire:submit="saveEdit" class="p-6 space-y-4">
                    <div class="bg-slate-50 rounded-lg p-3 text-xs text-slate-600">
                        <strong>Entity:</strong> {{ $editingId }} — <code>entity, code, data_type</code> kilitlidir (veri tutarlılığı).
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Label</label>
                        <input type="text" wire:model="editLabel"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('editLabel') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    @php
                        $f = collect($fields)->firstWhere('id', $editingId);
                    @endphp
                    @if($f && $f['data_type'] === 'enum')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Seçenekler (virgülle)</label>
                            <input type="text" wire:model="editEnumOptions"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('editEnumOptions') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
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
                    <th class="px-6 py-3">Entity</th>
                    <th class="px-6 py-3">Kod</th>
                    <th class="px-6 py-3">Label</th>
                    <th class="px-6 py-3">Tip</th>
                    @if($isSuper)
                        <th class="px-6 py-3">Tenant</th>
                    @endif
                    <th class="px-6 py-3 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($fields as $f)
                    <tr class="hover:bg-slate-50" wire:key="field-{{ $f['id'] }}">
                        <td class="px-6 py-3 font-mono text-slate-700">{{ $f['entity'] }}</td>
                        <td class="px-6 py-3 font-mono text-slate-900">{{ $f['code'] }}</td>
                        <td class="px-6 py-3 text-slate-900">{{ $f['label'] }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $f['data_type'] }}
                            </span>
                        </td>
                        @if($isSuper)
                            <td class="px-6 py-3 text-xs font-mono text-slate-500">{{ substr($f['tenant_id'], 0, 8) }}…</td>
                        @endif
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button wire:click="openEdit('{{ $f['id'] }}')"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                    Düzenle
                                </button>
                                <button wire:click="delete('{{ $f['id'] }}')"
                                        wire:confirm="Silmek istediğinize emin misiniz?"
                                        class="text-xs font-medium text-rose-600 hover:text-rose-800">
                                    Sil
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 6 : 5 }}" class="px-6 py-8 text-center text-slate-500">Henüz alan yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
