<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Yevmiye Kayıtları</h2>
            <p class="page-subtitle">{{ count($entries) }} kayıt</p>
        </div>
        <button wire:click="openCreate" class="btn-primary" @if(!$selectedTenantId && $isSuper) disabled @endif>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Kayıt
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
            @error('general') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>
    @endif

    @if($errors->has('balance') || $errors->has('status') || $errors->has('lines') || $errors->has('entry_date') || $errors->has('description'))
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
            {{ $errors->first('balance') ?: ($errors->first('status') ?: ($errors->first('lines') ?: ($errors->first('entry_date') ?: $errors->first('description')))) }}
        </div>
    @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-4xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ $editingId ? 'Kayıt Düzenle' : 'Yeni Yevmiye Kaydı' }}
                    </h3>
                    <button wire:click="cancel" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Tarih</label>
                            <input type="date" wire:model="entry_date" class="form-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Açıklama</label>
                            <input type="text" wire:model="description" class="form-input" placeholder="Ör: Peşin satış">
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Satırlar</h4>
                            <button wire:click="addLine" class="btn-ghost-primary" type="button">+ Satır Ekle</button>
                        </div>
                        <div class="space-y-2">
                            @foreach($lines as $i => $line)
                                <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2">
                                    <div class="col-span-12 sm:col-span-4">
                                        <select wire:model="lines.{{ $i }}.account_id" class="form-select text-xs">
                                            <option value="">— Hesap —</option>
                                            @foreach($accounts as $a)
                                                <option value="{{ $a['id'] }}">{{ $a['code'] }} — {{ $a['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-6 sm:col-span-2">
                                        <input type="number" step="0.01" wire:model="lines.{{ $i }}.debit" placeholder="Borç" class="form-input text-xs">
                                    </div>
                                    <div class="col-span-6 sm:col-span-2">
                                        <input type="number" step="0.01" wire:model="lines.{{ $i }}.credit" placeholder="Alacak" class="form-input text-xs">
                                    </div>
                                    <div class="col-span-10 sm:col-span-3">
                                        <input type="text" wire:model="lines.{{ $i }}.description" placeholder="Açıklama" class="form-input text-xs">
                                    </div>
                                    <div class="col-span-2 sm:col-span-1 flex justify-end pt-1">
                                        @if(count($lines) > 2)
                                            <button wire:click="removeLine({{ $i }})" type="button" class="text-rose-600 dark:text-rose-400 hover:text-rose-800 p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button wire:click="save(false)" class="btn-secondary">Draft Olarak Kaydet</button>
                        <button wire:click="save(true)" class="btn-primary">Kaydet + Post Et</button>
                        <button wire:click="cancel" class="btn-secondary">İptal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tarih</th>
                    <th>Açıklama</th>
                    <th>Durum</th>
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $e)
                    @php
                        $statusBadge = match($e['status']) {
                            'posted'   => 'badge-success',
                            'reversed' => 'badge-muted',
                            default    => 'badge-warning',
                        };
                    @endphp
                    <tr wire:key="je-{{ $e['id'] }}">
                        <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $e['entry_no'] }}</td>
                        <td class="text-xs">{{ $e['entry_date'] }}</td>
                        <td class="text-sm">{{ $e['description'] }}</td>
                        <td><span class="{{ $statusBadge }}">{{ $e['status'] }}</span></td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if($e['status'] === 'draft')
                                    <button wire:click="openEdit('{{ $e['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                    <button wire:click="post('{{ $e['id'] }}')" class="btn-ghost-warning">Post Et</button>
                                    <button wire:click="delete('{{ $e['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                                @elseif($e['status'] === 'posted')
                                    <button wire:click="reverse('{{ $e['id'] }}')" wire:confirm="Ters kayıt oluşturulsun mu?" class="btn-ghost-danger">Ters Kayıt</button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-12">
                        <div class="text-4xl mb-2">📒</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            @if($isSuper && !$selectedTenantId)
                                Yukarıdan bir tenant seçin.
                            @else
                                Henüz kayıt yok.
                            @endif
                        </p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
