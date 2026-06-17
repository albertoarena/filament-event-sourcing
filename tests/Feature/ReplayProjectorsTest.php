<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\Pages\ReplayProjectors;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostProjector;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAsUser();
    Filament::setCurrentPanel('admin');
});

it('hides the replay page when the config flag is off', function () {
    config()->set('filament-event-sourcing.replay.enabled', false);

    expect(ReplayProjectors::canAccess())->toBeFalse();
});

it('hides the replay page when the authorization ability fails', function () {
    config()->set('filament-event-sourcing.replay.enabled', true);
    config()->set('filament-event-sourcing.replay.authorize', 'replay-events');
    Gate::define('replay-events', fn (): bool => false);

    expect(ReplayProjectors::canAccess())->toBeFalse();
});

it('shows the replay page when every gate passes', function () {
    config()->set('filament-event-sourcing.replay.enabled', true);
    config()->set('filament-event-sourcing.replay.authorize', 'replay-events');
    Gate::define('replay-events', fn (): bool => true);

    expect(ReplayProjectors::canAccess())->toBeTrue();
});

it('hides the replay page when the plugin option is off', function () {
    config()->set('filament-event-sourcing.replay.enabled', true);

    Filament::setCurrentPanel('minimal');

    expect(ReplayProjectors::canAccess())->toBeFalse();
});

it('rebuilds a truncated projection when replayed', function () {
    config()->set('filament-event-sourcing.replay.enabled', true);

    PostAggregate::retrieve(Str::uuid()->toString())->createPost('A', 'x')->persist();
    PostAggregate::retrieve(Str::uuid()->toString())->createPost('B', 'y')->persist();
    expect(Post::count())->toBe(2);

    DB::table('posts')->delete();
    expect(Post::count())->toBe(0);

    livewire(ReplayProjectors::class)
        ->callAction('replay', arguments: ['projector' => PostProjector::class])
        ->assertNotified();

    expect(Post::count())->toBe(2);
});

it('notifies when the requested projector is not registered', function () {
    config()->set('filament-event-sourcing.replay.enabled', true);

    livewire(ReplayProjectors::class)
        ->callAction('replay', arguments: ['projector' => 'App\\Does\\Not\\Exist'])
        ->assertNotified();

    expect(Post::count())->toBe(0);
});

it('refuses to replay server-side when the config flag is off', function () {
    config()->set('filament-event-sourcing.replay.enabled', true);

    PostAggregate::retrieve(Str::uuid()->toString())->createPost('A', 'x')->persist();
    DB::table('posts')->delete();

    $page = livewire(ReplayProjectors::class);

    config()->set('filament-event-sourcing.replay.enabled', false);

    try {
        $page->callAction('replay', arguments: ['projector' => PostProjector::class]);
    } catch (Throwable) {
        // The server-side canAccess re-check aborts the request.
    }

    expect(Post::count())->toBe(0);
});
