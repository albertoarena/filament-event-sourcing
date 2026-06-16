<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\FilamentEventSourcingServiceProvider;

it('loads the config file', function () {
    expect(config('filament-event-sourcing'))->toBeArray()
        ->and(config('filament-event-sourcing.aggregate_uuid_column'))->toBe('uuid')
        ->and(config('filament-event-sourcing.replay.enabled'))->toBeFalse();
});

it('boots the service provider', function () {
    expect(app()->getLoadedProviders())
        ->toHaveKey(FilamentEventSourcingServiceProvider::class);
});
