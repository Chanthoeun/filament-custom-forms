<?php

namespace Dcx\FilamentCustomForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFormEntry extends Model
{
    use HasFactory;

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
        return $this->belongsTo(config('filament-custom-forms.models.form', CustomForm::class));
    }

    public function creator(): BelongsTo
    {
        $userModel = config('filament-custom-forms.models.user') ?? config('auth.providers.users.model') ?? 'App\Models\User';

        if (!class_exists($userModel)) {
            return $this->belongsTo(\Illuminate\Database\Eloquent\Model::class, 'created_by')->withDefault([
                'name' => 'Guest',
            ]);
        }

        return $this->belongsTo($userModel, 'created_by')->withDefault([
            'name' => 'Guest',
        ]);
    }
}
