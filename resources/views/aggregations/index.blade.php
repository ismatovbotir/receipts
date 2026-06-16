@extends('layouts.app')

@section('title', 'Agregatsiyalar — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5">
    <h1 class="text-xl font-bold text-slate-800">Agregatsiyalar</h1>
    <p class="text-sm text-slate-500 mt-0.5">Oldindan hisoblangan agregat jadvallarni boshqarish va yaratish</p>
</div>

<div class="px-8 py-6 space-y-6">

    {{-- Status Cards --}}
    <div>
        <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider mb-3">Agregat jadvali holati</h2>
        <div class="grid grid-cols-3 gap-4">

            @php
                $tableLabels = [
                    'receipt_aggregates' => ['label' => 'Chek agregatlari',     'icon_color' => 'bg-blue-50',    'text_color' => 'text-blue-600'],
                    'cashier_aggregates' => ['label' => 'Kassir agregatlari',   'icon_color' => 'bg-emerald-50', 'text_color' => 'text-emerald-600'],
                    'product_aggregates' => ['label' => 'Mahsulot agregatlari', 'icon_color' => 'bg-violet-50',  'text_color' => 'text-violet-600'],
                ];
            @endphp

            @foreach($aggregations as $table => $agg)
                @php $meta = $tableLabels[$table] ?? ['label' => $table, 'icon_color' => 'bg-slate-50', 'text_color' => 'text-slate-600']; @endphp
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">

                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 {{ $meta['icon_color'] }} rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $meta['text_color'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $meta['label'] }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ $table }}</p>
                            </div>
                        </div>
                        @if(isset($agg['error']))
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Xato</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">OK</span>
                        @endif
                    </div>

                    @if(isset($agg['error']))
                        <div class="mt-2 p-3 bg-red-50 rounded-lg">
                            <p class="text-xs text-red-600 font-mono">{{ $agg['error'] }}</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-slate-400 mb-0.5">Qatorlar soni</p>
                                <p class="text-lg font-bold text-slate-800">{{ number_format($agg['row_count']) }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-slate-400 mb-0.5">So'nggi hisoblangan</p>
                                <p class="text-xs font-semibold text-slate-700">
                                    {{ $agg['latest_computed_at']
                                        ? \Carbon\Carbon::parse($agg['latest_computed_at'])->format('d.m.Y H:i')
                                        : '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 bg-slate-50 rounded-lg px-3 py-2.5">
                            <p class="text-xs text-slate-400 mb-0.5">Sana oralig'i</p>
                            <p class="text-xs font-semibold text-slate-700">
                                @if($agg['min_date'] && $agg['max_date'])
                                    {{ \Carbon\Carbon::parse($agg['min_date'])->format('d.m.Y') }}
                                    &ndash;
                                    {{ \Carbon\Carbon::parse($agg['max_date'])->format('d.m.Y') }}
                                @else
                                    Ma'lumot yo'q
                                @endif
                            </p>
                        </div>
                    @endif

                </div>
            @endforeach

        </div>
    </div>

    {{-- Generate Aggregations Panel --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5"
         x-data="{
             mode: 'range',
             dateFrom: '',
             dateTo: '',
             loading: false,
             result: null,
             error: null,

             setPreset(type) {
                 const today = new Date();
                 const fmt = d => d.toISOString().slice(0,10);
                 if (type === 'yesterday') {
                     const d = new Date(today); d.setDate(d.getDate() - 1);
                     this.dateFrom = fmt(d); this.dateTo = fmt(d);
                 } else if (type === '7d') {
                     const d = new Date(today); d.setDate(d.getDate() - 6);
                     this.dateFrom = fmt(d); this.dateTo = fmt(today);
                 } else if (type === '30d') {
                     const d = new Date(today); d.setDate(d.getDate() - 29);
                     this.dateFrom = fmt(d); this.dateTo = fmt(today);
                 } else if (type === 'month') {
                     this.dateFrom = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
                     this.dateTo = fmt(today);
                 }
             },

             async generate() {
                 this.loading = true;
                 this.result = null;
                 this.error = null;
                 showLoadingModal('Agregatsiyalar hisoblanmoqda…', 'Bu bir necha soniya olishi mumkin');
                 try {
                     const body = { mode: this.mode };
                     if (this.mode === 'range') {
                         body.from = this.dateFrom;
                         body.to   = this.dateTo;
                     }
                     const resp = await fetch('/api/aggregations', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector(\'meta[name=\"csrf-token\"]\') ? document.querySelector(\'meta[name=\"csrf-token\"]\').content : ''
                         },
                         body: JSON.stringify(body)
                     });
                     const data = await resp.json();
                     if (!resp.ok) {
                         this.error = data.message || 'So\'rov muvaffaqiyatsiz tugadi, holat: ' + resp.status;
                     } else {
                         this.result = data;
                     }
                 } catch (e) {
                     this.error = 'Tarmoq xatosi: ' + e.message;
                 } finally {
                     this.loading = false;
                     hideLoadingModal();
                 }
             }
         }">

        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Agregatsiyalarni yaratish</h2>
                <p class="text-xs text-slate-400 mt-0.5">API orqali agregat jadvallarni qayta hisoblash</p>
            </div>
        </div>

        {{-- Mode Toggle --}}
        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg w-fit mb-5">
            <button type="button"
                    @click="mode = 'range'"
                    :class="mode === 'range' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all">
                Sana oralig'i
            </button>
            <button type="button"
                    @click="mode = 'all'"
                    :class="mode === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all">
                Barcha vaqt
            </button>
        </div>

        {{-- Date pickers (range mode only) --}}
        <div x-show="mode === 'range'" x-transition class="mb-5 space-y-4">

            <div class="flex items-end gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-slate-500">Dan</label>
                    <input type="date" x-model="dateFrom"
                           class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-slate-500">Gacha</label>
                    <input type="date" x-model="dateTo"
                           class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Quick presets --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400">Tez:</span>
                <button type="button" @click="setPreset('yesterday')"
                        class="px-3 py-1.5 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                    Kecha
                </button>
                <button type="button" @click="setPreset('7d')"
                        class="px-3 py-1.5 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                    So'nggi 7 kun
                </button>
                <button type="button" @click="setPreset('30d')"
                        class="px-3 py-1.5 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                    So'nggi 30 kun
                </button>
                <button type="button" @click="setPreset('month')"
                        class="px-3 py-1.5 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                    Shu oy
                </button>
            </div>
        </div>

        {{-- Generate Button --}}
        <button type="button"
                @click="generate()"
                :disabled="loading"
                class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors">
            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span x-text="loading ? 'Hisoblanmoqda…' : 'Agregatsiyalarni yaratish'"></span>
        </button>

        {{-- Success Result --}}
        <div x-show="result !== null" x-transition class="mt-5 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-semibold text-emerald-800">Agregatsiyalar muvaffaqiyatli yaratildi</p>
            </div>
            <p x-text="result && result.message ? result.message : ''" class="text-sm text-emerald-700 mb-2"></p>
            <pre x-show="result && result.stats" x-text="result && result.stats ? JSON.stringify(result.stats, null, 2) : ''"
                 class="text-xs text-emerald-800 bg-emerald-100 rounded-lg p-3 overflow-auto mt-2 font-mono"></pre>
        </div>

        {{-- Error Result --}}
        <div x-show="error !== null" x-transition class="mt-5 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-semibold text-red-800">Xato</p>
            </div>
            <p x-text="error" class="text-sm text-red-700 font-mono"></p>
        </div>

    </div>

</div>

@endsection
