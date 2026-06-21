<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Pages;

use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomForm extends CreateRecord
{
    use \LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

    protected static string $resource = CustomFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher::make(),
        ];
    }
}
