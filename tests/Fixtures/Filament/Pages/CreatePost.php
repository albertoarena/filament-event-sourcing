<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages;

use Albertoarena\FilamentEventSourcing\Concerns\CreatesEventSourcedRecord;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\PostResource;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use CreatesEventSourcedRecord;

    protected static string $resource = PostResource::class;

    protected function handleAggregateCreation(string $uuid, array $data): void
    {
        PostAggregate::retrieve($uuid)
            ->createPost($data['title'], $data['body'])
            ->persist();
    }
}
