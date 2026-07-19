<?php

namespace Chanthoeun\FilamentCustomForms\Models;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Models\Concerns\HasParsedOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GlobalField extends Model
{
    use HasFactory;
    use HasParsedOptions;
    use HasTranslations;

    public function toArray()
    {
        $attributes = parent::toArray();

        $hasTranslations = false;
        try {
            $hasTranslations = CustomFormPlugin::get()->hasTranslations();
        } catch (\Throwable $e) {
            $hasTranslations = false;
        }

        if (! $hasTranslations) {
            foreach ($this->getTranslatableAttributes() as $field) {
                if (array_key_exists($field, $attributes)) {
                    $attributes[$field] = $this->getAttribute($field);
                }
            }
        }

        return $attributes;
    }

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
            if (CustomFormField::where('global_field_id', $globalField->id)->exists()) {
                throw new \Exception('Cannot delete this global field because it is being used by one or more custom forms.');
            }
        });
    }
}
