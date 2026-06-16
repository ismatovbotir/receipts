<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ReceiptReport')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireStyles
</head>
<body class="bg-slate-50 font-sans">

    {{-- Sidebar --}}
    <aside class="fixed top-0 left-0 h-screen w-64 bg-slate-900 flex flex-col z-50">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-800">
            <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="text-white font-bold text-lg tracking-tight">ReceiptReport</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white rounded-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Bosh sahifa
            </a>

            {{-- Receipts --}}
            <a href="{{ route('receipts.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition-colors
                      {{ request()->routeIs('receipts.*') ? 'bg-blue-600 text-white rounded-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Cheklar
            </a>

            {{-- Aggregations --}}
            <a href="{{ route('aggregations') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition-colors
                      {{ request()->routeIs('aggregations') ? 'bg-blue-600 text-white rounded-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Agregatsiyalar
            </a>

            {{-- Calendar --}}
            <a href="{{ route('calendar') }}"
               class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition-colors
                      {{ request()->routeIs('calendar') ? 'bg-blue-600 text-white rounded-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Kalendar
            </a>

        </nav>

        {{-- Bottom user section --}}
        <div class="px-3 py-4 border-t border-slate-800">
            <div class="flex items-center gap-3 px-3 py-2.5">
                <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-white">Admin</p>
                    <p class="text-xs text-slate-400">Administrator</p>
                </div>
            </div>
        </div>

    </aside>

    {{-- Main content --}}
    <main class="ml-64 min-h-screen bg-slate-50">
        @yield('content')
    </main>

    @stack('scripts')
    @livewireScripts

    {{-- Global loading modal --}}
    <div id="loading-modal"
         style="display:none; opacity:0; transition:opacity 0.25s ease;"
         class="fixed inset-0 z-[500] flex items-center justify-center p-4">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        {{-- Card --}}
        <div class="relative bg-white rounded-2xl shadow-2xl px-10 py-9 flex flex-col items-center gap-5 min-w-[300px]">

            {{-- Spinner --}}
            <div class="relative w-16 h-16">
                <div class="absolute inset-0 rounded-full border-4 border-slate-100"></div>
                <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-600 animate-spin"></div>
                <div class="absolute inset-[6px] rounded-full border-4 border-transparent border-t-blue-300 animate-spin"
                     style="animation-duration:0.6s; animation-direction:reverse;"></div>
            </div>

            {{-- Bouncing dots --}}
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay:0ms;"></span>
                <span class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay:120ms;"></span>
                <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay:240ms;"></span>
            </div>

            {{-- Text --}}
            <div class="text-center">
                <p id="loading-modal-title" class="text-base font-semibold text-slate-800">Yuklanmoqda…</p>
                <p id="loading-modal-sub" class="text-sm text-slate-400 mt-1">Iltimos kuting</p>
            </div>
        </div>
    </div>

    <script>
    function showLoadingModal(title, sub) {
        var el = document.getElementById('loading-modal');
        document.getElementById('loading-modal-title').textContent = title || 'Yuklanmoqda…';
        document.getElementById('loading-modal-sub').textContent  = sub   || 'Iltimos kuting';
        el.style.display = 'flex';
        requestAnimationFrame(function () { el.style.opacity = '1'; });
    }
    function hideLoadingModal() {
        var el = document.getElementById('loading-modal');
        el.style.opacity = '0';
        setTimeout(function () { el.style.display = 'none'; }, 250);
    }
    </script>
</body>
</html>
