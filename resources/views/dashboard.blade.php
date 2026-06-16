@extends('layouts.app')

@section('title', 'Bosh sahifa — ReceiptReport')

@section('content')

{{-- Page Header --}}
<div class="bg-white border-b border-slate-200 px-8 py-5">
    <h1 class="text-xl font-bold text-slate-800">Bosh sahifa</h1>
    <p class="text-sm text-slate-500 mt-0.5">Joriy davr uchun asosiy ko'rsatkichlar</p>
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
          onsubmit="showLoadingModal('Hisobot tayyorlanmoqda…', 'Ma\'lumotlar tahlil qilinmoqda')"
          class="flex flex-wrap items-end gap-4">
        @csrf

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-slate-500">Dan</label>
            <input id="filter_from" type="date" name="from" value="{{ $from }}"
                   class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-slate-500">Gacha</label>
            <input id="filter_to" type="date" name="to" value="{{ $to }}"
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
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">Bugun</button>
            <button type="button" @click="setRange('7d')"
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">7 kun</button>
            <button type="button" @click="setRange('30d')"
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">30 kun</button>
            <button type="button" @click="setRange('month')"
                    class="px-3 py-2 text-xs font-medium bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors">Shu oy</button>
        </div>
    </form>
</div>

{{-- Main Content --}}
<div class="px-8 py-6 space-y-6">

    {{-- Anomaly Alert --}}
    @if($unannotated_anomalies->count() > 0)
        <div x-data="anomalyNoteModal()" x-cloak>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-4">
                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-amber-800">
                        {{ $unannotated_anomalies->count() }} ta noodatiy kun izohlarga ega emas
                    </h3>
                    <p class="text-xs text-amber-700 mt-0.5 mb-3">
                        Bu kunlardagi savdo o'rtachadan sezilarli farq qildi. Kunni bosib izoh qo'shing.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($unannotated_anomalies as $a)
                            <button type="button"
                                    @click="open('{{ $a->date }}', '{{ $a->direction }}', {{ (int)$a->revenue }}, {{ (int)$a->transactions }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                                        {{ $a->direction === 'high'
                                            ? 'bg-red-100 text-red-700 border border-red-200 hover:bg-red-200'
                                            : 'bg-blue-100 text-blue-700 border border-blue-200 hover:bg-blue-200' }}">
                                {{ $a->direction === 'high' ? '↑' : '↓' }}
                                {{ \Carbon\Carbon::parse($a->date)->format('d M') }}
                                <span class="opacity-70">— {{ $a->direction === 'high' ? 'noodatiy yuqori' : 'noodatiy past' }}</span>
                                <svg class="w-3 h-3 ml-0.5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Note modal --}}
            <div x-show="isOpen"
                 class="fixed inset-0 bg-black/50 flex items-center justify-center z-[300] p-4"
                 @click.self="isOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md"
                     @click.stop
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">

                    <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-xs font-semibold"
                                      x-text="dayOfWeek"></span>
                                <h3 class="font-semibold text-slate-800" x-text="formattedDate"></h3>
                            </div>
                            <p class="text-xs font-medium" :class="direction === 'high' ? 'text-red-600' : 'text-blue-600'"
                               x-text="direction === 'high' ? '↑ Noodatiy yuqori savdo' : '↓ Noodatiy past savdo'"></p>
                            <p class="text-xs text-slate-400 mt-0.5" x-text="salesInfo"></p>
                        </div>
                        <button @click="isOpen = false" class="text-slate-400 hover:text-slate-600 p-1 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-2">Tadbir turi</label>
                            <div class="grid grid-cols-5 gap-1.5">
                                <button type="button" @click="type='holiday'" :class="type==='holiday' ? 'bg-red-500 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200'" class="py-2 rounded-lg text-xs font-medium transition-colors">Bayram</button>
                                <button type="button" @click="type='weather'" :class="type==='weather' ? 'bg-blue-500 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200'" class="py-2 rounded-lg text-xs font-medium transition-colors">Ob-havo</button>
                                <button type="button" @click="type='sport'"   :class="type==='sport'   ? 'bg-green-500 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200'" class="py-2 rounded-lg text-xs font-medium transition-colors">Sport</button>
                                <button type="button" @click="type='promo'"   :class="type==='promo'   ? 'bg-purple-500 text-white' : 'bg-purple-100 text-purple-700 hover:bg-purple-200'" class="py-2 rounded-lg text-xs font-medium transition-colors">Aksiya</button>
                                <button type="button" @click="type='other'"   :class="type==='other'   ? 'bg-slate-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="py-2 rounded-lg text-xs font-medium transition-colors">Boshqa</button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Sarlavha <span class="text-red-400">*</span></label>
                            <input x-model="title" type="text" placeholder="masalan: Navro'z bayrami, Kuchli yomg'ir…"
                                   class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Izohlar <span class="text-slate-400">(ixtiyoriy)</span></label>
                            <textarea x-model="notes" rows="3" placeholder="Bu kun nima uchun noodatiy edi?"
                                      class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button @click="isOpen = false" type="button"
                                class="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                            Bekor qilish
                        </button>
                        <button @click="save()" :disabled="!title.trim() || saving" type="button"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                            <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-text="saving ? 'Saqlanmoqda…' : 'Izohni saqlash'"></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-5 gap-4">

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

    {{-- Revenue Trend --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-700">Davr bo'yicha savdo</h2>
            <div class="flex items-center gap-4 text-xs text-slate-400">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-400 inline-block"></span>Tushum</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-1 rounded bg-emerald-500 inline-block"></span>Tranzaksiyalar</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-300 inline-block"></span>Noodatiy yuqori</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-300 inline-block"></span>Noodatiy past</span>
            </div>
        </div>
        <div class="relative h-72">
            <canvas id="salesOverTimeChart"></canvas>
        </div>
    </div>

    {{-- Quick links to detailed analytics --}}
    <div class="grid grid-cols-3 gap-4">

        <a href="{{ route('sales.index') }}"
           class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center gap-4 hover:border-blue-200 hover:shadow-md transition-all group">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">Savdo tahlili</p>
                <p class="text-xs text-slate-400 mt-0.5">Soat, hafta kuni, do'konlar</p>
            </div>
            <svg class="w-4 h-4 text-slate-300 ml-auto group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <a href="{{ route('cashiers.index') }}"
           class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center gap-4 hover:border-emerald-200 hover:shadow-md transition-all group">
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-100 transition-colors">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">Kassirlar</p>
                <p class="text-xs text-slate-400 mt-0.5">Samaradorlik va tezlik tahlili</p>
            </div>
            <svg class="w-4 h-4 text-slate-300 ml-auto group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <a href="{{ route('products.index') }}"
           class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center gap-4 hover:border-violet-200 hover:shadow-md transition-all group">
            <div class="w-10 h-10 bg-violet-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-violet-100 transition-colors">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">Mahsulotlar</p>
                <p class="text-xs text-slate-400 mt-0.5">Top mahsulotlar va eng katta qatorlar</p>
            </div>
            <svg class="w-4 h-4 text-slate-300 ml-auto group-hover:text-violet-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

    </div>

</div>

@endsection

@push('scripts')
<script>
function anomalyNoteModal() {
    return {
        isOpen: false, saving: false, date: '', direction: '',
        type: 'other', title: '', notes: '',
        formattedDate: '', dayOfWeek: '', salesInfo: '',

        open(date, direction, revenue, transactions) {
            const dowNames = ['Yakshanba','Dushanba','Seshanba','Chorshanba','Payshanba','Juma','Shanba'];
            const d = new Date(date + 'T12:00:00');
            this.dayOfWeek     = dowNames[d.getDay()];
            this.formattedDate = d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
            this.salesInfo     = transactions + ' chek · ' + new Intl.NumberFormat('ru-RU').format(revenue) + ' UZS';
            this.date = date; this.direction = direction;
            this.type = direction === 'high' ? 'promo' : 'other';
            this.title = ''; this.notes = ''; this.saving = false;
            this.isOpen = true;
            this.$nextTick(() => { const inp = this.$el.querySelector('input[type=text]'); if (inp) inp.focus(); });
        },

        async save() {
            if (!this.title.trim() || this.saving) return;
            this.saving = true;
            try {
                const res = await fetch('{{ route("calendar.notes.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ date: this.date, type: this.type, title: this.title, notes: this.notes }),
                });
                if (res.ok) { showLoadingModal('Yangilanmoqda…', 'Izoh saqlandi, sahifa yangilanmoqda'); window.location.reload(); }
            } finally { this.saving = false; }
        },
    };
}
</script>
<script>
(function () {
    const salesData   = @json($sales_over_time);
    const dayNotesMap = @json($day_notes->map(fn($n) => ['type' => $n->type, 'title' => $n->title]));
    const anomalyMap  = @json($anomalies->keyBy('date')->map(fn($a) => $a->direction));

    const labels   = salesData.map(r => r.date);
    const revenues = salesData.map(r => r.revenue);
    const txCounts = salesData.map(r => r.transactions);

    const barBg = labels.map(d => {
        if (anomalyMap[d] === 'high') return 'rgba(239,68,68,0.5)';
        if (anomalyMap[d] === 'low')  return 'rgba(245,158,11,0.4)';
        if (dayNotesMap[d])           return 'rgba(139,92,246,0.35)';
        return 'rgba(59,130,246,0.25)';
    });
    const barBorder = labels.map(d => {
        if (anomalyMap[d] === 'high') return 'rgba(239,68,68,0.8)';
        if (anomalyMap[d] === 'low')  return 'rgba(245,158,11,0.8)';
        if (dayNotesMap[d])           return 'rgba(139,92,246,0.7)';
        return 'rgba(59,130,246,0.8)';
    });

    new Chart(document.getElementById('salesOverTimeChart').getContext('2d'), {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar', label: 'Tushum (UZS)',
                    data: revenues, backgroundColor: barBg, borderColor: barBorder,
                    borderWidth: 1, yAxisID: 'yRevenue',
                },
                {
                    type: 'line', label: 'Tranzaksiyalar',
                    data: txCounts, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)',
                    borderWidth: 2, pointRadius: 3, tension: 0.3, yAxisID: 'yTx',
                },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        afterBody(ctx) {
                            const date = ctx[0].label;
                            const lines = [];
                            if (anomalyMap[date]) lines.push('! ' + (anomalyMap[date] === 'high' ? 'Noodatiy YUQORI kun' : 'Noodatiy PAST kun'));
                            if (dayNotesMap[date]) lines.push('  ' + dayNotesMap[date].title + ' (' + dayNotesMap[date].type + ')');
                            return lines;
                        }
                    }
                }
            },
            scales: {
                yRevenue: { type: 'linear', position: 'left',  ticks: { font: { size: 10 } } },
                yTx:      { type: 'linear', position: 'right', ticks: { font: { size: 10 } }, grid: { drawOnChartArea: false } },
                x:        { ticks: { font: { size: 10 }, maxRotation: 45 } },
            },
        },
    });
})();
</script>
@endpush
