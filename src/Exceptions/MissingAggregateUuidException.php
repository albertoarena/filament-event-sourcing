<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Thrown when a record cannot be linked to its aggregate's stored events.
 *
 * Raised by the event history tooling when the model does not use the HasStoredEvents trait,
 * or when its aggregate uuid column is empty.
 */
final class MissingAggregateUuidException extends RuntimeException
{
    public static function for(Model $record): self
    {
        $model = $record::class;

        return new self(
            "Cannot resolve stored events for [{$model}]. The model must use the "
            .'Albertoarena\FilamentEventSourcing\Concerns\HasStoredEvents trait and hold a '
            .'non-empty aggregate uuid in its configured uuid column. Add the trait to the model '
            .'and make sure the record was created through its aggregate so the uuid is set.'
        );
    }
}
