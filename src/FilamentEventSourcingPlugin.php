<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing;

use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\StoredEventResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

/**
 * Filament plugin entry point for the event sourcing integration.
 *
 * Registers the optional Stored Events browser resource on a panel. Each feature is opt-in and
 * evaluated lazily, so the same plugin instance can be registered on multiple panels with
 * different options.
 */
final class FilamentEventSourcingPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool|Closure $hasStoredEventsResource = false;

    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'filament-event-sourcing';
    }

    public function storedEventsResource(bool|Closure $condition = true): static
    {
        $this->hasStoredEventsResource = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        if ($this->evaluate($this->hasStoredEventsResource)) {
            $panel->resources([
                StoredEventResource::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
