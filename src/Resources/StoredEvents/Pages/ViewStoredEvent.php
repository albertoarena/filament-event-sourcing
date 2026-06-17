<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages;

use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\StoredEventResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * View page for a single stored event, showing its pretty-printed payload and metadata.
 */
final class ViewStoredEvent extends ViewRecord
{
    protected static string $resource = StoredEventResource::class;
}
