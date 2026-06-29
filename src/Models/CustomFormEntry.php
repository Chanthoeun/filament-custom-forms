<?php

namespace Chanthoeun\FilamentCustomForms\Models;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class CustomFormEntry extends Model
{
    use HasFactory, HasTranslations;

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

    public $translatable = ['data'];

    protected $fillable = [
        'custom_form_id',
        'data',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function customForm(): BelongsTo
    {
        return $this->belongsTo(CustomFormPlugin::get()->getFormModel());
    }

    public function creator(): BelongsTo
    {
        $userModel = CustomFormPlugin::get()->getUserModel();

        if (! class_exists($userModel)) {
            return $this->belongsTo(Model::class, 'created_by')->withDefault([
                'name' => 'Guest',
            ]);
        }

        return $this->belongsTo($userModel, 'created_by')->withDefault([
            'name' => 'Guest',
        ]);
    }
}
