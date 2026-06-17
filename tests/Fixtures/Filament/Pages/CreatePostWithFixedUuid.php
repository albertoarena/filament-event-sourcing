<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages;

final class CreatePostWithFixedUuid extends CreatePost
{
    public const FIXED_UUID = '11111111-1111-1111-1111-111111111111';

    protected function newAggregateUuid(): string
    {
        return self::FIXED_UUID;
    }
}
