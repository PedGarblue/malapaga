<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Consumer;
use App\Models\Item;
use App\Models\Settlement;

class Event extends Model
{
    protected $fillable = ['title','date','user_id'];

    public function consumers() { return $this->hasMany(Consumer::class); }
    public function items() { return $this->hasMany(Item::class); }
    public function settlements() { return $this->hasMany(Settlement::class); }
}
