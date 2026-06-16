<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierAggregate extends Model
{
    public $timestamps = false;

    protected $table = 'cashier_aggregates';

    protected $fillable = [
        'period_type',
        'period_date',
        'cashier',
        'shop',
        'total_revenue',
        'transaction_count',
        'avg_transaction_value',
        'items_sold',
        'total_discount',
        'computed_at',
    ];

    protected $casts = [
        'period_date'           => 'date',
        'total_revenue'         => 'decimal:2',
        'avg_transaction_value' => 'decimal:2',
        'items_sold'            => 'decimal:3',
        'total_discount'        => 'decimal:2',
        'computed_at'           => 'datetime',
    ];
}
