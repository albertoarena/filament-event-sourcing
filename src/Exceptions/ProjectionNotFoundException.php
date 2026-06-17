<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Exceptions;

use RuntimeException;

/**
 * Thrown when a projection cannot be found after its aggregate has been persisted.
 *
 * The write bridge expects projectors to run synchronously for resources managed by Filament,
 * so a missing projection almost always means the projector is queued.
 */
final class ProjectionNotFoundException extends RuntimeException
{
    public static function for(string $modelClass, string $uuid): self
    {
        return new self(
            "Projection {$modelClass} with uuid {$uuid} not found after persisting the aggregate. "
            .'If your projector is queued, make it synchronous for resources managed by Filament, '
            .'or run the queue worker.'
        );
    }
}
