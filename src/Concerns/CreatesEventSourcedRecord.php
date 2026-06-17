<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Concerns;

use Albertoarena\FilamentEventSourcing\Exceptions\ProjectionNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Routes a Filament CreateRecord page's persistence through an aggregate.
 *
 * The page keeps Filament's full create lifecycle (validation, mutate hooks, notifications,
 * redirects); only the persistence step is replaced. Implement handleAggregateCreation() to
 * call your aggregate. The trait generates the uuid, resolves the resulting projection and
 * returns it so Filament's redirect and notification flow is untouched.
 */
trait CreatesEventSourcedRecord
{
    use ResolvesAggregateUuidColumn;

    /**
     * @param  array<string, mixed>  $data
     */
    abstract protected function handleAggregateCreation(string $uuid, array $data): void;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $uuid = $this->newAggregateUuid();

        $this->handleAggregateCreation($uuid, $data);

        $model = static::getModel();
        $column = $this->aggregateUuidColumn(new $model);

        $record = $model::query()->where($column, $uuid)->first();

        if ($record === null) {
            throw ProjectionNotFoundException::for($model, $uuid);
        }

        return $record;
    }

    protected function newAggregateUuid(): string
    {
        return Str::uuid()->toString();
    }
}
