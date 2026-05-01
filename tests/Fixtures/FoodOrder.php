<?php

namespace Rizalsaja\LaravelStatusTransition\Tests\Fixtures;

use Rizalsaja\LaravelStatusTransition\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;

class FoodOrder extends Model
{
    use HasStatus;

    protected $table = 'food_orders';

    protected $fillable = [
        'food_name', 
        'quantity', 
        'customer_name', 
        'status'
    ];

    protected $statuses = [
        'not_ordered',
        'ordered',
        'accepted',
        'rejected',
        'cooking',
        'prepared',
        'delivered',
        'completed'
    ];

    protected $transitions = [
        "not_ordered" => ["ordered"],
        "ordered" => ["accepted", "rejected"],
        "accepted" => ["cooking"],
        "cooking" => ["prepared"],
        "prepared" => ["delivered"],
        "delivered" => ["completed"]
    ];
}