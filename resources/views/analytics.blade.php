@extends('layouts.app')

@section('title', 'AI Tahlil')

@section('content')
<div class="p-6 pb-10">
    <livewire:analytics-chat />
</div>

{{-- ── Gemini API key missing modal ─────────────────────────────────────────── --}}
@if($keyMissing)
<div id="no-key-modal"
     class="fixed inset-0 z-[600] flex items-center justify-center p-4"
     style="background: rgba(15,23,42,0.65); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 flex flex-col items-center text-center gap-5">

        {{-- Icon --}}
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
        </div>

        {{-- Title --}}
        <div>
            <h2 class="text-lg font-bold text-slate-800">Gemini API kalit topilmadi</h2>
            <p class="text-sm text-slate-500 mt-1">
                AI Tahlil ishlashi uchun <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono text-xs">GEMINI_API_KEY</code>
                ni <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono text-xs">.env</code> fayliga qo'shing.
            </p>
        </div>

        {{-- Step --}}
        <div class="w-full bg-slate-900 rounded-xl px-5 py-4 text-left">
            <p class="text-xs text-slate-400 mb-2 font-medium">.env</p>
            <p class="font-mono text-sm text-green-400">GEMINI_API_KEY=<span class="text-slate-400">your_key_here</span></p>
            <p class="font-mono text-sm text-slate-400 mt-1">GEMINI_MODEL=gemini-2.5-flash</p>
        </div>

        {{-- Instructions --}}
        <ol class="text-sm text-slate-500 text-left space-y-1.5 w-full list-none">
            <li class="flex items-start gap-2">
                <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                <span><a href="https://aistudio.google.com/apikey" target="_blank" class="text-blue-600 hover:underline font-medium">aistudio.google.com/apikey</a> sahifasidan kalit oling</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                <span><code class="bg-slate-100 px-1 rounded font-mono text-xs">.env</code> fayliga <code class="bg-slate-100 px-1 rounded font-mono text-xs">GEMINI_API_KEY=...</code> qo'shing</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                <span>Serverni qayta ishga tushiring: <code class="bg-slate-100 px-1 rounded font-mono text-xs">php artisan config:clear</code></span>
            </li>
        </ol>

        {{-- Close --}}
        <button onclick="document.getElementById('no-key-modal').style.display='none'"
                class="mt-1 w-full bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium py-2.5 rounded-xl transition-colors">
            Yopish
        </button>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const COLORS = [
        '#3b82f6', '#8b5cf6', '#10b981', '#f59e0b',
        '#ef4444', '#06b6d4', '#f97316', '#84cc16',
        '#ec4899', '#14b8a6', '#6366f1', '#a855f7',
    ];

    let aiChart = null;

    function prepareData(raw, type) {
        const isPolar = ['pie', 'doughnut'].includes(type);
        return {
            labels: raw.labels || [],
            datasets: (raw.datasets || []).map((ds, i) => {
                const base = {
                    label: ds.label || '',
                    data:  ds.data  || [],
                };

                if (isPolar) {
                    base.backgroundColor = ds.backgroundColor
                        || COLORS.slice(0, (raw.labels || []).length);
                    base.borderWidth = 2;
                    base.borderColor = '#fff';
                } else if (type === 'line') {
                    const c = ds.borderColor || COLORS[i % COLORS.length];
                    base.borderColor     = c;
                    base.backgroundColor = c + '22'; // semi-transparent fill
                    base.borderWidth     = 2;
                    base.pointRadius     = 3;
                    base.pointHoverRadius = 5;
                    base.fill            = false;
                    base.tension         = 0.4;
                } else {
                    base.backgroundColor = ds.backgroundColor
                        || COLORS[i % COLORS.length];
                    base.borderRadius    = 4;
                    base.borderWidth     = 0;
                }

                return base;
            }),
        };
    }

    function renderChart(type, raw) {
        const section = document.getElementById('ai-chart-section');
        const canvas  = document.getElementById('ai-chart-canvas');
        if (!section || !canvas) return;

        section.classList.remove('hidden');
        if (aiChart) { aiChart.destroy(); aiChart = null; }

        const isPolar = ['pie', 'doughnut'].includes(type);

        aiChart = new Chart(canvas, {
            type: type,
            data: prepareData(raw, type),
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: isPolar ? 'right' : 'top',
                        labels: { font: { size: 12 }, padding: 16 },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const v = isPolar ? ctx.parsed : ctx.parsed.y;
                                const fmt = typeof v === 'number'
                                    ? v.toLocaleString('ru-RU')
                                    : v;
                                return ' ' + (ctx.dataset.label ? ctx.dataset.label + ': ' : '') + fmt;
                            },
                        },
                    },
                },
                scales: isPolar ? {} : {
                    x: {
                        ticks: { font: { size: 11 }, maxRotation: 45 },
                        grid:  { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 11 },
                            callback: v => typeof v === 'number' ? v.toLocaleString('ru-RU') : v,
                        },
                        grid: { color: '#f1f5f9' },
                    },
                },
            },
        });
    }

    function clearChart() {
        const section = document.getElementById('ai-chart-section');
        if (section) section.classList.add('hidden');
        if (aiChart) { aiChart.destroy(); aiChart = null; }
    }

    // Livewire dispatches these as browser events after DOM update
    window.addEventListener('ai-chart-render', function (e) {
        const { chartType, chartData } = e.detail;
        if (!chartType || chartType === 'none' || !chartData) {
            clearChart();
            return;
        }
        // Small delay ensures Livewire has finished morphing the DOM
        setTimeout(() => renderChart(chartType, chartData), 50);
    });

    window.addEventListener('ai-chart-clear', clearChart);
})();
</script>
@endpush
