<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detection extends Model
{
    protected $fillable = [
        'sensor_type',
        'is_daytime',
        'message',
        'actuator',
        'detected_at',
    ];

    protected $casts = [
        'is_daytime' => 'boolean',
        'detected_at' => 'datetime',
    ];
}
