<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Pages;

use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomForms extends ListRecords
{
    use \LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

    protected static string $resource = CustomFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
