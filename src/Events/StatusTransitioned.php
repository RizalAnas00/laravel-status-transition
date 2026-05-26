<?php

namespace Rizalsaja\LaravelStatusTransition\Events;

use Illuminate\Database\Eloquent\Model;

class StatusTransitioned
{
    public function __construct(
        public readonly Model $model,
        public readonly string $from,
        public readonly string $to,
        public readonly ?string $reason,
        public readonly int|string|null $changedBy = null,
    ) 
    {
        //
    }
}

