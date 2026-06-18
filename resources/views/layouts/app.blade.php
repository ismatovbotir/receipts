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

<div x-data="{ open: JSON.parse(localStorage.getItem('sidebarOpen') ?? 'true') }"
     x-effect="localStorage.setItem('sidebarOpen', JSON.stringify(open))">

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <aside class="fixed top-0 left-0 h-screen bg-slate-900 flex flex-col z-50
                  transition-all duration-300 overflow-hidden"
           :class="open ? 'w-64' : 'w-16'">

        {{-- Logo + toggle --}}
        <div class="flex items-center h-16 border-b border-slate-800 flex-shrink-0 transition-all duration-300"
             :class="open ? 'px-3 gap-2' : 'justify-center'">

            {{-- Logo icon: only shown when open --}}
            <div x-show="open"
                 class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>

            {{-- Title: only when open --}}
            <span x-show="open"
                  x-transition:enter="transition-opacity duration-200 delay-100"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  class="text-white font-bold text-lg flex-1 truncate">
                ReceiptReport
            </span>

            {{-- Toggle button: always visible, centered when collapsed --}}
            <button @click="open = !open"
                    class="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0
                           text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    :title="open ? 'Yopish' : 'Ochish'">
                <svg class="w-4 h-4 transition-transform duration-300"
                     :class="open ? '' : 'rotate-180'"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 py-3 overflow-y-auto overflow-x-hidden"
             :class="open ? 'px-3' : 'px-2'">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               :title="open ? null : 'Bosh sahifa'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span x-show="open" class="truncate">Bosh sahifa</span>
            </a>

            {{-- AI Tahlil --}}
            <a href="{{ route('analytics') }}"
               :title="open ? null : 'AI Tahlil'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('analytics') ? 'bg-violet-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                </svg>
                <span x-show="open" class="truncate flex-1">AI Tahlil</span>
                <span x-show="open"
                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full flex-shrink-0
                             {{ request()->routeIs('analytics') ? 'bg-white/20 text-white' : 'bg-violet-500/20 text-violet-400' }}">AI</span>
            </a>

            {{-- Group: Tahlil --}}
            <template x-if="open">
                <p class="px-3 pt-4 pb-1.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Tahlil</p>
            </template>
            <div x-show="!open" class="border-t border-slate-700/60 my-2 mx-1"></div>

            <a href="{{ route('sales.index') }}"
               :title="open ? null : 'Savdo tahlili'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('sales.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                <span x-show="open" class="truncate">Savdo tahlili</span>
            </a>

            <a href="{{ route('cashiers.index') }}"
               :title="open ? null : 'Kassirlar'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('cashiers.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-show="open" class="truncate">Kassirlar</span>
            </a>

            <a href="{{ route('products.index') }}"
               :title="open ? null : 'Mahsulotlar'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('products.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span x-show="open" class="truncate">Mahsulotlar</span>
            </a>

            {{-- Group: Ma'lumotlar --}}
            <template x-if="open">
                <p class="px-3 pt-4 pb-1.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Ma'lumotlar</p>
            </template>
            <div x-show="!open" class="border-t border-slate-700/60 my-2 mx-1"></div>

            <a href="{{ route('receipts.index') }}"
               :title="open ? null : 'Cheklar'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('receipts.*') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span x-show="open" class="truncate">Cheklar</span>
            </a>

            <a href="{{ route('calendar') }}"
               :title="open ? null : 'Kalendar'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('calendar') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span x-show="open" class="truncate">Kalendar</span>
            </a>

            {{-- Group: Tizim --}}
            <template x-if="open">
                <p class="px-3 pt-4 pb-1.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Tizim</p>
            </template>
            <div x-show="!open" class="border-t border-slate-700/60 my-2 mx-1"></div>

            <a href="{{ route('aggregations') }}"
               :title="open ? null : 'Agregatsiyalar'"
               class="flex items-center py-2.5 rounded-lg mb-0.5 transition-colors text-sm font-medium
                      {{ request()->routeIs('aggregations') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :class="open ? 'px-3 gap-3' : 'justify-center px-0'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
                <span x-show="open" class="truncate">Agregatsiyalar</span>
            </a>

        </nav>

        {{-- User --}}
        <div class="border-t border-slate-800 p-3 flex-shrink-0">
            <div class="flex items-center gap-3 rounded-lg"
                 :class="open ? 'px-2' : 'justify-center'">
                <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div x-show="open"
                     x-transition:enter="transition-opacity duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100">
                    <p class="text-sm font-medium text-white leading-tight">Admin</p>
                    <p class="text-xs text-slate-400">Administrator</p>
                </div>
            </div>
        </div>

    </aside>

    {{-- ── Main content ─────────────────────────────────────────────────────── --}}
    <main class="min-h-screen bg-slate-50 transition-all duration-300"
          :class="open ? 'ml-64' : 'ml-16'">
        @yield('content')
    </main>

</div>

@stack('scripts')
@livewireScripts

{{-- Global loading modal --}}
<div id="loading-modal"
     style="display:none; opacity:0; transition:opacity 0.25s ease;"
     class="fixed inset-0 z-[500] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl px-10 py-9 flex flex-col items-center gap-5 min-w-[300px]">
        <div class="relative w-16 h-16">
            <div class="absolute inset-0 rounded-full border-4 border-slate-100"></div>
            <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-600 animate-spin"></div>
            <div class="absolute inset-[6px] rounded-full border-4 border-transparent border-t-blue-300 animate-spin"
                 style="animation-duration:0.6s; animation-direction:reverse;"></div>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay:0ms;"></span>
            <span class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay:120ms;"></span>
            <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay:240ms;"></span>
        </div>
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
