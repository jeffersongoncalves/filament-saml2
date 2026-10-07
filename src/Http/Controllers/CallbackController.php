<?php

namespace JeffersonGoncalves\Filament\Saml2\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use JeffersonGoncalves\Filament\Saml2\Saml2Plugin;
use LogicException;

/**
 * RelayState target of the panel login buttons. The user was already logged in by
 * AuthenticateSaml2User during the ACS request; this only lands them on the panel.
 */
class CallbackController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $panel = Filament::getCurrentPanel() ?? throw new LogicException('No Filament panel for the SAML2 callback.');

        /** @var Saml2Plugin $plugin */
        $plugin = $panel->getPlugin('filament-saml2');

        if (! Auth::guard($plugin->getGuard($panel))->check()) {
            return redirect()->to($panel->getLoginUrl() ?? $panel->getUrl());
        }

        return redirect()->to($panel->getUrl());
    }
}
