<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Pages;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListCustomForms extends ListRecords
{
    use Translatable;

    protected static string $resource = CustomFormResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            CustomFormPlugin::get()->hasTranslations() ? LocaleSwitcher::make() : null,
            Actions\CreateAction::make(),
        ]);
    }
}
