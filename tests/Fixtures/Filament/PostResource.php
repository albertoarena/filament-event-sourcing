<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament;

use Albertoarena\FilamentEventSourcing\RelationManagers\StoredEventsRelationManager;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages\ListPosts;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages\ViewPost;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('uuid'),
            TextColumn::make('title'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            StoredEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'view' => ViewPost::route('/{record}'),
        ];
    }
}
