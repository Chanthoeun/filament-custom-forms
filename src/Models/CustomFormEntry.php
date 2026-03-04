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
        'status' => \App\Enums\CustomFormEntryStatus::class,
    ];



    public function customForm(): BelongsTo
    {
        return $this->belongsTo(CustomForm::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function land(): BelongsTo
    {
        return $this->belongsTo(Land::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
}
