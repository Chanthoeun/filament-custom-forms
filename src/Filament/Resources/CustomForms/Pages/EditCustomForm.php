<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Pages;

use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomForm extends EditRecord
{
    use \LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

    protected static string $resource = CustomFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
