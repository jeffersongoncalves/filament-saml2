<?php

namespace JeffersonGoncalves\Filament\Saml2\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use JeffersonGoncalves\Filament\Saml2\Models\Saml2Identity;

/**
 * @mixin Model
 */
trait HasSaml2Identities
{
    public function saml2Identities(): MorphMany
    {
        return $this->morphMany(Saml2Identity::class, 'authenticatable');
    }
}
