<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Tenants</h2>
            <p class="mt-1 text-slate-600">{{ count($tenants) }} kiracı</p>
        </div>
        <button wire:click="$toggle('showCreate')"
                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition">
            + Yeni Tenant
        </button>
    </div>

    {{-- Create Form --}}
    @if($showCreate)
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Yeni Tenant</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kod</label>
                    <input type="text" wire:model="code" placeholder="acme-corp"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ad</label>
                    <input type="text" wire:model="name" placeholder="Acme Corp"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tier</label>
                    <select wire:model="tier"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="shared">shared</option>
                        <option value="schema">schema</option>
                        <option value="dedicated">dedicated</option>
                    </select>
                </div>
                <div class="sm:col-span-3 flex items-center gap-3">
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
                    <h3 class="text-lg font-semibold text-slate-900">Tenant Düzenle</h3>
                    <button wire:click="cancelEdit" class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>
                <form wire:submit="saveEdit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ad</label>
                        <input type="text" wire:model="editName"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('editName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tier</label>
                        <select wire:model="editTier"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="shared">shared</option>
                            <option value="schema">schema</option>
                            <option value="dedicated">dedicated</option>
                        </select>
                        @error('editTier') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Config (JSON)</label>
                        <textarea wire:model="editConfigJson" rows="6"
                                  class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder='{"currency":"TRY","timezone":"Europe/Istanbul"}'></textarea>
                        @error('editConfigJson') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
                    <th class="px-6 py-3">Kod</th>
                    <th class="px-6 py-3">Ad</th>
                    <th class="px-6 py-3">Tier</th>
                    <th class="px-6 py-3">Durum</th>
                    <th class="px-6 py-3 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tenants as $t)
                    <tr class="hover:bg-slate-50" wire:key="tenant-{{ $t['id'] }}">
                        <td class="px-6 py-3 font-mono text-slate-700">{{ $t['code'] }}</td>
                        <td class="px-6 py-3 font-medium text-slate-900">{{ $t['name'] }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $t['tier'] }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $t['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : ($t['status'] === 'suspended' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                <span class="w-1.5 h-1.5 rounded-full
                                    {{ $t['status'] === 'active' ? 'bg-emerald-500' : ($t['status'] === 'suspended' ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                                {{ $t['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @if($t['status'] !== 'archived')
                                    <button wire:click="openEdit('{{ $t['id'] }}')"
                                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                        Düzenle
                                    </button>
                                    <button wire:click="toggleStatus('{{ $t['id'] }}')"
                                            class="text-xs font-medium text-amber-600 hover:text-amber-800">
                                        {{ $t['status'] === 'active' ? 'Askıya Al' : 'Aktive Et' }}
                                    </button>
                                    <button wire:click="archive('{{ $t['id'] }}')"
                                            wire:confirm="Bu tenant'ı arşivlemek istediğinize emin misiniz? Kullanıcıları giriş yapamayacak."
                                            class="text-xs font-medium text-rose-600 hover:text-rose-800">
                                        Arşivle
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">Arşivli</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Henüz tenant yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
