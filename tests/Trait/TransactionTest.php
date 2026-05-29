<?php

namespace Rizalsaja\LaravelStatusTransition\Tests\Traits;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Rizalsaja\LaravelStatusTransition\Events\StatusTransitioned;
use Rizalsaja\LaravelStatusTransition\Models\StatusHistory;
use Rizalsaja\LaravelStatusTransition\Tests\Fixtures\FailingHookOrder;
use Rizalsaja\LaravelStatusTransition\Tests\Fixtures\Order;
use Rizalsaja\LaravelStatusTransition\Tests\TestCase;

class TransactionTest extends TestCase
{
    #[Test]
    public function it_rolls_back_status_when_after_hook_throws(): void
    {
        $order = FailingHookOrder::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated after-hook failure.');

        // processing -> shipped triggers throwingAfterHook
        $order->transitionTo('shipped');
    }

    #[Test]
    public function it_does_not_persist_status_to_database_when_after_hook_throws(): void
    {
        $order = FailingHookOrder::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        try {
            $order->transitionTo('shipped');
        } catch (\RuntimeException) {
            // expected — hook failure must roll back the whole transition
        }

        // The database row must still hold 'processing', not 'shipped'
        $this->assertEquals('processing', $order->fresh()->status);
    }

    #[Test]
    public function it_does_not_create_history_record_when_after_hook_throws(): void
    {
        $order = FailingHookOrder::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        $historyCountBefore = StatusHistory::count();

        try {
            $order->transitionTo('shipped');
        } catch (\RuntimeException) {
            // expected
        }

        // No new history row should have been written
        $this->assertEquals($historyCountBefore, StatusHistory::count());
    }

    #[Test]
    public function it_does_not_dispatch_event_when_after_hook_throws(): void
    {
        $this->app['config']->set('status-flow.dispatch_events', true);

        $order = FailingHookOrder::create(['title' => 'Test Order']);
        $order->transitionTo('processing');

        // Fake events after the first clean transition so we only
        // assert on the failing transition's dispatches.
        Event::fake();

        try {
            $order->transitionTo('shipped');
        } catch (\RuntimeException) {
            // expected
        }

        // The StatusTransitioned event must NOT be dispatched for a rolled-back transition
        Event::assertNotDispatched(StatusTransitioned::class);
    }

    #[Test]
    public function it_commits_successfully_when_no_hook_throws(): void
    {
        $order = Order::create(['title' => 'Test Order']);

        // pending -> processing is a clean transition with no throwing hooks
        $order->transitionTo('processing');

        $this->assertEquals('processing', $order->fresh()->status);
        $this->assertCount(1, $order->statusHistory);
    }

    #[Test]
    public function it_keeps_history_count_unchanged_after_failed_transition(): void
    {
        $order = FailingHookOrder::create(['title' => 'Test Order']);
        $order->transitionTo('processing'); // 1 history record

        $countAfterFirstTransition = StatusHistory::count();

        try {
            $order->transitionTo('shipped'); // throws — must roll back
        } catch (\RuntimeException) {
            // expected
        }

        // Count must be the same as before the failing transition
        $this->assertEquals($countAfterFirstTransition, StatusHistory::count());
    }
}
