<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages;

use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\StoredEventResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page for the read-only Stored Events browser resource.
 */
final class ListStoredEvents extends ListRecords
{
    protected static string $resource = StoredEventResource::class;
}
