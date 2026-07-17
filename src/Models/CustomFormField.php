<?php

namespace Chanthoeun\FilamentCustomForms\Models;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CustomFormField extends Model
{
    use \Chanthoeun\FilamentCustomForms\Models\Concerns\HasParsedOptions, HasFactory, HasTranslations;

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

    public $translatable = ['label'];

    protected $fillable = [
        'custom_form_id',
        'parent_id',
        'global_field_id',
        'name',
        'label',
        'type',
        'required',
        'options',
        'sort',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
        'sort' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(CustomForm::class, 'custom_form_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort');
    }
}
