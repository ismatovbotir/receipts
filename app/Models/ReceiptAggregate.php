<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptAggregate extends Model
{
    public $timestamps = false;

    protected $table = 'receipt_aggregates';

    protected $fillable = [
        'period_type',
        'period_date',
        'shop',
        'total_revenue',
        'transaction_count',
        'avg_transaction_value',
        'items_sold',
        'avg_basket_size',
        'total_discount',
        'discount_pct',
        'vat_amount',
        'revenue_ex_vat',
        'payment_breakdown',
        'category_breakdown',
        'hourly_breakdown',
        'computed_at',
    ];

    protected $casts = [
        'period_date'           => 'date',
        'total_revenue'         => 'decimal:2',
        'avg_transaction_value' => 'decimal:2',
        'items_sold'            => 'decimal:3',
        'avg_basket_size'       => 'decimal:3',
        'total_discount'        => 'decimal:2',
        'discount_pct'          => 'decimal:2',
        'vat_amount'            => 'decimal:2',
        'revenue_ex_vat'        => 'decimal:2',
        'payment_breakdown'     => 'array',
        'category_breakdown'    => 'array',
        'hourly_breakdown'      => 'array',
        'computed_at'           => 'datetime',
    ];

    public function scopeDaily($query)  { return $query->where('period_type', 'daily'); }
    public function scopeWeekly($query) { return $query->where('period_type', 'weekly'); }
    public function scopeMonthly($query){ return $query->where('period_type', 'monthly'); }
}
