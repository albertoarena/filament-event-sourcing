<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages;

use Albertoarena\FilamentEventSourcing\Actions\EventHistoryAction;
use Albertoarena\FilamentEventSourcing\Actions\EventSourcedDeleteAction;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\PostResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

final class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EventHistoryAction::make(),
            // Intentionally missing ->using() to exercise the LogicException guard.
            EventSourcedDeleteAction::make(),
        ];
    }
}
