<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Puantaj Yönetimi</h2><p class="page-subtitle">Son {{ count($attendances) }} kayıt</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Puantaj</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>@endforeach</select></div>
    @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-2xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Puantaj Kaydı</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Personel</label>
                            <select wire:model="employee_id" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp['id'] }}">{{ $emp['employee_no'] }} — {{ $emp['first_name'] }} {{ $emp['last_name'] }}</option>
                                @endforeach
                            </select>
                            @error('employee_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="date" class="form-input">@error('date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div>
                            <label class="form-label">Durum</label>
                            <select wire:model="status" class="form-select">
                                <option value="present">Geldi (Çalıştı)</option>
                                <option value="absent">Gelmedi (Devamsız)</option>
                                <option value="leave">İzinli</option>
                                <option value="sick">Raporlu</option>
                                <option value="holiday">Tatil</option>
                            </select>
                            @error('status') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div><label class="form-label">Fazla Mesai (Saat)</label><input type="number" step="0.5" wire:model="overtime_hours" class="form-input">@error('overtime_hours') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div class="sm:col-span-2"><label class="form-label">Notlar</label><textarea wire:model="notes" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Tarih</th><th>Personel</th><th>Durum</th><th>Fazla Mesai</th><th>Notlar</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($attendances as $a)
                    @php
                        $badge = match($a['status']) {
                            'present' => 'badge-success',
                            'absent' => 'badge-danger',
                            'leave' => 'badge-warning',
                            'sick' => 'badge-info',
                            'holiday' => 'badge-muted',
                            default => 'badge-muted'
                        };
                        $label = match($a['status']) {
                            'present' => 'Geldi',
                            'absent' => 'Gelmedi',
                            'leave' => 'İzinli',
                            'sick' => 'Raporlu',
                            'holiday' => 'Tatil',
                            default => $a['status']
                        };
                    @endphp
                    <tr wire:key="att-{{ $a['id'] }}">
                        <td class="text-xs font-mono">{{ \Carbon\Carbon::parse($a['date'])->format('d.m.Y') }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $a['employee']['first_name'] }} {{ $a['employee']['last_name'] }}</td>
                        <td><span class="{{ $badge }}">{{ $label }}</span></td>
                        <td class="font-mono">{{ $a['overtime_hours'] > 0 ? $a['overtime_hours'] . ' saat' : '—' }}</td>
                        <td class="text-xs text-slate-500">{{ $a['notes'] ?: '—' }}</td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="delete('{{ $a['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-12 text-slate-500">Henüz puantaj kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
