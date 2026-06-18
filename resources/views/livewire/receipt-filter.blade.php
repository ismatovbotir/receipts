<div x-data="receiptModal()" @open-receipt.window="show($event.detail.id)">

    {{-- Filter Bar --}}
    <div class="bg-white border-b border-slate-200 px-8 py-4">
        <div class="flex flex-wrap items-end gap-3">


            {{-- Search --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500">Qidirish</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text"
                           wire:model.live.debounce.400ms="search"
                           placeholder="Raqam, kassir, do'kon…"
                           class="pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg w-52 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            {{-- Date From --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500">Dan</label>
                <input type="date"
                       wire:model.live="dateFrom"
                       class="px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Date To --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500">Gacha</label>
                <input type="date"
                       wire:model.live="dateTo"
                       class="px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Shop --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500">Do'kon</label>
                <select wire:model.live="shop"
                        class="px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Barcha do'konlar</option>
                    @foreach($shops as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Type --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500">Tur</label>
                <select wire:model.live="type"
                        class="px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Barcha turlar</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-slate-500">Holat</label>
                <select wire:model.live="status"
                        class="px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Barcha holatlar</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}">{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Clear --}}
            @if($search || $dateFrom || $dateTo || $shop || $type || $status)
                <button wire:click="clearFilters"
                        class="px-4 py-2 text-sm font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors self-end">
                    Tozalash
                </button>
            @endif

            {{-- Analytics toggle --}}
            <button wire:click="toggleAnalytics"
                    class="ml-auto flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg self-end transition-colors
                           {{ $showAnalytics ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                Tahlil
            </button>

        </div>
    </div>

    {{-- ── Analytics panel ──────────────────────────────────────────────────── --}}
    @if($showAnalytics && $analytics)
    @php
        $fmtTime = function(int|float|null $sec): string {
            $sec = (int) ($sec ?? 0);
            if ($sec <= 0) return '—';
            if ($sec < 60) return $sec . ' son';
            $m = intdiv($sec, 60);
            $s = $sec % 60;
            if ($m < 60) return $m . ' daq' . ($s > 0 ? ' ' . $s . ' son' : '');
            $h = intdiv($m, 60);
            $m2 = $m % 60;
            return $h . ' soat' . ($m2 > 0 ? ' ' . $m2 . ' daq' : '');
        };
        $timeColor = function(int|float|null $sec): string {
            $sec = (int) ($sec ?? 0);
            if ($sec <= 0)   return 'text-slate-400';
            if ($sec <= 120) return 'text-emerald-600';
            if ($sec <= 300) return 'text-amber-600';
            return 'text-red-600';
        };
    @endphp

    <div class="border-b border-slate-200 bg-slate-50 px-8 py-5"
         x-data="{ tab: 'shops' }">

        {{-- KPI summary cards --}}
        <div class="grid grid-cols-3 gap-4 mb-5">

            {{-- Biggest receipt --}}
            <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide mb-2">Eng katta chek</p>
                @if($analytics['biggest'])
                <p class="text-2xl font-bold text-slate-800">
                    {{ number_format($analytics['biggest']->total, 0, '.', ' ') }}
                    <span class="text-sm font-normal text-slate-400">UZS</span>
                </p>
                <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                    <span class="truncate">{{ $analytics['biggest']->cashier }}</span>
                    <span class="text-slate-300">·</span>
                    <span class="truncate">{{ $analytics['biggest']->shop }}</span>
                    <button @click="$dispatch('open-receipt', {id:'{{ $analytics['biggest']->id }}'})"
                            class="ml-auto flex-shrink-0 text-blue-500 hover:text-blue-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                    </button>
                </div>
                @else <p class="text-slate-400 text-sm">—</p> @endif
            </div>

            {{-- Smallest receipt --}}
            <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide mb-2">Eng kichik chek</p>
                @if($analytics['smallest'])
                <p class="text-2xl font-bold text-slate-800">
                    {{ number_format($analytics['smallest']->total, 0, '.', ' ') }}
                    <span class="text-sm font-normal text-slate-400">UZS</span>
                </p>
                <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                    <span class="truncate">{{ $analytics['smallest']->cashier }}</span>
                    <span class="text-slate-300">·</span>
                    <span class="truncate">{{ $analytics['smallest']->shop }}</span>
                    <button @click="$dispatch('open-receipt', {id:'{{ $analytics['smallest']->id }}'})"
                            class="ml-auto flex-shrink-0 text-blue-500 hover:text-blue-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                    </button>
                </div>
                @else <p class="text-slate-400 text-sm">—</p> @endif
            </div>

            {{-- Avg service time --}}
            <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide mb-2">O'rtacha xizmat vaqti</p>
                <p class="text-2xl font-bold {{ $timeColor($analytics['avgSec']) }}">
                    {{ $fmtTime($analytics['avgSec']) }}
                </p>
                <p class="mt-2 text-xs text-slate-400">chek ochilishidan yopilishigacha</p>
            </div>

        </div>

        {{-- Tab buttons + Excel export --}}
        <div class="flex items-center gap-2 mb-4">
            <button @click="tab = 'shops'"
                    :class="tab === 'shops'
                        ? 'bg-white shadow-sm border border-slate-200 text-slate-800 font-semibold'
                        : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-1.5 text-sm rounded-lg transition-colors">
                Do'konlar
            </button>
            <button @click="tab = 'cashiers'"
                    :class="tab === 'cashiers'
                        ? 'bg-white shadow-sm border border-slate-200 text-slate-800 font-semibold'
                        : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-1.5 text-sm rounded-lg transition-colors">
                Kassirlar
            </button>

            {{-- Excel download --}}
            <button class="ml-auto flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg
                           bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-sm"
                    @click="
                        const p = new URLSearchParams({
                            dateFrom: $wire.dateFrom,
                            dateTo:   $wire.dateTo,
                            shop:     $wire.shop,
                        });
                        window.open('{{ route('receipts.analytics.export') }}?' + p.toString(), '_blank');
                    ">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Excel
            </button>
        </div>

        {{-- Shops table --}}
        <div x-show="tab === 'shops'" class="bg-white rounded-xl border border-slate-100 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="text-left px-4 py-3">Do'kon</th>
                            <th class="text-right px-4 py-3">Cheklar</th>
                            <th class="text-right px-4 py-3">Jami (UZS)</th>
                            <th class="text-right px-4 py-3">Eng katta</th>
                            <th class="text-right px-4 py-3">Eng kichik</th>
                            <th class="text-right px-4 py-3">O'rtacha</th>
                            <th class="text-right px-4 py-3">O'rt. vaqt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($analytics['shopStats'] as $row)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $row->shop }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($row->cnt) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-700">
                                {{ number_format($row->sum_total, 0, '.', ' ') }}
                            </td>
                            @if(isset($analytics['biggestPerShop'][$row->shop]))
                            <td @click="$dispatch('open-receipt', {id:'{{ $analytics['biggestPerShop'][$row->shop]->id }}'})"
                                class="px-4 py-3 text-right font-semibold text-emerald-600 cursor-pointer hover:bg-emerald-50 transition-colors">
                                {{ number_format($row->max_total, 0, '.', ' ') }}
                            </td>
                            @else
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600">
                                {{ number_format($row->max_total, 0, '.', ' ') }}
                            </td>
                            @endif
                            @if(isset($analytics['smallestPerShop'][$row->shop]))
                            <td @click="$dispatch('open-receipt', {id:'{{ $analytics['smallestPerShop'][$row->shop]->id }}'})"
                                class="px-4 py-3 text-right font-semibold text-red-500 cursor-pointer hover:bg-red-50 transition-colors">
                                {{ number_format($row->min_total, 0, '.', ' ') }}
                            </td>
                            @else
                            <td class="px-4 py-3 text-right font-semibold text-red-500">
                                {{ number_format($row->min_total, 0, '.', ' ') }}
                            </td>
                            @endif
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ number_format($row->avg_total, 0, '.', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ $timeColor($row->avg_sec) }}">
                                {{ $fmtTime($row->avg_sec) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400 text-sm">Ma'lumot topilmadi</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cashiers table --}}
        <div x-show="tab === 'cashiers'" class="bg-white rounded-xl border border-slate-100 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="text-left px-4 py-3">Kassir</th>
                            <th class="text-left px-4 py-3">Do'kon</th>
                            <th class="text-right px-4 py-3">Cheklar</th>
                            <th class="text-right px-4 py-3">Jami (UZS)</th>
                            <th class="text-right px-4 py-3">Eng katta</th>
                            <th class="text-right px-4 py-3">Eng kichik</th>
                            <th class="text-right px-4 py-3">O'rtacha</th>
                            <th class="text-right px-4 py-3">O'rt. vaqt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($analytics['cashierStats'] as $row)
                        @php $ck = $row->cashier . '|' . $row->shop; @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $row->cashier }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $row->shop }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($row->cnt) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-700">
                                {{ number_format($row->sum_total, 0, '.', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600">
                                @if(isset($analytics['biggestPerCashier'][$ck]))
                                    <button @click="$dispatch('open-receipt', {id:'{{ $analytics['biggestPerCashier'][$ck]->id }}'})"
                                            class="font-semibold text-emerald-600 hover:underline cursor-pointer">
                                        {{ number_format($row->max_total, 0, '.', ' ') }}
                                    </button>
                                @else
                                    {{ number_format($row->max_total, 0, '.', ' ') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-red-500">
                                @if(isset($analytics['smallestPerCashier'][$ck]))
                                    <button @click="$dispatch('open-receipt', {id:'{{ $analytics['smallestPerCashier'][$ck]->id }}'})"
                                            class="font-semibold text-red-500 hover:underline cursor-pointer">
                                        {{ number_format($row->min_total, 0, '.', ' ') }}
                                    </button>
                                @else
                                    {{ number_format($row->min_total, 0, '.', ' ') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ number_format($row->avg_total, 0, '.', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ $timeColor($row->avg_sec) }}">
                                {{ $fmtTime($row->avg_sec) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">Ma'lumot topilmadi</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    @endif

    {{-- Loading indicator --}}
    <div wire:loading class="bg-blue-50 border-b border-blue-100 px-8 py-2 flex items-center gap-2">
        <svg class="animate-spin w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <span class="text-xs text-blue-600 font-medium">Filtrlanmoqda…</span>
    </div>

    {{-- Table --}}
    <div class="px-8 py-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">

            @if($receipts->isNotEmpty())

                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        <span class="font-semibold text-slate-700">{{ $receipts->firstItem() }}</span>–<span class="font-semibold text-slate-700">{{ $receipts->lastItem() }}</span> ko'rsatilmoqda,
                        jami <span class="font-semibold text-slate-700">{{ number_format($receipts->total()) }}</span> ta chek
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                                <th class="text-left px-5 py-3">#</th>
                                <th class="text-left px-5 py-3">Chek raqami</th>
                                <th class="text-left px-5 py-3">Sana / Vaqt</th>
                                <th class="text-left px-5 py-3">Do'kon</th>
                                <th class="text-left px-5 py-3">Kassir</th>
                                <th class="text-left px-5 py-3">POS</th>
                                <th class="text-left px-5 py-3">Tur</th>
                                <th class="text-left px-5 py-3">Holat</th>
                                <th class="text-right px-5 py-3">Jami (UZS)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($receipts as $i => $receipt)
                                @php $closed = in_array($receipt->status, ['Закрыт','closed','Closed']); @endphp
                                <tr class="hover:bg-blue-50/40 transition-colors cursor-pointer"
                                    @click="$dispatch('open-receipt', {id:'{{ $receipt->id }}'})">
                                    <td class="px-5 py-3 text-slate-400 text-xs">{{ $receipts->firstItem() + $i }}</td>
                                    <td class="px-5 py-3 font-medium text-blue-600">
                                        {{ $receipt->number }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                        {{ $receipt->date_close ? \Carbon\Carbon::parse($receipt->date_close)->format('d.m.Y H:i') : '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-700">{{ $receipt->shop ?? '—' }}</td>
                                    <td class="px-5 py-3 text-slate-700">{{ $receipt->cashier ?? '—' }}</td>
                                    <td class="px-5 py-3 text-slate-500 text-xs font-mono">{{ $receipt->pos ?? '—' }}</td>
                                    <td class="px-5 py-3 text-slate-600 text-xs">{{ $receipt->type ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $closed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $closed ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                            {{ $receipt->status ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-800">
                                        {{ number_format($receipt->total, 0, '.', ' ') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $receipts->links() }}
                </div>

            @else

                <div class="flex flex-col items-center justify-center py-20 px-8">
                    <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-slate-700 font-semibold">Cheklar topilmadi</p>
                    <p class="text-slate-400 text-sm mt-1">Filtrlarni o'zgartirip ko'ring</p>
                    @if($search || $dateFrom || $dateTo || $shop || $type || $status)
                        <button wire:click="clearFilters"
                                class="mt-4 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            Filtrlarni tozalash
                        </button>
                    @endif
                </div>

            @endif

        </div>
    </div>

    {{-- ── Receipt detail modal ──────────────────────────────────────────── --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="close()"
         @click.self="close()"
         class="fixed inset-0 z-[700] flex items-start justify-center overflow-y-auto p-4 py-10"
         style="background:rgba(15,23,42,.75);backdrop-filter:blur(3px)">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl my-auto flex flex-col"
             style="max-height:90vh"
             @click.stop>

            {{-- Fixed header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div x-show="loading" class="h-5 w-32 bg-slate-200 animate-pulse rounded"></div>
                    <template x-if="!loading && data && !data.error">
                        <div class="flex items-center gap-3">
                            <h2 class="text-base font-bold text-slate-800" x-text="'Chek #' + data.number"></h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                  :class="data.status === 'Закрыт' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                  x-text="data.status || ''"></span>
                        </div>
                    </template>
                </div>
                <div class="flex items-center gap-1.5">
                    <template x-if="data && !data.error">
                        <a :href="'/receipts/' + data.id" target="_blank" rel="noopener"
                           class="flex items-center gap-1.5 text-xs text-slate-500 hover:text-blue-600 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                            Sahifada ko'rish
                        </a>
                    </template>
                    <button @click="close()"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Scrollable body --}}
            <div class="overflow-y-auto flex-1 p-6 space-y-5">

                <div x-show="loading" class="flex justify-center py-16">
                    <div class="w-12 h-12 border-4 border-slate-100 border-t-blue-600 rounded-full animate-spin"></div>
                </div>

                <template x-if="!loading && data && data.error">
                    <p class="text-center py-12 text-sm text-red-500" x-text="'Xatolik: ' + data.error"></p>
                </template>

                <template x-if="!loading && data && !data.error">
                    <div class="space-y-5">

                        {{-- Meta info --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">Ochilgan</p>
                                <p class="text-sm font-medium text-slate-700" x-text="dt(data.date_open)"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">Yopilgan</p>
                                <p class="text-sm font-medium text-slate-700" x-text="dt(data.date_close)"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">Tur</p>
                                <p class="text-sm font-medium text-slate-700" x-text="data.type || '—'"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">Do'kon</p>
                                <p class="text-sm font-medium text-slate-700" x-text="data.shop || '—'"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">Kassir</p>
                                <p class="text-sm font-medium text-slate-700" x-text="data.cashier || '—'"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">POS</p>
                                <p class="text-sm font-medium text-slate-700 font-mono" x-text="data.pos || '—'"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">Smena</p>
                                <p class="text-sm font-medium text-slate-700" x-text="data.shift || '—'"></p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-400 mb-0.5">Karta</p>
                                <p class="text-sm font-medium text-slate-700" x-text="data.card || '—'"></p>
                            </div>
                            <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                                <p class="text-xs text-blue-400 mb-0.5">Jami</p>
                                <p class="text-sm font-bold text-blue-700" x-text="n(data.total) + ' UZS'"></p>
                            </div>
                        </div>

                        {{-- Items table --}}
                        <div class="bg-white rounded-xl border border-slate-100 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-50 flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-slate-700">Mahsulotlar</h3>
                                <span class="text-xs text-slate-400" x-text="data.items ? '(' + data.items.length + ' qator)' : ''"></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                                            <th class="text-left px-4 py-2.5">№</th>
                                            <th class="text-left px-4 py-2.5">Kod</th>
                                            <th class="text-left px-4 py-2.5">Nomi</th>
                                            <th class="text-right px-4 py-2.5">Narx</th>
                                            <th class="text-right px-4 py-2.5">Miqdor</th>
                                            <th class="text-right px-4 py-2.5">Chegirma</th>
                                            <th class="text-right px-4 py-2.5">Jami</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="item in (data.items || [])" :key="item.id">
                                            <tr class="border-t border-slate-50 hover:bg-slate-50">
                                                <td class="px-4 py-2.5 text-slate-400 text-xs" x-text="item.no"></td>
                                                <td class="px-4 py-2.5 font-mono text-xs text-slate-500" x-text="item.code || '—'"></td>
                                                <td class="px-4 py-2.5 font-medium text-slate-800" x-text="item.name"></td>
                                                <td class="px-4 py-2.5 text-right text-slate-600" x-text="n(item.price)"></td>
                                                <td class="px-4 py-2.5 text-right text-slate-600" x-text="item.qty"></td>
                                                <td class="px-4 py-2.5 text-right text-slate-500"
                                                    x-text="item.discountTotal > 0 ? n(item.discountTotal) : '—'"></td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800" x-text="n(item.total)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="border-t-2 border-slate-100">
                                        <tr class="bg-slate-50">
                                            <td colspan="6" class="px-4 py-2.5 text-right text-sm font-semibold text-slate-600">Jami</td>
                                            <td class="px-4 py-2.5 text-right font-bold text-slate-800" x-text="n(data.total)"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Payments table --}}
                        <div class="bg-white rounded-xl border border-slate-100 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-50">
                                <h3 class="text-sm font-semibold text-slate-700">To'lovlar</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                                            <th class="text-left px-4 py-2.5">To'lov turi</th>
                                            <th class="text-right px-4 py-2.5">Summa (UZS)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="pay in (data.payments || [])" :key="pay.id">
                                            <tr class="border-t border-slate-50 hover:bg-slate-50">
                                                <td class="px-4 py-2.5 font-medium text-slate-700" x-text="pay.type"></td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800" x-text="n(pay.total)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="border-t-2 border-slate-100">
                                        <tr class="bg-slate-50">
                                            <td class="px-4 py-2.5 text-right text-sm font-semibold text-slate-600">Jami</td>
                                            <td class="px-4 py-2.5 text-right font-bold text-slate-800" x-text="n(data.total)"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Discounts (only if present) --}}
                        <template x-if="data.discounts && data.discounts.length > 0">
                            <div class="bg-white rounded-xl border border-slate-100 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-50">
                                    <h3 class="text-sm font-semibold text-slate-700">Chegirmalar</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                                                <th class="text-left px-4 py-2.5">№</th>
                                                <th class="text-left px-4 py-2.5">Nomi / turi</th>
                                                <th class="text-right px-4 py-2.5">Chegirma (UZS)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="disc in data.discounts" :key="disc.id">
                                                <tr class="border-t border-slate-50 hover:bg-slate-50">
                                                    <td class="px-4 py-2.5 text-slate-400 text-xs" x-text="disc.no"></td>
                                                    <td class="px-4 py-2.5 text-slate-700">
                                                        <span x-text="disc.type || disc.name || '—'"></span>
                                                        <template x-if="disc.scope === 'receipt'">
                                                            <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-medium bg-blue-50 text-blue-600 rounded-full">Chek bo'yicha</span>
                                                        </template>
                                                    </td>
                                                    <td class="px-4 py-2.5 text-right font-semibold text-slate-800" x-text="n(disc.total)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>

            </div>{{-- /scrollable body --}}
        </div>{{-- /card --}}
    </div>{{-- /modal overlay --}}

</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', function () {
    if (window.__receiptModalRegistered) return;
    window.__receiptModalRegistered = true;

    Alpine.data('receiptModal', function () {
        return {
            open: false,
            loading: false,
            data: null,

            async show(id) {
                this.open = true;
                this.loading = true;
                this.data = null;
                try {
                    var res = await fetch('/receipts/' + id + '/json', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    this.data = await res.json();
                } catch (e) {
                    this.data = { error: e.message };
                }
                this.loading = false;
            },

            close() {
                this.open = false;
                var self = this;
                setTimeout(function () { self.data = null; }, 200);
            },

            n(v) {
                return parseInt(v || 0).toLocaleString('ru-RU');
            },

            dt(v) {
                if (!v) return '—';
                try {
                    return new Date(v.replace(' ', 'T')).toLocaleString('ru-RU', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit', second: '2-digit'
                    });
                } catch (_) { return v; }
            }
        };
    });
});
</script>
@endpush
