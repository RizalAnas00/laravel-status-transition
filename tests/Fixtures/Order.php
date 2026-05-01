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
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered'],
        'delivered'  => [],
        'cancelled'  => [],
    ];
}