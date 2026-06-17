<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Concerns;

use Albertoarena\FilamentEventSourcing\Exceptions\MissingAggregateUuidException;
use Illuminate\Database\Eloquent\Model;

/**
 * Routes a Filament EditRecord page's persistence through an aggregate.
 *
 * The page keeps Filament's full edit lifecycle; only the persistence step is replaced.
 * Implement handleAggregateUpdate() to call your aggregate. The trait reads the aggregate uuid
 * from the record, delegates to the aggregate and returns the refreshed projection.
 */
trait EditsEventSourcedRecord
{
    use ResolvesAggregateUuidColumn;

    /**
     * @param  array<string, mixed>  $data
     */
    abstract protected function handleAggregateUpdate(Model $record, array $data): void;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (blank($record->getAttribute($this->aggregateUuidColumn($record)))) {
            throw MissingAggregateUuidException::for($record);
        }

        $this->handleAggregateUpdate($record, $data);

        return $record->refresh();
    }
}
