<?php

namespace LaraSpace\FilamentCustomForms\Filament\Resources;

use LaraSpace\FilamentCustomForms\Filament\Resources\Pages;
use LaraSpace\FilamentCustomForms\Filament\Resources\Schemas\CustomFormForm;
use LaraSpace\FilamentCustomForms\Filament\Resources\Tables\CustomFormsTable;
use LaraSpace\FilamentCustomForms\Models\CustomForm;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class CustomFormResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return tenant()?->checkFeature('custom_form') ?? false;
    }

    public static function canAccess(): bool
    {
        return tenant()?->checkFeature('custom_form') ?? false;
    }

    protected static ?string $model = CustomForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('custom_form.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('custom_form.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('custom_form.builder_group');
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
