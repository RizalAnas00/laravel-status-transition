<?php

namespace Rizalsaja\LaravelStatusTransition\Tests\Fixtures;

use Rizalsaja\LaravelStatusTransition\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasStatus;

    protected $fillable = ['title', 'status'];

    protected $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    protected $transitions = [
        'pending' => [
            'processing' => [
                'before' => 'beforeHooks',
                'after'  => 'changeTitleWhenProcessingDone',
            ],
            'cancelled',
        ],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered'],
        'delivered'  => [],
        'cancelled'  => [],
    ];

    public function beforeHooks(): void
    {
        $this->title = 'before hooks order 1';
    }

    private function changeTitleWhenProcessingDone(): void
    {
        $this->title = 'after hooks order 1';
    }
}