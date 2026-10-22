<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $title ?? 'Dashboard' }} — Kurumsal ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        // FOUC önleme — sayfa render edilmeden önce tema uygula
        (function() {
            const theme = localStorage.getItem('erp_theme') || 'system';
            const color = localStorage.getItem('erp_color') || 'indigo';
            const isDark = theme === 'dark' ||
                (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.setAttribute('data-color', color);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased font-sans">
    <div class="min-h-full flex">
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0">
            @include('layouts.partials.topbar')

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 scrollbar-thin">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('layouts.partials.theme-panel')

    @livewireScripts
</body>
</html>
