<?php

namespace JeffersonGoncalves\Filament\Saml2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $authenticatable_type
 * @property int|string $authenticatable_id
 * @property string $tenant_uuid
 * @property string $name_id
 * @property string|null $email
 * @property array<string, list<string>>|null $saml_attributes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Saml2Identity extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('filament-saml2.identities_table', 'saml2_identities');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'saml_attributes' => 'array',
        ];
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }
}
