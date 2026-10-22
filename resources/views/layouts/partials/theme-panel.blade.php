@php
    $colorOptions = [
        ['key' => 'indigo',  'label' => 'Indigo',  'hex' => '#6366f1'],
        ['key' => 'violet',  'label' => 'Violet',  'hex' => '#8b5cf6'],
        ['key' => 'emerald', 'label' => 'Emerald', 'hex' => '#10b981'],
        ['key' => 'rose',    'label' => 'Rose',    'hex' => '#f43f5e'],
        ['key' => 'sky',     'label' => 'Sky',     'hex' => '#0ea5e9'],
        ['key' => 'amber',   'label' => 'Amber',   'hex' => '#f59e0b'],
    ];
@endphp

<div id="theme-panel"
     class="fixed inset-0 z-[100] hidden">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
         onclick="window.dispatchEvent(new CustomEvent('close-theme-panel'))"></div>

    {{-- Panel --}}
    <div class="absolute right-0 top-0 h-full w-full max-w-sm bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800 flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Görünüm</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tema ve renk tercihleri</p>
            </div>
            <button onclick="window.dispatchEvent(new CustomEvent('close-theme-panel'))"
                    class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-8 scrollbar-thin">
            {{-- Tema Modu --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Tema Modu</h3>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" data-theme-btn="light"
                            class="theme-mode-btn flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-primary-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="text-xs font-medium">Açık</span>
                    </button>
                    <button type="button" data-theme-btn="dark"
                            class="theme-mode-btn flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-primary-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <span class="text-xs font-medium">Koyu</span>
                    </button>
                    <button type="button" data-theme-btn="system"
                            class="theme-mode-btn flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-primary-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs font-medium">Sistem</span>
                    </button>
                </div>
            </div>

            {{-- Renk --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Ana Renk</h3>
                <div class="grid grid-cols-3 gap-3">
                    @foreach($colorOptions as $c)
                        <button type="button" data-color-btn="{{ $c['key'] }}"
                                class="color-choice-btn group flex flex-col items-center gap-2">
                            <div class="w-full aspect-square rounded-xl ring-2 ring-offset-2 ring-transparent group-data-[active=true]:ring-primary-500 transition-all shadow-md"
                                 style="background: linear-gradient(135deg, {{ $c['hex'] }} 0%, {{ $c['hex'] }}cc 100%);">
                            </div>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ $c['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Kullanıcı Notu --}}
            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    💡 Tema tercihleri bu tarayıcıda saklanır. Farklı bir cihazdan girdiğinizde varsayılan temayla başlarsınız.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const panel = document.getElementById('theme-panel');

    function openPanel() {
        panel.classList.remove('hidden');
        syncButtons();
    }
    function closePanel() {
        panel.classList.add('hidden');
    }

    function syncButtons() {
        const current = window.ThemeManager.apply();
        document.querySelectorAll('.theme-mode-btn').forEach(btn => {
            const isActive = btn.dataset.themeBtn === current.theme;
            btn.classList.toggle('border-primary-500', isActive);
            btn.classList.toggle('bg-primary-50', isActive);
            btn.classList.toggle('dark:bg-primary-950/30', isActive);
            btn.classList.toggle('border-slate-200', !isActive);
            btn.classList.toggle('dark:border-slate-700', !isActive);
        });
        document.querySelectorAll('.color-choice-btn').forEach(btn => {
            btn.dataset.active = btn.dataset.colorBtn === current.color ? 'true' : 'false';
        });
    }

    window.addEventListener('open-theme-panel', openPanel);
    window.addEventListener('close-theme-panel', closePanel);

    document.querySelectorAll('.theme-mode-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            window.ThemeManager.setTheme(btn.dataset.themeBtn);
            syncButtons();
        });
    });

    document.querySelectorAll('.color-choice-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            window.ThemeManager.setColor(btn.dataset.colorBtn);
            syncButtons();
        });
    });

    // Escape ile kapat
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.classList.contains('hidden')) closePanel();
    });
})();
</script>
