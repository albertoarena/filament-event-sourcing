<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing;

use Albertoarena\FilamentEventSourcing\Pages\ReplayProjectors;
use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\StoredEventResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

/**
 * Filament plugin entry point for the event sourcing integration.
 *
 * Registers the optional Stored Events browser resource and projector replay page on a panel.
 * Each feature is opt-in and evaluated lazily, so the same plugin instance can be registered on
 * multiple panels with different options.
 */
final class FilamentEventSourcingPlugin implements Plugin
{
    use EvaluatesClosures;

    public const ID = 'filament-event-sourcing';

    protected bool|Closure $hasStoredEventsResource = false;

    protected bool|Closure $hasReplayPage = false;

    public static function make(): static
    {
        return app(self::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = Filament::getCurrentPanel()->getPlugin(self::ID);

        return $plugin;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function storedEventsResource(bool|Closure $condition = true): static
    {
        $this->hasStoredEventsResource = $condition;

        return $this;
    }

    public function replayPage(bool|Closure $condition = true): static
    {
        $this->hasReplayPage = $condition;

        return $this;
    }

    public function hasReplayPage(): bool
    {
        return (bool) $this->evaluate($this->hasReplayPage);
    }

    public function register(Panel $panel): void
    {
        if ($this->evaluate($this->hasStoredEventsResource)) {
            $panel->resources([
                StoredEventResource::class,
            ]);
        }

        if ($this->hasReplayPage()) {
            $panel->pages([
                ReplayProjectors::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
