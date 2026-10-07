<?php

namespace JeffersonGoncalves\Filament\Saml2;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\Filament\Saml2\Http\Controllers\CallbackController;
use JeffersonGoncalves\LaravelSaml2\Facades\Saml2;
use JeffersonGoncalves\LaravelSaml2\Models\Saml2Tenant;
use JeffersonGoncalves\LaravelSaml2\Saml2User;

class Saml2Plugin implements Plugin
{
    protected ?string $guard = null;

    protected ?string $userModel = null;

    /** @var list<string>|null */
    protected ?array $tenants = null;

    protected ?bool $autoCreateUsers = null;

    protected ?bool $matchUsersByEmail = null;

    protected ?string $buttonPosition = null;

    /** @var (Closure(Saml2User): array<string, mixed>)|null */
    protected ?Closure $userAttributesUsing = null;

    /** @var (Closure(Saml2User): ?Model)|null */
    protected ?Closure $resolveUserUsing = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-saml2';
    }

    public function register(Panel $panel): void
    {
        $panel->routes(function (): void {
            Route::get('saml2/callback', CallbackController::class)->name('saml2.callback');
        });

        $panel->renderHook(
            $this->getButtonPosition() === 'before'
                ? PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE
                : PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): View => app(ViewFactory::class)->make('filament-saml2::login-buttons', [
                'panel' => $panel,
                'tenants' => $this->getTenants(),
            ]),
        );
    }

    public function boot(Panel $panel): void {}

    public function guard(?string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    public function getGuard(Panel $panel): string
    {
        return $this->guard ?? $panel->getAuthGuard();
    }

    public function userModel(?string $userModel): static
    {
        $this->userModel = $userModel;

        return $this;
    }

    /**
     * @return class-string<Model>
     */
    public function getUserModel(): string
    {
        /** @var class-string<Model> */
        return $this->userModel ?? config('filament-saml2.user_model', 'App\\Models\\User');
    }

    /**
     * Limit the panel to these tenants (key or UUID). Null allows every tenant.
     *
     * @param  list<string>|null  $tenants
     */
    public function tenants(?array $tenants): static
    {
        $this->tenants = $tenants;

        return $this;
    }

    /**
     * @return Collection<int, Saml2Tenant>
     */
    public function getTenants(): Collection
    {
        $query = Saml2::tenants();

        if ($this->tenants !== null) {
            $query->where(fn ($query) => $query->whereIn('key', $this->tenants)->orWhereIn('uuid', $this->tenants));
        }

        return $query->get();
    }

    public function allowsTenant(Saml2Tenant $tenant): bool
    {
        return $this->tenants === null
            || in_array($tenant->uuid, $this->tenants, true)
            || ($tenant->key !== null && in_array($tenant->key, $this->tenants, true));
    }

    public function autoCreateUsers(bool $condition = true): static
    {
        $this->autoCreateUsers = $condition;

        return $this;
    }

    public function shouldAutoCreateUsers(): bool
    {
        return $this->autoCreateUsers ?? (bool) config('filament-saml2.auto_create_users', true);
    }

    /**
     * Link an unknown NameID to the local user with the same e-mail. Only safe when every
     * allowed IdP is trusted to assert e-mails it owns.
     */
    public function matchUsersByEmail(bool $condition = true): static
    {
        $this->matchUsersByEmail = $condition;

        return $this;
    }

    public function shouldMatchUsersByEmail(): bool
    {
        return $this->matchUsersByEmail ?? (bool) config('filament-saml2.match_users_by_email', true);
    }

    public function position(string $position): static
    {
        $this->buttonPosition = $position;

        return $this;
    }

    public function getButtonPosition(): string
    {
        return $this->buttonPosition ?? config('filament-saml2.button.position', 'after');
    }

    /**
     * @param  Closure(Saml2User): array<string, mixed>  $callback
     */
    public function userAttributesUsing(Closure $callback): static
    {
        $this->userAttributesUsing = $callback;

        return $this;
    }

    /**
     * @return (Closure(Saml2User): array<string, mixed>)|null
     */
    public function getUserAttributesUsing(): ?Closure
    {
        return $this->userAttributesUsing;
    }

    /**
     * Find (or create) the local user yourself. Return null to fall back to the default lookup.
     *
     * @param  Closure(Saml2User): ?Model  $callback
     */
    public function resolveUserUsing(Closure $callback): static
    {
        $this->resolveUserUsing = $callback;

        return $this;
    }

    /**
     * @return (Closure(Saml2User): ?Model)|null
     */
    public function getResolveUserUsing(): ?Closure
    {
        return $this->resolveUserUsing;
    }
}
