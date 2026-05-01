<?php

namespace Rizalsaja\LaravelStatusTransition\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Rizalsaja\LaravelStatusTransition\LaravelStatusTransitionServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/Migration');
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelStatusTransitionServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');


    }
}