<div>

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

        </div>
    </div>

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
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-5 py-3 text-slate-400 text-xs">{{ $receipts->firstItem() + $i }}</td>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('receipts.show', $receipt->id) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $receipt->number }}
                                        </a>
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

</div>
