<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherSubscription extends Model
{
    protected $fillable = ['chat_id', 'city'];
}
