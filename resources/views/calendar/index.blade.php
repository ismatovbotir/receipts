@extends('layouts.app')

@section('title', 'Kalendar — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Savdo kalendari</h1>
        <p class="text-sm text-slate-500 mt-0.5">Savdo naqshlarini tushuntirish uchun bayramlar, tadbirlar va noodatiy kunlarni belgilang</p>
    </div>
    {{-- Month Navigation --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('calendar', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
           class="p-2 rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-base font-semibold text-slate-800 min-w-[10rem] text-center">
            {{ $startOfMonth->format('F Y') }}
        </span>
        <a href="{{ route('calendar', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
           class="p-2 rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <a href="{{ route('calendar', ['year' => now()->year, 'month' => now()->month]) }}"
           class="px-3 py-2 text-sm font-medium border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-slate-600">
            Bugun
        </a>
    </div>
</div>

{{-- Legend --}}
<div class="bg-white border-b border-slate-200 px-8 py-3 flex items-center gap-6">
    <span class="text-xs font-medium text-slate-500">Tadbir turlari:</span>
    @foreach($types as $key => $meta)
        <span class="flex items-center gap-1.5 text-xs text-slate-600">
            <span class="w-2.5 h-2.5 rounded-full
                {{ $meta['color'] === 'red'    ? 'bg-red-400'    : '' }}
                {{ $meta['color'] === 'blue'   ? 'bg-blue-400'   : '' }}
                {{ $meta['color'] === 'green'  ? 'bg-green-400'  : '' }}
                {{ $meta['color'] === 'purple' ? 'bg-purple-400' : '' }}
                {{ $meta['color'] === 'slate'  ? 'bg-slate-400'  : '' }}
            "></span>
            {{ $meta['label'] }}
        </span>
    @endforeach
    <span class="ml-auto text-xs text-slate-400">Izoh qo'shish yoki tahrirlash uchun istalgan kunni bosing</span>
</div>

{{-- Calendar --}}
<div class="px-8 py-6" x-data="calendarModal()" x-cloak>

    {{-- Day-of-week headers --}}
    <div class="grid grid-cols-7 gap-1 mb-1">
        @foreach(['Du','Se','Ch','Pa','Ju','Sh','Ya'] as $dow)
            <div class="text-center text-xs font-semibold text-slate-400 py-2">{{ $dow }}</div>
        @endforeach
    </div>

    {{-- Calendar grid --}}
    <div class="grid grid-cols-7 gap-1">
        @foreach($days as $day)
            @if($day === null)
                {{-- Padding cell --}}
                <div class="h-28 rounded-lg bg-slate-50 border border-slate-100"></div>
            @else
                @php
                    $isToday   = $day['date'] === now()->toDateString();
                    $hasNote   = !is_null($day['note']);
                    $hasSales  = !is_null($day['revenue']);
                    $noteColor = $hasNote ? ($types[$day['note']['type']]['color'] ?? 'slate') : null;
                @endphp
                <button
                    @click="open({{ json_encode($day) }})"
                    class="h-28 rounded-lg border text-left p-2 transition-all hover:shadow-md hover:-translate-y-0.5 cursor-pointer flex flex-col
                        {{ $isToday  ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300' }}
                        {{ $hasNote  ? 'ring-1 ring-inset ring-offset-0 ring-' . $noteColor . '-300' : '' }}
                    ">
                    {{-- Day number --}}
                    <div class="flex items-start justify-between">
                        <span class="text-sm font-semibold {{ $isToday ? 'text-blue-600' : 'text-slate-700' }}">
                            {{ $day['day'] }}
                        </span>
                        @if($hasNote)
                            <span class="w-2 h-2 rounded-full mt-1 flex-shrink-0
                                {{ $noteColor === 'red'    ? 'bg-red-400'    : '' }}
                                {{ $noteColor === 'blue'   ? 'bg-blue-400'   : '' }}
                                {{ $noteColor === 'green'  ? 'bg-green-400'  : '' }}
                                {{ $noteColor === 'purple' ? 'bg-purple-400' : '' }}
                                {{ $noteColor === 'slate'  ? 'bg-slate-400'  : '' }}
                            "></span>
                        @endif
                    </div>

                    {{-- Sales data --}}
                    @if($hasSales)
                        <div class="mt-auto">
                            <div class="text-xs font-semibold text-slate-800 truncate">
                                {{ number_format($day['revenue'], 0, '.', ' ') }}
                                <span class="font-normal text-slate-400">UZS</span>
                            </div>
                            <div class="text-xs text-slate-400">{{ $day['transactions'] }} chek</div>
                        </div>
                    @else
                        <div class="mt-auto text-xs text-slate-300">Savdo yo'q</div>
                    @endif

                    {{-- Note title --}}
                    @if($hasNote)
                        <div class="text-xs truncate mt-1
                            {{ $noteColor === 'red'    ? 'text-red-600'    : '' }}
                            {{ $noteColor === 'blue'   ? 'text-blue-600'   : '' }}
                            {{ $noteColor === 'green'  ? 'text-green-600'  : '' }}
                            {{ $noteColor === 'purple' ? 'text-purple-600' : '' }}
                            {{ $noteColor === 'slate'  ? 'text-slate-500'  : '' }}
                        ">{{ $day['note']['title'] }}</div>
                    @endif
                </button>
            @endif
        @endforeach
    </div>

    {{-- Modal --}}
    <div x-show="isOpen"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="isOpen = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md"
             @click.stop
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            {{-- Modal header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-800" x-text="formattedDate"></h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-show="salesInfo" x-text="salesInfo"></p>
                </div>
                <button @click="isOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Type --}}
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-2">Tadbir turi</label>
                    <div class="grid grid-cols-5 gap-1.5">
                        <template x-for="[key, meta] in Object.entries(typeOptions)" :key="key">
                            <button type="button"
                                    @click="type = key"
                                    :class="type === key ? 'ring-2 ring-offset-1' : 'opacity-60 hover:opacity-80'"
                                    class="py-2 px-1 rounded-lg border text-xs font-medium transition-all text-center"
                                    :style="type === key ? `border-color: var(--color-${key}); ring-color: var(--color-${key});` : ''"
                                    x-text="meta.label">
                            </button>
                        </template>
                    </div>
                    {{-- Simple colored buttons --}}
                    <div class="grid grid-cols-5 gap-1.5 mt-2">
                        <button type="button" @click="type='holiday'"
                                :class="type==='holiday' ? 'bg-red-500 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200'"
                                class="py-2 rounded-lg text-xs font-medium transition-colors">Bayram</button>
                        <button type="button" @click="type='weather'"
                                :class="type==='weather' ? 'bg-blue-500 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200'"
                                class="py-2 rounded-lg text-xs font-medium transition-colors">Ob-havo</button>
                        <button type="button" @click="type='sport'"
                                :class="type==='sport' ? 'bg-green-500 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200'"
                                class="py-2 rounded-lg text-xs font-medium transition-colors">Sport</button>
                        <button type="button" @click="type='promo'"
                                :class="type==='promo' ? 'bg-purple-500 text-white' : 'bg-purple-100 text-purple-700 hover:bg-purple-200'"
                                class="py-2 rounded-lg text-xs font-medium transition-colors">Aksiya</button>
                        <button type="button" @click="type='other'"
                                :class="type==='other' ? 'bg-slate-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="py-2 rounded-lg text-xs font-medium transition-colors">Boshqa</button>
                    </div>
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Sarlavha <span class="text-red-400">*</span></label>
                    <input x-model="title"
                           type="text"
                           placeholder="masalan: Navro'z bayrami, Kuchli yomg'ir, Chempionlar ligasi finali…"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Izohlar <span class="text-slate-400">(ixtiyoriy)</span></label>
                    <textarea x-model="notes"
                              rows="3"
                              placeholder="Bu kun nima uchun noodatiy edi? Savdodagi o'zgarishga nima sabab bo'ldi?"
                              class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>

            </div>

            {{-- Modal footer --}}
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <button x-show="noteId"
                        @click="deleteNote()"
                        type="button"
                        class="text-sm text-red-500 hover:text-red-700 font-medium transition-colors">
                    Izohni o'chirish
                </button>
                <div class="flex gap-2 ml-auto">
                    <button @click="isOpen = false" type="button"
                            class="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Bekor qilish
                    </button>
                    <button @click="save()"
                            :disabled="!title.trim()"
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        Izohni saqlash
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function calendarModal() {
    return {
        isOpen:    false,
        noteId:    null,
        date:      '',
        type:      'other',
        title:     '',
        notes:     '',
        formattedDate: '',
        salesInfo: '',

        open(day) {
            this.date   = day.date;
            this.noteId = day.note ? day.note.id   : null;
            this.type   = day.note ? day.note.type : 'other';
            this.title  = day.note ? (day.note.title || '') : '';
            this.notes  = day.note ? (day.note.notes || '') : '';

            const d = new Date(day.date + 'T12:00:00');
            this.formattedDate = d.toLocaleDateString('uz-UZ', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });

            this.salesInfo = day.revenue
                ? day.transactions + ' chek · ' + new Intl.NumberFormat('ru-RU').format(day.revenue) + ' UZS'
                : 'Savdo qayd etilmagan';

            this.isOpen = true;
        },

        async save() {
            if (!this.title.trim()) return;
            const res = await fetch('{{ route("calendar.notes.store") }}', {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({
                    date:  this.date,
                    type:  this.type,
                    title: this.title,
                    notes: this.notes,
                }),
            });
            if (res.ok) window.location.reload();
        },

        async deleteNote() {
            if (!this.noteId) return;
            if (!confirm('Ushbu izohni o\'chirishni istaysizmi?')) return;
            const res = await fetch('/calendar/notes/' + this.noteId, {
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            });
            if (res.ok) window.location.reload();
        },
    };
}
</script>
@endpush
