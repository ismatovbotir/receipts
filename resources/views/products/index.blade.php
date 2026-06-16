@extends('layouts.app')

@section('title', 'Mahsulotlar — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5">
    <h1 class="text-xl font-bold text-slate-800">Mahsulotlar tahlili</h1>
    <p class="text-sm text-slate-500 mt-0.5">Eng ko'p sotilgan mahsulotlar va katta qatorlar</p>
</div>

{{-- Filter Bar --}}
<div class="bg-white border-b border-slate-200 px-8 py-4"
     x-data="{
         setRange(type) {
             const today = new Date();
             const fmt = d => d.toISOString().slice(0,10);
             let from, to = fmt(today);
             if (type === 'today') {
                 from = fmt(today);
             } else if (type === '7d') {
                 const d = new Date(today); d.setDate(d.getDate() - 6); from = fmt(d);
             } else if (type === '30d') {
                 const d = new Date(today); d.setDate(d.getDate() - 29); from = fmt(d);
             } else if (type === 'month') {
                 from = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
             }
             document.getElementById('filter_from').value = from;
             document.getElementById('filter_to').value = to;
             document.getElementById('filter_form').requestSubmit();
         }
     }">
    <form id="filter_form" method="POST"
          onsubmit="showLoadingModal('Yuklanmoqda…', 'Mahsulotlar tahlil qilinmoqda')"
          class="flex flex-wrap items-end gap-4">
        @csrf

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-slate-500">Dan</label>
            <input id="filter_from" type="date" name="from"
                   value="{{ $from }}"
                   class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-slate-500">Gacha</label>
            <input id="filter_to" type="date" name="to"
                   value="{{ $to }}"
                   class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-slate-500">Do'kon</label>
            <select name="shop"
                    class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Barcha do'konlar</option>
                @foreach($shops_list as $s)
                    <option value="{{ $s }}" {{ $shop === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
            Qo'llash
        </button>

        <div class="flex items-end gap-2 ml-2">
            <button type="button" @click="setRange('today')"
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                Bugun
            </button>
            <button type="button" @click="setRange('7d')"
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                7 kun
            </button>
            <button type="button" @click="setRange('30d')"
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                30 kun
            </button>
            <button type="button" @click="setRange('month')"
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">
                Shu oy
            </button>
        </div>
    </form>
</div>

{{-- Main Content --}}
<div class="px-8 py-6 space-y-6">

    @php
        $maxRevenue = $top_products->max('revenue') ?: 1;
        $topByQty   = $top_products->sortByDesc('qty_sold')->first();
    @endphp

    {{-- Summary KPI Row --}}
    <div class="grid grid-cols-3 gap-4">

        {{-- Jami mahsulot turlari --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Jami mahsulot turlari</p>
            </div>
            <p class="text-3xl font-bold text-slate-800">{{ $top_products->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">tanlangan davr uchun</p>
        </div>

        {{-- Eng ko'p sotilgan --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Eng ko'p sotilgan</p>
            </div>
            <p class="text-base font-bold text-slate-800 truncate" title="{{ $topByQty->name ?? '' }}">
                {{ $topByQty->name ?? '&mdash;' }}
            </p>
            @if($topByQty)
                <p class="text-xs text-green-600 font-semibold mt-0.5">
                    {{ number_format($topByQty->qty_sold, 0) }} dona
                </p>
            @endif
        </div>

        {{-- Jami tushum --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Jami tushum</p>
            </div>
            <p class="text-2xl font-bold text-slate-800">
                {{ number_format($total_product_revenue, 0, '.', ' ') }}
            </p>
            <p class="text-xs text-slate-400 mt-1">UZS</p>
        </div>

    </div>

    {{-- Chart row --}}
    <div class="grid grid-cols-3 gap-4">

        {{-- Left: horizontal bar chart of top 10 products by revenue --}}
        <div class="col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Top 10 mahsulot</h2>
            <div style="position: relative; height: 320px;">
                <canvas id="productsChart"></canvas>
            </div>
        </div>

        {{-- Right: top 5 by qty_sold as a ranked list --}}
        <div class="col-span-1 bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Top 5 — miqdor bo'yicha</h2>
            <div class="space-y-3">
                @foreach($top_products->sortByDesc('qty_sold')->take(5) as $qi => $qp)
                    @php
                        $qRankColor = match($qi + 1) {
                            1 => 'bg-amber-400 text-white',
                            2 => 'bg-slate-400 text-white',
                            3 => 'bg-orange-400 text-white',
                            default => 'bg-slate-200 text-slate-600',
                        };
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold flex-shrink-0 {{ $qRankColor }}">
                            {{ $qi + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-700 truncate" title="{{ $qp->name }}">
                                {{ $qp->name }}
                            </p>
                        </div>
                        <span class="flex-shrink-0 bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            {{ number_format($qp->qty_sold, 0) }}
                        </span>
                    </div>
                @endforeach

                @if($top_products->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-6">Ma'lumot yo'q</p>
                @endif
            </div>
        </div>

    </div>

    {{-- Top products table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Top mahsulotlar reytingi</h2>
            <span class="text-xs text-slate-400">{{ $top_products->count() }} ta mahsulot</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="px-4 py-3 text-left w-10">#</th>
                        <th class="px-4 py-3 text-left">Mahsulot nomi</th>
                        <th class="px-4 py-3 text-left">Kod</th>
                        <th class="px-4 py-3 text-center">Sotilgan (dona)</th>
                        <th class="px-4 py-3 text-center">O'rtacha narx</th>
                        <th class="px-4 py-3 text-center">Cheklar soni</th>
                        <th class="px-4 py-3 text-right">Tushum (UZS)</th>
                        <th class="px-4 py-3 text-right">Ulush (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($top_products as $i => $p)
                        @php
                            $rank = $i + 1;
                            $rankBg = match($rank) {
                                1 => 'bg-amber-400 text-white',
                                2 => 'bg-slate-400 text-white',
                                3 => 'bg-orange-400 text-white',
                                default => 'bg-slate-200 text-slate-600',
                            };
                            $barPct = $maxRevenue > 0 ? max(2, round($p->revenue / $maxRevenue * 100)) : 0;
                            $share  = $total_product_revenue > 0 ? ($p->revenue / $total_product_revenue * 100) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">

                            {{-- Rank --}}
                            <td class="px-4 py-3">
                                <div class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold {{ $rankBg }}">
                                    {{ $rank }}
                                </div>
                            </td>

                            {{-- Product name --}}
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-800">{{ $p->name }}</span>
                            </td>

                            {{-- Code --}}
                            <td class="px-4 py-3">
                                <span class="text-slate-400 font-mono text-xs">{{ $p->code }}</span>
                            </td>

                            {{-- Qty sold --}}
                            <td class="px-4 py-3 text-center">
                                <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                    {{ number_format($p->qty_sold, 0) }}
                                </span>
                            </td>

                            {{-- Avg price --}}
                            <td class="px-4 py-3 text-center text-slate-600 tabular-nums">
                                {{ number_format($p->avg_price, 0, '.', ' ') }}
                            </td>

                            {{-- Receipt count --}}
                            <td class="px-4 py-3 text-center text-slate-600">
                                {{ number_format($p->receipt_count, 0) }}
                            </td>

                            {{-- Revenue + progress bar --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-blue-700 font-bold text-sm tabular-nums">
                                        {{ number_format($p->revenue, 0, '.', ' ') }}
                                    </span>
                                    <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full transition-all"
                                             style="width: {{ $barPct }}%"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Share % --}}
                            <td class="px-4 py-3 text-right text-slate-500 tabular-nums text-xs">
                                {{ number_format($share, 1) }}%
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400 text-sm">
                                Tanlangan davr uchun ma'lumot topilmadi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Big items table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Eng katta qatorlar</h2>
            <span class="text-xs text-slate-400">{{ $big_items->count() }} ta qator</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="px-4 py-3 text-left w-10">#</th>
                        <th class="px-4 py-3 text-left">Mahsulot</th>
                        <th class="px-4 py-3 text-left">Do'kon / Kassir</th>
                        <th class="px-4 py-3 text-left">Chek</th>
                        <th class="px-4 py-3 text-center">Miqdor x Narx</th>
                        <th class="px-4 py-3 text-right">Qator summasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($big_items as $bi => $b)
                        @php
                            $biRank = $bi + 1;
                            $biRankBg = match($biRank) {
                                1 => 'bg-amber-400 text-white',
                                2 => 'bg-slate-400 text-white',
                                3 => 'bg-orange-400 text-white',
                                default => 'bg-slate-200 text-slate-600',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">

                            {{-- Rank --}}
                            <td class="px-4 py-3">
                                <div class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold {{ $biRankBg }}">
                                    {{ $biRank }}
                                </div>
                            </td>

                            {{-- Product name + code --}}
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $b->name }}</p>
                                <p class="text-slate-400 font-mono text-xs mt-0.5">{{ $b->code }}</p>
                            </td>

                            {{-- Shop / Cashier --}}
                            <td class="px-4 py-3">
                                <p class="text-slate-700 text-sm">{{ $b->shop }}</p>
                                <p class="text-slate-400 text-xs mt-0.5">{{ $b->cashier }}</p>
                            </td>

                            {{-- Receipt number + date --}}
                            <td class="px-4 py-3">
                                <p class="text-slate-700 text-sm font-mono">{{ $b->number ?? '—' }}</p>
                                <p class="text-slate-400 text-xs mt-0.5">
                                    {{ $b->date_close ? \Illuminate\Support\Carbon::parse($b->date_close)->format('d.m.Y H:i') : '—' }}
                                </p>
                            </td>

                            {{-- Qty x Price --}}
                            <td class="px-4 py-3 text-center text-slate-600 tabular-nums text-sm">
                                {{ number_format($b->qty, 2, '.', '') }}
                                &times;
                                {{ number_format($b->price, 0, '.', ' ') }}
                            </td>

                            {{-- Line total --}}
                            <td class="px-4 py-3 text-right">
                                <span class="text-red-600 font-bold text-sm tabular-nums">
                                    {{ number_format($b->line_total, 0, '.', ' ') }}
                                </span>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
                                Tanlangan davr uchun ma'lumot topilmadi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    const data = @json($chart_products->values());

    if (!data || data.length === 0) return;

    const labels   = data.map(r => r.name);
    const revenues = data.map(r => parseFloat(r.revenue) || 0);

    // Indigo gradient palette — darker for top ranks
    const baseColors = data.map((_, i) => {
        const alpha = Math.max(0.45, 1 - i * 0.05);
        return `rgba(99, 102, 241, ${alpha})`;
    });
    const borderColors = data.map(() => 'rgba(67, 56, 202, 0.9)');

    const ctx = document.getElementById('productsChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tushum (UZS)',
                data: revenues,
                backgroundColor: baseColors,
                borderColor: borderColors,
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const val = context.parsed.x || 0;
                            return ' ' + val.toLocaleString('ru-RU', { maximumFractionDigits: 0 }) + ' UZS';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.15)',
                    },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                        callback: function (value) {
                            if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';
                            if (value >= 1_000)     return (value / 1_000).toFixed(0) + 'K';
                            return value;
                        }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: '500' },
                        color: '#475569',
                    }
                }
            }
        }
    });
})();
</script>
@endpush
