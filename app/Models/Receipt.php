<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'number', 'date_open', 'date_close', 'type', 'cashier',
        'status', 'card', 'pos', 'total', 'shop', 'shift'
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'receipt_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'receipt_id');
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'receipt_id');
    }
}
