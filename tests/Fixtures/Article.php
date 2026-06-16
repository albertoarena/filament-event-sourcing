<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures;

use Albertoarena\FilamentEventSourcing\Concerns\HasStoredEvents;
use Illuminate\Database\Eloquent\Model;

/**
 * Fixture with an `id` primary key and the aggregate uuid in a config-named `ref` column.
 *
 * @property string $ref
 */
final class Article extends Model
{
    use HasStoredEvents;

    protected $guarded = [];
}
