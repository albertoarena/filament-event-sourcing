<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages;

use Albertoarena\FilamentEventSourcing\Actions\EventSourcedDeleteAction;
use Albertoarena\FilamentEventSourcing\Concerns\EditsEventSourcedRecord;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\PostResource;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditPost extends EditRecord
{
    use EditsEventSourcedRecord;

    protected static string $resource = PostResource::class;

    protected function handleAggregateUpdate(Model $record, array $data): void
    {
        PostAggregate::retrieve($record->uuid)
            ->changeTitle($data['title'])
            ->persist();
    }

    protected function getHeaderActions(): array
    {
        return [
            EventSourcedDeleteAction::make()
                ->using(fn (Post $record) => PostAggregate::retrieve($record->uuid)->deletePost()->persist()),
        ];
    }
}
