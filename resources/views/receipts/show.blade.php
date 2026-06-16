@extends('layouts.app')

@section('title', 'Receipt #{{ $receipt->number }} — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5 flex items-center gap-4">
    <a href="{{ route('receipts.index') }}"
       class="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Cheklarga qaytish
    </a>
    <span class="text-slate-300">/</span>
    <h1 class="text-xl font-bold text-slate-800">Receipt #{{ $receipt->number }}</h1>
</div>

<div class="px-8 py-6 space-y-5">

    {{-- Receipt Header Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-slate-800">{{ $receipt->number }}</h2>
                <p class="text-sm text-slate-400 mt-0.5">Chek ID: <span class="font-mono text-xs">{{ $receipt->id }}</span></p>
            </div>
            @php
                $closedStatuses = ['Закрыт', 'closed', 'Closed'];
                $isClosed = in_array($receipt->status, $closedStatuses);
            @endphp
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                {{ $isClosed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $isClosed ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $receipt->status ?? 'Unknown' }}
            </span>
        </div>

        <div class="grid grid-cols-4 gap-x-8 gap-y-4">
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Ochilgan vaqt</p>
                <p class="text-sm text-slate-700">
                    {{ $receipt->date_open ? \Carbon\Carbon::parse($receipt->date_open)->format('d.m.Y H:i:s') : '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Yopilgan vaqt</p>
                <p class="text-sm text-slate-700">
                    {{ $receipt->date_close ? \Carbon\Carbon::parse($receipt->date_close)->format('d.m.Y H:i:s') : '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Tur</p>
                <p class="text-sm text-slate-700">{{ $receipt->type ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Smena</p>
                <p class="text-sm text-slate-700">{{ $receipt->shift ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Do'kon</p>
                <p class="text-sm text-slate-700">{{ $receipt->shop ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">POS</p>
                <p class="text-sm text-slate-700 font-mono">{{ $receipt->pos ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Kassir</p>
                <p class="text-sm text-slate-700">{{ $receipt->cashier ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Karta</p>
                <p class="text-sm text-slate-700">{{ $receipt->card ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Holat</p>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                    {{ $isClosed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isClosed ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $receipt->status ?? '—' }}
                </span>
            </div>
        </div>

        <div class="mt-5 pt-5 border-t border-slate-100 flex items-center justify-end">
            <div class="text-right">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Umumiy summa</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">
                    {{ number_format($receipt->total, 0, '.', ' ') }} <span class="text-base font-medium text-slate-400">UZS</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">
            Mahsulotlar
            <span class="ml-2 text-xs font-normal text-slate-400">({{ $receipt->items->count() }} qator)</span>
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="text-left px-4 py-3">№</th>
                        <th class="text-left px-4 py-3">Kod</th>
                        <th class="text-left px-4 py-3">Nomi</th>
                        <th class="text-right px-4 py-3">Narx</th>
                        <th class="text-right px-4 py-3">Miqdor</th>
                        <th class="text-right px-4 py-3">Chegirma</th>
                        <th class="text-right px-4 py-3">Jami (UZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($receipt->items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-400 text-xs">{{ $item->no }}</td>
                            <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $item->code }}</td>
                            <td class="px-4 py-3 text-slate-800 font-medium">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($item->price, 0, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($item->qty, 0, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">
                                {{ $item->discountTotal ? number_format($item->discountTotal, 0, '.', ' ') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                {{ number_format($item->total, 0, '.', ' ') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400">Mahsulotlar yo'q</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($receipt->items->isNotEmpty())
                    <tfoot>
                        <tr class="bg-slate-50 font-semibold">
                            <td colspan="4" class="px-4 py-3 text-slate-500 text-sm">Jami</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($receipt->items->sum('qty'), 0, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($receipt->items->sum('discountTotal'), 0, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">{{ number_format($receipt->items->sum('total'), 0, '.', ' ') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Payments Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">To'lovlar</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="text-left px-4 py-3">To'lov turi</th>
                        <th class="text-right px-4 py-3">Miqdor (UZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($receipt->payments as $payment)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $payment->type ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                {{ number_format($payment->total, 0, '.', ' ') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-slate-400">To'lov yozuvlari yo'q</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($receipt->payments->isNotEmpty())
                    <tfoot>
                        <tr class="bg-slate-50 font-semibold">
                            <td class="px-4 py-3 text-slate-500">Jami to'langan</td>
                            <td class="px-4 py-3 text-right text-slate-800">{{ number_format($receipt->payments->sum('total'), 0, '.', ' ') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Discounts Table (only if present) --}}
    @if($receipt->discounts->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Chegirmalar</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="text-left px-4 py-3">№</th>
                            <th class="text-left px-4 py-3">Chek darajasi</th>
                            <th class="text-right px-4 py-3">Jami (UZS)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($receipt->discounts as $discount)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-slate-400 text-xs">{{ $discount->no }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if($discount->receipt)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Ha</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-500">Yo'q</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                    {{ number_format($discount->total, 0, '.', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

@endsection
