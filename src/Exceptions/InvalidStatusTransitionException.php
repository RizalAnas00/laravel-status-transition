<?php

namespace Rizalsaja\LaravelStatusTransition\Exceptions;

use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to, array $allowed)
    {
        $allowedList = implode(', ', $allowed);

        parent::__construct(
            "Cannot transition from [{$from}] to [{$to}]. ".
            "Allowed transitions: [{$allowedList}]."
        );
    }
}