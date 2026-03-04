<?php

namespace Dcx\FilamentCustomForms\Filament\Resources\CustomForms\Pages;

use Dcx\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomForm extends EditRecord
{
    protected static string $resource = CustomFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
