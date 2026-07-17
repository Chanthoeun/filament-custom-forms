<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages;

use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\GlobalFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlobalField extends EditRecord
{
    protected static string $resource = GlobalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn ($record) => \Chanthoeun\FilamentCustomForms\Models\CustomFormField::where('global_field_id', $record->id)->exists()),
        ];
    }
}
