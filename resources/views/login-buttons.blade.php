@php
    /** @var \Filament\Panel $panel */
    /** @var \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\LaravelSaml2\Models\Saml2Tenant> $tenants */
    $returnTo = route("filament.{$panel->getId()}.saml2.callback", absolute: false);
@endphp

@if ($tenants->isNotEmpty())
    <div class="fi-saml2-login-buttons" style="display: grid; gap: 0.75rem;">
        @foreach ($tenants as $tenant)
            <x-filament::button
                tag="a"
                :href="\JeffersonGoncalves\LaravelSaml2\Facades\Saml2::loginUrl($tenant, $returnTo)"
                color="gray"
                outlined
                icon="heroicon-o-key"
            >
                {{ __('filament-saml2::filament-saml2.button.label', ['name' => $tenant->metadata['name'] ?? $tenant->key ?? $tenant->idp_entity_id]) }}
            </x-filament::button>
        @endforeach
    </div>
@endif
