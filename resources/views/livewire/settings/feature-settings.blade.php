<div class="space-y-6" style="max-width: 1000px;">

    {{-- Başlık --}}
    <div>
        <h2 class="page-title">Sistem Özellikleri</h2>
        <p class="page-subtitle">Şirketiniz için aktif olacak modül ve özellikleri seçin.</p>
    </div>

    {{-- Flash mesaj --}}
    @if($message)
        <div style="padding: 10px 14px; border-radius: 8px; font-size: 13px;
            @if($messageType === 'success') background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;
            @elseif($messageType === 'error') background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;
            @else background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;
            @endif">
            {{ $message }}
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         MODÜLLER — Kart grid, ana seçim
         ═══════════════════════════════════════════════════════ --}}
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin: 0;">
                📦 Modüller
            </h3>
            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">
                Kapalı modüller tüm kullanıcılar için gizlenir.
            </p>
        </div>

        <div style="padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
            @foreach($moduleFlags as $key => $flag)
                @php $enabled = $this->flags[$key]['enabled'] ?? false; @endphp
                <div style="border: 2px solid {{ $enabled ? '#4f46e5' : '#e2e8f0' }};
                            background: {{ $enabled ? '#eef2ff' : 'white' }};
                            border-radius: 10px; padding: 14px 16px;
                            display: flex; align-items: center; justify-content: space-between; gap: 12px;
                            transition: all 0.15s;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-weight: 600; font-size: 14px; color: #0f172a; margin-bottom: 2px;">
                            {{ $flag['label'] }}
                        </div>
                        @if(!empty($flag['description']))
                            <div style="font-size: 11px; color: #64748b; line-height: 1.4;">
                                {{ $flag['description'] }}
                            </div>
                        @endif
                    </div>

                    <button type="button"
                            wire:click="toggle('{{ $key }}')"
                            class="erp-toggle {{ $enabled ? 'is-on' : '' }}"
                            aria-label="Toggle {{ $flag['label'] }}">
                        <span class="erp-toggle-knob"></span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         DETAYLI AYARLAR — Katlanır gruplar
         ═══════════════════════════════════════════════════════ --}}
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin: 0;">
                ⚙️ Detaylı Ayarlar
            </h3>
            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">
                Modül içi özellikler. Üst modül kapalıysa alt özellikler otomatik devre dışı kalır.
            </p>
        </div>

        @foreach($detailGroups as $groupKey => $group)
            <details class="erp-details" {{ $loop->first ? 'open' : '' }}>
                <summary class="erp-summary">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <svg class="erp-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span style="font-weight: 600; font-size: 14px; color: #0f172a;">{{ $group['label'] }}</span>
                    </span>
                    <span style="font-size: 11px; color: #94a3b8; background: #f1f5f9; padding: 2px 8px; border-radius: 10px;">
                        {{ count($group['items']) }} ayar
                    </span>
                </summary>

                <div style="padding: 0 20px 8px 20px;">
                    @foreach($group['items'] as $key => $flag)
                        @php
                            $enabled = $this->flags[$key]['enabled'] ?? false;
                            $isDisabled = $flag['disabled_by_parent'] ?? false;
                            $hasOverride = $flag['has_override'] ?? false;
                        @endphp
                        <div style="display: flex; align-items: center; gap: 14px;
                                    padding: 14px 0; border-bottom: 1px solid #f1f5f9;
                                    {{ $isDisabled ? 'opacity: 0.5;' : '' }}">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-weight: 500; font-size: 13px; color: #0f172a;">
                                        {{ $flag['label'] }}
                                    </span>
                                    @if($hasOverride)
                                        <span style="font-size: 10px; padding: 1px 6px; border-radius: 4px;
                                                     background: #fef3c7; color: #92400e;">
                                            özel
                                        </span>
                                    @endif
                                </div>
                                @if(!empty($flag['description']))
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                        {{ $flag['description'] }}
                                    </div>
                                @endif
                                @if($isDisabled)
                                    <div style="font-size: 11px; color: #d97706; margin-top: 4px;">
                                        ⚠ Üst özellik kapalı — önce modülü açın
                                    </div>
                                @endif

                                {{-- type=int ise sayı input --}}
                                @if(($flag['type'] ?? 'bool') === 'int')
                                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
                                        <input type="number"
                                               value="{{ $this->flags[$key]['config'] ?? $flag['value'] }}"
                                               min="{{ $flag['min'] ?? 0 }}"
                                               max="{{ $flag['max'] ?? 9999 }}"
                                               wire:change="setValue('{{ $key }}', $event.target.value)"
                                               @if($isDisabled) disabled @endif
                                               style="width: 90px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                                        @if(isset($flag['min']) && isset($flag['max']))
                                            <span style="font-size: 11px; color: #94a3b8;">({{ $flag['min'] }}–{{ $flag['max'] }})</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Reset butonu (override varsa) --}}
                            @if($hasOverride)
                                <button wire:click="resetKey('{{ $key }}')"
                                        wire:confirm="Bu özellik varsayılana dönsün mü?"
                                        style="font-size: 11px; color: #94a3b8; background: none; border: none; cursor: pointer; padding: 4px 8px;">
                                    Sıfırla
                                </button>
                            @endif

                            {{-- Toggle --}}
                            <button type="button"
                                    wire:click="toggle('{{ $key }}')"
                                    @if($isDisabled) disabled @endif
                                    class="erp-toggle {{ $enabled ? 'is-on' : '' }}"
                                    aria-label="Toggle {{ $flag['label'] }}">
                                <span class="erp-toggle-knob"></span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>

    <div style="padding: 12px 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;
                font-size: 12px; color: #1e40af; display: flex; align-items: flex-start; gap: 8px;">
        <span style="font-size: 16px;">ℹ️</span>
        <span>Değişiklikler anında kaydedilir. Kullanıcılar bir sonraki sayfa yenilemesinde yeni durumu görür.</span>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════
     TOGGLE CSS — Tailwind'e bağımlı değil, plain CSS
     ═══════════════════════════════════════════════════════ --}}
<style>
    .erp-toggle {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        border-radius: 9999px;
        background: #cbd5e1;
        border: none;
        cursor: pointer;
        transition: background 0.2s ease;
        flex-shrink: 0;
        padding: 0;
    }
    .erp-toggle.is-on {
        background: #4f46e5;
    }
    .erp-toggle:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .erp-toggle-knob {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        transition: transform 0.2s ease;
        pointer-events: none;
    }
    .erp-toggle.is-on .erp-toggle-knob {
        transform: translateX(20px);
    }

    .erp-details {
        border-top: 1px solid #e2e8f0;
    }
    .erp-details:first-of-type {
        border-top: none;
    }
    .erp-summary {
        list-style: none;
        cursor: pointer;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        user-select: none;
        transition: background 0.15s;
    }
    .erp-summary::-webkit-details-marker { display: none; }
    .erp-summary:hover {
        background: #f8fafc;
    }
    .erp-details[open] > .erp-summary {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .erp-chevron {
        transition: transform 0.2s ease;
        color: #94a3b8;
    }
    .erp-details[open] > .erp-summary .erp-chevron {
        transform: rotate(90deg);
    }
</style>
