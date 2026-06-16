<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

final class CustomStoredEvent extends EloquentStoredEvent {}
