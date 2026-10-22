<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Tenants</h2>
            <p class="page-subtitle">{{ count($tenants) }} kiracı</p>
        </div>
        <button wire:click="$toggle('showCreate')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Tenant
        </button>
    </div>

    @if($showCreate)
        <div class="card card-body">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Yeni Tenant</h3>
            <form wire:submit="create" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Kod</label>
                    <input type="text" wire:model="code" placeholder="acme-corp" class="form-input">
                    @error('code') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Ad</label>
                    <input type="text" wire:model="name" placeholder="Acme Corp" class="form-input">
                    @error('name') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Tier</label>
                    <select wire:model="tier" class="form-select">
                        <option value="shared">shared</option>
                        <option value="schema">schema</option>
                        <option value="dedicated">dedicated</option>
                    </select>
                </div>
                <div class="sm:col-span-3 flex items-center gap-3">
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
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Tenant Düzenle</h3>
                    <button wire:click="cancelEdit" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit="saveEdit" class="modal-body">
                    <div>
                        <label class="form-label">Ad</label>
                        <input type="text" wire:model="editName" class="form-input">
                        @error('editName') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Tier</label>
                        <select wire:model="editTier" class="form-select">
                            <option value="shared">shared</option>
                            <option value="schema">schema</option>
                            <option value="dedicated">dedicated</option>
                        </select>
                        @error('editTier') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Config (JSON)</label>
                        <textarea wire:model="editConfigJson" rows="6" class="form-textarea"
                                  placeholder='{"currency":"TRY"}'></textarea>
                        @error('editConfigJson') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
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
                    <th>Kod</th>
                    <th>Ad</th>
                    <th>Tier</th>
                    <th>Durum</th>
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $t)
                    <tr wire:key="tenant-{{ $t['id'] }}">
                        <td class="font-mono text-xs">{{ $t['code'] }}</td>
                        <td class="font-semibold text-slate-900 dark:text-white">{{ $t['name'] }}</td>
                        <td>{{ $t['tier'] }}</td>
                        <td>
                            @php
                                $statusBadge = match($t['status']) {
                                    'active'    => 'badge-success',
                                    'suspended' => 'badge-warning',
                                    'archived'  => 'badge-muted',
                                    default     => 'badge-muted',
                                };
                                $statusDot = match($t['status']) {
                                    'active'    => 'bg-emerald-500',
                                    'suspended' => 'bg-amber-500',
                                    default     => 'bg-slate-400',
                                };
                            @endphp
                            <span class="{{ $statusBadge }}">
                                <span class="badge-dot {{ $statusDot }}"></span>
                                {{ $t['status'] }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($t['status'] !== 'archived')
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="openEdit('{{ $t['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                    <button wire:click="toggleStatus('{{ $t['id'] }}')" class="btn-ghost-warning">
                                        {{ $t['status'] === 'active' ? 'Askıya Al' : 'Aktive Et' }}
                                    </button>
                                    <button wire:click="archive('{{ $t['id'] }}')"
                                            wire:confirm="Bu tenant'ı arşivlemek istediğinize emin misiniz?"
                                            class="btn-ghost-danger">Arşivle</button>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">Arşivli</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-12">
                        <div class="text-4xl mb-2">🏢</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Henüz tenant yok.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
