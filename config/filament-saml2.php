<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model looked up and provisioned from SAML assertions. Each
    | panel may override it on the plugin instance via ->userModel(...).
    |
    */
    'user_model' => env('SAML2_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | SAML2 identities table
    |--------------------------------------------------------------------------
    |
    | Polymorphic link between an authenticatable and its SAML identities
    | (tenant UUID + NameID). Adjust it when publishing the migration.
    |
    */
    'identities_table' => 'saml2_identities',

    /*
    |--------------------------------------------------------------------------
    | Auto-create users
    |--------------------------------------------------------------------------
    |
    | Create a local user the first time an unknown NameID signs in. Disable
    | it when users must be provisioned out of band.
    |
    */
    'auto_create_users' => true,

    /*
    |--------------------------------------------------------------------------
    | Match users by e-mail
    |--------------------------------------------------------------------------
    |
    | Link an unknown NameID to the existing user with the same e-mail. Disable
    | it when an IdP you do not control (e.g. a customer's) is allowed on the
    | panel, as it could assert any e-mail address.
    |
    */
    'match_users_by_email' => true,

    /*
    |--------------------------------------------------------------------------
    | Login buttons
    |--------------------------------------------------------------------------
    |
    | Render the IdP buttons "before" or "after" the panel login form.
    |
    */
    'button' => [
        'position' => 'after',
    ],
];
