<?php

namespace LaraSpace\FilamentCustomForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;



class CustomForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'schema',
        'accounting_config',
        'is_active',
        'allowed_roles',
        'enable_workflow',
        'reviewer_roles',
        'approver_roles',
        'reviewer_users',
        'approver_users',
    ];

    protected $casts = [
        'schema' => 'array',
        'accounting_config' => 'array',
        'is_active' => 'boolean',
        'allowed_roles' => 'array',
        'enable_workflow' => 'boolean',
        'reviewer_roles' => 'array',
        'approver_roles' => 'array',
        'reviewer_users' => 'array',
        'approver_users' => 'array',
    ];



    public function entries(): HasMany
    {
        return $this->hasMany(CustomFormEntry::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CustomFormField::class)->orderBy('sort');
    }
}
