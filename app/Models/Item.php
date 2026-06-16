<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'receipt_id', 'code', 'name', 'price', 'total', 'discountTotal',
        'qty', 'roundTotal', 'status', 'no'
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
}
