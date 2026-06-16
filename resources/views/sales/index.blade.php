@extends('layouts.app')

@section('title', 'Savdo tahlili — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5">
    <h1 class="text-xl font-bold text-slate-800">Savdo tahlili</h1>
    <p class="text-sm text-slate-500 mt-0.5">Savdo tendensiyalari va taqsimoti tahlili</p>
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
          onsubmit="showLoadingModal('Tahlil yuklanmoqda…', 'Ma\'lumotlar hisoblanmoqda')"
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

    {{-- Row 1: Revenue over time (col-span-2) + Payment doughnut (col-span-1) --}}
    <div class="grid grid-cols-3 gap-4">

        {{-- Sales Over Time — dual-axis bar/line with day-note annotations --}}
        <div class="col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Davr bo'yicha savdo tushumi</h2>
            <div class="relative h-72">
                <canvas id="salesOverTimeChart"></canvas>
            </div>
        </div>

        {{-- Payment Breakdown Doughnut --}}
        <div class="col-span-1 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">To'lov usullari</h2>
            <div class="relative h-72">
                <canvas id="paymentBreakdownChart"></canvas>
            </div>
        </div>

    </div>

    {{-- Row 2: Hourly chart + DOW chart --}}
    <div class="grid grid-cols-2 gap-4">

        {{-- Sales by Hour --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-700">Soat bo'yicha savdo</h2>
                @if($peak_hour && $peak_hour->transactions > 0)
                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">
                        Eng yuqori:
                        <span class="font-bold">{{ sprintf('%02d:00', $peak_hour->hour) }}–{{ sprintf('%02d:00', ($peak_hour->hour + 1) % 24) }}</span>
                        ({{ number_format($peak_hour->transactions, 0) }} tranzaksiya)
                    </span>
                @endif
            </div>
            <div class="relative h-64">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>

        {{-- Sales by Day of Week --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-700">Hafta kunlari bo'yicha savdo</h2>
                @if($peak_dow && $peak_dow->transactions > 0)
                    @php
                        $dowFullNames = [0 => 'Yakshanba', 1 => 'Dushanba', 2 => 'Seshanba',
                                         3 => 'Chorshanba', 4 => 'Payshanba', 5 => 'Juma', 6 => 'Shanba'];
                    @endphp
                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">
                        Eng yuqori:
                        <span class="font-bold">{{ $dowFullNames[$peak_dow->dow] }}</span>
                        ({{ number_format($peak_dow->transactions, 0) }} tranzaksiya)
                    </span>
                @endif
            </div>
            <div class="relative h-64">
                <canvas id="dowChart"></canvas>
            </div>
        </div>

    </div>

    {{-- Row 3: Top shops horizontal bar + Day-note impact grouped bar --}}
    <div class="grid grid-cols-2 gap-4">

        {{-- Top Shops --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Do'konlar reytingi (top 10)</h2>
            <div class="relative h-64">
                <canvas id="topShopsChart"></canvas>
            </div>
        </div>

        {{-- Day Notes Impact --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-700">Izohlar ta'siri (o'rtacha kunlik)</h2>
                <span class="text-xs text-slate-400">Kunlar soni ko'rsatilgan</span>
            </div>
            @if($note_impact->count() > 1)
                <div class="relative h-64">
                    <canvas id="noteImpactChart"></canvas>
                </div>
            @else
                <div class="flex items-center justify-center h-64 text-slate-400 text-sm">
                    Yetarli izoh yo'q
                </div>
            @endif
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    // --- Data from PHP ---
    const salesData       = @json($sales_over_time);
    const hourlyData      = @json($sales_by_hour);
    const dowData         = @json($sales_by_dow);
    const topShopsData    = @json($top_shops);
    const paymentData     = @json($payment_breakdown);
    const noteImpactData  = @json($note_impact->values());
    const dayNotesMap     = @json($day_notes->map(fn($n) => ['type' => $n->type, 'title' => $n->title]));

    // -------------------------------------------------------------------------
    // 1. Sales Over Time — dual axis: bars = revenue (left Y), line = txn (right Y)
    //    Day-note dates get purple bars; plain dates get blue.
    // -------------------------------------------------------------------------
    const sotLabels    = salesData.map(r => r.date);
    const sotRevenue   = salesData.map(r => r.revenue);
    const sotTxCount   = salesData.map(r => r.transactions);

    const sotBarColors = sotLabels.map(date =>
        dayNotesMap[date] ? 'rgba(139,92,246,0.45)' : 'rgba(59,130,246,0.7)'
    );
    const sotBorderColors = sotLabels.map(date =>
        dayNotesMap[date] ? 'rgba(139,92,246,0.85)' : 'rgba(59,130,246,0.9)'
    );

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
                    borderRadius: 3,
                    yAxisID: 'yRevenue',
                },
                {
                    type: 'line',
                    label: 'Tranzaksiyalar',
                    data: sotTxCount,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
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
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        afterBody(ctx) {
                            const date = ctx[0].label;
                            const lines = [];
                            if (dayNotesMap[date]) {
                                const typeLabels = {
                                    holiday: 'Bayram', weather: 'Ob-havo', sport: 'Sport',
                                    promo: 'Aksiya', other: 'Boshqa'
                                };
                                lines.push(
                                    '📝 ' + dayNotesMap[date].title +
                                    ' (' + (typeLabels[dayNotesMap[date].type] || dayNotesMap[date].type) + ')'
                                );
                            }
                            return lines;
                        }
                    }
                }
            },
            scales: {
                yRevenue: {
                    type: 'linear', position: 'left',
                    beginAtZero: true,
                    ticks: { font: { size: 10 } }
                },
                yTx: {
                    type: 'linear', position: 'right',
                    beginAtZero: true,
                    ticks: { font: { size: 10 }, precision: 0 },
                    grid: { drawOnChartArea: false }
                },
                x: { ticks: { font: { size: 10 }, maxRotation: 45 } }
            }
        }
    });

    // -------------------------------------------------------------------------
    // 2. Payment Breakdown — Doughnut
    // -------------------------------------------------------------------------
    const payColors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
    const payLabels = paymentData.map(r => r.type || 'Boshqa');
    const payTotals = paymentData.map(r => r.total);

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

    // -------------------------------------------------------------------------
    // 3. Hourly chart — bar = transactions (left Y), line = revenue (right Y)
    //    Peak bar highlighted red.
    // -------------------------------------------------------------------------
    const hourLabels  = hourlyData.map(r => String(r.hour).padStart(2,'0') + ':00');
    const hourTx      = hourlyData.map(r => r.transactions);
    const hourRevenue = hourlyData.map(r => r.revenue);
    const maxHourTx   = Math.max(...hourTx);
    const hourBarColors = hourTx.map(v =>
        v === maxHourTx && maxHourTx > 0 ? 'rgba(239,68,68,0.75)' : 'rgba(59,130,246,0.7)'
    );

    new Chart(document.getElementById('hourlyChart').getContext('2d'), {
        data: {
            labels: hourLabels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Tranzaksiyalar',
                    data: hourTx,
                    backgroundColor: hourBarColors,
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
                        title: ctx => {
                            const h = parseInt(ctx[0].label);
                            return String(h).padStart(2,'0') + ':00–' + String(h + 1).padStart(2,'0') + ':00';
                        }
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

    // -------------------------------------------------------------------------
    // 4. DOW chart — Mon–Sun (display order [1,2,3,4,5,6,0])
    //    Uzbek short labels: Du Se Ch Pa Ju Sh Ya
    //    Peak bar highlighted red.
    // -------------------------------------------------------------------------
    const dowShortLabels = ['Du', 'Se', 'Ch', 'Pa', 'Ju', 'Sh', 'Ya'];
    const dowTx      = dowData.map(r => r.transactions);
    const dowRevenue = dowData.map(r => r.revenue);
    const maxDowTx   = Math.max(...dowTx);
    const dowBarColors = dowTx.map(v =>
        v === maxDowTx && maxDowTx > 0 ? 'rgba(239,68,68,0.75)' : 'rgba(59,130,246,0.7)'
    );

    new Chart(document.getElementById('dowChart').getContext('2d'), {
        data: {
            labels: dowShortLabels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Tranzaksiyalar',
                    data: dowTx,
                    backgroundColor: dowBarColors,
                    borderRadius: 3,
                    yAxisID: 'yTx',
                },
                {
                    type: 'line',
                    label: 'Tushum (UZS)',
                    data: dowRevenue,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'yRevenue',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 } } }
            },
            scales: {
                yTx:      { type: 'linear', position: 'left',  beginAtZero: true, ticks: { font: { size: 10 }, precision: 0 } },
                yRevenue: { type: 'linear', position: 'right', beginAtZero: true, ticks: { font: { size: 10 } }, grid: { drawOnChartArea: false } },
                x:        { ticks: { font: { size: 10 } } }
            }
        }
    });

    // -------------------------------------------------------------------------
    // 5. Top Shops — horizontal bar
    // -------------------------------------------------------------------------
    new Chart(document.getElementById('topShopsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: topShopsData.map(r => r.shop),
            datasets: [{
                label: 'Tushum (UZS)',
                data: topShopsData.map(r => r.revenue),
                backgroundColor: 'rgba(99,102,241,0.7)',
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

    // -------------------------------------------------------------------------
    // 6. Day Notes Impact — grouped bar (avg_revenue per note type) + line (avg_tx)
    // -------------------------------------------------------------------------
    if (noteImpactData.length > 1 && document.getElementById('noteImpactChart')) {
        const noteLabels = {
            'holiday': 'Bayram', 'weather': 'Ob-havo', 'sport': 'Sport',
            'promo': 'Aksiya', 'other': 'Boshqa', 'none': 'Izohsiz'
        };
        const noteColors = {
            'holiday': 'rgba(239,68,68,0.7)',
            'weather': 'rgba(59,130,246,0.7)',
            'sport':   'rgba(16,185,129,0.7)',
            'promo':   'rgba(139,92,246,0.7)',
            'other':   'rgba(100,116,139,0.7)',
            'none':    'rgba(203,213,225,0.8)'
        };

        const niLabels   = noteImpactData.map(r => (noteLabels[r.type] || r.type) + ' (' + r.days + ' kun)');
        const niRevenue  = noteImpactData.map(r => r.avg_revenue);
        const niTx       = noteImpactData.map(r => r.avg_transactions);
        const niBgColors = noteImpactData.map(r => noteColors[r.type] || 'rgba(100,116,139,0.5)');

        new Chart(document.getElementById('noteImpactChart').getContext('2d'), {
            data: {
                labels: niLabels,
                datasets: [
                    {
                        type: 'bar',
                        label: "O'rtacha tushum (UZS)",
                        data: niRevenue,
                        backgroundColor: niBgColors,
                        borderRadius: 4,
                        yAxisID: 'yRevenue',
                    },
                    {
                        type: 'line',
                        label: "O'rtacha tranzaksiyalar",
                        data: niTx,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        borderWidth: 2,
                        pointRadius: 4,
                        tension: 0.2,
                        yAxisID: 'yTx',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 } } }
                },
                scales: {
                    yRevenue: { type: 'linear', position: 'left',  beginAtZero: true, ticks: { font: { size: 10 } } },
                    yTx:      { type: 'linear', position: 'right', beginAtZero: true, ticks: { font: { size: 10 } }, grid: { drawOnChartArea: false } },
                    x:        { ticks: { font: { size: 10 } } }
                }
            }
        });
    }

})();
</script>
@endpush
