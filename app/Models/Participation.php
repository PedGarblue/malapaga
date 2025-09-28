<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Item;
use App\Models\Consumer;

class Participation extends Model
{
    protected $fillable = ['item_id','consumer_id','qty','paid_by_id'];

    public function item() { return $this->belongsTo(Item::class); }
    public function consumer() { return $this->belongsTo(Consumer::class); }
    public function payer() { return $this->belongsTo(Consumer::class, 'paid_by_id'); }
}
