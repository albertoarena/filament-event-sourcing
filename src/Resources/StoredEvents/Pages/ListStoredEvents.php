<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages;

use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\StoredEventResource;
use Filament\Resources\Pages\ListRecords;

final class ListStoredEvents extends ListRecords
{
    protected static string $resource = StoredEventResource::class;
}
