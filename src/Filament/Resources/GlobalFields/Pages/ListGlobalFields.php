<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\GlobalFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListGlobalFields extends ListRecords
{
    use Translatable;

    protected static string $resource = GlobalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            CustomFormPlugin::get()->hasTranslations() ? LocaleSwitcher::make() : null,
            CreateAction::make(),
        ]);
    }
}
