<?php

namespace Rizalsaja\LaravelStatusTransition\Events;

use Illuminate\Database\Eloquent\Model;

class StatusTransitionFailed
{
    /**
     * @param  array<string>  $allowedTransitions  Statuses the model could have transitioned to from $from.
     */
    public function __construct(
        public readonly Model $model,
        public readonly string $from,
        public readonly string $attemptedTo,
        public readonly array $allowedTransitions = [],
        public readonly int|string|null $changedBy = null,
    )
    {
        //
    }
}
