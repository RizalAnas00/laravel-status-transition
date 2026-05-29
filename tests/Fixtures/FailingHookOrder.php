<?php

namespace Rizalsaja\LaravelStatusTransition\Tests\Fixtures;

use Rizalsaja\LaravelStatusTransition\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Fixture model used exclusively by transaction rollback tests.
 *
 * The 'processing' -> 'shipped' transition has an after-hook that always
 * throws, simulating a real-world failure (e.g. a timed-out HTTP call,
 * a failed queue connection, or any unexpected exception).
 */
class FailingHookOrder extends Model
{
    use HasStatus;

    protected $table = 'failing_hook_orders';

    protected $fillable = ['title', 'status'];

    protected $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    protected $transitions = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => [
            'shipped' => [
                'after' => 'throwingAfterHook',
            ],
            'cancelled',
        ],
        'shipped'    => ['delivered'],
        'delivered'  => [],
        'cancelled'  => [],
    ];

    /**
     * Simulates a failing after-hook (e.g. a synchronous HTTP call that times out).
     * Used exclusively by transaction rollback tests.
     *
     * @throws \RuntimeException
     */
    public function throwingAfterHook(): void
    {
        throw new \RuntimeException('Simulated after-hook failure.');
    }
}
