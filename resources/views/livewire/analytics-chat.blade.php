<div class="flex flex-col gap-5 max-w-4xl mx-auto" x-data="{}">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="bg-gradient-to-br from-blue-600 to-violet-600 rounded-2xl p-6 text-white flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold leading-tight">AI Tahlil Assistenti</h1>
                <p class="text-sm text-blue-100 mt-0.5">Savdo ma'lumotlari bo'yicha savollar bering (UZ / RU / EN)</p>
            </div>
        </div>
        @if(count($history) > 0)
        <button wire:click="clearHistory"
                class="flex items-center gap-2 text-sm text-white/70 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            Tozalash
        </button>
        @endif
    </div>

    {{-- ── Suggested questions ──────────────────────────────────────────────── --}}
    @if(count($history) === 0)
    <div>
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Tavsiya etilgan savollar</p>
        <div class="flex flex-wrap gap-2">
            @foreach($suggestions as $i => $s)
            <button wire:click="askSuggestion({{ $i }})"
                    wire:loading.attr="disabled"
                    @disabled($keyMissing)
                    class="px-3 py-1.5 text-sm bg-white border border-slate-200 rounded-full text-slate-600
                           hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-colors
                           disabled:opacity-40 disabled:cursor-not-allowed">
                {{ $s }}
            </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Chat history ─────────────────────────────────────────────────────── --}}
    @if(count($history) > 0)
    <div class="space-y-5">
        @foreach($history as $entry)

        {{-- User question --}}
        <div class="flex justify-end">
            <div class="bg-blue-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-xl text-sm shadow-sm">
                {{ $entry['q'] }}
            </div>
        </div>

        {{-- AI answer --}}
        <div class="flex gap-3">
            <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-violet-600 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5 shadow">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0 space-y-3">
                {{-- Answer text --}}
                <div class="bg-white rounded-2xl rounded-tl-sm p-5 shadow-sm border border-slate-100">
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $entry['r']['answer'] }}</p>

                    @if(!empty($entry['r']['highlights']))
                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                        @foreach($entry['r']['highlights'] as $hl)
                        <div class="flex items-start gap-2 text-sm text-slate-600">
                            <span class="flex-shrink-0 text-base leading-snug">•</span>
                            <span>{{ $hl }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Chart area (JS-controlled, wire:ignore keeps it safe from re-renders) --}}
    <div wire:ignore>
        <div id="ai-chart-section" class="hidden bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                <span class="text-sm font-semibold text-slate-700">Grafik</span>
            </div>
            <canvas id="ai-chart-canvas"></canvas>
        </div>
    </div>

    {{-- ── Loading indicator ────────────────────────────────────────────────── --}}
    <div wire:loading.flex class="hidden items-center gap-3 bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
        <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-violet-600 rounded-xl flex items-center justify-center flex-shrink-0 animate-pulse">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
            </svg>
        </div>
        <div>
            <div class="flex items-center gap-1 mb-1">
                <span class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                <span class="w-2 h-2 bg-blue-300 rounded-full animate-bounce" style="animation-delay:300ms"></span>
            </div>
            <p class="text-xs text-slate-400">Ma'lumotlar yuklanmoqda va AI tahlil qilmoqda…</p>
        </div>
    </div>

    {{-- ── Error ────────────────────────────────────────────────────────────── --}}
    @if($error)
    @php $isQuota = str_contains($error, 'limit') || str_contains($error, 'kvota') || str_contains($error, 'billing'); @endphp
    <div class="flex items-start gap-3 rounded-xl p-4 text-sm
                {{ $isQuota ? 'bg-amber-50 border border-amber-200 text-amber-800' : 'bg-red-50 border border-red-200 text-red-700' }}">
        @if($isQuota)
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        @else
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        @endif
        <div>
            <p>{{ $error }}</p>
            @if($isQuota)
            <a href="https://aistudio.google.com/apikey" target="_blank"
               class="inline-flex items-center gap-1 mt-2 text-xs font-medium text-amber-700 hover:text-amber-900 underline underline-offset-2">
                Google AI Studio → Billing →
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Input ────────────────────────────────────────────────────────────── --}}
    <form wire:submit="ask" class="flex gap-3">
        <div class="flex-1 relative">
            <input
                type="text"
                wire:model="question"
                placeholder="{{ $keyMissing ? 'GEMINI_API_KEY sozlanmagan — kalit kiriting' : "Masalan: Bu oy eng ko'p sotilgan 5 ta mahsulot?" }}"
                class="w-full bg-white border rounded-xl px-4 py-3 pr-12 text-sm text-slate-800
                       placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-transparent shadow-sm
                       {{ $keyMissing ? 'border-amber-300 bg-amber-50 cursor-not-allowed' : 'border-slate-200 focus:ring-blue-500' }}"
                wire:loading.attr="disabled"
                @disabled($keyMissing)
                autocomplete="off"
            >
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/>
                </svg>
            </div>
        </div>
        <button
            type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl text-sm font-medium
                   transition-colors flex items-center gap-2 shadow-sm flex-shrink-0
                   disabled:opacity-50 disabled:cursor-not-allowed"
            wire:loading.attr="disabled"
            @disabled($keyMissing)
        >
            <span wire:loading.remove wire:target="ask,askSuggestion">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                </svg>
            </span>
            <span wire:loading wire:target="ask,askSuggestion" class="flex items-center gap-1">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </span>
            <span wire:loading.remove wire:target="ask,askSuggestion" class="hidden sm:block">Yuborish</span>
        </button>
    </form>

    {{-- Keyboard hint --}}
    <p class="text-center text-xs text-slate-400">Enter bosing yoki "Yuborish" tugmasini bosing</p>

</div>
