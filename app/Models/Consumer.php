<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Event;
use App\Models\Participation;
use App\Models\Settlement;

class Consumer extends Model
{
    protected $fillable = ['name','user_id','event_id'];

    public function event() { return $this->belongsTo(Event::class); }
    public function participations() { return $this->hasMany(Participation::class); }
    public function settlements() { return $this->hasMany(Settlement::class); }
    public function settlementsAsPayer() { return $this->hasMany(Settlement::class, 'payer_id'); }
    public function settlementsAsPayee() { return $this->hasMany(Settlement::class, 'payee_id'); }
}
