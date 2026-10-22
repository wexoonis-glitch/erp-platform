import './bootstrap';

/**
 * Tema yöneticisi:
 *  - theme: 'light' | 'dark' | 'system'
 *  - color: 'indigo' | 'violet' | 'emerald' | 'rose' | 'sky' | 'amber'
 *
 * Anlık uygulama: document.documentElement üzerinde class + data-color.
 * Kalıcılık: server'a Livewire ile POST (users.preferences) + localStorage (fallback).
 */
const THEME_KEY = 'erp_theme';
const COLOR_KEY = 'erp_color';

window.ThemeManager = {
    apply() {
        const theme = localStorage.getItem(THEME_KEY) || 'system';
        const color = localStorage.getItem(COLOR_KEY) || 'indigo';

        const isDark = theme === 'dark' ||
            (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.setAttribute('data-color', color);

        // Meta theme-color (mobil tarayıcı adres çubuğu rengi)
        let meta = document.querySelector('meta[name="theme-color"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.name = 'theme-color';
            document.head.appendChild(meta);
        }
        meta.content = isDark ? '#0f172a' : '#ffffff';

        return { theme, color, isDark };
    },

    setTheme(theme) {
        localStorage.setItem(THEME_KEY, theme);
        this.apply();
    },

    setColor(color) {
        localStorage.setItem(COLOR_KEY, color);
        this.apply();
    },

    init() {
        this.apply();

        // Sistem teması değişirse (system modundaysa) yeniden uygula
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if ((localStorage.getItem(THEME_KEY) || 'system') === 'system') {
                this.apply();
            }
        });
    },
};

// Sayfa yüklenir yüklenmez uygula (FOUC önleme)
window.ThemeManager.init();
