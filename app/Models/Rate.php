<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $fillable = ['source', 'value', 'currency', 'effective_at'];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'value' => 'decimal:4',
        ];
    }
}
