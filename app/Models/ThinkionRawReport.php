<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThinkionRawReport extends Model
{
    protected $table = 'thinkion_raw_reports';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'fetched_at' => 'datetime',
    ];
}
