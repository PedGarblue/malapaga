<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Event;
use App\Models\Rate;
use App\Models\Participation;

class Item extends Model
{
    protected $fillable = ['event_id','name','price_usd','rate_id'];

    public function event() { return $this->belongsTo(Event::class); }
    public function rate() { return $this->belongsTo(Rate::class); }
    public function participations() { return $this->hasMany(Participation::class); }
}
