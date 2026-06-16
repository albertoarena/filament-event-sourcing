<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Resources\StoredEvents;

use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages\ListStoredEvents;
use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages\ViewStoredEvent;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Read-only Filament resource for browsing the Spatie stored events table.
 *
 * Provides a list page and a view page over the configured stored-event model. The resource
 * never creates, edits or deletes events. Navigation group, sort and pagination come from the
 * `filament-event-sourcing.stored_events_resource` config.
 */
final class StoredEventResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModel(): string
    {
        return config('event-sourcing.stored_event_model');
    }

    public static function getNavigationLabel(): string
    {
        return 'Stored Events';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('filament-event-sourcing.stored_events_resource.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-event-sourcing.stored_events_resource.navigation_sort');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(config('filament-event-sourcing.stored_events_resource.per_page'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('aggregate_uuid')
                    ->label('Aggregate UUID')
                    ->copyable()
                    ->searchable(),
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
                    ->options(fn (): array => self::getModel()::query()
                        ->distinct()
                        ->pluck('event_class', 'event_class')
                        ->map(fn (string $class): string => class_basename($class))
                        ->all()),
                Filter::make('aggregate_uuid')
                    ->schema([
                        TextInput::make('aggregate_uuid')->label('Aggregate UUID'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['aggregate_uuid'] ?? null,
                        fn (Builder $query, string $uuid): Builder => $query->where('aggregate_uuid', $uuid),
                    )),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label('Recorded from'),
                        DatePicker::make('created_until')->label('Recorded until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('aggregate_uuid')->label('Aggregate UUID')->copyable(),
                TextEntry::make('aggregate_version')->label('Version'),
                TextEntry::make('event_class')->label('Event'),
                TextEntry::make('created_at')->label('Recorded at')->dateTime(),
                TextEntry::make('event_properties')
                    ->label('Event properties')
                    ->html()
                    ->state(fn ($record): HtmlString => self::jsonBlock($record->event_properties)),
                TextEntry::make('meta_data')
                    ->label('Metadata')
                    ->html()
                    ->state(fn ($record): HtmlString => self::jsonBlock($record->meta_data)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoredEvents::route('/'),
            'view' => ViewStoredEvent::route('/{record}'),
        ];
    }

    protected static function jsonBlock(mixed $value): HtmlString
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            $decoded = $value->toArray();
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
        } else {
            $decoded = $value;
        }

        $json = json_encode($decoded ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

        return new HtmlString('<pre class="fi-code-block">'.e($json).'</pre>');
    }
}
