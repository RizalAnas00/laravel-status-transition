<?php
// src/Models/StatusHistory.php

namespace Rizalsaja\LaravelStatusTransition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusHistory extends Model
{
    protected $fillable = [
        'statusable_type',
        'statusable_id',
        'from',
        'to',
        'changed_by',
        'reason',
    ];

    public function statusable(): MorphTo
    {
        return $this->morphTo();
    }
}