<?php

namespace Dcx\FilamentCustomForms\Filament\Resources\CustomForms;

use Dcx\FilamentCustomForms\Filament\Resources\CustomForms\Pages;
use Dcx\FilamentCustomForms\Filament\Resources\CustomForms\Schemas\CustomFormForm;
use Dcx\FilamentCustomForms\Filament\Resources\CustomForms\Tables\CustomFormsTable;
use Dcx\FilamentCustomForms\Models\CustomForm;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Dcx\FilamentCustomForms\CustomFormPlugin;

class CustomFormResource extends Resource
{
    public static function getModel(): string
    {
        return CustomFormPlugin::get()->getFormModel();
    }

    public static function getModelLabel(): string
    {
        return __('custom_form.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('custom_form.plural');
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return CustomFormPlugin::get()->getNavigationFormIcon();
    }

    public static function getNavigationGroup(): ?string
    {
        return CustomFormPlugin::get()->getNavigationGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return CustomFormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomFormsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\FieldsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomForms::route('/'),
            'create' => Pages\CreateCustomForm::route('/create'),
            'edit' => Pages\EditCustomForm::route('/{record}/edit'),
        ];
    }
}
