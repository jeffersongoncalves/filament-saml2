<?php

namespace JeffersonGoncalves\Filament\Saml2\Exceptions;

use RuntimeException;

class Saml2AuthenticationException extends RuntimeException
{
    public static function autoCreateDisabled(): self
    {
        return new self(__('filament-saml2::filament-saml2.errors.auto_create_disabled'));
    }

    public static function tenantNotAllowed(): self
    {
        return new self(__('filament-saml2::filament-saml2.errors.tenant_not_allowed'));
    }
}
