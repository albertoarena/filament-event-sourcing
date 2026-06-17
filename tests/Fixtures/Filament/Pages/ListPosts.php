<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages;

use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\PostResource;
use Filament\Resources\Pages\ListRecords;

final class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
