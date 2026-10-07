<?php

namespace JeffersonGoncalves\Filament\Saml2\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use JeffersonGoncalves\Filament\Saml2\Concerns\HasSaml2Identities;

class User extends Authenticatable implements FilamentUser
{
    use HasSaml2Identities;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
