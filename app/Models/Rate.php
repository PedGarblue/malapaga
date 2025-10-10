<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $fillable = ['source', 'value', 'currency_from', 'currency_to', 'effective_at'];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'value' => 'decimal:4',
        ];
    }
}
