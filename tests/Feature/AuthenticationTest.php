<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use JeffersonGoncalves\Filament\Saml2\Models\Saml2Identity;
use JeffersonGoncalves\Filament\Saml2\Saml2Plugin;
use JeffersonGoncalves\Filament\Saml2\Tests\Fixtures\User;

function plugin(): Saml2Plugin
{
    return Filament::getPanel('admin')->getPlugin('filament-saml2');
}

it('creates the user, links the identity and logs in', function (): void {
    $tenant = tenant();

    fireSignedIn(samlUser($tenant));

    $user = User::query()->sole();

    expect($user->name)->toBe('Jane Doe')
        ->and($user->email)->toBe('jane@acme.test')
        ->and(Auth::guard('web')->id())->toBe($user->id)
        ->and($user->saml2Identities()->sole())
        ->tenant_uuid->toBe($tenant->uuid)
        ->name_id->toBe('jane-id');
});

it('logs in the user linked to the NameID even after the e-mail changed', function (): void {
    $tenant = tenant();
    fireSignedIn(samlUser($tenant));
    Auth::guard('web')->logout();

    fireSignedIn(samlUser($tenant, email: 'jane.doe@acme.test'));

    expect(User::query()->count())->toBe(1)
        ->and(Auth::guard('web')->check())->toBeTrue()
        ->and(Saml2Identity::query()->sole()->email)->toBe('jane.doe@acme.test');
});

it('links an existing user by e-mail', function (): void {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@acme.test', 'password' => 'secret']);

    fireSignedIn(samlUser(tenant()));

    expect(Auth::guard('web')->id())->toBe($user->id)
        ->and(User::query()->count())->toBe(1);
});

it('does not match by e-mail when disabled', function (): void {
    User::query()->create(['name' => 'Jane', 'email' => 'jane@acme.test', 'password' => 'secret']);
    plugin()->matchUsersByEmail(false)->autoCreateUsers(false);

    fireSignedIn(samlUser(tenant()));

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('refuses unknown users when auto-creation is disabled', function (): void {
    plugin()->autoCreateUsers(false);

    fireSignedIn(samlUser(tenant()));

    expect(User::query()->count())->toBe(0)
        ->and(Auth::guard('web')->check())->toBeFalse()
        ->and(session('filament.notifications'))->toHaveCount(1);
});

it('refuses tenants that are not allowed on the panel', function (): void {
    plugin()->tenants(['okta']);

    fireSignedIn(samlUser(tenant('acme')));

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('ignores sign-ins that do not target a panel', function (): void {
    fireSignedIn(samlUser(tenant()), relayState: '/dashboard');

    expect(User::query()->count())->toBe(0)
        ->and(Auth::guard('web')->check())->toBeFalse();
});

it('uses the custom resolver and attribute mapper', function (): void {
    plugin()->userAttributesUsing(fn ($saml) => [
        'name' => 'Mapped '.$saml->nameId(),
        'email' => $saml->email(),
        'password' => 'secret',
    ]);

    fireSignedIn(samlUser(tenant()));

    expect(User::query()->sole()->name)->toBe('Mapped jane-id');

    $other = User::query()->create(['name' => 'Other', 'email' => 'other@acme.test', 'password' => 'secret']);
    plugin()->resolveUserUsing(fn () => $other);
    Auth::guard('web')->logout();

    fireSignedIn(samlUser(tenant('okta'), nameId: 'bob'));

    expect(Auth::guard('web')->id())->toBe($other->id);
});
