<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the aggregate-uuid column for a projection model.
 *
 * Honours a per-model `getAggregateUuidColumn()` override, falling back to the configured
 * default. Shared by the write-bridge page traits.
 *
 * @internal
 */
trait ResolvesAggregateUuidColumn
{
    protected function aggregateUuidColumn(Model $model): string
    {
        if (method_exists($model, 'getAggregateUuidColumn')) {
            return $model->getAggregateUuidColumn();
        }

        return config('filament-event-sourcing.aggregate_uuid_column');
    }
}
