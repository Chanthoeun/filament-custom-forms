<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\GlobalFieldResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateGlobalField extends CreateRecord
{
    use Translatable;

    protected static string $resource = GlobalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            CustomFormPlugin::get()->hasTranslations() ? LocaleSwitcher::make() : null,
        ]);
    }
}
