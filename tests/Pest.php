<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\Filament\Saml2\Tests\TestCase;
use JeffersonGoncalves\LaravelSaml2\Events\SignedIn;
use JeffersonGoncalves\LaravelSaml2\Models\Saml2Tenant;
use JeffersonGoncalves\LaravelSaml2\Saml2Auth;
use JeffersonGoncalves\LaravelSaml2\Saml2User;

uses(TestCase::class)->in('Feature');

function tenant(string $key = 'acme'): Saml2Tenant
{
    return Saml2Tenant::query()->create([
        'key' => $key,
        'idp_entity_id' => "https://{$key}.test/idp",
        'idp_login_url' => "https://{$key}.test/sso",
        'idp_x509_cert' => 'MIIC',
    ]);
}

/**
 * @param  array<string, mixed>  $mapped
 */
function samlUser(Saml2Tenant $tenant, string $nameId = 'jane-id', ?string $email = 'jane@acme.test', array $mapped = ['name' => 'Jane Doe']): Saml2User
{
    $user = Mockery::mock(Saml2User::class);
    $user->shouldReceive('tenant')->andReturn($tenant);
    $user->shouldReceive('nameId')->andReturn($nameId);
    $user->shouldReceive('email')->andReturn($email);
    $user->shouldReceive('mapped')->andReturn($mapped);
    $user->shouldReceive('attributes')->andReturn(['mail' => [$email]]);

    return $user;
}

function fireSignedIn(Saml2User $user, string $relayState = '/admin/saml2/callback'): void
{
    $request = Request::create('/saml2/x/acs', 'POST', ['RelayState' => $relayState]);
    $request->setLaravelSession(app('session.store'));

    event(new SignedIn($user, Mockery::mock(Saml2Auth::class), $request));
}
