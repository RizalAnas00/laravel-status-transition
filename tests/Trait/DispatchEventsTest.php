<?php

namespace Rizalsaja\LaravelStatusTransition\Tests\Traits;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Rizalsaja\LaravelStatusTransition\Exceptions\InvalidStatusTransitionException;
use Rizalsaja\LaravelStatusTransition\Tests\Fixtures\Order;
use Rizalsaja\LaravelStatusTransition\Events\StatusTransitioned;
use Rizalsaja\LaravelStatusTransition\Events\StatusTransitionFailed;
use Rizalsaja\LaravelStatusTransition\Tests\TestCase;

class DispatchEventsTest extends TestCase
{
    #[Test]
    public function it_dispatches_status_transitioned_on_success_when_config_enabled(): void
    {
        $this->app['config']->set('status-flow.dispatch_events', true);

        Event::fake();

        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('processing', reason: 'Payment confirmed');

        Event::assertDispatched(StatusTransitioned::class, function (StatusTransitioned $event) use ($order) {
            return $event->model->is($order)
                && $event->from === 'pending'
                && $event->to === 'processing'
                && $event->reason === 'Payment confirmed';
        });
    }

    #[Test]
    public function it_dispatches_status_transition_failed_on_invalid_transition_when_config_enabled(): void
    {
        $this->app['config']->set('status-flow.dispatch_events', true);

        Event::fake();

        $order = Order::create(['title' => 'Test Order']);

        try {
            $order->transitionTo('shipped'); // invalid from pending
        } catch (InvalidStatusTransitionException) {
            // expected
        }

        Event::assertDispatched(StatusTransitionFailed::class, function (StatusTransitionFailed $event) use ($order) {
            return $event->model->is($order)
                && $event->from === 'pending'
                && $event->attemptedTo === 'shipped'
                && $event->allowedTransitions === ['processing', 'cancelled'];
        });

        Event::assertNotDispatched(StatusTransitioned::class);
    }

    #[Test]
    public function it_does_not_dispatch_any_event_when_config_disabled(): void
    {
        $this->app['config']->set('status-flow.dispatch_events', false);

        Event::fake();

        $order = Order::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        Event::assertNotDispatched(StatusTransitioned::class);
        Event::assertNotDispatched(StatusTransitionFailed::class);
    }

    #[Test]
    public function it_does_not_dispatch_failed_event_on_invalid_transition_when_config_disabled(): void
    {
        $this->app['config']->set('status-flow.dispatch_events', false);

        Event::fake();

        $order = Order::create(['title' => 'Test Order']);

        try {
            $order->transitionTo('shipped'); // invalid from pending
        } catch (InvalidStatusTransitionException) {
            // expected
        }

        Event::assertNotDispatched(StatusTransitionFailed::class);
    }

    #[Test]
    public function it_dispatches_failed_event_for_unknown_status(): void
    {
        $this->app['config']->set('status-flow.dispatch_events', true);

        Event::fake();

        $order = Order::create(['title' => 'Test Order']);

        try {
            $order->transitionTo('unknown-status');
        } catch (\InvalidArgumentException) {
            // expected
        }

        Event::assertDispatched(StatusTransitionFailed::class, function (StatusTransitionFailed $event) use ($order) {
            return $event->model->is($order)
                && $event->from === 'pending'
                && $event->attemptedTo === 'unknown-status'
                && $event->allowedTransitions === ['processing', 'cancelled'];
        });

        Event::assertNotDispatched(StatusTransitioned::class);
    }
}
