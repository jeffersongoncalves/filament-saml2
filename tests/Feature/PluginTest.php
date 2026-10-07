<?php

use Filament\Facades\Filament;
use JeffersonGoncalves\Filament\Saml2\Saml2Plugin;
use JeffersonGoncalves\Filament\Saml2\Tests\Fixtures\User;

it('registers the plugin and its callback route', function (): void {
    expect(Filament::getPanel('admin')->getPlugin('filament-saml2'))->toBeInstanceOf(Saml2Plugin::class)
        ->and(route('filament.admin.saml2.callback', absolute: false))->toBe('/admin/saml2/callback');
});

it('renders one login button per tenant', function (): void {
    tenant('acme');
    tenant('okta');

    $html = view('filament-saml2::login-buttons', [
        'panel' => Filament::getPanel('admin'),
        'tenants' => Saml2Plugin::make()->getTenants(),
    ])->render();

    expect($html)
        ->toContain('Sign in with acme')
        ->toContain('Sign in with okta')
        ->toContain('/saml2/acme/login?returnTo=%2Fadmin%2Fsaml2%2Fcallback');
});

it('renders nothing without tenants', function (): void {
    $html = view('filament-saml2::login-buttons', [
        'panel' => Filament::getPanel('admin'),
        'tenants' => Saml2Plugin::make()->getTenants(),
    ])->render();

    expect(trim($html))->toBe('');
});

it('only lists the allowed tenants', function (): void {
    tenant('acme');
    tenant('okta');

    $plugin = Saml2Plugin::make()->tenants(['okta']);

    expect($plugin->getTenants()->pluck('key')->all())->toBe(['okta']);
});

it('sends authenticated users from the callback to the panel', function (): void {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@acme.test', 'password' => 'secret']);

    $this->actingAs($user)->get('/admin/saml2/callback')->assertRedirect('/admin');
});

it('sends guests from the callback back to the login page', function (): void {
    $this->get('/admin/saml2/callback')->assertRedirect('/admin/login');
});
