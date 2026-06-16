<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Adds a read-only relation to the stored events of the aggregate a projection belongs to.
 *
 * The relation links the projection's aggregate-uuid column to the `aggregate_uuid` column on
 * Spatie's configured stored-event model. The column is taken from the
 * `filament-event-sourcing.aggregate_uuid_column` config, or from a `getAggregateUuidColumn()`
 * method on the model when present. The primary key of the projection is irrelevant.
 */
trait HasStoredEvents
{
    public function storedEvents(): HasMany
    {
        /** @var class-string<Model> $storedEventModel */
        $storedEventModel = config('event-sourcing.stored_event_model');

        return $this->hasMany(
            $storedEventModel,
            'aggregate_uuid',
            $this->aggregateUuidColumn(),
        );
    }

    protected function aggregateUuidColumn(): string
    {
        if (method_exists($this, 'getAggregateUuidColumn')) {
            return $this->getAggregateUuidColumn();
        }

        return config('filament-event-sourcing.aggregate_uuid_column');
    }
}
