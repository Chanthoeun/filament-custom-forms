<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages;

use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\GlobalFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlobalFields extends ListRecords
{
    protected static string $resource = GlobalFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
