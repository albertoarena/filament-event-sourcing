<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Actions;

use Albertoarena\FilamentEventSourcing\Exceptions\MissingAggregateUuidException;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Row or header action that shows a record's aggregate event history in a slide-over.
 *
 * The host model must use the HasStoredEvents trait and hold a non-empty aggregate uuid,
 * otherwise a MissingAggregateUuidException is thrown. The latest events are listed
 * chronologically, capped at {@see self::EVENT_CAP} with a notice when more exist.
 */
final class EventHistoryAction extends Action
{
    public const EVENT_CAP = 100;

    public static function getDefaultName(): string
    {
        return 'eventHistory';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('History');
        $this->icon(Heroicon::OutlinedClock);
        $this->slideOver();
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('Close');

        $this->modalContent(function (Model $record) {
            /** @var view-string $view */
            $view = 'filament-event-sourcing::event-history';

            return view($view, static::historyViewData($record));
        });
    }

    /**
     * Build the chronological, capped event list rendered in the history modal.
     *
     * @return array{events: Collection<int, Model>, capped: bool, cap: int}
     */
    public static function historyViewData(Model $record): array
    {
        $events = self::aggregateStoredEvents($record)
            ->orderByDesc('id')
            ->limit(self::EVENT_CAP + 1)
            ->get();

        return [
            'events' => $events->take(self::EVENT_CAP)->reverse()->values(),
            'capped' => $events->count() > self::EVENT_CAP,
            'cap' => self::EVENT_CAP,
        ];
    }

    /**
     * @return HasMany<Model, Model>
     */
    public static function aggregateStoredEvents(Model $record): HasMany
    {
        if (! method_exists($record, 'storedEvents')) {
            throw MissingAggregateUuidException::for($record);
        }

        /** @var HasMany<Model, Model> $relation */
        $relation = $record->storedEvents();

        if (blank($record->getAttribute($relation->getLocalKeyName()))) {
            throw MissingAggregateUuidException::for($record);
        }

        return $relation;
    }
}
