<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures;

use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostCreated;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostDeleted;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostTitleChanged;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

final class PostProjector extends Projector
{
    public function onPostCreated(PostCreated $event): void
    {
        (new Post([
            'uuid' => $event->aggregateRootUuid(),
            'title' => $event->title,
            'body' => $event->body,
        ]))->writeable()->save();
    }

    public function onPostTitleChanged(PostTitleChanged $event): void
    {
        $post = Post::find($event->aggregateRootUuid());
        $post->title = $event->title;
        $post->writeable()->save();
    }

    public function onPostDeleted(PostDeleted $event): void
    {
        Post::find($event->aggregateRootUuid())?->writeable()->delete();
    }
}
