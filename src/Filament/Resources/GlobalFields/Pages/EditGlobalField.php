<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\GlobalFieldResource;
use Chanthoeun\FilamentCustomForms\Models\CustomFormField;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditGlobalField extends EditRecord
{
    use Translatable;

    protected static string $resource = GlobalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            CustomFormPlugin::get()->hasTranslations() ? LocaleSwitcher::make() : null,
            DeleteAction::make()
                ->hidden(fn ($record) => CustomFormField::where('global_field_id', $record->id)->exists()),
        ]);
    }
}
