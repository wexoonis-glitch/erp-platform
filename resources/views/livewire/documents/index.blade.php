<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Doküman Yönetimi</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Dosya Yükle</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Dosya Yükle</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Dosya Seç (Maks 10MB)</label>
                            <input type="file" wire:model="file" class="form-input text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <div wire:loading wire:target="file" class="text-xs text-primary-600 mt-2">Yükleniyor...</div>
                            @error('file') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">İlişkili Modül (Opsiyonel)</label>
                            <select wire:model="entity_type" class="form-select">
                                <option value="general">Genel / Bağımsız</option>
                                <option value="ap_invoice">Tedarikçi Faturası</option>
                                <option value="ar_invoice">Müşteri Faturası</option>
                                <option value="employee">Personel Özlük</option>
                                <option value="asset">Varlık / Makine</option>
                            </select>
                        </div>
                        @if($entity_type !== 'general')
                            <div>
                                <label class="form-label">İlgili Kayıt ID (Opsiyonel)</label>
                                <input type="text" wire:model="entity_id" class="form-input" placeholder="UUID girin...">
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Yükle</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Dosya Adı</th><th>Boyut</th><th>İlişkili Modül</th><th>Yüklenme Tarihi</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($documents as $d)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            {{ $d['name'] }}
                        </td>
                        <td class="text-xs font-mono">{{ number_format($d['file_size'] / 1024, 1) }} KB</td>
                        <td><span class="badge-muted">{{ $d['entity_type'] ?? 'Genel' }}</span></td>
                        <td class="text-xs">{{ \Carbon\Carbon::parse($d['created_at'])->format('d.m.Y H:i') }}</td>
                        <td>
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('documents.download', $d['id']) }}" class="btn-ghost-primary text-xs" target="_blank">İndir</a>
                                <button wire:click="delete('{{ $d['id'] }}')" wire:confirm="Dosyayı silmek istediğinize emin misiniz?" class="btn-ghost-danger text-xs">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6">Dosya bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
