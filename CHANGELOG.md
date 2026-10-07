# Changelog

All notable changes to `filament-saml2` will be documented in this file.

## 1.0.0 - 2026-10-07

First release of `filament-saml2` for **Filament v3**.

### Features

- SAML 2.0 single sign-on for Filament panels, powered by [`jeffersongoncalves/laravel-saml2`](https://github.com/jeffersongoncalves/laravel-saml2)
- One "Sign in with …" button per Identity Provider (tenant) on the panel login page
- Panel resolved from the RelayState (works with SameSite=Lax cookies), multi-panel ready
- User resolution by NameID identity, custom resolver, e-mail, or auto-creation
- Polymorphic `saml2_identities` table and `HasSaml2Identities` trait — the `users` table is never altered
- Per-panel options: `tenants`, `guard`, `userModel`, `autoCreateUsers`, `matchUsersByEmail`, `position`, `userAttributesUsing`, `resolveUserUsing`
- Translations: `en`, `pt_BR`, `es`

### Installation

```bash
composer require jeffersongoncalves/filament-saml2:"^1.0"

```
## Unreleased
