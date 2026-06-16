<?php

namespace App\Livewire;

use App\Models\Receipt;
use Livewire\Component;
use Livewire\WithPagination;

class ReceiptFilter extends Component
{
    use WithPagination;

    public string $search    = '';
    public string $dateFrom  = '';
    public string $dateTo    = '';
    public string $shop      = '';
    public string $type      = '';
    public string $status    = '';

    // Reset page when any filter changes
    public function updatingSearch():   void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo():   void { $this->resetPage(); }
    public function updatingShop():     void { $this->resetPage(); }
    public function updatingType():     void { $this->resetPage(); }
    public function updatingStatus():   void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo', 'shop', 'type', 'status']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Receipt::query()->orderByDesc('date_close');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('number',  'like', '%' . $this->search . '%')
                  ->orWhere('cashier', 'like', '%' . $this->search . '%')
                  ->orWhere('shop',    'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('date_close', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('date_close', '<=', $this->dateTo);
        }

        if ($this->shop !== '') {
            $query->where('shop', $this->shop);
        }

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        $receipts   = $query->paginate(20);
        $shops      = Receipt::select('shop')->distinct()->orderBy('shop')->pluck('shop');
        $types      = Receipt::select('type')->distinct()->orderBy('type')->pluck('type');
        $statuses   = Receipt::select('status')->distinct()->orderBy('status')->pluck('status');

        return view('livewire.receipt-filter', compact('receipts', 'shops', 'types', 'statuses'));
    }
}
