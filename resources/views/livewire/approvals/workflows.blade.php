<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Onay Akışları</h2><p class="page-subtitle">Çok adımlı onay tanımları</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Akış</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-3xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Onay Akışı</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input" placeholder="EXPENSE_2STEP"></div>
                        <div><label class="form-label">Ad</label><input type="text" wire:model="name" class="form-input" placeholder="2 Adımlı Masraf Onayı"></div>
                        <div>
                            <label class="form-label">Entity Türü</label>
                            <select wire:model="entity_type" class="form-select">
                                @foreach($entityTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="form-label">Açıklama</label><input type="text" wire:model="description" class="form-input"></div>
                    </div>

                    <div class="mt-4">
                        <div class="flex justify-between mb-2">
                            <h4 class="text-sm font-semibold">Adımlar</h4>
                            <button type="button" wire:click="addStep" class="btn-ghost-primary">+ Adım Ekle</button>
                        </div>
                        @foreach($steps as $i => $step)
                            <div class="grid grid-cols-12 gap-2 items-end bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 mb-2">
                                <div class="col-span-1">
                                    <label class="text-[10px] text-slate-500">#</label>
                                    <div class="text-center font-bold">{{ $i + 1 }}</div>
                                </div>
                                <div class="col-span-4">
                                    <label class="text-[10px] text-slate-500">Adım Adı</label>
                                    <input type="text" wire:model="steps.{{ $i }}.name" class="form-input text-xs" placeholder="Müdür Onayı">
                                </div>
                                <div class="col-span-3">
                                    <label class="text-[10px] text-slate-500">Onaylayıcı Rol</label>
                                    <select wire:model="steps.{{ $i }}.approver_role" class="form-select text-xs">
                                        @foreach($roles as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-[10px] text-slate-500">Min Tutar</label>
                                    <input type="number" step="0.01" wire:model="steps.{{ $i }}.min_amount" class="form-input text-xs" placeholder="boş=0">
                                </div>
                                <div class="col-span-2">
                                    <label class="text-[10px] text-slate-500">Max Tutar</label>
                                    <input type="number" step="0.01" wire:model="steps.{{ $i }}.max_amount" class="form-input text-xs" placeholder="boş=sınırsız">
                                </div>
                                <div class="col-span-12 flex justify-end">
                                    @if(count($steps) > 1)
                                        <button type="button" wire:click="removeStep({{ $i }})" class="text-rose-600 text-xs">Kaldır</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
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
            <thead><tr><th>Kod</th><th>Ad</th><th>Entity</th><th>Adımlar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($workflows as $w)
                    <tr wire:key="wf-{{ $w['id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $w['code'] }}</td>
                        <td class="font-medium">{{ $w['name'] }}</td>
                        <td><span class="badge-muted">{{ $entityTypes[$w['entity_type']] ?? $w['entity_type'] }}</span></td>
                        <td class="text-xs">
                            @foreach($w['steps'] as $s)
                                <div>{{ $s['order_no'] }}. {{ $s['name'] }} ({{ $roles[$s['approver_role']] ?? $s['approver_role'] }}@if($s['min_amount'] || $s['max_amount']) — {{ $s['min_amount'] ?? '0' }}…{{ $s['max_amount'] ?? '∞' }} ₺@endif)</div>
                            @endforeach
                        </td>
                        <td>
                            <span class="{{ $w['is_active'] ? 'badge-success' : 'badge-muted' }}">{{ $w['is_active'] ? 'Aktif' : 'Pasif' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="toggleActive('{{ $w['id'] }}')" class="btn-ghost-primary text-xs">{{ $w['is_active'] ? 'Pasifleştir' : 'Aktifleştir' }}</button>
                                <button wire:click="delete('{{ $w['id'] }}')" wire:confirm="Silinsin mi?" class="btn-ghost-danger text-xs">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6 text-slate-500">Henüz onay akışı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
