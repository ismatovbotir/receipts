<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayNote extends Model
{
    protected $fillable = ['date', 'type', 'title', 'notes'];

    protected $casts = ['date' => 'date'];

    public static array $types = [
        'holiday' => ['label' => 'Bayram',   'color' => 'red'],
        'weather' => ['label' => 'Ob-havo',  'color' => 'blue'],
        'sport'   => ['label' => 'Sport',    'color' => 'green'],
        'promo'   => ['label' => 'Aksiya',   'color' => 'purple'],
        'other'   => ['label' => 'Boshqa',   'color' => 'slate'],
    ];
}
