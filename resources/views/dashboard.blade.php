@extends('layouts.app')

@section('title', 'Bosh sahifa — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5">
    <h1 class="text-xl font-bold text-slate-800">Bosh sahifa</h1>
    <p class="text-sm text-slate-500 mt-0.5">Savdo tahlili xulosasi</p>
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
             document.getElementById('filter_form').submit();
         }
     }">
    <form id="filter_form" method="GET" action="{{ route('dashboard') }}"
          class="flex flex-wrap items-end gap-4">

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

    {{-- Anomaly Alert --}}
    @if($unannotated_anomalies->count() > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-4">
            <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-amber-800">
                    {{ $unannotated_anomalies->count() }} ta noodatiy kun izohlarga ega emas
                </h3>
                <p class="text-xs text-amber-700 mt-0.5 mb-3">
                    Bu kunlardagi savdo o'rtachadan sezilarli farq qildi. Nima bo'lganini tushuntirish uchun izoh qo'shing.
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($unannotated_anomalies as $a)
                        @php $calY = substr($a->date, 0, 4); $calM = ltrim(substr($a->date, 5, 2), '0'); @endphp
                        <a href="{{ route('calendar', ['year' => $calY, 'month' => $calM]) }}#{{ $a->date }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                               {{ $a->direction === 'high' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-blue-100 text-blue-700 border border-blue-200' }}">
                            {{ $a->direction === 'high' ? '↑' : '↓' }}
                            {{ \Carbon\Carbon::parse($a->date)->format('d M') }}
                            <span class="opacity-70">— {{ $a->direction === 'high' ? 'noodatiy yuqori' : 'noodatiy past' }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-5 gap-4">

        {{-- Total Revenue --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jami tushum</span>
                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($total_revenue, 0, '.', ' ') }}</p>
            <p class="text-xs text-slate-400 mt-1">UZS</p>
        </div>

        {{-- Transactions --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tranzaksiyalar</span>
                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($transaction_count, 0, '.', ' ') }}</p>
            <p class="text-xs text-slate-400 mt-1">chek</p>
        </div>

        {{-- Avg Transaction Value --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">O'rtacha chek</span>
                <div class="w-8 h-8 bg-violet-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($avg_transaction_value, 0, '.', ' ') }}</p>
            <p class="text-xs text-slate-400 mt-1">UZS / chek</p>
        </div>

        {{-- Items Sold --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sotilgan tovarlar</span>
                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($items_sold, 0, '.', ' ') }}</p>
            <p class="text-xs text-slate-400 mt-1">dona</p>
        </div>

        {{-- Avg Basket Size --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">O'rtacha savat</span>
                <div class="w-8 h-8 bg-rose-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($avg_basket_size, 1, '.', ' ') }}</p>
            <p class="text-xs text-slate-400 mt-1">dona / chek</p>
        </div>

    </div>

    {{-- Chart Row 1 --}}
    <div class="grid grid-cols-3 gap-4">

        {{-- Sales Over Time --}}
        <div class="col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Davr bo'yicha savdo</h2>
            <div class="relative h-64">
                <canvas id="salesOverTimeChart"></canvas>
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="col-span-1 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">To'lov usullari</h2>
            <div class="relative h-64">
                <canvas id="paymentBreakdownChart"></canvas>
            </div>
        </div>

    </div>

    {{-- Chart Row 2 --}}
    <div class="grid grid-cols-2 gap-4">

        {{-- Top Shops --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Eng yaxshi do'konlar</h2>
            <div class="relative h-56">
                <canvas id="topShopsChart"></canvas>
            </div>
        </div>

        {{-- Top Cashiers --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Eng yaxshi kassirlar</h2>
            <div class="relative h-56">
                <canvas id="topCashiersChart"></canvas>
            </div>
        </div>

    </div>

    {{-- Top Products Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">Eng ko'p sotilgan mahsulotlar</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="text-left px-4 py-3 rounded-l-lg">#</th>
                        <th class="text-left px-4 py-3">Mahsulot nomi</th>
                        <th class="text-right px-4 py-3">Sotilgan miqdor</th>
                        <th class="text-right px-4 py-3 rounded-r-lg">Tushum (UZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($top_products as $i => $product)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-400 font-medium">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-slate-800 font-medium">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($product->qty_sold, 0, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right text-slate-800 font-semibold">{{ number_format($product->revenue, 0, '.', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">Tanlangan davr uchun ma'lumot yo'q</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Hourly Sales Chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-700">Soat bo'yicha savdo</h2>
            @if($peak_hour && $peak_hour->transactions > 0)
                <span class="text-xs text-slate-500">
                    Eng yuqori: <span class="font-semibold text-blue-600">{{ sprintf('%02d:00', $peak_hour->hour) }}–{{ sprintf('%02d:00', $peak_hour->hour + 1) }}</span>
                    ({{ number_format($peak_hour->transactions, 0) }} tranzaksiya)
                </span>
            @endif
        </div>
        <div class="relative h-64">
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>

    {{-- Big Receipts + Big Items row --}}
    <div class="grid grid-cols-2 gap-4">

        {{-- Big Receipts (top by total) --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Eng katta cheklar</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="text-left px-3 py-2.5 rounded-l-lg">#</th>
                            <th class="text-left px-3 py-2.5">Chek</th>
                            <th class="text-left px-3 py-2.5">Do'kon / Kassir</th>
                            <th class="text-right px-3 py-2.5 rounded-r-lg">Jami (UZS)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($big_receipts as $i => $r)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2.5 text-slate-400 text-xs">{{ $i + 1 }}</td>
                                <td class="px-3 py-2.5">
                                    <a href="{{ route('receipts.show', $r->id) }}"
                                       class="text-blue-600 hover:underline font-medium">#{{ $r->number }}</a>
                                    <div class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($r->date_close)->format('d.m.Y H:i') }}</div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="text-slate-700 text-xs">{{ $r->shop }}</div>
                                    <div class="text-slate-400 text-xs">{{ $r->cashier }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-800">
                                    {{ number_format($r->total, 0, '.', ' ') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-400">Ma'lumot yo'q</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Big Items (largest single line items) --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Eng katta qatorlar</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="text-left px-3 py-2.5 rounded-l-lg">#</th>
                            <th class="text-left px-3 py-2.5">Mahsulot</th>
                            <th class="text-right px-3 py-2.5">Miqdor × Narx</th>
                            <th class="text-right px-3 py-2.5 rounded-r-lg">Qator summasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($big_items as $i => $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2.5 text-slate-400 text-xs">{{ $i + 1 }}</td>
                                <td class="px-3 py-2.5">
                                    <div class="text-slate-800 font-medium text-xs leading-tight">{{ Str::limit($item->name, 35) }}</div>
                                    <div class="text-slate-400 text-xs">Chek #{{ $item->number }} · {{ $item->shop }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-right text-slate-500 text-xs">
                                    {{ number_format($item->qty, 0) }} × {{ number_format($item->price, 0, '.', ' ') }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-800">
                                    {{ number_format($item->line_total, 0, '.', ' ') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-400">Ma'lumot yo'q</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    const salesData       = @json($sales_over_time);
    const topShopsData    = @json($top_shops);
    const topCashiersData = @json($top_cashiers);
    const paymentData     = @json($payment_breakdown);
    const hourlyData      = @json($sales_by_hour);
    const dayNotesMap     = @json($day_notes->map(fn($n) => ['type' => $n->type, 'title' => $n->title]));
    const anomalyMap      = @json($anomalies->keyBy('date')->map(fn($a) => $a->direction));

    // --- Sales Over Time (dual axis: bar revenue + line transactions) ---
    const sotLabels   = salesData.map(r => r.date);
    const sotRevenue  = salesData.map(r => r.revenue);
    const sotTxCount  = salesData.map(r => r.transactions);

    // Color bars: anomaly-high = red, anomaly-low = amber, has-note = purple, normal = blue
    const sotBarColors = sotLabels.map(date => {
        if (anomalyMap[date] === 'high') return 'rgba(239,68,68,0.5)';
        if (anomalyMap[date] === 'low')  return 'rgba(245,158,11,0.4)';
        if (dayNotesMap[date])           return 'rgba(139,92,246,0.35)';
        return 'rgba(59,130,246,0.25)';
    });
    const sotBorderColors = sotLabels.map(date => {
        if (anomalyMap[date] === 'high') return 'rgba(239,68,68,0.8)';
        if (anomalyMap[date] === 'low')  return 'rgba(245,158,11,0.8)';
        if (dayNotesMap[date])           return 'rgba(139,92,246,0.7)';
        return 'rgba(59,130,246,0.8)';
    });

    new Chart(document.getElementById('salesOverTimeChart').getContext('2d'), {
        data: {
            labels: sotLabels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Tushum (UZS)',
                    data: sotRevenue,
                    backgroundColor: sotBarColors,
                    borderColor: sotBorderColors,
                    borderWidth: 1,
                    yAxisID: 'yRevenue',
                },
                {
                    type: 'line',
                    label: 'Tranzaksiyalar',
                    data: sotTxCount,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'yTx',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        afterBody(ctx) {
                            const date = ctx[0].label;
                            const lines = [];
                            if (anomalyMap[date]) lines.push('⚠ ' + (anomalyMap[date] === 'high' ? 'Noodatiy YUQORI kun' : 'Noodatiy PAST kun'));
                            if (dayNotesMap[date]) lines.push('📝 ' + dayNotesMap[date].title + ' (' + dayNotesMap[date].type + ')');
                            return lines;
                        }
                    }
                }
            },
            scales: {
                yRevenue: { type: 'linear', position: 'left',  ticks: { font: { size: 10 } } },
                yTx:      { type: 'linear', position: 'right', ticks: { font: { size: 10 } }, grid: { drawOnChartArea: false } },
                x: { ticks: { font: { size: 10 }, maxRotation: 45 } }
            }
        }
    });

    // --- Payment Breakdown (Doughnut) ---
    const payLabels = paymentData.map(r => r.type || 'Unknown');
    const payTotals = paymentData.map(r => r.total);
    const payColors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316'];

    new Chart(document.getElementById('paymentBreakdownChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: payLabels,
            datasets: [{
                data: payTotals,
                backgroundColor: payColors.slice(0, payLabels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } }
            },
            cutout: '65%'
        }
    });

    // --- Top Shops (horizontal bar) ---
    new Chart(document.getElementById('topShopsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: topShopsData.map(r => r.shop),
            datasets: [{
                label: 'Tushum (UZS)',
                data: topShopsData.map(r => r.revenue),
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // --- Top Cashiers (horizontal bar) ---
    new Chart(document.getElementById('topCashiersChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: topCashiersData.map(r => r.cashier),
            datasets: [{
                label: 'Tushum (UZS)',
                data: topCashiersData.map(r => r.revenue),
                backgroundColor: 'rgba(16,185,129,0.7)',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // --- Sales by Hour (bar: transactions count, line: revenue) ---
    const hourLabels    = hourlyData.map(r => String(r.hour).padStart(2,'0') + ':00');
    const hourTx        = hourlyData.map(r => r.transactions);
    const hourRevenue   = hourlyData.map(r => r.revenue);
    const maxTx         = Math.max(...hourTx);
    const barColors     = hourTx.map(v => v === maxTx && maxTx > 0 ? 'rgba(239,68,68,0.75)' : 'rgba(59,130,246,0.6)');

    new Chart(document.getElementById('hourlyChart').getContext('2d'), {
        data: {
            labels: hourLabels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Tranzaksiyalar',
                    data: hourTx,
                    backgroundColor: barColors,
                    borderRadius: 3,
                    yAxisID: 'yTx',
                },
                {
                    type: 'line',
                    label: 'Tushum (UZS)',
                    data: hourRevenue,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,0.08)',
                    borderWidth: 2,
                    pointRadius: 2,
                    tension: 0.35,
                    yAxisID: 'yRevenue',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        title: ctx => ctx[0].label + '–' + String(parseInt(ctx[0].label)+1).padStart(2,'0') + ':00'
                    }
                }
            },
            scales: {
                yTx:      { type: 'linear', position: 'left',  beginAtZero: true, ticks: { font: { size: 10 }, precision: 0 } },
                yRevenue: { type: 'linear', position: 'right', beginAtZero: true, ticks: { font: { size: 10 } }, grid: { drawOnChartArea: false } },
                x:        { ticks: { font: { size: 9 }, maxRotation: 45 } }
            }
        }
    });

})();
</script>
@endpush
