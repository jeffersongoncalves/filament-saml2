<?php

namespace JeffersonGoncalves\Filament\Saml2\Listeners;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JeffersonGoncalves\Filament\Saml2\Exceptions\Saml2AuthenticationException;
use JeffersonGoncalves\Filament\Saml2\Models\Saml2Identity;
use JeffersonGoncalves\Filament\Saml2\Saml2Plugin;
use JeffersonGoncalves\LaravelSaml2\Events\SignedIn;
use JeffersonGoncalves\LaravelSaml2\Saml2User;
use LogicException;
use Throwable;

/**
 * Logs the SAML user into the panel whose callback route is the RelayState.
 *
 * The panel travels in the RelayState, not the session: the IdP posts cross-site,
 * so SameSite=Lax session cookies are not sent to the ACS endpoint.
 */
class AuthenticateSaml2User
{
    public function handle(SignedIn $event): void
    {
        $panel = $this->panelFor($event->request);

        if ($panel === null) {
            return; // Not a Filament login; leave it to the application's own listeners.
        }

        /** @var Saml2Plugin $plugin */
        $plugin = $panel->getPlugin('filament-saml2');

        try {
            $user = $this->resolveUser($event->user, $plugin);
        } catch (Saml2AuthenticationException $exception) {
            $this->notify($exception->getMessage());

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->notify(__('filament-saml2::filament-saml2.errors.failed'));

            return;
        }

        Auth::guard($plugin->getGuard($panel))->login($user);

        $event->request->session()->regenerate();
    }

    protected function panelFor(Request $request): ?Panel
    {
        $relayState = $request->input('RelayState');

        if (! is_string($relayState) || $relayState === '') {
            return null;
        }

        $path = '/'.ltrim((string) parse_url($relayState, PHP_URL_PATH), '/');

        foreach (Filament::getPanels() as $panel) {
            if ($panel->hasPlugin('filament-saml2')
                && $path === route("filament.{$panel->getId()}.saml2.callback", absolute: false)) {
                return $panel;
            }
        }

        return null;
    }

    protected function resolveUser(Saml2User $samlUser, Saml2Plugin $plugin): Authenticatable&Model
    {
        $tenant = $samlUser->tenant();

        if (! $plugin->allowsTenant($tenant)) {
            throw Saml2AuthenticationException::tenantNotAllowed();
        }

        return DB::transaction(function () use ($samlUser, $plugin, $tenant): Authenticatable&Model {
            $identity = Saml2Identity::query()
                ->where('tenant_uuid', $tenant->uuid)
                ->where('name_id', $samlUser->nameId())
                ->first();

            $user = $identity?->authenticatable;

            if (! $user instanceof Model) {
                $user = $this->findOrCreateUser($samlUser, $plugin);
            }

            if (! $user instanceof Authenticatable) {
                throw new LogicException(get_class($user).' is not authenticatable.');
            }

            ($identity ?? new Saml2Identity)->fill([
                'authenticatable_type' => $user->getMorphClass(),
                'authenticatable_id' => $user->getKey(),
                'tenant_uuid' => $tenant->uuid,
                'name_id' => $samlUser->nameId(),
                'email' => $samlUser->email(),
                'saml_attributes' => $samlUser->attributes() ?: null,
            ])->save();

            return $user;
        });
    }

    protected function findOrCreateUser(Saml2User $samlUser, Saml2Plugin $plugin): Model
    {
        if (($resolver = $plugin->getResolveUserUsing()) && ($user = $resolver($samlUser))) {
            return $user;
        }

        $model = $plugin->getUserModel();
        $email = $samlUser->email();

        if ($email !== null && $plugin->shouldMatchUsersByEmail()
            && ($user = $model::query()->where('email', $email)->first())) {
            return $user;
        }

        if (! $plugin->shouldAutoCreateUsers()) {
            throw Saml2AuthenticationException::autoCreateDisabled();
        }

        $attributes = ($mapper = $plugin->getUserAttributesUsing())
            ? $mapper($samlUser)
            : $this->defaultAttributes($samlUser);

        return $model::query()->create($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultAttributes(Saml2User $samlUser): array
    {
        $mapped = array_map(fn ($value) => is_array($value) ? ($value[0] ?? null) : $value, $samlUser->mapped());
        $email = $samlUser->email();

        $name = $mapped['name']
            ?? (trim(($mapped['first_name'] ?? '').' '.($mapped['last_name'] ?? '')) ?: null)
            ?? $email
            ?? $samlUser->nameId();

        return [
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::random(40)),
        ];
    }

    protected function notify(string $message): void
    {
        Notification::make()->title($message)->danger()->send();
    }
}
