<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests\Fixtures;

use Albertoarena\FilamentEventSourcing\FilamentEventSourcingPlugin;
use Filament\Panel;
use Filament\PanelProvider;

final class MinimalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('minimal')
            ->path('minimal')
            ->authGuard('web')
            ->plugin(FilamentEventSourcingPlugin::make());
    }
}
