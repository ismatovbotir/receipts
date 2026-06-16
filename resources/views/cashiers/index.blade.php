@extends('layouts.app')

@section('title', 'Kassirlar — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5">
    <h1 class="text-xl font-bold text-slate-800">Kassirlar</h1>
    <p class="text-sm text-slate-500 mt-0.5">Kassirlar bo'yicha savdo va samaradorlik tahlili</p>
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
          onsubmit="showLoadingModal('Kassirlar tahlili…', 'Ma\'lumotlar hisoblanmoqda')"
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
        $totalNetRevenue = $cashier_stats->sum('net_revenue');
        $topCashier      = $cashier_stats->first();
        $maxNetRevenue   = $cashier_stats->max('net_revenue') ?: 1;
    @endphp

    {{-- Summary KPI Row --}}
    <div class="grid grid-cols-3 gap-4">

        {{-- Total kassirlar --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Jami kassirlar</p>
            </div>
            <p class="text-3xl font-bold text-slate-800">{{ $cashier_stats->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">tanlangan davr uchun</p>
        </div>

        {{-- Total net revenue --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-emerald-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Umumiy sof tushum</p>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($totalNetRevenue, 0, '.', ' ') }}</p>
            <p class="text-xs text-slate-400 mt-1">barcha kassirlar bo'yicha</p>
        </div>

        {{-- Top kassir --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Top kassir</p>
            </div>
            @if($topCashier)
                <p class="text-lg font-bold text-slate-800 truncate">{{ $topCashier->cashier }}</p>
                <p class="text-sm text-emerald-600 font-semibold mt-0.5">
                    {{ number_format($topCashier->net_revenue, 0, '.', ' ') }}
                </p>
            @else
                <p class="text-slate-400 text-sm">Ma'lumot yo'q</p>
            @endif
        </div>

    </div>

    {{-- Cashier Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Kassirlar reytingi</h2>
            <span class="text-xs text-slate-400">{{ $cashier_stats->count() }} ta kassir</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="px-4 py-3 text-left w-10">#</th>
                        <th class="px-4 py-3 text-left">Kassir</th>
                        <th class="px-4 py-3 text-center">Sotuv (soni / summa)</th>
                        <th class="px-4 py-3 text-center">Qaytarish (soni / summa)</th>
                        <th class="px-4 py-3 text-center">Bekor</th>
                        <th class="px-4 py-3 text-center">Tovarlar</th>
                        <th class="px-4 py-3 text-center">O'rtacha vaqt / chek</th>
                        <th class="px-4 py-3 text-center">Vaqt / tovar</th>
                        <th class="px-4 py-3 text-right">Sof tushum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($cashier_stats as $i => $r)
                        @php
                            $rank = $i + 1;

                            // Rank badge color
                            $rankBg = match($rank) {
                                1 => 'bg-amber-400 text-white',
                                2 => 'bg-slate-400 text-white',
                                3 => 'bg-orange-400 text-white',
                                default => 'bg-slate-200 text-slate-600',
                            };

                            // Format time helper: seconds → "Xd Ys"
                            $fmtTime = function(float $secs): string {
                                if ($secs <= 0) return '—';
                                $min = (int) floor($secs / 60);
                                $sec = (int) round($secs % 60);
                                return ($min > 0 ? "{$min}d " : '') . "{$sec}s";
                            };

                            // Progress bar width (relative to max net_revenue)
                            $barWidth = $maxNetRevenue > 0
                                ? max(2, round($r->net_revenue / $maxNetRevenue * 100))
                                : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">

                            {{-- Rank --}}
                            <td class="px-4 py-3">
                                <div class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold {{ $rankBg }}">
                                    {{ $rank }}
                                </div>
                            </td>

                            {{-- Cashier name --}}
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-800">{{ $r->cashier }}</span>
                            </td>

                            {{-- Sales count / total --}}
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 rounded-full font-medium">
                                        {{ $r->sales_count }}
                                    </span>
                                    <span class="text-slate-700 text-xs font-medium">
                                        {{ number_format($r->sales_total, 0, '.', ' ') }}
                                    </span>
                                </div>
                            </td>

                            {{-- Refund count / total --}}
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-medium">
                                        {{ $r->refund_count }}
                                    </span>
                                    <span class="text-red-600 text-xs font-medium">
                                        {{ number_format($r->refund_total, 0, '.', ' ') }}
                                    </span>
                                </div>
                            </td>

                            {{-- Cancelled --}}
                            <td class="px-4 py-3 text-center">
                                @if($r->cancelled_count > 0)
                                    <span class="bg-amber-100 text-amber-700 text-xs px-2 py-0.5 rounded-full font-medium">
                                        {{ $r->cancelled_count }}
                                    </span>
                                @else
                                    <span class="text-slate-300 text-sm">—</span>
                                @endif
                            </td>

                            {{-- Qty sold --}}
                            <td class="px-4 py-3 text-center text-slate-700 text-sm">
                                {{ number_format($r->qty_sold, 0) }}
                            </td>

                            {{-- Avg time per receipt --}}
                            <td class="px-4 py-3 text-center text-slate-600 text-sm tabular-nums">
                                {{ $fmtTime($r->avg_time_sec) }}
                            </td>

                            {{-- Avg time per item --}}
                            <td class="px-4 py-3 text-center text-slate-600 text-sm tabular-nums">
                                {{ $fmtTime($r->avg_time_per_item_sec) }}
                            </td>

                            {{-- Net revenue + progress bar --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-blue-700 font-bold text-sm tabular-nums">
                                        {{ number_format($r->net_revenue, 0, '.', ' ') }}
                                    </span>
                                    <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full transition-all"
                                             style="width: {{ $barWidth }}%"></div>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-400 text-sm">
                                Tanlangan davr uchun ma'lumot topilmadi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Chart: Cashier Rankings by Net Revenue --}}
    @if($cashier_stats->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">Kassirlar reytingi — sof tushum bo'yicha</h2>
        <div style="position: relative; height: {{ min($cashier_stats->take(15)->count() * 40 + 40, 660) }}px;">
            <canvas id="cashierChart"></canvas>
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
(function () {
    const data = @json($cashier_stats->take(15)->values());

    if (!data || data.length === 0) return;

    const labels   = data.map(r => r.cashier);
    const revenues = data.map(r => parseFloat(r.net_revenue) || 0);

    // Emerald gradient palette — darker for top ranks
    const baseColors = data.map((_, i) => {
        const alpha = Math.max(0.45, 1 - i * 0.045);
        return `rgba(16, 185, 129, ${alpha})`;   // emerald-500 family
    });
    const borderColors = data.map(() => 'rgba(5, 150, 105, 0.9)'); // emerald-600

    const ctx = document.getElementById('cashierChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sof tushum',
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
                            return ' ' + val.toLocaleString('ru-RU', { maximumFractionDigits: 0 });
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
                        font: { size: 12, weight: '500' },
                        color: '#475569',
                    }
                }
            }
        }
    });
})();
</script>
@endpush
