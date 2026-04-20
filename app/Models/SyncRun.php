<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    protected $table = 'thinkion_sync_runs';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'context_json' => 'array',
        'totals_json' => 'array',
        'status' => SyncStatus::class,
    ];
}
