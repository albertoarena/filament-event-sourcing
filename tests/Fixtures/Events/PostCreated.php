<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PostCreated extends ShouldBeStored
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
    ) {}
}
