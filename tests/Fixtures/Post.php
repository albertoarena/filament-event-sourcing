<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures;

use Spatie\EventSourcing\Projections\Projection;

/**
 * @property string $uuid
 * @property string $title
 * @property string $body
 */
final class Post extends Projection
{
    protected $guarded = [];
}
