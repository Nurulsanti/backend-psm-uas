<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardMetric extends Model
{
    protected $fillable = [
        'metric_key',
        'metric_value',
        'last_updated',
    ];

    protected $casts = [
        'metric_value' => 'array',
        'last_updated' => 'datetime',
    ];
}
