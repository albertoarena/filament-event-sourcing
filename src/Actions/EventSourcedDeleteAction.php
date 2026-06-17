<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Actions;

use Filament\Actions\DeleteAction;
use LogicException;

/**
 * A DeleteAction that deletes through an aggregate instead of Eloquent.
 *
 * You must provide a `->using()` closure that calls your aggregate's delete method. The action
 * never calls `$record->delete()` itself: the projector owns the projection's lifecycle. The
 * confirmation modal, success notification and table refresh behaviour are kept intact.
 */
final class EventSourcedDeleteAction extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (): void {
            if ($this->using === null) {
                throw new LogicException(
                    "EventSourcedDeleteAction requires a ->using() closure that deletes through your aggregate, e.g.\n\n"
                    ."EventSourcedDeleteAction::make()\n"
                    .'    ->using(fn (Post $record) => PostAggregate::retrieve($record->uuid)->deletePost()->persist());'
                );
            }

            $this->process(null);

            $this->success();
        });
    }
}
