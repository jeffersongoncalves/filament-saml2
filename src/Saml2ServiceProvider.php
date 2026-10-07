<?php

namespace JeffersonGoncalves\Filament\Saml2;

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\Filament\Saml2\Listeners\AuthenticateSaml2User;
use JeffersonGoncalves\LaravelSaml2\Events\SignedIn;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class Saml2ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-saml2')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigration('create_saml2_identities_table');
    }

    public function packageBooted(): void
    {
        Event::listen(SignedIn::class, AuthenticateSaml2User::class);
    }
}
