<?php

namespace Chanthoeun\FilamentCustomForms\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class CustomForm extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = ['name', 'schema'];

    protected $fillable = [
        'name',
        'slug',
        'schema',
        'is_active',
        'allowed_roles',
        'panel_access',
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
        'allowed_roles' => 'array',
        'panel_access' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(CustomFormEntry::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CustomFormField::class)->orderBy('sort');
    }

    public function canAccessInPanel(string $panelId, Authenticatable $user): bool
    {
        // First check if there are any specific panel restrictions defined
        if (empty($this->panel_access)) {
            // If no panel configurations, fallback to global allowed_roles if it exists
            if (! empty($this->allowed_roles)) {
                if (method_exists($user, 'hasAnyRole')) {
                    return $user->hasAnyRole($this->allowed_roles);
                }

                return false; // Roles required but user doesn't support hasAnyRole
            }

            return false; // Explicit opt-in required for panel access
        }

        // Check if the current panel is in the panel_access rules
        $panelConfig = collect($this->panel_access)->firstWhere('panel_id', $panelId);

        if (! $panelConfig) {
            // If panel_access array exists but doesn't include this panel, deny access.
            // (Or we could allow access? Usually, if rules exist, anything not explicitly allowed is denied).
            return false;
        }

        $roles = data_get($panelConfig, 'allowed_roles', []);

        $formPermissions = data_get($panelConfig, 'custom_form_permissions', []);
        $entryPermissions = data_get($panelConfig, 'custom_form_entry_permissions', []);
        $legacyPermissions = data_get($panelConfig, 'allowed_permissions', []);
        $permissions = array_unique(array_merge($formPermissions, $entryPermissions, $legacyPermissions));
        $users = data_get($panelConfig, 'allowed_users', []);

        // If explicitly in the allowed users list, grant access
        $userId = (string) $user->getAuthIdentifier();
        $stringUsers = array_map('strval', $users);
        if (! empty($users) && in_array($userId, $stringUsers, true)) {
            return true;
        }

        // If no roles and no permissions are defined, but users ARE defined, access is denied
        if (empty($roles) && empty($permissions)) {
            return empty($users); // True if no restrictions at all, false if restricted to specific users
        }

        $hasRole = ! empty($roles) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles);

        return $hasRole;
    }

    public function hasPermissionInPanel(string $panelId, string $permission): bool
    {
        if (empty($this->panel_access)) {
            return true;
        }

        $panelConfig = collect($this->panel_access)->firstWhere('panel_id', $panelId);

        if (! $panelConfig) {
            return true;
        }

        $formPermissions = data_get($panelConfig, 'custom_form_permissions', []);
        $entryPermissions = data_get($panelConfig, 'custom_form_entry_permissions', []);
        $legacyPermissions = data_get($panelConfig, 'allowed_permissions', []);
        $permissions = array_unique(array_merge($formPermissions, $entryPermissions, $legacyPermissions));

        if (empty($permissions)) {
            return true; // If no specific permissions are configured, allow default policy to handle it
        }

        return in_array($permission, $permissions);
    }

    public function shouldIsolateUsersInPanel(string $panelId): bool
    {
        if (empty($this->panel_access)) {
            return false;
        }

        $panelConfig = collect($this->panel_access)->firstWhere('panel_id', $panelId);

        if (! $panelConfig) {
            return false;
        }

        return (bool) data_get($panelConfig, 'isolate_users', false);
    }
}
