<?php

namespace LaraSpace\FilamentCustomForms\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomFormEntryStatus: string implements HasLabel, HasColor
{
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Approved = 'approved';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Submitted => __('custom_form_entry.submitted'),
            self::Reviewed => __('custom_form_entry.reviewed'),
            self::Approved => __('custom_form_entry.approved'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Submitted => 'gray',
            self::Reviewed => 'warning',
            self::Approved => 'success',
        };
    }
}
