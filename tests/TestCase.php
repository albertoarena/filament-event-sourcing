<?php

declare(strict_types=1);

namespace Albertoarena\FilamentEventSourcing\Tests;

use Albertoarena\FilamentEventSourcing\FilamentEventSourcingServiceProvider;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\MinimalPanelProvider;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostProjector;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\TestPanelProvider;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\User;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\ViewErrorBag;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\EventSourcing\EventSourcingServiceProvider;
use Spatie\EventSourcing\Projectionist;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->runSpatieMigrations();
        $this->runFixtureMigrations();

        app(Projectionist::class)->addProjector(PostProjector::class);

        view()->share('errors', new ViewErrorBag);
    }

    protected function getPackageProviders($app): array
    {
        return [
            // Spatie event sourcing
            EventSourcingServiceProvider::class,

            // Filament and its dependencies.
            // SupportServiceProvider rebinds Livewire's DataStore to DataStoreOverride, so
            // LivewireServiceProvider must register AFTER it: Livewire's instance() then captures
            // the override as the shared singleton. Registering Livewire first would leave the
            // DataStore unshared and break component rendering in tests.
            BladeCaptureDirectiveServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,

            // This package and the test panels
            FilamentEventSourcingServiceProvider::class,
            TestPanelProvider::class,
            MinimalPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function actingAsUser(): Authenticatable
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function runSpatieMigrations(): void
    {
        $base = dirname(__DIR__).'/vendor/spatie/laravel-event-sourcing/database/migrations';

        foreach (['create_stored_events_table', 'create_snapshots_table'] as $name) {
            (include "{$base}/{$name}.php.stub")->up();
        }
    }

    private function runFixtureMigrations(): void
    {
        $base = __DIR__.'/Fixtures/database/migrations';

        foreach (['create_users_table', 'create_posts_table', 'create_articles_table', 'create_comments_table'] as $name) {
            (include "{$base}/{$name}.php")->up();
        }
    }
}
