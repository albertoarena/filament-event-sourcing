<?php

declare(strict_types=1);

use Filament\Facades\Filament;

it('registers the test panel as default', function () {
    $panel = Filament::getPanel('admin');

    expect($panel)->not->toBeNull()
        ->and($panel->isDefault())->toBeTrue();
});

it('authenticates a user who can access the panel', function () {
    $user = $this->actingAsUser();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(Filament::auth()->user()->is($user))->toBeTrue()
        ->and($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});
