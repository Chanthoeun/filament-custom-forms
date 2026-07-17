<?php

namespace Chanthoeun\FilamentCustomForms\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GlobalField extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'label',
        'type',
        'required',
        'config',
        'options',
    ];

    public $translatable = ['label'];

    protected $casts = [
        'required' => 'boolean',
        'config' => 'array',
        'options' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($globalField) {
            if (\Chanthoeun\FilamentCustomForms\Models\CustomFormField::where('global_field_id', $globalField->id)->exists()) {
                throw new \Exception('Cannot delete this global field because it is being used by one or more custom forms.');
            }
        });
    }
}
