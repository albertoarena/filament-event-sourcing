<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages;

use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\StoredEventResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewStoredEvent extends ViewRecord
{
    protected static string $resource = StoredEventResource::class;
}
