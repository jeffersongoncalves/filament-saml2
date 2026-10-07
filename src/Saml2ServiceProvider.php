<?php

namespace JeffersonGoncalves\Filament\Saml2;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class Saml2ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-saml2')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
