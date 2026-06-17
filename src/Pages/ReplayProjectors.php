<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Pages;

use Albertoarena\FilamentEventSourcing\FilamentEventSourcingPlugin;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\Projectionist;

/**
 * Config-gated admin page to replay projectors one at a time.
 *
 * Visibility is gated three ways: the `replay.enabled` config flag, the plugin's replayPage()
 * option and the configured authorization ability. All three are re-checked server-side before a
 * replay runs. Replays execute synchronously in the request; the CLI is preferred in production.
 */
final class ReplayProjectors extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected string $view = 'filament-event-sourcing::pages.replay-projectors';

    public static function getNavigationLabel(): string
    {
        return 'Replay Projectors';
    }

    public static function canAccess(): bool
    {
        if (config('filament-event-sourcing.replay.enabled') !== true) {
            return false;
        }

        if (! FilamentEventSourcingPlugin::get()->hasReplayPage()) {
            return false;
        }

        $ability = config('filament-event-sourcing.replay.authorize');

        if ($ability === null) {
            return true;
        }

        return (bool) Filament::auth()->user()?->can($ability);
    }

    /**
     * @return array<int, class-string<Projector>>
     */
    public function getProjectors(): array
    {
        return collect(app(Projectionist::class)->getProjectors()->all())
            ->map(fn (Projector $projector): string => $projector::class)
            ->values()
            ->all();
    }

    public function replayAction(): Action
    {
        return Action::make('replay')
            ->label('Replay')
            ->requiresConfirmation()
            ->modalHeading('Replay projector')
            ->modalDescription(
                'This runs synchronously in the request and may take a long time on large event '
                .'stores. For production, prefer the CLI: php artisan event-sourcing:replay.'
            )
            ->action(function (array $arguments): void {
                abort_unless(static::canAccess(), 403);

                $projectorClass = $arguments['projector'] ?? null;
                $projector = is_string($projectorClass)
                    ? app(Projectionist::class)->getProjector($projectorClass)
                    : null;

                if ($projector === null) {
                    Notification::make()
                        ->title('Projector not found')
                        ->danger()
                        ->send();

                    return;
                }

                $count = 0;

                app(Projectionist::class)->replay(
                    collect([$projector]),
                    onEventReplayed: function () use (&$count): void {
                        $count++;
                    },
                );

                Notification::make()
                    ->title('Replay complete')
                    ->body($count.' events replayed for '.class_basename($projector))
                    ->success()
                    ->send();
            });
    }
}
