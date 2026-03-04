<?php

namespace LaraSpace\FilamentCustomForms\Models;

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
        'transaction_id',
        'status',
        'season_id',
        'farmer_id',
        'land_id',
        'block_id',
    ];

    protected $casts = [
        'data' => 'array',
        'status' => \LaraSpace\FilamentCustomForms\Enums\CustomFormEntryStatus::class,
    ];

    public function customForm(): BelongsTo
    {
        return $this->belongsTo(CustomForm::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?? 'App\Models\User', 'created_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo('App\Models\Transaction');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo('App\Models\Season');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Farmer');
    }

    public function land(): BelongsTo
    {
        return $this->belongsTo('App\Models\Land');
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo('App\Models\Block');
    }
}
