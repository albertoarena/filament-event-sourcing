<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the filament-event-sourcing package.
 *
 * Registers the package configuration via spatie/laravel-package-tools. The Filament
 * plugin (resources, pages) is wired separately through FilamentEventSourcingPlugin.
 */
final class FilamentEventSourcingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-event-sourcing')
            ->hasConfigFile()
            ->hasViews();
    }
}
