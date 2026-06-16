<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAggregate extends Model
{
    public $timestamps = false;

    protected $table = 'product_aggregates';

    protected $fillable = [
        'period_type',
        'period_date',
        'product_code',
        'product_name',
        'category',
        'total_revenue',
        'quantity_sold',
        'transaction_count',
        'total_discount',
        'computed_at',
    ];

    protected $casts = [
        'period_date'       => 'date',
        'total_revenue'     => 'decimal:2',
        'quantity_sold'     => 'decimal:3',
        'total_discount'    => 'decimal:2',
        'computed_at'       => 'datetime',
    ];
}
