<div class="filament-hidden">

![Filament SAML2](https://raw.githubusercontent.com/jeffersongoncalves/filament-saml2/3.x/art/jeffersongoncalves-filament-saml2.png)

</div>

# Filament SAML2

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-support-FFDD00?style=flat-square&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/jeffersongoncalves)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/filament-saml2.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-saml2)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-saml2/phpstan.yml?branch=3.x&label=phpstan&style=flat-square)](https://github.com/jeffersongoncalves/filament-saml2/actions/workflows/phpstan.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-saml2/tests.yml?branch=3.x&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/filament-saml2/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/filament-saml2.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-saml2)

SAML 2.0 single sign-on for Filament v5 panels, powered by [`jeffersongoncalves/laravel-saml2`](https://github.com/jeffersongoncalves/laravel-saml2). Every Identity Provider registered in `laravel-saml2` (Entra ID, Okta, Google Workspace, Keycloak, ADFS, OneLogin…) gets a button on the panel login page; users are provisioned on first sign-in and linked to their SAML identity in a polymorphic table, so the host application's `users` table is never altered.

## Compatibility

| Plugin Version | Filament |
|----------------|----------|
| 1.x            | ^3.2     |
| 2.x            | ^4.0     |
| 3.x            | ^5.0     |

## Installation

```bash
composer require jeffersongoncalves/filament-saml2:"^3.0"
```

Publish and run the migrations (`saml2_tenants` comes from `laravel-saml2`, `saml2_identities` from this plugin):

```bash
php artisan vendor:publish --tag="filament-saml2-migrations"
php artisan migrate
```

Optionally publish the configuration and translations:

```bash
php artisan vendor:publish --tag="filament-saml2-config"
php artisan vendor:publish --tag="filament-saml2-translations"
```

Register your Identity Providers with the `laravel-saml2` commands, e.g.:

```bash
php artisan saml2:create-tenant --key=acme --metadata-url="https://idp.acme.com/metadata.xml"
```

See the [`laravel-saml2` README](https://github.com/jeffersongoncalves/laravel-saml2#readme) for the URLs to configure at the IdP, certificates, per-tenant settings and proxies.

## Registering the plugin in a panel

```php
use JeffersonGoncalves\Filament\Saml2\Saml2Plugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->login()
        ->plugin(Saml2Plugin::make());
}
```

The login page now shows a **Sign in with …** button per tenant (labelled with `metadata.name`, the tenant key or the IdP entity ID).

### Options

```php
Saml2Plugin::make()
    ->tenants(['acme', 'okta'])        // only these tenants (key or UUID); null = all
    ->guard('admin')                   // defaults to the panel auth guard
    ->userModel(\App\Models\Admin::class)
    ->autoCreateUsers(false)           // only let in users that already exist
    ->matchUsersByEmail(false)         // only link by NameID, never by e-mail
    ->position('before');              // buttons before the login form
```

## How it works

1. The button sends the user to `saml2.login` with `returnTo` set to the panel callback route (`filament.{panel}.saml2.callback`, path `/{panel}/saml2/callback`).
2. The IdP posts the assertion to `laravel-saml2`'s ACS endpoint, which fires `SignedIn`.
3. The plugin listener reads the panel from the `RelayState`, resolves the user and logs them into the panel guard.
4. The ACS redirects to the callback route, which lands the user on the panel (or back on the login page with a notification if the sign-in was refused).

The panel travels in the `RelayState` rather than the session because the IdP posts cross-site and `SameSite=Lax` cookies are not sent with that request. `SignedIn` events whose `RelayState` is not a panel callback are ignored, so your own listeners keep working for the rest of the app.

For IdP-initiated sign-in, set the tenant `relay_state_url` to the panel callback path (`/admin/saml2/callback`).

### User resolution

1. The `saml2_identities` row for the tenant UUID + NameID.
2. `resolveUserUsing()`, when set.
3. The user with the same e-mail (`Saml2User::email()`), unless `matchUsersByEmail(false)`.
4. A new user (`name`, `email`, random `password`), unless `autoCreateUsers(false)`.

The identity is then created or refreshed with the latest e-mail and attributes.

> **Security:** matching by e-mail trusts the IdP to only assert e-mails it owns. If a panel allows an IdP you do not control (e.g. a customer-managed tenant), limit it with `->tenants([...])` and disable `matchUsersByEmail`.

Add the `HasSaml2Identities` trait to expose the relation on your user model:

```php
use JeffersonGoncalves\Filament\Saml2\Concerns\HasSaml2Identities;

class User extends Authenticatable
{
    use HasSaml2Identities;
}
```

## Customising user provisioning

```php
use JeffersonGoncalves\LaravelSaml2\Saml2User;

Saml2Plugin::make()
    ->userAttributesUsing(fn (Saml2User $saml) => [
        'name' => $saml->mapped()['name'] ?? $saml->email(),
        'email' => $saml->email(),
        'department' => $saml->first('department'),
        'password' => bcrypt(\Illuminate\Support\Str::random(40)),
    ])
    ->resolveUserUsing(fn (Saml2User $saml) => \App\Models\User::query()
        ->where('employee_id', $saml->first('employeeNumber'))
        ->first()); // null falls back to the default lookup
```

## Logout

Filament's logout ends the local session. To also end the IdP session (Single Logout), send the user to `laravel-saml2`'s logout route:

```php
use JeffersonGoncalves\LaravelSaml2\Facades\Saml2;

redirect(Saml2::logoutUrl(returnTo: filament()->getLoginUrl()));
```

IdP-initiated logout is handled by `laravel-saml2`; set `saml2.logout.guard` to the panel guard.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
