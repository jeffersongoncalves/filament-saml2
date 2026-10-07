<?php

namespace JeffersonGoncalves\Filament\Saml2\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Support\SupportServiceProvider;
use JeffersonGoncalves\Filament\Saml2\Saml2ServiceProvider;
use JeffersonGoncalves\Filament\Saml2\Tests\Fixtures\TestPanelProvider;
use JeffersonGoncalves\Filament\Saml2\Tests\Fixtures\User;
use JeffersonGoncalves\LaravelSaml2\Saml2ServiceProvider as LaravelSaml2ServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            LaravelSaml2ServiceProvider::class,
            Saml2ServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('auth.providers.users.model', User::class);
        config()->set('filament-saml2.user_model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->artisan('migrate')->run();

        (include __DIR__.'/../database/migrations/create_saml2_identities_table.php.stub')->up();
    }
}
