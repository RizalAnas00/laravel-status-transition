<?php

namespace Rizalsaja\LaravelStatusTransition\Tests\Model;

use PHPUnit\Framework\Attributes\Test;
use Rizalsaja\LaravelStatusTransition\Models\StatusHistory;
use Rizalsaja\LaravelStatusTransition\Tests\Fixtures\FoodOrder;
use Rizalsaja\LaravelStatusTransition\Tests\Fixtures\Order;
use Rizalsaja\LaravelStatusTransition\Tests\TestCase;

class StatusHistoryModelTest extends TestCase
{
    #[Test]
    public function it_records_status_history(): void
    {
        $order = Order::create(['title' => 'Order #1 Object']);
        $order->transitionTo('processing', reason: 'Payment confirmed');

        $this->assertCount(1, $order->statusHistory);

        $order_history = $order->statusHistory->first();

        $food_order = FoodOrder::create([
            'food_name'     => 'Fried Rice',
            'quantity'      => 2,
            'customer_name' => 'John Dony',
        ]);
        $food_order->transitionTo('ordered');

        $this->assertCount(1, $food_order->statusHistory);

        $food_order_history = $food_order->statusHistory->first();

        $this->assertEquals('pending', $order_history->from);
        $this->assertEquals('processing', $order_history->to);
        $this->assertEquals('Payment confirmed', $order_history->reason);

        $this->assertEquals('not_ordered', $food_order_history->from);
        $this->assertEquals('ordered', $food_order_history->to);
        $this->assertNull($food_order_history->reason);

        $this->assertEquals(2, StatusHistory::count());
    }

    #[Test]
    public function it_stores_the_correct_morph_type(): void
    {
        $order = Order::create(['title' => 'Order #2 Object']);
        $order->transitionTo('processing');

        $food_order = FoodOrder::create([
            'food_name'     => 'Satay',
            'quantity'      => 5,
            'customer_name' => 'Dony John',
        ]);
        $food_order->transitionTo('ordered');

        $order_history     = $order->statusHistory->first();
        $food_order_history = $food_order->statusHistory->first();

        $this->assertEquals(Order::class, $order_history->statusable_type);
        $this->assertEquals(FoodOrder::class, $food_order_history->statusable_type);
    }

    #[Test]
    public function it_stores_the_correct_statusable_id(): void
    {
        $order = Order::create(['title' => 'Order #3 Object']);
        $order->transitionTo('processing');

        $history = $order->statusHistory->first();

        $this->assertEquals($order->id, $history->statusable_id);
    }

    #[Test]
    public function it_return_latest_status_history(): void
    {
        $order = Order::create(['title' => 'Order #10 Object']);
        $order->transitionTo('processing');
        $order->transitionTo('shipped');
        $order->transitionTo('delivered');

        $this->assertEquals('delivered', $order->latestStatus->to);
    }

    #[Test]
    public function it_records_multiple_transitions_in_order(): void
    {
        $order = Order::create(['title' => 'Order #4 Object']);
        $order->transitionTo('processing');
        $order->transitionTo('shipped');

        $this->assertCount(2, $order->statusHistory);

        // statusHistory is ordered by latest, so first() = most recent
        $this->assertEquals('shipped', $order->statusHistory->first()->to);
        $this->assertEquals('processing', $order->statusHistory->last()->to);
    }

    #[Test]
    public function it_records_the_reason_when_provided(): void
    {
        $order = Order::create(['title' => 'Order #5 Object']);
        $order->transitionTo('processing', reason: 'Paid via transfer');

        $history = $order->statusHistory->first();

        $this->assertEquals('Paid via transfer', $history->reason);
    }

    #[Test]
    public function it_records_null_reason_when_not_provided(): void
    {
        $order = Order::create(['title' => 'Order #6 Object']);
        $order->transitionTo('processing');

        $history = $order->statusHistory->first();

        $this->assertNull($history->reason);
    }

    #[Test]
    public function it_records_null_changed_by_when_unauthenticated(): void
    {
        $order = Order::create(['title' => 'Order #7 Object']);
        $order->transitionTo('processing');

        $history = $order->statusHistory->first();

        $this->assertNull($history->changed_by);
    }

    #[Test]
    public function it_does_not_mix_histories_between_models(): void
    {
        $order_a = Order::create(['title' => 'Order A']);
        $order_b = Order::create(['title' => 'Order B']);

        $order_a->transitionTo('processing');
        $order_b->transitionTo('processing');
        $order_b->transitionTo('shipped');

        $this->assertCount(1, $order_a->statusHistory);
        $this->assertCount(2, $order_b->statusHistory);
        $this->assertEquals(3, StatusHistory::count());
    }

    #[Test]
    public function it_does_not_record_history_when_disabled(): void
    {
        $this->app['config']->set('status-flow.record_history', false);

        $order = Order::create(['title' => 'Order #8 Object']);
        $order->transitionTo('processing', reason: 'Payment confirmed');

        $this->assertCount(0, $order->statusHistory);
        $this->assertEquals(0, StatusHistory::count());
    }

    #[Test]
    public function it_resolves_the_statusable_relation_back_to_the_model(): void
    {
        $order = Order::create(['title' => 'Order #9 Object']);
        $order->transitionTo('processing');

        $history = $order->statusHistory->first();

        $this->assertInstanceOf(Order::class, $history->statusable);
        $this->assertEquals($order->id, $history->statusable->id);
    }
}