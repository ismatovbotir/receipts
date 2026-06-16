@extends('layouts.app')

@section('title', 'Cheklar — ReceiptReport')

@section('content')

<div class="bg-white border-b border-slate-200 px-8 py-5">
    <h1 class="text-xl font-bold text-slate-800">Cheklar</h1>
    <p class="text-sm text-slate-500 mt-0.5">Barcha POS cheklarini ko'rish va qidirish</p>
</div>

<livewire:receipt-filter />

@endsection
