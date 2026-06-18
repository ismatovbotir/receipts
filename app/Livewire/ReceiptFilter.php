<?php

namespace App\Livewire;

use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReceiptFilter extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';
    public string $shop         = '';
    public string $type         = '';
    public string $status       = '';
    public bool   $showAnalytics = false;

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

    public function toggleAnalytics(): void
    {
        $this->showAnalytics = !$this->showAnalytics;
    }

    // ── Analytics ────────────────────────────────────────────────────────────

    private function baseAnalyticsQuery()
    {
        $q = Receipt::query()
            ->where('status', 'Закрыт')
            ->where('type', 'Продажа');

        if ($this->dateFrom !== '') $q->where('date_close', '>=', $this->dateFrom);
        if ($this->dateTo   !== '') $q->where('date_close', '<=', $this->dateTo);
        if ($this->shop     !== '') $q->where('shop', $this->shop);

        return $q;
    }

    private function computeAnalytics(): array
    {
        $driver   = DB::getDriverName();
        $timeExpr = $driver === 'mysql'
            ? 'TIMESTAMPDIFF(SECOND, date_open, date_close)'
            : "(CAST(strftime('%s', date_close) AS INTEGER) - CAST(strftime('%s', date_open) AS INTEGER))";

        $selectShop = "shop,
            COUNT(*) as cnt,
            ROUND(SUM(total),0) as sum_total,
            ROUND(MAX(total),0) as max_total,
            ROUND(MIN(total),0) as min_total,
            ROUND(AVG(total),0) as avg_total,
            ROUND(AVG(CASE WHEN date_open IS NOT NULL AND date_close > date_open
                           THEN {$timeExpr} END), 0) as avg_sec";

        $selectCashier = "cashier, shop,
            COUNT(*) as cnt,
            ROUND(SUM(total),0) as sum_total,
            ROUND(MAX(total),0) as max_total,
            ROUND(MIN(total),0) as min_total,
            ROUND(AVG(total),0) as avg_total,
            ROUND(AVG(CASE WHEN date_open IS NOT NULL AND date_close > date_open
                           THEN {$timeExpr} END), 0) as avg_sec";

        $shopStats = $this->baseAnalyticsQuery()
            ->selectRaw($selectShop)
            ->groupBy('shop')
            ->orderByDesc('sum_total')
            ->limit(20)
            ->get();

        $cashierStats = $this->baseAnalyticsQuery()
            ->selectRaw($selectCashier)
            ->groupBy('cashier', 'shop')
            ->orderByDesc('sum_total')
            ->limit(20)
            ->get();

        // Global biggest and smallest receipt (with id for linking)
        $biggest  = $this->baseAnalyticsQuery()
            ->orderByDesc('total')
            ->first(['id', 'number', 'total', 'cashier', 'shop', 'date_close']);

        $smallest = $this->baseAnalyticsQuery()
            ->where('total', '>', 0)
            ->orderBy('total')
            ->first(['id', 'number', 'total', 'cashier', 'shop', 'date_close']);

        $avgSec = (int) round(
            $this->baseAnalyticsQuery()
                ->whereNotNull('date_open')
                ->whereRaw("date_close > date_open")
                ->selectRaw("AVG({$timeExpr}) as v")
                ->first()?->v ?? 0
        );

        return compact('shopStats', 'cashierStats', 'biggest', 'smallest', 'avgSec');
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
        $analytics  = $this->showAnalytics ? $this->computeAnalytics() : null;

        return view('livewire.receipt-filter', compact('receipts', 'shops', 'types', 'statuses', 'analytics'));
    }
}
