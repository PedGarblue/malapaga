<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Event;
use App\Models\Consumer;

class Settlement extends Model
{
    protected $fillable = ['event_id','payer_id','payee_id','amount','paid'];

    public function event() { return $this->belongsTo(Event::class); }
    public function payer() { return $this->belongsTo(Consumer::class, 'payer_id'); }
    public function payee() { return $this->belongsTo(Consumer::class, 'payee_id'); }
}
