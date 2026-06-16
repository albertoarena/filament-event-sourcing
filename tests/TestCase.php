<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests;

use Albertoarena\FilamentEventSourcing\FilamentEventSourcingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\EventSourcing\EventSourcingServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->runStoredEventsMigration();
    }

    protected function getPackageProviders($app): array
    {
        return [
            EventSourcingServiceProvider::class,
            FilamentEventSourcingServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function runStoredEventsMigration(): void
    {
        $migration = include dirname(__DIR__)
            .'/vendor/spatie/laravel-event-sourcing/database/migrations/create_stored_events_table.php.stub';

        $migration->up();
    }
}
