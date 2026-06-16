<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures;

use Albertoarena\FilamentEventSourcing\Concerns\HasStoredEvents;
use Illuminate\Database\Eloquent\Model;

/**
 * Fixture with an `id` primary key that overrides the aggregate uuid column via method.
 *
 * @property string $comment_uuid
 */
final class Comment extends Model
{
    use HasStoredEvents;

    protected $guarded = [];

    public function getAggregateUuidColumn(): string
    {
        return 'comment_uuid';
    }
}
