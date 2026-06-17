<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only relation manager listing the stored events of a record's aggregate.
 *
 * Attach it to a Filament resource whose model uses the HasStoredEvents trait. The table mirrors
 * the Stored Events resource minus the aggregate uuid column, which is constant for one record.
 */
final class StoredEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'storedEvents';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Event history')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('aggregate_version')
                    ->label('Version')
                    ->sortable(),
                TextColumn::make('event_class')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->tooltip(fn (TextColumn $column): string => (string) $column->getState()),
                TextColumn::make('created_at')
                    ->label('Recorded at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_class')
                    ->label('Event')
                    ->options(fn (): array => $this->getRelationship()->getRelated()->newQuery()
                        ->distinct()
                        ->pluck('event_class', 'event_class')
                        ->map(fn (string $class): string => class_basename($class))
                        ->all()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label('Recorded from'),
                        DatePicker::make('created_until')->label('Recorded until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
