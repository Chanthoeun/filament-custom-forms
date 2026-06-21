<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Pages;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateCustomForm extends CreateRecord
{
    use Translatable;

    protected static string $resource = CustomFormResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            CustomFormPlugin::get()->hasTranslations() ? LocaleSwitcher::make() : null,
        ]);
    }
}
