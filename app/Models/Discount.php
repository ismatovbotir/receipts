<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    public $timestamps = false;

    protected $fillable = ['receipt_id', 'receipt', 'total', 'no'];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
}
