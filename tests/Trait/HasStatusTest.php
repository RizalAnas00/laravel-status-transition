<?php

namespace Rizalsaja\LaravelStatusTransition\Tests\Traits;

use PHPUnit\Framework\Attributes\Test;
use Rizalsaja\LaravelStatusTransition\Exceptions\InvalidStatusTransitionException;
use Rizalsaja\LaravelStatusTransition\Tests\Fixtures\Order;
use Rizalsaja\LaravelStatusTransition\Tests\TestCase;

class HasStatusTest extends TestCase
{

    #[Test]
    public function it_sets_initial_status_on_create(): void
    {
        $order = Order::create(['title' => 'Test Order']);

        $this->assertEquals('pending', $order->getCurrentStatus());
    }

    #[Test]
    public function it_can_transition_to_valid_status(): void
    {
        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        $this->assertTrue($order->isStatus('processing'));
    }

    #[Test]
    public function it_throws_exception_for_invalid_transition(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('shipped'); // tidak boleh langsung dari pending
    }

    #[Test]
    public function it_records_status_history(): void
    {
        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('processing', reason: 'Payment confirmed');

        $history = $order->statusHistory->first();

        $this->assertEquals('pending', $history->from);
        $this->assertEquals('processing', $history->to);
        $this->assertEquals('Payment confirmed', $history->reason);
    }

    public function it_check_transition_policy_when_config_is_false(): void
    {
        $this->app['config']->set('status-flow.record_history', false);

        $order = Order::create(['title' => 'Order #1 Object']);
        $order->transitionTo('processing', reason: 'Payment confirmed');

        $this->assertEquals('processing', $order->status);
    }

    #[Test]
    public function it_returns_available_transitions(): void
    {
        $order = Order::create(['title' => 'Test Order']);

        $this->assertEquals(['processing', 'cancelled'], $order->availableTransitions());
    }

    #[Test]
    public function it_cannot_transition_to_unknown_status(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('unknown-status');
    }

    #[Test]
    public function it_can_query_by_status(): void
    {
        Order::create(['title' => 'Order A']);
        Order::create(['title' => 'Order B']);

        Order::first()->transitionTo('processing');

        $this->assertEquals(1, Order::whereStatus('pending')->count());
        $this->assertEquals(1, Order::whereStatus('processing')->count());
    }

    #[Test]
    public function it_executes_before_hook_before_transition(): void
    {
        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        $history = $order->statusHistory->first();

        $this->assertEquals('pending', $history->from);
        $this->assertEquals('processing', $history->to);
    }

    #[Test]
    public function it_executes_after_hook_after_transition(): void
    {
        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        $this->assertEquals('after hooks order 1', $order->title);
        $this->assertTrue($order->isStatus('processing'));
    }

    #[Test]
    public function it_does_not_execute_hooks_on_transition_without_callbacks(): void
    {
        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('processing');
        $order->transitionTo('shipped'); // shipped doesn't have hooks

        // title did not change
        $this->assertEquals('after hooks order 1', $order->title);
        $this->assertTrue($order->isStatus('shipped'));
    }

    #[Test]
    public function it_does_not_execute_hooks_when_transition_is_invalid(): void
    {
        $order = Order::create(['title' => 'Test Order']);

        try {
            $order->transitionTo('shipped'); // invalid from pending
        } catch (InvalidStatusTransitionException) {
            // hooks not executed
            $this->assertEquals('Test Order', $order->title);
            $this->assertTrue($order->isStatus('pending'));
            return;
        }

        $this->fail('Expected InvalidStatusTransitionException was not thrown.');
    }

    #[Test]
    public function it_only_executes_hooks_for_the_matching_transition(): void
    {
        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('cancelled'); // cancelled doesn't have hooks

        // title did not change
        $this->assertEquals('Test Order', $order->title);
        $this->assertTrue($order->isStatus('cancelled'));
    }
}