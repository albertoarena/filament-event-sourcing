<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures;

use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostCreated;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostDeleted;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostTitleChanged;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class PostAggregate extends AggregateRoot
{
    public function createPost(string $title, string $body): self
    {
        $this->recordThat(new PostCreated($title, $body));

        return $this;
    }

    public function changeTitle(string $title): self
    {
        $this->recordThat(new PostTitleChanged($title));

        return $this;
    }

    public function deletePost(): self
    {
        $this->recordThat(new PostDeleted);

        return $this;
    }
}
