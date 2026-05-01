<?php

namespace Rizalsaja\LaravelStatusTransition\Tests;

use Rizalsaja\LaravelStatusTransition\LaravelStatusTransitionServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelStatusTransitionServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}